<?php

namespace App\Services\Media;

use Illuminate\Support\Str;

class TranscodePolicy
{
    public function decide(VideoProbe $probe): TranscodeDecision
    {
        $config = config('media.compliance');

        $failures = [];

        if (! Str::contains($probe->formatName, $config['containers'])) {
            $failures['container'] = 'conteneur non MP4/MOV';
        }

        if (! $probe->faststart) {
            $failures['faststart'] = 'index (moov) non placé en tête de fichier';
        }

        if ($probe->videoCodec !== $config['video_codec']) {
            $failures['video_codec'] = "codec vidéo {$probe->videoCodec} non supporté";
        }

        if (! in_array($probe->profile, $config['profiles'], true)) {
            $failures['profile'] = "profil {$probe->profile} non supporté";
        }

        if ($probe->level === null || $probe->level > $config['max_level']) {
            $failures['level'] = 'niveau H.264 trop élevé';
        }

        if ($probe->pixFmt !== $config['pix_fmt']) {
            $failures['pix_fmt'] = "format de pixel {$probe->pixFmt} non supporté";
        }

        if (! $this->resolutionCompliant($probe, $config)) {
            $failures['resolution'] = "résolution {$probe->width}x{$probe->height} au-delà de {$config['max_width']}x{$config['max_height']}";
        }

        if (! $this->audioCompliant($probe, $config)) {
            $failures['audio'] = "piste audio {$probe->audioCodec} non supportée";
        }

        if (! $this->bitrateCompliant($probe)) {
            $failures['bitrate'] = 'débit vidéo au-delà du plafond autorisé pour cette résolution';
        }

        // Everything except container/faststart is a "stream-level" property
        // that only a real re-encode can fix; container and faststart alone
        // can be fixed by a lossless remux.
        $streamFailures = array_diff_key($failures, array_flip(['container', 'faststart']));

        if ($failures === []) {
            return new TranscodeDecision(TranscodeAction::SKIP, 'Déjà conforme');
        }

        if ($streamFailures === []) {
            return new TranscodeDecision(TranscodeAction::REMUX, implode(', ', $failures));
        }

        return new TranscodeDecision(TranscodeAction::ENCODE, implode(', ', $failures));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolutionCompliant(VideoProbe $probe, array $config): bool
    {
        if ($probe->width === null || $probe->height === null) {
            return false;
        }

        return $probe->width <= $config['max_width'] && $probe->height <= $config['max_height'];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function audioCompliant(VideoProbe $probe, array $config): bool
    {
        if (! $probe->hasAudio) {
            return true;
        }

        return $probe->audioCodec === $config['audio_codec']
            && $probe->audioChannels !== null
            && $probe->audioChannels <= $config['max_audio_channels'];
    }

    private function bitrateCompliant(VideoProbe $probe): bool
    {
        // Bitrate could not be determined at all ; don't force a re-encode over missing data.
        if ($probe->videoBitrate === null || $probe->height === null) {
            return true;
        }

        return $probe->videoBitrate <= $this->bitrateCeilingForHeight($probe->height);
    }

    public function bitrateCeilingForHeight(int $height): int
    {
        $ceilingsByHeight = config('media.compliance.max_video_bitrate_by_height');
        ksort($ceilingsByHeight);

        foreach ($ceilingsByHeight as $bucketHeight => $ceiling) {
            if ($height <= $bucketHeight) {
                return $ceiling;
            }
        }

        // Height exceeds every configured bucket: use the highest one.
        return end($ceilingsByHeight);
    }
}
