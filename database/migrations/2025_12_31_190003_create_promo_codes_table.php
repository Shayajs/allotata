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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->enum('type', ['pourcentage', 'montant_fixe'])->default('pourcentage');
            $table->decimal('valeur', 10, 2); // Ex: 20 pour 20% ou 20.00€
            $table->integer('usages_max')->nullable(); // null = illimité
            $table->integer('usages_actuels')->default(0);
            $table->integer('duree_mois')->nullable(); // Durée de l'abonnement offert/réduit
            $table->datetime('date_debut')->nullable();
            $table->datetime('date_fin')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->boolean('premier_abonnement_uniquement')->default(true); // Uniquement pour les nouveaux abonnés
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('code');
            $table->index('est_actif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
