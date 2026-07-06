<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoReaction;
use Illuminate\Http\Request;

class VideoReactionController extends Controller
{
    public function toggle(Request $request, Video $video)
    {
        if (! $request->user()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        abort_unless($video->isPublished(), 404);

        $this->authorize('view', $video);

        $username = $request->user()->username;

        $reaction = VideoReaction::where('video_token', $video->token)
            ->where('username', $username)
            ->first();

        if ($reaction) {
            $reaction->delete();
            $video->decrement('reactions');
            $active = false;
        } else {
            VideoReaction::create([
                'video_token' => $video->token,
                'username' => $username,
                'created_on' => now(),
            ]);
            $video->increment('reactions');
            $active = true;
        }

        return response()->json(['active' => $active]);
    }
}
