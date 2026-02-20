<?php

namespace App\Http\Controllers;

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

        if (! $video->file_identifier || ! Storage::disk('local')->exists($video->file_identifier)) {
            abort(404);
        }

        $filename = Str::slug($video->name).'.mp4';

        // X-Accel-Redirect for nginx
        $internalPath = '/internal-storage/'.$video->file_identifier;

        return response('', 200, [
            'X-Accel-Redirect' => $internalPath,
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function videoDownload(Request $request, string $token)
    {
        $video = Video::where('token', $token)->published()->firstOrFail();

        $this->authorize('view', $video);

        if (! $video->file_identifier || ! Storage::disk('local')->exists($video->file_identifier)) {
            abort(404);
        }

        $filename = Str::slug($video->name).'.mp4';

        // X-Accel-Redirect for nginx
        $internalPath = '/internal-storage/'.$video->file_identifier;

        return response('', 200, [
            'X-Accel-Redirect' => $internalPath,
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function thumbnail(Request $request, string $token)
    {
        $video = Video::where('token', $token)->firstOrFail();
        $size = (int) $request->query('size', 1080);

        $this->authorize('view', $video);

        if (! $video->thumbnail_identifier) {
            return $this->placeholderRedirect($size);
        }

        $thumbnailService = app(ThumbnailService::class);
        $path = $thumbnailService->getVariantPath($video->thumbnail_identifier, $size);

        if (! Storage::disk('local')->exists($path)) {
            return $this->placeholderRedirect($size);
        }

        // X-Accel-Redirect for nginx
        $internalPath = '/internal-storage/'.$path;

        return response('', 200, [
            'X-Accel-Redirect' => $internalPath,
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private const PLACEHOLDER_MAP = [
        1080 => 'placeholder.jpg',
        480 => 'placeholder-480.jpg',
        120 => 'placeholder-120.jpg',
    ];

    private function placeholderRedirect(int $size = 1080)
    {
        $placeholder = self::PLACEHOLDER_MAP[$size] ?? self::PLACEHOLDER_MAP[1080];

        return redirect('/static/myclap/thumbnail/'.$placeholder, 302, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
