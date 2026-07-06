<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Console\Command;

class SyncVideoMetadata extends Command
{
    protected $signature = 'videos:sync-metadata';

    protected $description = 'Sync duration and file size for all videos';

    public function __construct(
        private readonly VideoService $videoService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Updating video metadata...');

        $count = 0;

        Video::whereNotNull('file_identifier')
            ->chunkById(50, function ($videos) use (&$count) {
                foreach ($videos as $video) {
                    try {
                        $this->videoService->syncMetadata($video);
                        $count++;
                        $this->line("✓ {$video->token}");
                    } catch (\Throwable $e) {
                        $this->error("✗ {$video->token} : {$e->getMessage()}");
                        report($e);
                    }
                }
            });

        $this->info("Done. {$count} videos processed.");

        return self::SUCCESS;
    }
}
