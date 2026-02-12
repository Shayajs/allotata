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
        Schema::create('stripe_transactions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Identifiants Stripe
            $table->string('stripe_customer_id')->nullable()->index(); // ID du customer Stripe
            $table->string('stripe_payment_intent_id')->nullable()->index(); // ID du payment intent
            $table->string('stripe_charge_id')->nullable()->index(); // ID de la charge
            $table->string('stripe_invoice_id')->nullable()->index(); // ID de la facture
            $table->string('stripe_subscription_id')->nullable()->index(); // ID de l'abonnement
            $table->string('stripe_checkout_session_id')->nullable()->index(); // ID de la session checkout
            
            // Informations de l'événement
            $table->string('event_type'); // Type d'événement Stripe (payment_intent.succeeded, etc.)
            $table->string('stripe_event_id')->unique()->index(); // ID unique de l'événement Stripe
            
            // Informations financières
            $table->decimal('amount', 10, 2)->nullable(); // Montant en centimes (divisé par 100)
            $table->string('currency', 3)->default('eur'); // Devise
            $table->string('status')->nullable(); // Statut de la transaction
            
            // Métadonnées et données brutes
            $table->json('metadata')->nullable(); // Métadonnées personnalisées
            $table->json('raw_data')->nullable(); // Données brutes de l'événement pour debug
            
            // Informations de suivi
            $table->text('description')->nullable(); // Description de la transaction
            $table->boolean('processed')->default(false); // Indique si l'événement a été traité
            $table->timestamp('processed_at')->nullable(); // Date de traitement
            
            $table->timestamps();
            
            // Index pour les recherches fréquentes
            $table->index(['user_id', 'event_type']);
            $table->index(['stripe_customer_id', 'created_at']);
            $table->index('processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_transactions');
    }
};
