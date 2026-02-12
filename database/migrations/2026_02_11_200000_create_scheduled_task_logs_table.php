<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_logs', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->string('description')->nullable();
            $table->enum('status', ['success', 'error', 'running'])->default('running');
            $table->text('output')->nullable();
            $table->integer('exit_code')->nullable();
            $table->decimal('duration_seconds', 10, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['command', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_logs');
    }
};
