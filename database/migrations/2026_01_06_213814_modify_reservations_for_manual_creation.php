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
        Schema::table('reservations', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte foreign key
            $table->dropForeign(['user_id']);
            
            // Rendre user_id nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Recréer la contrainte avec nullOnDelete
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            
            // Ajouter les colonnes pour les clientes non inscrites
            $table->string('nom_client')->nullable()->after('user_id');
            $table->string('email_client')->nullable()->after('nom_client');
            $table->string('telephone_client_non_inscrit')->nullable()->after('email_client');
            $table->boolean('creee_manuellement')->default(false)->after('telephone_client_non_inscrit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Supprimer les nouvelles colonnes
            $table->dropColumn(['nom_client', 'email_client', 'telephone_client_non_inscrit', 'creee_manuellement']);
            
            // Supprimer la contrainte foreign key
            $table->dropForeign(['user_id']);
            
            // Remettre user_id en non-nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Recréer l'ancienne contrainte
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
