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
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->dateTime('date_heure');
            $table->integer('duree_minutes');
            $table->string('titre')->nullable(); // Ex: "Cahier des charges", "Validation", "Livraison"
            $table->text('notes')->nullable();
            $table->string('statut')->default('en_attente'); // en_attente, confirmee, terminee, annulee
            $table->foreignId('membre_id')->nullable()->constrained('entreprise_membres')->onDelete('set null');
            $table->string('lieu')->nullable();
            $table->timestamps();
            
            $table->index('reservation_id');
            $table->index('date_heure');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
    }
};
