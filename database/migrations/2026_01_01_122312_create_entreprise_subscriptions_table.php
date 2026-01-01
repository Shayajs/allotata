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
        Schema::create('entreprise_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'site_web' ou 'multi_personnes'
            $table->string('name'); // Nom de l'abonnement (ex: 'site_web', 'multi_personnes')
            $table->string('stripe_id')->unique()->nullable(); // ID Stripe si abonnement Stripe
            $table->string('stripe_status')->nullable(); // Statut Stripe
            $table->string('stripe_price')->nullable(); // Prix Stripe
            $table->boolean('est_manuel')->default(false); // Si activé manuellement par admin
            $table->date('actif_jusqu')->nullable(); // Date d'expiration pour abonnement manuel
            $table->text('notes_manuel')->nullable(); // Notes pour abonnement manuel
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['entreprise_id', 'type', 'stripe_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprise_subscriptions');
    }
};
