<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // facture | devis
            $table->unsignedSmallInteger('annee');
            $table->unsignedInteger('dernier_numero')->default(0);
            $table->timestamps();

            $table->unique(['entreprise_id', 'type', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
