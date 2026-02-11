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
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('conversation_id')->constrained('admin_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Contenu
            $table->text('contenu')->nullable(); // Texte du message (nullable si c'est juste un fichier)
            $table->enum('type', ['texte', 'image', 'video'])->default('texte');
            $table->string('fichier')->nullable(); // Chemin vers le fichier
            
            $table->timestamps();
            
            // Index pour requêtes rapides
            $table->index(['conversation_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_messages');
    }
};
