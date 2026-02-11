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
        Schema::create('admin_message_reactions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('message_id')->constrained('admin_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Réaction
            $table->string('emoji', 10); // Emoji (ex: '👍', '❤️', '😂')
            
            $table->timestamps();
            
            // Un utilisateur ne peut réagir qu'une fois avec le même emoji sur un message
            $table->unique(['message_id', 'user_id', 'emoji']);
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_message_reactions');
    }
};
