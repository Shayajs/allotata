<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdpr_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['export', 'deletion']);
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'failed']);
            $table->text('reason')->nullable();
            $table->string('export_path')->nullable();
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('processed_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_requests');
    }
};
