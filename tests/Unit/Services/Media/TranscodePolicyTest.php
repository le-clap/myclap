<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\TranscodeAction;
use App\Services\Media\TranscodePolicy;
use App\Services\Media\VideoProbe;
use Tests\TestCase;

class TranscodePolicyTest extends TestCase
{
    private TranscodePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TranscodePolicy;
    }

    /**
     * A fully compliant 720p h264/aac faststart MP4 — the baseline every
     * test tweaks one property away from.
     */
    private function compliantProbe(array $overrides = []): VideoProbe
    {
        $defaults = [
            'formatName' => 'mov,mp4,m4a,3gp,3g2,mj2',
            'duration' => 60.0,
            'size' => 10_000_000,
            'videoCodec' => 'h264',
            'profile' => 'High',
            'level' => 40,
            'pixFmt' => 'yuv420p',
            'width' => 1280,
            'height' => 720,
            'videoBitrate' => 2_000_000,
            'audioCodec' => 'aac',
            'audioChannels' => 2,
            'hasAudio' => true,
            'faststart' => true,
        ];

        return new VideoProbe(...array_merge($defaults, $overrides));
    }

    public function test_fully_compliant_file_is_skipped(): void
    {
        $decision = $this->policy->decide($this->compliantProbe());

        $this->assertSame(TranscodeAction::SKIP, $decision->action);
    }

    public function test_compliant_file_with_no_audio_track_is_skipped(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'hasAudio' => false,
            'audioCodec' => null,
            'audioChannels' => null,
        ]));

        $this->assertSame(TranscodeAction::SKIP, $decision->action);
    }

    public function test_unmeasurable_bitrate_is_not_forced_to_encode(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'videoBitrate' => null,
        ]));

        $this->assertSame(TranscodeAction::SKIP, $decision->action);
    }

    public function test_bitrate_exactly_at_the_ceiling_is_compliant(): void
    {
        // 720p ceiling is 6_000_000 per config/media.php.
        $decision = $this->policy->decide($this->compliantProbe([
            'videoBitrate' => 6_000_000,
        ]));

        $this->assertSame(TranscodeAction::SKIP, $decision->action);
    }

    public function test_wrong_container_alone_is_remuxed_not_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'formatName' => 'matroska,webm',
        ]));

        $this->assertSame(TranscodeAction::REMUX, $decision->action);
    }

    public function test_missing_faststart_alone_is_remuxed(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'faststart' => false,
        ]));

        $this->assertSame(TranscodeAction::REMUX, $decision->action);
    }

    public function test_wrong_container_and_missing_faststart_together_is_still_a_remux(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'formatName' => 'matroska,webm',
            'faststart' => false,
        ]));

        $this->assertSame(TranscodeAction::REMUX, $decision->action);
    }

    public function test_non_h264_codec_is_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'videoCodec' => 'vp9',
            'profile' => 'Profile 0',
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_unsupported_profile_is_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'profile' => 'High 10',
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_level_above_ceiling_is_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'level' => 51,
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_non_420_pixel_format_is_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'pixFmt' => 'yuv420p10le',
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_resolution_above_1080p_is_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'width' => 3840,
            'height' => 2160,
            'videoBitrate' => 15_000_000,
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_non_aac_audio_is_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'audioCodec' => 'mp3',
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_surround_audio_is_encoded(): void
    {
        $decision = $this->policy->decide($this->compliantProbe([
            'audioChannels' => 6,
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_bitrate_above_ceiling_for_its_resolution_is_encoded(): void
    {
        // 720p ceiling is 6_000_000 per config/media.php.
        $decision = $this->policy->decide($this->compliantProbe([
            'videoBitrate' => 7_000_000,
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
    }

    public function test_real_vp9_sample_is_encoded_for_multiple_reasons(): void
    {
        // Matches storage/app/private/videos/*.mp4 as probed today.
        $decision = $this->policy->decide($this->compliantProbe([
            'formatName' => 'matroska,webm',
            'faststart' => false,
            'videoCodec' => 'vp9',
            'profile' => 'Profile 0',
            'level' => null,
            'width' => 1920,
            'height' => 1052,
            'videoBitrate' => 28_105_309,
            'hasAudio' => false,
            'audioCodec' => null,
            'audioChannels' => null,
        ]));

        $this->assertSame(TranscodeAction::ENCODE, $decision->action);
        $this->assertStringContainsString('vp9', $decision->reason);
    }
}
