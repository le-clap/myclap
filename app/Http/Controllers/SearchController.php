<?php

namespace App\Http\Controllers;

use App\Enums\ContentAccess;
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
            $videosQuery = Video::published()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                        ->orWhere('description', 'ILIKE', "%{$query}%");
                })
                ->orderBy('created_on', 'desc');

            if ($user) {
                $videosQuery->whereIn('access', [
                    ContentAccess::CENTRALIENS->value,
                    ContentAccess::PUBLIC->value,
                ]);
            } else {
                $videosQuery->where('access', ContentAccess::PUBLIC->value);
            }

            $videos = $videosQuery->limit(50)->get();
        }

        return Inertia::render('Search/Index', [
            'videos' => $videos,
            'query' => $query,
        ]);
    }

    public function searchApi(Request $request)
    {
        $user = $request->user();
        $query = $request->get('q', '');
        $limit = $request->get('limit', 20);

        $videosQuery = Video::published()
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%");
            })
            ->orderBy('created_on', 'desc');

        if ($user) {
            $videosQuery->whereIn('access', [
                ContentAccess::CENTRALIENS->value,
                ContentAccess::PUBLIC->value,
            ]);
        } else {
            $videosQuery->where('access', ContentAccess::PUBLIC->value);
        }

        $videos = $videosQuery->limit($limit)->get();

        return response()->json(['videos' => $videos, 'total' => $videos->count()]);
    }
}
