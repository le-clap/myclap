<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\VideoStatService;
use Illuminate\Http\Request;

class VideoStatController extends Controller
{
    public function __construct(
        private VideoStatService $statService
    ) {}

    public function init(Request $request, Video $video)
    {
        abort_unless($video->isPublished(), 404);

        $this->authorize('view', $video);

        $playbackSid = $this->statService->initView($video, $request);

        return response()->json(['playback_sid' => $playbackSid]);
    }

    public function update(Request $request, Video $video)
    {
        abort_unless($video->isPublished(), 404);

        $this->authorize('view', $video);

        $playbackSid = $request->input('playback_sid');

        if (! $playbackSid) {
            return response()->json(['message' => 'missing playback_sid'], 400);
        }

        $this->statService->updateView($video, $playbackSid, $request);

        return response()->noContent();
    }
}
