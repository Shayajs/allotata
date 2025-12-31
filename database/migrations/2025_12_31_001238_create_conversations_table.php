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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Le client
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade'); // L'entreprise
            
            // Métadonnées
            $table->timestamp('dernier_message_at')->nullable(); // Pour trier les conversations
            $table->boolean('est_archivee')->default(false); // Pour archiver les conversations
            
            $table->timestamps();
            
            // Une seule conversation active par client/entreprise
            $table->unique(['user_id', 'entreprise_id']);
            $table->index('dernier_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
