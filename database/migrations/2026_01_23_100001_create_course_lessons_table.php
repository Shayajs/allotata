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
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->longText('contenu_rich_html')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('type', ['course', 'quiz'])->default('course');
            $table->integer('ordre')->default(0);
            $table->integer('points_quiz')->default(0);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            
            $table->index('module_id');
            $table->index(['module_id', 'ordre']);
            $table->index('type');
            $table->index('est_actif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};
