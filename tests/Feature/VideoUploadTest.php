<?php

namespace Tests\Feature;

use App\Enums\UploadStatus;
use App\Jobs\TranscodeVideoJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Requires ffprobe on the PATH (finalize probes the uploaded file). */
class VideoUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $uploader;

    private Video $video;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
        config(['media.upload_chunk_size' => 8192]);

        $this->uploader = User::factory()->withPermissions('manager.video.upload')->create();
        $this->video = Video::factory()->create([
            'uploaded_by' => $this->uploader->username,
            'upload_status' => UploadStatus::UPLOAD_NULL,
        ]);
    }

    private function url(string $step): string
    {
        return "/manager/videos/v/{$this->video->token}/upload/{$step}";
    }

    /** Sends $bytes through init + chunked process, as the browser uploader does. */
    private function upload(string $bytes): void
    {
        $this->actingAs($this->uploader)
            ->postJson($this->url('init'), ['fileName' => 'clip.mp4', 'fileSize' => strlen($bytes)])
            ->assertOk()
            ->assertJson(['startIndex' => 0]);

        foreach (str_split($bytes, 8192) as $i => $chunk) {
            $this->postJson($this->url('process'), [
                'fileChunk' => UploadedFile::fake()->createWithContent('chunk', $chunk),
                'startIndex' => $i * 8192,
            ])->assertOk();
        }
    }

    public function test_full_upload_publishes_the_video_and_queues_a_transcode_check(): void
    {
        $this->upload(file_get_contents(base_path('tests/fixtures/tiny.mp4')));

        $this->postJson($this->url('end'))->assertNoContent();

        $video = $this->video->fresh();
        $this->assertSame(UploadStatus::UPLOAD_END, $video->upload_status);
        $this->assertSame('h264', $video->video_codec);
        $this->assertSame(1, $video->duration);
        Storage::disk('local')->assertExists($video->file_identifier);
        Queue::assertPushed(TranscodeVideoJob::class);
    }

    public function test_non_video_file_is_rejected_and_upload_reset(): void
    {
        $this->upload(str_repeat('not a video ', 1000));

        $this->postJson($this->url('end'))
            ->assertStatus(422)
            ->assertJson(['message' => "Le fichier envoyé n'est pas une vidéo valide."]);

        $this->assertSame(UploadStatus::UPLOAD_NULL, $this->video->fresh()->upload_status);
        $this->assertNull($this->video->fresh()->upload);
        Queue::assertNothingPushed();
    }

    public function test_file_over_the_size_cap_is_refused_at_init(): void
    {
        config(['media.max_video_upload_size' => 1000]);

        $this->actingAs($this->uploader)
            ->postJson($this->url('init'), ['fileName' => 'big.mp4', 'fileSize' => 1001])
            ->assertStatus(422);
    }

    public function test_chunk_larger_than_declared_size_is_refused(): void
    {
        $this->actingAs($this->uploader)
            ->postJson($this->url('init'), ['fileName' => 'clip.mp4', 'fileSize' => 100])
            ->assertOk();

        $this->postJson($this->url('process'), [
            'fileChunk' => UploadedFile::fake()->createWithContent('chunk', str_repeat('x', 200)),
            'startIndex' => 0,
        ])->assertStatus(422);
    }

    public function test_finalize_refuses_an_incomplete_upload(): void
    {
        $this->actingAs($this->uploader)
            ->postJson($this->url('init'), ['fileName' => 'clip.mp4', 'fileSize' => 100])
            ->assertOk();

        $this->postJson($this->url('end'))->assertStatus(422);
        $this->assertSame(UploadStatus::UPLOAD_INIT, $this->video->fresh()->upload_status);
    }

    public function test_other_users_video_cannot_be_uploaded_to(): void
    {
        $other = User::factory()->withPermissions('manager.video.upload')->create();

        $this->actingAs($other)
            ->postJson($this->url('init'), ['fileName' => 'clip.mp4', 'fileSize' => 100])
            ->assertForbidden();
    }
}
