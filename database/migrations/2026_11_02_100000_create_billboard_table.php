<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Billboards used to live in storage/app/private/billboard.json.
 */
return new class extends Migration
{
    private const string LEGACY_FILE = 'billboard.json';

    public function up(): void
    {
        Schema::create('billboard', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('button');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->string('color');
        });

        $disk = Storage::disk('local');
        $legacy = $disk->exists(self::LEGACY_FILE)
            ? json_decode($disk->get(self::LEGACY_FILE), true) ?? []
            : [];

        foreach ($legacy as $billboard) {
            DB::table('billboard')->insert([
                'title' => $billboard['title'],
                'button' => $billboard['button'],
                'url' => $billboard['url'],
                'icon' => $billboard['icon'] ?: null,
                'color' => $billboard['color'],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billboard');
    }
};
