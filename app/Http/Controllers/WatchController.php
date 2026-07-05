<?php

namespace App\Http\Controllers;

use App\Enums\ContentAccess;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WatchController extends Controller
{
    public function show(Request $request, Video $video): Response
    {
        abort_unless($video->isPublished(), 404);

        $video->load('categories');
        $user = $request->user();

        if ($gate = $this->accessGate($video, $user)) {
            return $gate;
        }

        return Inertia::render('Watch/Index', [
            'video' => $video,
            'userDidLike' => $this->userDidLike($video, $user),
        ]);
    }

    public function showInPlaylist(Request $request, Playlist $playlist, Video $video): Response
    {
        abort_unless($video->isPublished(), 404);

        $video->load('categories');
        $user = $request->user();

        if ($gate = $this->accessGate($video, $user)) {
            return $gate;
        }

        if (! in_array($video->token, $playlist->getVideoTokens(), true)) {
            abort(404);
        }

        $videos = $playlist->getVideosCollection($user);
        $currentIndex = $videos->search(fn ($v) => $v->token === $video->token);

        return Inertia::render('Watch/Playlist', [
            'playlist' => $playlist,
            'video' => $video,
            'videos' => $videos->values(),
            'currentIndex' => $currentIndex !== false ? $currentIndex : 0,
            'userDidLike' => $this->userDidLike($video, $user),
        ]);
    }

    public function download(Video $video): RedirectResponse
    {
        return redirect()->route('watch.media.video.download', $video);
    }

    /**
     * Return the appropriate "access denied" Inertia page for the given video,
     * or null when the user is allowed to watch it.
     */
    private function accessGate(Video $video, ?User $user): ?Response
    {
        return match ($video->access) {
            ContentAccess::CENTRALIENS => $user === null
                ? Inertia::render('Watch/LoginRequired', ['video' => $video])
                : null,
            ContentAccess::PRIVATE => ($user === null || ! $user->hasPermission('myclap.private'))
                ? Inertia::render('Watch/PrivateVideo', ['video' => $video])
                : null,
            default => null,
        };
    }

    private function userDidLike(Video $video, ?User $user): bool
    {
        return $user !== null
            && $video->videoReactions()->where('username', $user->username)->exists();
    }
}
