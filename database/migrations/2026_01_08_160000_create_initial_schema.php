<?php

use App\Enums\ContentAccess;
use App\Enums\PlaylistType;
use App\Enums\UploadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Initial schema migration for MyClap.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('clap_user', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('school_email')->nullable();
            $table->integer('promo');
            $table->boolean('alumni')->default(false);
            $table->timestamp('created_on')->useCurrent();
            $table->timestamp('logged_on');

            $table->index('promo', 'idx_clap_user_promo');
        });

        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('created_by');
            $table->timestamp('created_on')->useCurrent();

            $table->foreign('created_by')
                ->references('username')
                ->on('clap_user')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index('slug', 'idx_category_slug');
        });

        Schema::create('playlist', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('type')->default(PlaylistType::CLASSIC->value);
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('access')->default(ContentAccess::PUBLIC->value);
            $table->boolean('pinned')->default(false);
            $table->timestamp('created_on')->useCurrent();
            $table->timestamp('modified_on')->useCurrent();
            $table->string('modified_by');

            $table->foreign('modified_by')
                ->references('username')
                ->on('clap_user')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index('type', 'idx_playlist_type');
            $table->index(['type', 'modified_on'], 'idx_playlist_type_modified');
            $table->index('access', 'idx_playlist_access');
            $table->index('pinned', 'idx_playlist_pinned');
        });

        Schema::create('video', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token')->unique();
            $table->date('created_on');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('access')->default(ContentAccess::PUBLIC->value);
            $table->string('thumbnail_identifier')->nullable();
            $table->string('file_identifier')->nullable();
            $table->integer('duration')->nullable();
            $table->unsignedTinyInteger('upload_status')->default(UploadStatus::UPLOAD_NULL->value);
            $table->string('uploaded_by');
            $table->timestamp('uploaded_on')->useCurrent();
            $table->integer('views')->default(0);
            $table->integer('reactions')->default(0);

            $table->foreign('uploaded_by')
                ->references('username')
                ->on('clap_user')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index(['access', 'upload_status'], 'idx_video_access_status');
            $table->index('upload_status', 'idx_video_upload_status');
            $table->index('created_on', 'idx_video_created_on');
            $table->index('uploaded_by', 'idx_video_uploaded_by');
        });

        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE video ADD CONSTRAINT chk_video_views_positive CHECK (views >= 0)');
            DB::statement('ALTER TABLE video ADD CONSTRAINT chk_video_reactions_positive CHECK (reactions >= 0)');
        }

        Schema::create('video_category', function (Blueprint $table) {
            $table->string('video_token');
            $table->string('category_slug');

            $table->primary(['video_token', 'category_slug']);

            $table->foreign('video_token')
                ->references('token')
                ->on('video')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('category_slug')
                ->references('slug')
                ->on('category')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('category_slug', 'idx_video_category_category');
        });

        Schema::create('playlist_video', function (Blueprint $table) {
            $table->string('playlist_slug');
            $table->string('video_token');
            $table->integer('position')->default(0);

            $table->primary(['playlist_slug', 'video_token']);

            $table->foreign('playlist_slug')
                ->references('slug')
                ->on('playlist')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('video_token')
                ->references('token')
                ->on('video')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('video_token', 'idx_playlist_video_video');
            $table->index(['playlist_slug', 'position'], 'idx_playlist_video_position');
        });

        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE playlist_video ADD CONSTRAINT chk_playlist_video_position_positive CHECK (position >= 0)');
        }

        Schema::create('video_reaction', function (Blueprint $table) {
            $table->id();
            $table->string('video_token');
            $table->string('username');
            $table->timestamp('created_on')->useCurrent();

            $table->unique(['video_token', 'username'], 'uq_video_reaction_user_video');

            $table->foreign('video_token')
                ->references('token')
                ->on('video')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('username')
                ->references('username')
                ->on('clap_user')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('video_token', 'idx_video_reaction_video_token');
            $table->index('username', 'idx_video_reaction_username');
        });

        Schema::create('video_upload', function (Blueprint $table) {
            $table->id();
            $table->string('video_token');
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('file_identifier');
            $table->string('created_by');
            $table->timestamp('created_on')->useCurrent();

            $table->foreign('video_token')
                ->references('token')
                ->on('video')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('created_by')
                ->references('username')
                ->on('clap_user')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index('video_token', 'idx_video_upload_video_token');
            $table->index('created_by', 'idx_video_upload_created_by');
        });

        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE video_upload ADD CONSTRAINT chk_video_upload_file_size_positive CHECK (file_size > 0)');
        }

        Schema::create('video_view', function (Blueprint $table) {
            $table->id();
            $table->string('video_token');
            $table->string('php_sid');
            $table->string('playback_sid');
            $table->string('username')->nullable();
            $table->boolean('count_as_view')->default(false);
            $table->integer('watch_time')->nullable();
            $table->string('view_source')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamp('created_on')->useCurrent();
            $table->timestamp('updated_on')->useCurrent();

            $table->foreign('video_token')
                ->references('token')
                ->on('video')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('username')
                ->references('username')
                ->on('clap_user')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->index(['video_token', 'php_sid', 'created_on'], 'idx_video_view_token_sid_created');
            $table->index(['video_token', 'count_as_view'], 'idx_video_view_token_count');
            $table->index('view_source', 'idx_video_view_source');
            $table->index('device_type', 'idx_video_view_device');
            $table->index('created_on', 'idx_video_view_created_on');
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('identifier');
            $table->string('created_by');
            $table->timestamp('created_on')->useCurrent();

            $table->unique(['username', 'identifier'], 'uq_user_permission_user_identifier');

            $table->foreign('username')
                ->references('username')
                ->on('clap_user')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('created_by')
                ->references('username')
                ->on('clap_user')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index('identifier', 'idx_user_permission_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('video_view');
        Schema::dropIfExists('video_upload');
        Schema::dropIfExists('video_reaction');
        Schema::dropIfExists('playlist_video');
        Schema::dropIfExists('video_category');
        Schema::dropIfExists('video');
        Schema::dropIfExists('playlist');
        Schema::dropIfExists('category');
        Schema::dropIfExists('clap_user');
    }
};
