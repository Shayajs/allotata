<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('feedbacks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('contenu');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('feedback_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_comments');
    }
};
