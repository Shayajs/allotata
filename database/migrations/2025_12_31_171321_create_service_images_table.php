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
        Schema::create('service_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_service_id')->constrained('types_services')->onDelete('cascade');
            $table->string('image_path'); // Chemin de l'image dans storage/app/public/services/
            $table->boolean('est_couverture')->default(false); // Image de couverture du service
            $table->integer('ordre')->default(0); // Ordre d'affichage
            $table->timestamps();
            
            $table->index('type_service_id');
            $table->index('est_couverture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_images');
    }
};
