<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_module_progress', function (Blueprint $table) {
            $table->timestamp('video_watched_at')->nullable()->after('points_total');
            $table->integer('video_points_earned')->default(0)->after('video_watched_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_module_progress', function (Blueprint $table) {
            $table->dropColumn(['video_watched_at', 'video_points_earned']);
        });
    }
};
