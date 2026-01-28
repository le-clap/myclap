<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $stats = [];

        if ($user->hasPermission('manager.stat')) {
            $stats = [
                'total_videos' => Video::count(),
                'published_videos' => Video::published()->count(),
                'total_playlists' => Playlist::count(),
                'total_categories' => Category::count(),
                'total_views' => Video::sum('views'),
                'total_reactions' => Video::sum('reactions'),
            ];
        }

        $recentVideos = [];
        if ($user->hasPermissionGroup('manager.video')) {
            $recentVideos = Video::orderBy('uploaded_on', 'desc')
                ->limit(5)
                ->get();
        }

        return Inertia::render('Manager/Dashboard', [
            'stats' => $stats,
            'recentVideos' => $recentVideos,
        ]);
    }
}
