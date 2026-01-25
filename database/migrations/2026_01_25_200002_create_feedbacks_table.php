<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('titre');
            $table->text('description');
            $table->enum('categorie', ['demande', 'remerciement', 'erreur', 'conseil', 'autre'])->default('demande');
            $table->enum('statut', ['poste', 'traitement_en_cours', 'termine', 'refuse', 'deja_fait'])->default('poste');
            $table->integer('votes_count')->default(0);
            $table->integer('commentaires_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('categorie');
            $table->index('statut');
            $table->index('votes_count');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
