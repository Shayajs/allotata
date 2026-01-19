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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('destinataire', 20); // Numéro de téléphone
            $table->text('message');
            $table->enum('statut', ['envoye', 'echec', 'en_attente'])->default('en_attente');
            $table->string('provider', 50)->default('twilio'); // twilio, log, etc.
            $table->string('provider_message_id')->nullable(); // ID du message chez le provider
            $table->text('error_message')->nullable(); // Message d'erreur en cas d'échec
            $table->string('ip_address', 45)->nullable(); // IP de l'expéditeur pour rate limiting
            $table->unsignedBigInteger('user_id')->nullable(); // ID de l'utilisateur (si applicable)
            $table->unsignedBigInteger('reservation_id')->nullable(); // ID de la réservation liée
            $table->timestamp('sent_at')->nullable(); // Date d'envoi réel
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('destinataire');
            $table->index('statut');
            $table->index('created_at');
            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'created_at']);
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
