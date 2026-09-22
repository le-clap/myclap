<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the columns/table needed by the async transcode service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video', function (Blueprint $table) {
            $table->string('video_codec', 32)->nullable();
            $table->string('audio_codec', 32)->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->bigInteger('bitrate')->nullable();
            $table->boolean('faststart')->nullable();
        });

        Schema::create('video_transcode', function (Blueprint $table) {
            $table->id();
            $table->string('video_token');
            $table->string('action', 10)->nullable();
            $table->unsignedTinyInteger('status');
            $table->text('reason')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_on')->useCurrent();
            $table->timestamp('finished_on')->nullable();

            $table->foreign('video_token')->references('token')->on('video')->cascadeOnDelete();
            $table->index(['video_token', 'started_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_transcode');

        Schema::table('video', function (Blueprint $table) {
            $table->dropColumn([
                'video_codec',
                'audio_codec',
                'width',
                'height',
                'bitrate',
                'faststart',
            ]);
        });
    }
};
