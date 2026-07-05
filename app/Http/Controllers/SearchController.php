<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('value', '');
        $user = $request->user();

        $videos = collect([]);

        if (strlen($query) >= 2) {
            $videos = Video::published()
                ->accessibleBy($user)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                        ->orWhere('description', 'ILIKE', "%{$query}%");
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

    public function searchApi(Request $request)
    {
        $user = $request->user();
        $query = $request->query('q', '');
        $limit = max(0, min((int) $request->query('limit', 20), 20));

        $baseQuery = Video::published()
            ->accessibleBy($user)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%");
            });

        $total = $baseQuery->clone()->count();

        $videos = $baseQuery
            ->orderBy('created_on', 'desc')
            ->limit($limit)
            ->get();

        return response()->json(['videos' => $videos, 'total' => $total]);
    }
}
