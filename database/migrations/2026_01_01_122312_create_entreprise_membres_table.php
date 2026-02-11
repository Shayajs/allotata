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
        Schema::create('entreprise_membres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('administrateur'); // 'administrateur' ou 'membre'
            $table->boolean('est_actif')->default(true);
            $table->timestamp('invite_at')->nullable(); // Date d'invitation
            $table->timestamp('accepte_at')->nullable(); // Date d'acceptation
            $table->timestamps();

            // Un utilisateur ne peut être membre qu'une seule fois par entreprise
            $table->unique(['entreprise_id', 'user_id']);
            $table->index(['entreprise_id', 'est_actif']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprise_membres');
    }
};
