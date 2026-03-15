<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add a 'file_size' column to the 'video' table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video', function (Blueprint $table) {
            $table->bigInteger('file_size')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('video', function (Blueprint $table) {
            $table->dropColumn('file_size');
        });
    }
};
