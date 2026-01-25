<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('feedback_votes');
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        Schema::create('feedback_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('feedbacks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['feedback_id', 'user_id']);
            $table->index('feedback_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_votes');
    }
};
