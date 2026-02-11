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
        Schema::create('proposition_rendez_vouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('message_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Client
            $table->foreignId('entreprise_id')->constrained('entreprises')->onDelete('cascade');
            $table->date('date_rdv');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->decimal('prix_propose', 10, 2);
            $table->decimal('prix_final', 10, 2)->nullable(); // Prix après négociation
            $table->string('statut')->default('proposee'); // proposee, negociee, acceptee, refusee, expiree
            $table->text('notes')->nullable();
            $table->string('lieu')->nullable();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['conversation_id', 'statut']);
            $table->index('date_rdv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposition_rendez_vouses');
    }
};
