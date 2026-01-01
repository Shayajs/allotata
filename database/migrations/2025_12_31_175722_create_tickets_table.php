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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ticket')->unique();
            $table->string('sujet');
            $table->text('description');
            $table->enum('statut', ['ouvert', 'en_cours', 'resolu', 'ferme'])->default('ouvert');
            $table->enum('priorite', ['basse', 'normale', 'haute', 'urgente'])->default('normale');
            $table->enum('categorie', ['technique', 'facturation', 'compte', 'autre'])->default('autre');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigne_a')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolu_at')->nullable();
            $table->timestamps();
            
            $table->index('statut');
            $table->index('user_id');
            $table->index('assigne_a');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
