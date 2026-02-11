<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->text('google_access_token')->nullable()->after('site_web_externe');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
            $table->string('google_calendar_id')->nullable()->after('google_token_expires_at');
            $table->string('google_watch_channel_id')->nullable()->after('google_calendar_id');
            $table->timestamp('google_watch_expiration')->nullable()->after('google_watch_channel_id');
            $table->string('google_sync_token')->nullable()->after('google_watch_expiration');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn([
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'google_calendar_id',
                'google_watch_channel_id',
                'google_watch_expiration',
                'google_sync_token',
            ]);
        });
    }
};
