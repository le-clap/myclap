<?php

namespace Tests\Feature;

use App\Services\ThumbnailService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThumbnailTest extends TestCase
{
    public function test_every_variant_is_a_jpeg_fitting_its_box(): void
    {
        Storage::fake('local');
        $this->withoutDefer();
        $service = app(ThumbnailService::class);

        // 4:3 source, so each variant must be fitted by height inside its 16:9 box.
        $identifier = $service->store(UploadedFile::fake()->image('thumb.png', 1600, 1200));

        foreach (ThumbnailService::SIZES as [$width, $height]) {
            $path = Storage::disk('local')->path($service->getVariantPath($identifier, $height));
            $this->assertFileExists($path);
            [$w, $h, $type] = getimagesize($path);
            $this->assertSame(IMAGETYPE_JPEG, $type);
            $this->assertSame($height, $h);
            $this->assertLessThanOrEqual($width, $w);
        }
    }
}
