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
        Schema::create('membre_statistiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membre_id')->constrained('entreprise_membres')->onDelete('cascade');
            $table->date('date');
            $table->integer('nombre_reservations')->default(0);
            $table->decimal('revenu_total', 10, 2)->default(0);
            $table->integer('duree_totale_minutes')->default(0);
            $table->timestamps();
            
            $table->unique(['membre_id', 'date']);
            $table->index(['membre_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membre_statistiques');
    }
};
