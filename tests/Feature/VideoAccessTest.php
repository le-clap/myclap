<?php

namespace Tests\Feature;

use App\Enums\ContentAccess;
use App\Enums\UploadStatus;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VideoAccessTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(string $who): ?User
    {
        return match ($who) {
            'guest' => null,
            'student' => User::factory()->create(),
            'private' => User::factory()->withPermissions('myclap.private')->create(),
        };
    }

    private function actingAsViewer(string $who): static
    {
        $user = $this->viewer($who);

        return $user ? $this->actingAs($user) : $this;
    }

    public static function watchMatrix(): array
    {
        return [
            'public / guest' => [ContentAccess::PUBLIC, 'guest', 'Watch/Index'],
            'unlisted / guest' => [ContentAccess::UNLINKED, 'guest', 'Watch/Index'],
            'centraliens / guest' => [ContentAccess::CENTRALIENS, 'guest', 'Watch/LoginRequired'],
            'centraliens / student' => [ContentAccess::CENTRALIENS, 'student', 'Watch/Index'],
            'private / guest' => [ContentAccess::PRIVATE, 'guest', 'Watch/PrivateVideo'],
            'private / student' => [ContentAccess::PRIVATE, 'student', 'Watch/PrivateVideo'],
            'private / member' => [ContentAccess::PRIVATE, 'private', 'Watch/Index'],
        ];
    }

    #[DataProvider('watchMatrix')]
    public function test_watch_page_respects_access(ContentAccess $access, string $who, string $component): void
    {
        $video = Video::factory()->access($access)->create();

        $this->actingAsViewer($who)
            ->get("/regarder/{$video->token}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
    }

    public static function mediaMatrix(): array
    {
        return [
            'public / guest' => [ContentAccess::PUBLIC, 'guest', 200],
            'unlisted / guest' => [ContentAccess::UNLINKED, 'guest', 200],
            'centraliens / guest' => [ContentAccess::CENTRALIENS, 'guest', 403],
            'centraliens / student' => [ContentAccess::CENTRALIENS, 'student', 200],
            'private / student' => [ContentAccess::PRIVATE, 'student', 403],
            'private / member' => [ContentAccess::PRIVATE, 'private', 200],
        ];
    }

    #[DataProvider('mediaMatrix')]
    public function test_media_file_respects_access(ContentAccess $access, string $who, int $status): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('videos/test.mp4', 'x');
        $video = Video::factory()->access($access)->create(['file_identifier' => 'videos/test.mp4']);

        $this->actingAsViewer($who)->get("/media/{$video->token}")->assertStatus($status);
        $this->actingAsViewer($who)->get("/media/{$video->token}/download")->assertStatus($status);
    }

    public function test_link_preview_meta_only_for_crawler_fetchable_videos(): void
    {
        $public = Video::factory()->access(ContentAccess::PUBLIC)->create(['name' => 'Public clip']);
        $private = Video::factory()->access(ContentAccess::PRIVATE)->create(['name' => 'Secret clip']);

        $this->get("/regarder/{$public->token}")
            ->assertSee('property="og:title" content="Public clip"', false)
            ->assertSee('property="og:image"', false);
        $this->get("/regarder/{$private->token}")->assertDontSee('og:title', false);
    }

    public function test_unpublished_video_is_not_found(): void
    {
        $video = Video::factory()->create(['upload_status' => UploadStatus::UPLOAD_INIT]);

        $this->get("/regarder/{$video->token}")->assertNotFound();
        $this->get("/media/{$video->token}")->assertNotFound();
    }

    public static function browseMatrix(): array
    {
        return [
            'guest sees public only' => ['guest', ['public']],
            'student also sees centraliens' => ['student', ['public', 'centraliens']],
            // Private and unlisted videos never show up in browse/search, even for members.
            'member sees no private' => ['private', ['public', 'centraliens']],
        ];
    }

    #[DataProvider('browseMatrix')]
    public function test_feed_and_search_only_list_browsable_videos(string $who, array $visible): void
    {
        foreach (['public' => ContentAccess::PUBLIC, 'unlisted' => ContentAccess::UNLINKED,
            'centraliens' => ContentAccess::CENTRALIENS, 'private' => ContentAccess::PRIVATE] as $name => $access) {
            Video::factory()->access($access)->create(['name' => "clip {$name}"]);
        }
        $this->actingAsViewer($who);

        $names = fn ($response) => collect($response->json('videos'))->pluck('name')->map(fn ($n) => substr($n, 5))->sort()->values()->all();
        sort($visible);

        $this->assertSame($visible, $names($this->getJson('/api/videos')));
        $this->assertSame($visible, $names($this->getJson('/api/search?q=CLIP')), 'search is case-insensitive');
    }

    public function test_unlisted_video_is_listed_inside_a_playlist(): void
    {
        $playlist = Playlist::factory()->create();
        $video = Video::factory()->access(ContentAccess::UNLINKED)->create();
        $playlist->videos()->attach($video->token, ['position' => 0]);

        $this->get("/playlists/{$playlist->slug}/{$video->token}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Watch/Playlist')
                ->has('videos', 1));
    }

    public function test_public_api_hides_internal_fields(): void
    {
        Video::factory()->create(['file_identifier' => 'videos/secret.mp4']);

        $video = $this->getJson('/api/videos')->json('videos.0');

        $this->assertArrayNotHasKey('file_identifier', $video);
        $this->assertArrayNotHasKey('uploaded_by', $video);
    }
}
