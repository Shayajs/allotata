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
        Schema::create('site_web_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->onDelete('cascade');
            $table->json('contenu')->comment('Snapshot du contenu du site web');
            $table->integer('version_number')->default(1);
            $table->string('description')->nullable()->comment('Description optionnelle de la version');
            $table->boolean('is_auto_save')->default(true)->comment('True si sauvegarde automatique, false si manuelle');
            $table->timestamps();
            
            $table->index(['entreprise_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_web_versions');
    }
};
