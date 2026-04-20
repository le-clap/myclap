<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlist', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0);
        });

        foreach ([1, 0] as $type) {
            $playlists = DB::table('playlist')
                ->select('id')
                ->where('type', $type)
                ->orderByDesc('pinned')
                ->orderBy('name')
                ->orderBy('id')
                ->get();

            foreach ($playlists as $index => $playlist) {
                DB::table('playlist')
                    ->where('id', $playlist->id)
                    ->update(['position' => $index]);
            }
        }

        Schema::table('playlist', function (Blueprint $table) {
            $table->index(['type', 'position'], 'idx_playlist_type_position');
            $table->dropIndex('idx_playlist_pinned');
            $table->dropColumn('pinned');
        });
    }

    public function down(): void
    {
        Schema::table('playlist', function (Blueprint $table) {
            $table->boolean('pinned')->default(false);
        });

        foreach ([1, 0] as $type) {
            $playlists = DB::table('playlist')
                ->select('id')
                ->where('type', $type)
                ->orderBy('position')
                ->orderBy('name')
                ->orderBy('id')
                ->get();

            foreach ($playlists as $index => $playlist) {
                DB::table('playlist')
                    ->where('id', $playlist->id)
                    ->update(['pinned' => $index === 0]);
            }
        }

        Schema::table('playlist', function (Blueprint $table) {
            $table->index('pinned', 'idx_playlist_pinned');
            $table->dropIndex('idx_playlist_type_position');
            $table->dropColumn('position');
        });
    }
};
