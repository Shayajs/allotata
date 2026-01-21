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
        Schema::create('admin_conversation_members', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('conversation_id')->constrained('admin_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Métadonnées
            $table->timestamp('dernier_vu_at')->nullable();
            
            $table->timestamps();
            
            // Un admin ne peut être membre qu'une fois par conversation
            $table->unique(['conversation_id', 'user_id']);
            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_conversation_members');
    }
};
