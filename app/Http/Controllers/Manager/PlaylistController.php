<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ContentAccess;
use App\Enums\PlaylistType;
use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PlaylistController extends Controller
{
    public function index()
    {
        $playlists = Playlist::withCount('videos')
            ->orderedForDisplay()
            ->get();

        return Inertia::render('Manager/Playlists/Index', [
            'playlists' => $playlists,
        ]);
    }

    public function create()
    {
        return Inertia::render('Manager/Playlists/Create', [
            'typeOptions' => PlaylistType::options(),
            'accessOptions' => ContentAccess::options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:75',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|integer|in:0,1',
            'access' => 'required|integer|in:0,1,2,3',
            'videos' => 'nullable|array',
        ]);

        // Generate unique slug
        $slugBase = Str::slug($validated['name']);
        $slug = $slugBase;
        $acc = 1;
        while (Playlist::where('slug', $slug)->exists()) {
            $acc++;
            $slug = $slugBase.'-'.$acc;
        }

        // Validate video tokens
        $videoTokens = $validated['videos'] ?? [];
        $validTokens = Video::whereIn('token', $videoTokens)->pluck('token')->toArray();
        $orderedTokens = array_values(array_filter($videoTokens, fn ($t) => in_array($t, $validTokens)));

        $playlist = Playlist::create([
            'slug' => $slug,
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'type' => $validated['type'],
            'access' => $validated['access'],
            'modified_by' => $request->user()->username,
        ]);

        $playlist->syncVideosWithOrder($orderedTokens);

        return redirect()->route('manager.playlists.edit', $playlist)
            ->with('success', 'La playlist a été créée avec succès');
    }

    public function edit(Playlist $playlist)
    {
        // Get the videos in order via relationship
        $playlistVideos = $playlist->videos()->get();

        return Inertia::render('Manager/Playlists/Edit', [
            'playlist' => $playlist,
            'playlistVideos' => $playlistVideos,
            'typeOptions' => PlaylistType::options(),
            'accessOptions' => ContentAccess::options(),
        ]);
    }

    public function update(Request $request, Playlist $playlist)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:75',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|integer|in:0,1',
            'access' => 'required|integer|in:0,1,2,3',
            'videos' => 'nullable|array',
        ]);

        // Validate video tokens
        $videoTokens = $validated['videos'] ?? [];
        $validTokens = Video::whereIn('token', $videoTokens)->pluck('token')->toArray();
        // Maintain order from request
        $orderedTokens = array_values(array_filter($videoTokens, fn ($t) => in_array($t, $validTokens)));

        $previousType = $playlist->type;
        $nextType = PlaylistType::from($validated['type']);

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'type' => $nextType,
            'access' => $validated['access'],
            'modified_by' => $request->user()->username,
            'modified_on' => now(),
        ];

        $playlist->update($payload);

        // Sync videos with order
        $playlist->syncVideosWithOrder($orderedTokens);

        if ($nextType !== $previousType) {
            $playlist->moveToEnd();
            // Fix the two groups
            $this->normalizeTypeOrder($previousType);
            $this->normalizeTypeOrder($nextType);
        }

        return back()->with('success', 'Les changements ont bien été sauvegardés');
    }

    public function destroy(Playlist $playlist)
    {
        $playlist->delete();

        return redirect()->route('manager.playlists.index')
            ->with('success', 'La playlist a bien été supprimée');
    }

    public function move(Request $request, Playlist $playlist)
    {
        $validated = $request->validate([
            'direction' => 'required|string|in:up,down',
        ]);

        $direction = $validated['direction'];

        if ($direction === 'up') {
            $playlist->moveOrderUp();
        } else {
            $playlist->moveOrderDown();
        }

        return response()->noContent();
    }

    public function searchVideos(Request $request)
    {
        $query = $request->query('q', '');
        $limit = max(0, min((int) $request->query('limit', 5), 20));
        $exclude = $request->query('exclude', []);

        if (is_string($exclude)) {
            $exclude = json_decode($exclude, true) ?? [];
        }

        $baseQuery = Video::published()
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                    ->orWhere('token', 'ILIKE', "%{$query}%");
            })
            ->when(! empty($exclude), function ($q) use ($exclude) {
                $q->whereNotIn('token', $exclude);
            });

        $total = (clone $baseQuery)->count();

        $videos = $baseQuery
            ->orderBy('created_on', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'videos' => $videos,
            'total' => $total,
        ]);
    }

    private function normalizeTypeOrder(PlaylistType $type): void
    {
        $playlistIds = Playlist::where('type', $type)
            ->orderBy('position')
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        if ($playlistIds === []) {
            return;
        }

        Playlist::setNewOrder($playlistIds, 0);
    }
}
