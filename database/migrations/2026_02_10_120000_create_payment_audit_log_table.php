<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier si la table existe déjà (peut arriver si créée manuellement ou migration partielle)
        if (Schema::hasTable('payment_audit_log')) {
            return;
        }

        Schema::create('payment_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action', 64);
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_setup_intent_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->unsignedBigInteger('echeance_id')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('status', 32)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 64)->nullable()->index();
            $table->json('context')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audit_log');
    }
};
