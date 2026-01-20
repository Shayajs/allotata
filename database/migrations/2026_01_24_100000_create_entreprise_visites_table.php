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
        Schema::create('entreprise_visites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id')->index();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('page_type', ['accueil', 'agenda', 'store', 'services', 'produits'])->default('accueil');
            $table->integer('duree_seconde')->nullable();
            $table->boolean('a_quitte_rapidement')->default(false); // < 3 secondes
            $table->boolean('a_quitte_apres_exploration')->default(false); // > 7 secondes sans réservation
            $table->integer('nb_clics_services')->default(0);
            $table->integer('nb_clics_produits')->default(0);
            $table->boolean('a_passe_commande')->default(false);
            $table->timestamp('date_reservation')->nullable();
            $table->integer('temps_avant_reservation_secondes')->nullable();
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['entreprise_id', 'created_at']);
            $table->index(['user_id', 'entreprise_id']);
            $table->index(['session_id', 'entreprise_id']);
            $table->index('a_passe_commande');
            $table->index('a_quitte_apres_exploration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprise_visites');
    }
};
