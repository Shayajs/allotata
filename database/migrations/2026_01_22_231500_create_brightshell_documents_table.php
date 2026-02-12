<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brightshell_documents', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('type'); // attestation, courrier, autre
            $table->unsignedBigInteger('client_id')->nullable(); // Optionnel : document lié à un client
            $table->text('contenu'); // Contenu HTML ou JSON
            $table->string('destinataire_nom')->nullable();
            $table->string('destinataire_adresse')->nullable();
            $table->date('date_document');
            $table->string('lieu')->default('Vichy');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brightshell_documents');
    }
};
