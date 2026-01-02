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
        Schema::create('custom_prices', function (Blueprint $table) {
            $table->id();
            
            // Lien vers l'utilisateur (pour les abonnements utilisateur)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            // Lien vers l'entreprise (pour les abonnements d'entreprise)
            $table->foreignId('entreprise_id')->nullable()->constrained()->onDelete('cascade');
            
            // Type d'abonnement : 'default', 'site_web', 'multi_personnes'
            $table->string('subscription_type')->index();
            
            // ID du prix Stripe personnalisé
            $table->string('stripe_price_id')->unique();
            
            // Montant personnalisé (pour référence)
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('eur');
            
            // Notes/raison du prix personnalisé
            $table->text('notes')->nullable();
            
            // Créé par (admin)
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Dates
            $table->timestamp('expires_at')->nullable(); // Si le prix personnalisé expire
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Index pour les recherches rapides
            $table->index(['user_id', 'subscription_type', 'is_active']);
            $table->index(['entreprise_id', 'subscription_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_prices');
    }
};
