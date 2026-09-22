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
            'sort' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:12|max:120',
            'status' => 'nullable|string|in:processing,failed,ok,all',
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $sort = (string) ($validated['sort'] ?? '-started_on');
        $limit = (int) ($validated['limit'] ?? 24);
        $status = (string) ($validated['status'] ?? 'processing');

        $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortBy = ltrim($sort, '-') === 'status' ? 'status' : 'started_on';

        $attemptsQuery = VideoTranscode::query()->with('video:token,name,thumbnail_identifier');

        if ($query !== '') {
            $attemptsQuery->whereHas('video', fn ($q) => $q->search($query));
        }

        if (isset(self::STATUS_BUCKETS[$status])) {
            $attemptsQuery->whereIn(
                'status',
                array_map(fn (TranscodeStatus $s) => $s->value, self::STATUS_BUCKETS[$status])
            );
        }

        $attemptsQuery->orderBy($sortBy, $sortDir);
        if ($sortBy !== 'started_on') {
            $attemptsQuery->orderByDesc('started_on');
        }

        $attempts = $attemptsQuery
            ->paginate($limit)
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
            if ($attempt->status === TranscodeStatus::PROCESSING) {
                $attempt->progress = Cache::get("transcode:{$attempt->video_token}:progress");
            }

            return $attempt;
        });

        return Inertia::render('Manager/Transcode/Index', [
            'attempts' => $attempts,
            'filters' => [
                'q' => $query,
                'sort' => $sort,
                'limit' => $limit,
                'status' => $status,
            ],
            'statusOptions' => [
                ['value' => 'processing', 'label' => 'En cours', 'count' => $bucketCounts['processing']],
                ['value' => 'failed', 'label' => 'Échecs', 'count' => $bucketCounts['failed']],
                ['value' => 'ok', 'label' => 'Réussis', 'count' => $bucketCounts['ok']],
                ['value' => 'all', 'label' => 'Toutes', 'count' => $bucketCounts['all']],
            ],
            'sortOptions' => [
                ['value' => 'started_on', 'label' => 'Date'],
                ['value' => 'status', 'label' => 'Statut'],
            ],
        ]);
    }
}
