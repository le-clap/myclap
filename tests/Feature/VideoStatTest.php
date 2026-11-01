<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Services\VideoStatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/** Called directly so every call shares one session id, like a real browser. */
class VideoStatTest extends TestCase
{
    use RefreshDatabase;

    private VideoStatService $stats;

    private Video $video;

    protected function setUp(): void
    {
        parent::setUp();

        session()->start();
        $this->stats = app(VideoStatService::class);
        $this->video = Video::factory()->create();
    }

    private function watch(int $seconds): void
    {
        $sid = $this->stats->initView($this->video, new Request);
        $this->travel($seconds)->seconds();
        if ($sid === null) {
            return; // already counted this hour: the player sends no updates
        }
        $this->stats->updateView($this->video, $sid, new Request(['watch_time' => $seconds]));
    }

    public function test_view_counts_only_after_25_seconds(): void
    {
        $sid = $this->stats->initView($this->video, new Request);

        $this->travel(10)->seconds();
        $this->stats->updateView($this->video, $sid, new Request);
        $this->assertSame(0, $this->video->fresh()->views);

        $this->travel(20)->seconds();
        $this->stats->updateView($this->video, $sid, new Request);
        $this->assertSame(1, $this->video->fresh()->views);
    }

    public function test_same_session_counts_once_per_hour(): void
    {
        $this->watch(30);
        $this->watch(30);
        $this->assertSame(1, $this->video->fresh()->views);

        $this->travel(61)->minutes();
        $this->watch(30);
        $this->assertSame(2, $this->video->fresh()->views);
    }

    public function test_unknown_playback_session_is_ignored(): void
    {
        $this->stats->initView($this->video, new Request);
        $this->travel(30)->seconds();

        $this->stats->updateView($this->video, 'forged', new Request);

        $this->assertSame(0, $this->video->fresh()->views);
    }
}
