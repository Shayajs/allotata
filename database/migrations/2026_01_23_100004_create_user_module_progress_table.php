<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('total_lessons')->default(0);
            $table->integer('points_total')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'module_id']);
            $table->index('user_id');
            $table->index('module_id');
            $table->index('last_accessed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_module_progress');
    }
};
