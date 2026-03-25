<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ContentAccess;
use App\Enums\UploadStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Video;
use App\Models\VideoUpload;
use App\Services\ThumbnailService;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VideoController extends Controller
{
    public function __construct(
        private readonly VideoService $videoService,
        private readonly ThumbnailService $thumbnailService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermissionGroup('manager.video')) {
            abort(403);
        }

        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'sort' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:12|max:120',
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $sort = (string) ($validated['sort'] ?? '-uploaded_on');
        $limit = (int) ($validated['limit'] ?? 24);

        $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortBy = ltrim($sort, '-');

        $allowedSortFields = [
            'uploaded_on',
            'created_on',
            'name',
            'views',
            'reactions',
            'duration',
            'file_size',
            'bitrate',
            'access',
            'upload_status',
        ];

        if (! in_array($sortBy, $allowedSortFields, true)) {
            $sortBy = 'uploaded_on';
            $sortDir = 'desc';
        }

        $videosQuery = Video::query();

        if ($query !== '') {
            $videosQuery->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                    ->orWhere('token', 'ILIKE', "%{$query}%");
            });
        }

        if ($sortBy === 'bitrate') {
            $videosQuery
                ->orderByRaw('CASE WHEN duration IS NULL OR duration = 0 OR file_size IS NULL THEN 1 ELSE 0 END ASC')
                ->orderByRaw('(file_size * 1.0) / NULLIF(duration, 0) '.strtoupper($sortDir));
        } else {
            $videosQuery->orderBy($sortBy, $sortDir);
        }

        if ($sortBy !== 'uploaded_on') {
            $videosQuery->orderByDesc('uploaded_on');
        }

        $videos = $videosQuery
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Manager/Videos/Index', [
            'videos' => $videos,
            'filters' => [
                'q' => $query,
                'sort' => $sort,
                'limit' => $limit,
            ],
            'sortOptions' => $this->getSortOptions(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermission('manager.video.upload')) {
            abort(403);
        }

        return Inertia::render('Manager/Videos/Create', [
            'categories' => Category::orderBy('label')->get(),
            'accessOptions' => ContentAccess::options(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermission('manager.video.upload')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:75',
            'description' => 'nullable|string|max:1000',
            'created_on' => 'required|date',
            'categories' => 'nullable|array',
            'access' => 'required|integer|in:0,1,2,3',
            'thumbnail' => 'nullable|image|max:10240',
        ]);

        // Generate unique token
        $token = Str::random(6);
        if (Video::where('token', $token)->exists()) {
            abort(503); // Very unlikely
        }

        $thumbnailIdentifier = null;
        if ($request->hasFile('thumbnail')) {
            try {
                $thumbnailIdentifier = $this->thumbnailService->store($request->file('thumbnail'));
            } catch (\Exception $e) {
                return back()->withErrors(['thumbnail' => $e->getMessage()]);
            }
        }

        $video = Video::create([
            'token' => $token,
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'access' => $validated['access'],
            'thumbnail_identifier' => $thumbnailIdentifier,
            'uploaded_by' => $user->username,
            'created_on' => $validated['created_on'],
        ]);

        $video->syncCategories($validated['categories'] ?? []);

        return redirect()->route('manager.videos.upload', $video->token)
            ->with('success', 'Vidéo créée. Vous pouvez maintenant envoyer le fichier vidéo.');
    }

    public function edit(Request $request, string $token)
    {
        $video = Video::with('categories')->where('token', $token)->firstOrFail();

        $this->authorize('update', $video);

        // Check and update file metadata for the file might have changed
        if ($video->file_identifier) {
            $this->videoService->checkAndUpdateMetadata($video);
        }

        return Inertia::render('Manager/Videos/Edit', [
            'video' => $video,
            'videoCategorySlugs' => $video->categories->pluck('slug')->toArray(),
            'categories' => Category::orderBy('label')->get(),
            'accessOptions' => ContentAccess::options(),
        ]);
    }

    public function update(Request $request, string $token)
    {
        $video = Video::where('token', $token)->firstOrFail();

        $this->authorize('update', $video);

        $validated = $request->validate([
            'name' => 'required|string|max:75',
            'description' => 'nullable|string|max:1000',
            'created_on' => 'required|date',
            'categories' => 'nullable|array',
            'access' => 'required|integer|in:0,1,2,3',
            'thumbnail' => 'nullable|image|max:10240',
        ]);

        $video->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'created_on' => $validated['created_on'],
            'access' => $validated['access'],
        ]);

        $video->syncCategories($validated['categories'] ?? []);

        if ($request->hasFile('thumbnail')) {

            // Delete old thumbnails
            if ($video->thumbnail_identifier) {
                $this->thumbnailService->delete($video->thumbnail_identifier);
            }

            try {
                $video->thumbnail_identifier = $this->thumbnailService->store($request->file('thumbnail'));
            } catch (\Exception $e) {
                return back()->withErrors(['thumbnail' => $e->getMessage()]);
            }
        }

        $video->save();

        return back()->with('success', 'Les changements ont bien été sauvegardés !');
    }

    public function upload(Request $request, string $token)
    {
        $video = Video::where('token', $token)->firstOrFail();
        $user = $request->user();

        $this->authorize('update', $video);

        if ($video->upload_status === UploadStatus::UPLOAD_END->value) {
            return redirect()->route('manager.videos.edit', $token)
                ->with('info', 'La vidéo a déjà été uploadée.');
        }

        $uploadProgress = null;
        if ($video->upload_status === UploadStatus::UPLOAD_INIT->value) {
            $upload = VideoUpload::where('video_token', $video->token)->first();
            if ($upload) {
                $path = Storage::disk('local')->path($upload->file_identifier);
                $currentSize = file_exists($path) ? filesize($path) : 0;
                $uploadProgress = [
                    'fileName' => $upload->file_name,
                    'fileSize' => $upload->file_size,
                    'uploadedSize' => $currentSize,
                    'percentage' => $upload->file_size > 0
                        ? round(($currentSize / $upload->file_size) * 100, 2)
                        : 0,
                ];
            }
        }

        return Inertia::render('Manager/Videos/Upload', [
            'video' => $video,
            'uploadProgress' => $uploadProgress,
        ]);
    }

    public function destroy(Request $request, string $token)
    {
        $video = Video::where('token', $token)->firstOrFail();

        $this->authorize('delete', $video);

        // Delete video file
        if ($video->file_identifier && Storage::disk('local')->exists($video->file_identifier)) {
            Storage::disk('local')->delete($video->file_identifier);
        }

        // Delete thumbnails
        if ($video->thumbnail_identifier) {
            $this->thumbnailService->delete($video->thumbnail_identifier);
        }

        // Delete upload if exists
        VideoUpload::where('video_token', $video->token)->delete();

        $video->delete();

        return redirect()->route('manager.videos.index')
            ->with('success', 'La vidéo a bien été supprimée');
    }

    private function getSortOptions(): array
    {
        return [
            ['value' => 'uploaded_on', 'label' => "Date d'upload"],
            ['value' => 'created_on', 'label' => 'Date de référence'],
            ['value' => 'name', 'label' => 'Nom'],
            ['value' => 'views', 'label' => 'Vues'],
            ['value' => 'reactions', 'label' => 'Réactions'],
            ['value' => 'duration', 'label' => 'Durée'],
            ['value' => 'file_size', 'label' => 'Poids'],
            ['value' => 'bitrate', 'label' => 'Bitrate'],
            ['value' => 'access', 'label' => 'Accès'],
            ['value' => 'upload_status', 'label' => "Statut d'upload"],
        ];
    }
}
