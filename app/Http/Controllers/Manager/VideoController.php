<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ContentAccess;
use App\Enums\TranscodeStatus;
use App\Enums\UploadStatus;
use App\Http\Controllers\Controller;
use App\Jobs\TranscodeVideoJob;
use App\Models\Category;
use App\Models\Playlist;
use App\Models\Video;
use App\Services\ThumbnailService;
use App\Services\VideoUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VideoController extends Controller
{
    public function __construct(
        private readonly ThumbnailService $thumbnailService,
        private readonly VideoUploadService $uploadService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'sort' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:12|max:120',
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $sort = (string) ($validated['sort'] ?? '-uploaded_on');
        $limit = (int) ($validated['limit'] ?? 24);

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

        $videosQuery = Video::query()->with('latestTranscodeAttempt');

        if ($query !== '') {
            $videosQuery->search($query);
        }

        $videosQuery->sortBy($sort, $allowedSortFields, nullableLast: ['bitrate']);

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

        $appendablePlaylists = collect();
        if ($user->hasPermission('manager.playlist')) {
            $appendablePlaylists = Playlist::orderedForDisplay()
                ->get(['slug', 'name', 'type', 'access'])
                ->values();
        }

        return Inertia::render('Manager/Videos/Create', [
            'categories' => Category::orderBy('label')->get(),
            'accessOptions' => ContentAccess::options(),
            'appendablePlaylists' => $appendablePlaylists,
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
            'append_playlist_slug' => 'nullable|string|exists:playlist,slug',
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

        $appendPlaylistSlug = $validated['append_playlist_slug'] ?? null;
        if ($appendPlaylistSlug) {
            if (! $user->hasPermission('manager.playlist')) {
                abort(403);
            }

            $playlist = Playlist::where('slug', $appendPlaylistSlug)->first();
            if ($playlist) {
                $maxPosition = $playlist->videos()->max('playlist_video.position');
                $nextPosition = $maxPosition === null ? 0 : $maxPosition + 1;
                $playlist->videos()->syncWithoutDetaching([
                    $video->token => ['position' => $nextPosition],
                ]);
            }
        }

        return redirect()->route('manager.videos.upload', $video)
            ->with('success', 'Vidéo créée. Vous pouvez maintenant envoyer le fichier vidéo.');
    }

    public function edit(Request $request, Video $video)
    {
        $this->authorize('update', $video);

        $video->load('categories', 'latestTranscodeAttempt');

        return Inertia::render('Manager/Videos/Edit', [
            'video' => $video,
            'videoCategorySlugs' => $video->categories->pluck('slug')->toArray(),
            'categories' => Category::orderBy('label')->get(),
            'accessOptions' => ContentAccess::options(),
        ]);
    }

    public function update(Request $request, Video $video)
    {
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

    public function upload(Request $request, Video $video)
    {
        $this->authorize('update', $video);

        if ($video->upload_status === UploadStatus::UPLOAD_END) {
            return redirect()->route('manager.videos.edit', $video)
                ->with('info', 'La vidéo a déjà été uploadée.');
        }

        if ($video->upload_status === UploadStatus::UPLOAD_PROCESSING) {
            return redirect()->route('manager.videos.edit', $video)
                ->with('info', 'La vidéo est en cours de traitement.');
        }

        $uploadProgress = $video->upload_status === UploadStatus::UPLOAD_INIT
            ? $this->uploadService->getUploadProgress($video)
            : null;

        return Inertia::render('Manager/Videos/Upload', [
            'video' => $video,
            'uploadProgress' => $uploadProgress,
        ]);
    }

    public function transcode(Request $request, Video $video)
    {
        $this->authorize('transcode', $video);

        if (! $video->file_identifier) {
            abort(404);
        }

        $inFlight = $video->transcodeAttempts()
            ->whereIn('status', [TranscodeStatus::PENDING->value, TranscodeStatus::PROCESSING->value])
            ->exists();

        if ($inFlight) {
            return back()->with('info', 'Un traitement est déjà en cours pour cette vidéo.');
        }

        $attempt = $video->transcodeAttempts()->create([
            'status' => TranscodeStatus::PENDING,
            'started_on' => now(),
        ]);

        TranscodeVideoJob::dispatch($video, $attempt);

        return back()->with('success', 'La vidéo a été mise en file pour vérification/ré-encodage.');
    }

    public function destroy(Request $request, Video $video)
    {
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
        $video->upload()->delete();

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
