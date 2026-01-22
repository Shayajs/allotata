<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Renommer la table des documents générés en brightshell_legals
        if (Schema::hasTable('brightshell_documents') && !Schema::hasTable('brightshell_legals')) {
            Schema::rename('brightshell_documents', 'brightshell_legals');
        }

        // 2. Créer la table pour les fichiers uploadés (Documents)
        if (!Schema::hasTable('brightshell_documents')) {
            Schema::create('brightshell_documents', function (Blueprint $table) {
                $table->id();
                $table->string('nom');
                $table->string('fichier');
                $table->string('extension')->nullable();
                $table->bigInteger('taille')->default(0);
                $table->string('categorie')->default('autre');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->timestamps();
            });
        }
        
        // 3. Ajouter le champ signature dans les paramètres de l'entreprise si nécessaire
        // (Géré via le fichier de config ou une table si elle existe, 
        // ici nous utiliserons le dossier media/brightshell/signature.png)
    }

    public function down(): void
    {
        Schema::dropIfExists('brightshell_documents');
        if (Schema::hasTable('brightshell_legals')) {
            Schema::rename('brightshell_legals', 'brightshell_documents');
        }
    }
};
