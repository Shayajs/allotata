<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_daily_visitors', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->string('session_id', 128);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_type', 20); // member, guest, bot
            $table->unsignedInteger('page_views')->default(1);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['visit_date', 'session_id']);
            $table->index(['visit_date', 'visitor_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_daily_visitors');
    }
};
