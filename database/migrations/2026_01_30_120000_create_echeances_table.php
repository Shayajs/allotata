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
        Schema::create('echeances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->unsignedTinyInteger('jour_facturation'); // 1-31

            $table->decimal('montant_du', 10, 2);
            $table->decimal('montant_final', 10, 2);
            $table->decimal('reduction_promo', 10, 2)->default(0);
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->onDelete('set null');

            $table->string('stripe_checkout_session_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->timestamp('paye_at')->nullable();

            $table->string('statut', 32)->default('a_payer'); // a_payer | en_attente | paye | echec | annule
            $table->json('metadata')->nullable();
            $table->foreignId('facture_id')->nullable()->constrained('factures')->onDelete('set null');

            $table->timestamps();

            $table->index(['user_id', 'periode_debut', 'periode_fin']);
            $table->index(['user_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('echeances');
    }
};
