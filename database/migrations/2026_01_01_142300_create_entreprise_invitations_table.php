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
        Schema::create('entreprise_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->string('email')->index();
            $table->string('role')->default('membre'); // 'administrateur' ou 'membre'
            $table->string('statut')->default('en_attente_compte'); // 'en_attente_compte', 'en_attente_acceptation', 'acceptee', 'refusee'
            $table->string('token')->unique();
            $table->foreignId('invite_par_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamp('accepte_at')->nullable();
            $table->timestamp('refuse_at')->nullable();
            $table->timestamp('expire_at')->nullable(); // Expiration après 30 jours
            $table->timestamps();

            // Index composites
            $table->index(['entreprise_id', 'email']);
            $table->index(['email', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprise_invitations');
    }
};
