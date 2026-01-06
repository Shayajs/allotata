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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained()->onDelete('cascade');
            
            $table->decimal('prix_promotion', 10, 2); // Prix en promotion
            $table->dateTime('date_debut'); // Date de début de la promotion
            $table->dateTime('date_fin'); // Date de fin de la promotion
            $table->boolean('est_active')->default(true); // Promotion active/inactive
            
            $table->timestamps();
            
            $table->index('produit_id');
            $table->index('est_active');
            $table->index(['date_debut', 'date_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
