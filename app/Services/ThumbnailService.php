<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ThumbnailService
{
    private ImageManager $manager;

    /**
     * Thumbnail sizes to generate (height => [width, height])
     * - 1080: Video player poster (full screen)
     * - 480: Video cards in grids
     * - 120: Small thumbnails (search, playlists)
     */
    public const SIZES = [
        1080 => [1920, 1080],
        480 => [854, 480],
        120 => [213, 120],
    ];

    private const THUMBNAILS_DIR = 'thumbnails';

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    public function generateIdentifier(): string
    {
        return Str::random(10);
    }

    /**
     * Store a thumbnail and generate all size variants
     *
     * @param  UploadedFile  $file  The uploaded image file
     * @return string The thumbnail identifier
     *
     * @throws Exception If the image type is invalid
     */
    public function store(UploadedFile $file): string
    {
        $imageType = exif_imagetype($file->getRealPath());
        if ($imageType !== IMAGETYPE_JPEG && $imageType !== IMAGETYPE_PNG) {
            throw new Exception('La miniature doit être un fichier PNG ou JPEG.');
        }

        $identifier = $this->generateIdentifier();
        $this->ensureDirectoryExists();

        // Persist the source so the variant generation can run
        // after the response is sent, keeping the request non-blocking.
        $sourcePath = Storage::disk('local')->path(self::THUMBNAILS_DIR.'/source_'.$identifier);
        $file->move(dirname($sourcePath), basename($sourcePath));

        defer(function () use ($sourcePath, $identifier) {
            try {
                // Read the source once, then derive every variant from memory.
                $binary = file_get_contents($sourcePath);
                foreach (self::SIZES as [$width, $height]) {
                    $this->generateVariant($binary, $identifier, $width, $height);
                }
            } catch (\Throwable $e) {
                Log::error('Thumbnail generation failed', [
                    'identifier' => $identifier,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                if (is_file($sourcePath)) {
                    @unlink($sourcePath);
                }
            }
        });

        return $identifier;
    }

    private function generateVariant(string $binary, string $identifier, int $width, int $height): void
    {
        $image = $this->manager->read($binary);

        // Calculate dimensions maintaining 16:9 aspect ratio
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Scale to fit within target dimensions
        if ($originalWidth / $originalHeight > $width / $height) {
            // Wider than target, fit by width
            $newWidth = $width;
            $newHeight = (int) ceil(($originalHeight / $originalWidth) * $width);
        } else {
            // Taller than target, fit by height
            $newHeight = $height;
            $newWidth = (int) ceil(($originalWidth / $originalHeight) * $height);
        }

        $image->scale($newWidth, $newHeight);

        $outputPath = Storage::disk('local')->path($this->getVariantPath($identifier, $height));
        $image->toJpeg(quality: 85)->save($outputPath);
    }

    public function getVariantFilename(string $identifier, int $height): string
    {
        return "{$identifier}:{$height}.jpg";
    }

    public function getVariantPath(string $identifier, int $height): string
    {
        return self::THUMBNAILS_DIR.'/'.$height.'/'.$this->getVariantFilename($identifier, $height);
    }

    public function delete(string $identifier): void
    {
        foreach (array_keys(self::SIZES) as $height) {
            $path = $this->getVariantPath($identifier, $height);
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private function ensureDirectoryExists(): void
    {
        foreach (array_keys(self::SIZES) as $height) {
            $dir = Storage::disk('local')->path(self::THUMBNAILS_DIR.'/'.$height);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public static function getAvailableSizes(): array
    {
        return array_keys(self::SIZES);
    }
}
