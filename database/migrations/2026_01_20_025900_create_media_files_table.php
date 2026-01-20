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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom du fichier (peut être renommé)
            $table->string('original_name'); // Nom original du fichier uploadé
            $table->string('path'); // Chemin relatif dans storage/app/public/media/
            $table->string('folder_path')->nullable(); // Chemin du dossier (ex: "images/2024", "videos/promo")
            $table->string('type')->nullable(); // Type général: image, video, audio, document, etc.
            $table->string('mime_type')->nullable(); // MIME type exact
            $table->unsignedBigInteger('size')->nullable(); // Taille en octets
            $table->unsignedInteger('width')->nullable(); // Largeur (pour images/videos)
            $table->unsignedInteger('height')->nullable(); // Hauteur (pour images/videos)
            $table->text('description')->nullable(); // Description optionnelle
            $table->string('alt_text')->nullable(); // Texte alternatif (pour images)
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('folder_path');
            $table->index('type');
            $table->index('uploaded_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
