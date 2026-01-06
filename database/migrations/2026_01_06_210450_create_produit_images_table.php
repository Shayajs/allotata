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
        Schema::create('produit_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained()->onDelete('cascade');
            $table->string('image_path'); // Chemin de l'image dans storage/app/public/produits/
            $table->boolean('est_couverture')->default(false); // Image de couverture du produit
            $table->integer('ordre')->default(0); // Ordre d'affichage
            $table->timestamps();
            
            $table->index('produit_id');
            $table->index('est_couverture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produit_images');
    }
};
