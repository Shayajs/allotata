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
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            
            // Identité (Obligatoire)
            $table->string('nom');
            $table->string('slug')->unique();
            $table->string('type_activite'); // Coiffeuse, Cuisinière...
            
            // Légal (Facultatif car "en cours" possible)
            $table->string('siren', 9)->nullable();
            $table->string('status_juridique')->default('en_cours'); // auto-entrepreneur, etc.
            $table->boolean('est_verifiee')->default(false);

            // Contact & Services
            $table->string('email')->unique();
            $table->string('telephone')->nullable();
            $table->text('description')->nullable();
            
            // Localisation
            $table->string('ville')->nullable();
            $table->integer('rayon_deplacement')->default(0); // 0 = fixe, >0 = mobile

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
