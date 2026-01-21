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
        Schema::create('admin_typing_indicators', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('conversation_id')->constrained('admin_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Métadonnées
            $table->timestamp('updated_at'); // Mis à jour lors de la frappe
            
            // Un admin ne peut avoir qu'un indicateur de frappe par conversation
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['conversation_id', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_typing_indicators');
    }
};
