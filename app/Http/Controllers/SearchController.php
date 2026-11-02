<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $request->query('value', '');
        $user = $request->user();

        $videos = collect();

        if (strlen($query) >= 2) {
            $videos = Video::published()
                ->accessibleBy($user)
                ->where(function ($q) use ($query) {
                    $q->whereLike('name', "%{$query}%")
                        ->orWhereLike('description', "%{$query}%");
                })
                ->orderBy('created_on', 'desc')
                ->limit(50)
                ->get();
        }

        return Inertia::render('Search/Index', [
            'videos' => $videos,
            'query' => $query,
        ]);
    }

    public function searchApi(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->query('q', '');
        $limit = max(0, min((int) $request->query('limit', 20), 20));

        $baseQuery = Video::published()
            ->accessibleBy($user)
            ->whereLike('name', "%{$query}%");

        $total = $baseQuery->clone()->count();

        $videos = $baseQuery
            ->orderBy('created_on', 'desc')
            ->limit($limit)
            ->get();

        return response()->json(['videos' => $videos, 'total' => $total]);
    }
}
