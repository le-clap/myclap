<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\API\VideoReactionController;
use App\Http\Controllers\API\VideoStatController;
use App\Http\Controllers\Auth\CLAController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Manager\BillboardController;
use App\Http\Controllers\Manager\CategoryController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\PlaylistController;
use App\Http\Controllers\Manager\StatController;
use App\Http\Controllers\Manager\VideoController;
use App\Http\Controllers\Manager\VideoUploadApiController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

// ===== Authentication =====
Route::get('/login', [CLAController::class, 'login'])->name('login');
Route::get('/login/cla', [CLAController::class, 'handleCallback'])->name('auth.cla.callback');
Route::get('/logout', [CLAController::class, 'logout'])->name('logout');

// ===== Public Pages =====
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/playlists', [HomeController::class, 'playlists'])->name('home.playlists');
Route::get('/playlists/{slug}', [HomeController::class, 'playlistDetails'])->name('home.playlists.details');

Route::get('/categories', [HomeController::class, 'categories'])->name('home.categories');
Route::get('/categories/{slug}', [HomeController::class, 'categoryDetails'])->name('home.categories.details');

Route::get('/favoris', [HomeController::class, 'favorites'])->name('home.favorites');

Route::get('/search', [SearchController::class, 'index'])->name('search');

// ===== Watch Video =====
Route::get('/regarder/{token}', [WatchController::class, 'show'])->name('watch');
Route::get('/playlists/{slug}/{token}', [WatchController::class, 'showInPlaylist'])->name('watch.playlist');
Route::get('/download/{token}', [WatchController::class, 'download'])->name('watch.download');

// ===== Media (Video/Thumbnail) =====
Route::get('/media/{token}', [MediaController::class, 'video'])->name('watch.media.video');
Route::get('/media/{token}/download', [MediaController::class, 'videoDownload'])->name('watch.media.video.download');
Route::get('/media/{token}/thumbnail', [MediaController::class, 'thumbnail'])->name('watch.media.thumbnail');

// ===== User API =====
Route::get('/api/videos', [HomeController::class, 'loadVideos'])->name('api.videos');
Route::get('/api/categories/{slug}/videos', [HomeController::class, 'loadCategoryVideos'])->name('api.categories.videos');
Route::get('/api/search', [SearchController::class, 'searchApi'])->name('api.search');
Route::post('/api/reaction/{token}', [VideoReactionController::class, 'toggle'])->name('api.reaction.toggle');

// ===== Video Stats API =====
Route::post('/api/stats/{token}/init', [VideoStatController::class, 'init'])->name('api.stats.init');
Route::post('/api/stats/{token}/update', [VideoStatController::class, 'update'])->name('api.stats.update');

// ===== Manager =====
Route::prefix('manager')->name('manager.')->middleware(['auth', 'permission_group:manager'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Videos
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/ajouter', [VideoController::class, 'create'])->name('videos.create');
    Route::get('/videos/v/{token}', [VideoController::class, 'edit'])->name('videos.edit');
    Route::get('/videos/v/{token}/envoyer', [VideoController::class, 'upload'])->name('videos.upload');

    // Videos API
    Route::post('/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::put('/videos/v/{token}', [VideoController::class, 'update'])->name('videos.update');
    Route::delete('/videos/v/{token}', [VideoController::class, 'destroy'])->name('videos.destroy');

    // Video Upload API
    Route::post('/videos/v/{token}/upload/init', [VideoUploadApiController::class, 'init'])->name('videos.upload.init');
    Route::post('/videos/v/{token}/upload/process', [VideoUploadApiController::class, 'process'])->name('videos.upload.process');
    Route::post('/videos/v/{token}/upload/end', [VideoUploadApiController::class, 'end'])->name('videos.upload.end');
    Route::post('/videos/v/{token}/upload/reset', [VideoUploadApiController::class, 'reset'])->name('videos.upload.reset');

    // Playlists
    Route::middleware('permission:manager.playlist')->group(function () {
        Route::get('/playlists', [PlaylistController::class, 'index'])->name('playlists.index');
        Route::get('/playlists/ajouter', [PlaylistController::class, 'create'])->name('playlists.create');
        Route::get('/playlists/s/{slug}', [PlaylistController::class, 'edit'])->name('playlists.edit');

        // Playlists API
        Route::post('/playlists', [PlaylistController::class, 'store'])->name('playlists.store');
        Route::put('/playlists/s/{slug}', [PlaylistController::class, 'update'])->name('playlists.update');
        Route::delete('/playlists/s/{slug}', [PlaylistController::class, 'destroy'])->name('playlists.destroy');
        Route::get('/playlists/api/search', [PlaylistController::class, 'searchVideos'])->name('playlists.api.search');
    });

    // Categories
    Route::middleware('permission:manager.category')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/ajouter', [CategoryController::class, 'create'])->name('categories.create');
        Route::get('/categories/s/{slug}', [CategoryController::class, 'edit'])->name('categories.edit');

        // Categories API
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/s/{slug}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/s/{slug}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Stats
    Route::middleware('permission:manager.stat')->group(function () {
        Route::get('/stats', [StatController::class, 'index'])->name('stats.index');
        Route::get('/stats/video/{token}', [StatController::class, 'video'])->name('stats.video');
    });

    // Billboard / Presentation
    Route::middleware('permission:manager.design')->group(function () {
        Route::get('/presentation', [BillboardController::class, 'index'])->name('billboard.index');
        Route::get('/presentation/ajouter', [BillboardController::class, 'create'])->name('billboard.create');
        Route::get('/presentation/{identifier}', [BillboardController::class, 'edit'])->name('billboard.edit');

        // Billboard / Presentation API
        Route::post('/presentation', [BillboardController::class, 'store'])->name('billboard.store');
        Route::put('/presentation/{identifier}', [BillboardController::class, 'update'])->name('billboard.update');
        Route::delete('/presentation/{identifier}', [BillboardController::class, 'destroy'])->name('billboard.destroy');
    });
});

// ===== Admin =====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/identifier/{identifier}', [PermissionController::class, 'byIdentifier'])->name('permissions.identifier')->where('identifier', '.*');
    Route::get('/permissions/user/{username}', [PermissionController::class, 'byUser'])->name('permissions.user');
    Route::post('/permissions/grant', [PermissionController::class, 'grant'])->name('permissions.grant');
    Route::post('/permissions/revoke', [PermissionController::class, 'revoke'])->name('permissions.revoke');
    Route::get('/permissions/api/search', [PermissionController::class, 'searchUsers'])->name('permissions.api.search');
});
