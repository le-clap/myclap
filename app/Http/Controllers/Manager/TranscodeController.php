<?php

namespace App\Http\Controllers\Manager;

use App\Enums\TranscodeStatus;
use App\Http\Controllers\Controller;
use App\Models\VideoTranscode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TranscodeController extends Controller
{
    private const array STATUS_BUCKETS = [
        'processing' => [TranscodeStatus::PENDING, TranscodeStatus::PROCESSING],
        'failed' => [TranscodeStatus::FAILED],
        'ok' => [TranscodeStatus::COMPLIANT, TranscodeStatus::TRANSCODED],
    ];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:processing,failed,ok,all',
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $status = (string) ($validated['status'] ?? 'all');

        $attemptsQuery = VideoTranscode::query()
            ->with('video:token,name,thumbnail_identifier')
            ->orderByDesc('started_on')
            ->orderByDesc('id');

        if ($query !== '') {
            $attemptsQuery->whereHas('video', fn ($q) => $q->search($query));
        }

        if (isset(self::STATUS_BUCKETS[$status])) {
            $attemptsQuery->whereIn(
                'status',
                array_map(fn (TranscodeStatus $s) => $s->value, self::STATUS_BUCKETS[$status])
            );
        }

        $attempts = $attemptsQuery
            ->paginate(24)
            ->withQueryString();

        $countsByStatus = VideoTranscode::query()
            ->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bucketCounts = ['all' => (int) $countsByStatus->sum()];
        foreach (self::STATUS_BUCKETS as $bucket => $statuses) {
            $bucketCounts[$bucket] = array_sum(array_map(
                fn (TranscodeStatus $s) => (int) $countsByStatus->get($s->value, 0),
                $statuses
            ));
        }

        $attempts->getCollection()->transform(function (VideoTranscode $attempt) {
            $attempt->video->setAppends(['thumbnail_urls']);

            if ($attempt->status === TranscodeStatus::PROCESSING) {
                $attempt->progress = Cache::get("transcode:{$attempt->video_token}:progress");
            }

            return $attempt;
        });

        return Inertia::render('Manager/Transcode/Index', [
            'attempts' => $attempts,
            'filters' => [
                'q' => $query,
                'status' => $status,
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'Toutes', 'count' => $bucketCounts['all']],
                ['value' => 'processing', 'label' => 'En cours', 'count' => $bucketCounts['processing']],
                ['value' => 'failed', 'label' => 'Échecs', 'count' => $bucketCounts['failed']],
                ['value' => 'ok', 'label' => 'Réussies', 'count' => $bucketCounts['ok']],
            ],
        ]);
    }
}
