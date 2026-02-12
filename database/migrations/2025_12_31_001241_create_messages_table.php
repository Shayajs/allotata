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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // L'expéditeur
            
            // Contenu
            $table->text('contenu')->nullable(); // Texte du message (nullable si c'est juste une image)
            $table->string('image')->nullable(); // Chemin vers l'image compressée
            
            // Statut
            $table->boolean('est_lu')->default(false); // Pour marquer les messages comme lus
            
            $table->timestamps();
            
            $table->index('conversation_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
