<?php

namespace App\Http\Controllers;

use App\Enums\ContentAccess;
use App\Enums\UploadStatus;
use App\Models\Category;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $videos = Video::published()
            ->accessibleBy($user)
            ->orderBy('created_on', 'desc')
            ->limit(12)
            ->get();

        $billboards = [];
        if (Storage::disk('local')->exists('billboard.json')) {
            $content = Storage::disk('local')->get('billboard.json');
            $billboards = json_decode($content, true) ?? [];
        }

        return Inertia::render('Home/Index', [
            'videos' => $videos,
            'billboards' => $billboards,
        ]);
    }

    public function loadVideos(Request $request)
    {
        $user = $request->user();
        $offset = max((int) $request->get('offset', 0), 0);
        $limit = min((int) $request->get('limit', 8), 20);

        $query = Video::published()->accessibleBy($user);
        $total = (clone $query)->count();

        $videos = $query
            ->orderBy('created_on', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json(['videos' => $videos, 'total' => $total]);
    }

    public function playlists(Request $request)
    {
        $user = $request->user();

        $getPlaylistsQuery = fn ($type) => Playlist::where('type', $type)
            ->with(['videos' => fn ($q) => $q->published()->accessibleBy($user, true)])
            ->ordered()
            ->orderBy('name')
            ->get();

        $broadcastPlaylists = $getPlaylistsQuery(1)
            ->filter(fn ($p) => Gate::allows('view', $p))
            ->map(fn ($p) => $this->enrichPlaylist($p));

        $classicPlaylists = $getPlaylistsQuery(0)
            ->filter(fn ($p) => Gate::allows('view', $p))
            ->map(fn ($p) => $this->enrichPlaylist($p));

        return Inertia::render('Home/Playlists', [
            'broadcastPlaylists' => $broadcastPlaylists->values(),
            'classicPlaylists' => $classicPlaylists->values(),
        ]);
    }

    public function playlistDetails(Request $request, Playlist $playlist)
    {
        $this->authorize('view', $playlist);

        $videos = $playlist->getVideosCollection($request->user());

        return Inertia::render('Home/PlaylistDetails', [
            'playlist' => $playlist,
            'videos' => $videos,
        ]);
    }

    public function categories(Request $request)
    {
        $categories = Category::orderBy('label')->get();

        return Inertia::render('Home/Categories', [
            'categories' => $categories,
        ]);
    }

    public function categoryDetails(Request $request, Category $category)
    {
        $user = $request->user();

        $videos = $category->publishedVideos($user)
            ->orderBy('created_on', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Home/CategoryDetails', [
            'category' => $category,
            'videos' => $videos,
        ]);
    }

    public function loadCategoryVideos(Request $request, Category $category)
    {
        $user = $request->user();
        $offset = max((int) $request->get('offset', 0), 0);
        $limit = min((int) $request->get('limit', 8), 20);

        $total = $category->publishedVideos($user)->count();

        $videos = $category->publishedVideos($user)
            ->orderBy('created_on', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json(['videos' => $videos, 'total' => $total]);
    }

    public function favorites(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return Inertia::render('Home/Favorites', [
                'videos' => [],
                'needsAuth' => true,
            ]);
        }

        $videos = Video::join('video_reaction', 'video.token', '=', 'video_reaction.video_token')
            ->where('video_reaction.username', $user->username)
            ->where('video.upload_status', UploadStatus::UPLOAD_END->value)
            ->whereIn('video.access', [
                ContentAccess::CENTRALIENS->value,
                ContentAccess::PUBLIC->value,
            ])
            ->orderBy('video_reaction.created_on', 'desc')
            ->select('video.*')
            ->get();

        return Inertia::render('Home/Favorites', [
            'videos' => $videos,
            'needsAuth' => false,
        ]);
    }

    private function enrichPlaylist(Playlist $playlist): array
    {
        $videos = $playlist->videos;

        $data = $playlist->toArray();
        unset($data['videos']);

        return array_merge($data, [
            'video_count' => $videos->count(),
            'total_duration' => $videos->sum('duration'),
            'first_video_thumbnail' => $videos->first()?->thumbnail_url,
            'videos_preview' => $videos->take(5)->values(),
        ]);
    }
}
