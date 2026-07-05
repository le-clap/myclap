<?php

namespace App\Http\Controllers\Manager;

use App\Exceptions\UploadException;
use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\VideoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class VideoUploadApiController extends Controller
{
    public function __construct(
        private readonly VideoUploadService $uploadService
    ) {}

    public function init(Request $request, Video $video): JsonResponse
    {
        $this->authorize('update', $video);

        $validated = $request->validate([
            'fileName' => 'required|string',
            'fileSize' => 'required|integer|min:1|max:'.config('media.max_video_upload_size'),
        ]);

        return $this->guard($video, fn () => response()->json(
            $this->uploadService->initUpload(
                $video,
                $validated['fileName'],
                $validated['fileSize'],
                $request->user()->username
            )
        ));
    }

    public function process(Request $request, Video $video): JsonResponse
    {
        $this->authorize('update', $video);

        $validated = $request->validate([
            'fileChunk' => 'required|file',
            'startIndex' => 'required|integer|min:0',
            'chunkSize' => 'required|integer|min:1',
        ]);

        return $this->guard($video, fn () => response()->json(
            $this->uploadService->processChunk(
                $video,
                $request->file('fileChunk'),
                $validated['startIndex'],
                $validated['chunkSize']
            )
        ));
    }

    public function end(Request $request, Video $video): JsonResponse|Response
    {
        $this->authorize('update', $video);

        return $this->guard($video, function () use ($video) {
            $this->uploadService->finalizeUpload($video);

            return response()->noContent();
        });
    }

    public function reset(Request $request, Video $video): JsonResponse|Response
    {
        $this->authorize('update', $video);

        return $this->guard($video, function () use ($video) {
            $this->uploadService->resetUpload($video);

            return response()->noContent();
        });
    }

    /**
     * Run an upload action, returning safe UploadException messages to the
     * client while logging any unexpected failure and hiding its details.
     */
    private function guard(Video $video, callable $action): JsonResponse|Response
    {
        try {
            return $action();
        } catch (UploadException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Video upload failed', [
                'video' => $video->token,
                'exception' => $e,
            ]);

            return response()->json(['message' => "Une erreur est survenue lors de l'envoi de la vidéo."], 500);
        }
    }
}
