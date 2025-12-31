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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // 'reservation', 'paiement', 'rappel', 'systeme', etc.
            $table->string('titre');
            $table->text('message');
            $table->string('lien')->nullable(); // Lien vers la ressource concernée
            $table->boolean('est_lue')->default(false);
            $table->timestamp('lue_at')->nullable();
            $table->json('donnees')->nullable(); // Données supplémentaires (JSON)
            $table->timestamps();
            
            $table->index(['user_id', 'est_lue']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
