<?php

namespace App\Http\Controllers;

use App\Enums\ContentAccess;
use App\Models\Video;
use App\Services\ThumbnailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function video(Request $request, string $token)
    {
        $video = Video::where('token', $token)->published()->firstOrFail();

        $this->authorize('view', $video);

        if (!$video->file_identifier || !Storage::disk('local')->exists($video->file_identifier)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($video->file_identifier);
        $filename = Str::slug($video->name) . '.mp4';

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function videoDownload(Request $request, string $token)
    {
        $video = Video::where('token', $token)->published()->firstOrFail();

        $this->authorize('view', $video);

        if (!$video->file_identifier || !Storage::disk('local')->exists($video->file_identifier)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($video->file_identifier);
        $filename = Str::slug($video->name) . '.mp4';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function thumbnail(Request $request, string $token)
    {
        $video = Video::where('token', $token)->first();
        $size = (int)$request->query('size', 1080);

        if (!$video) {
            return $this->placeholderRedirect();
        }

        $access = ContentAccess::from($video->access);
        if ($access === ContentAccess::PRIVATE) {
            $user = $request->user();
            if (!$user?->hasPermission('myclap.private')) {
                return $this->placeholderRedirect();
            }
        }

        if (!$video->thumbnail_identifier) {
            return $this->placeholderRedirect();
        }

        $thumbnailService = app(ThumbnailService::class);
        $path = $thumbnailService->getVariantPath($video->thumbnail_identifier, $size);

        if (!Storage::disk('local')->exists($path)) {
            return $this->placeholderRedirect();
        }
        return $this->serveFile($path);
    }

    private function serveFile(string $identifier)
    {
        $path = Storage::disk('local')->path($identifier);
        $mimeType = mime_content_type($path) ?: 'image/jpeg';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function placeholderRedirect()
    {
        return redirect('/static/myclap/thumbnail/placeholder.png');
    }
}
