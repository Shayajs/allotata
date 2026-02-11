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
        Schema::create('site_web_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('slug');
            $table->string('type')->default('custom'); // custom, reservation, agenda, contact, services
            $table->json('blocs')->nullable(); // null pour les types systeme
            $table->integer('ordre')->default(0);
            $table->boolean('est_actif')->default(true);
            $table->string('icone')->nullable();
            $table->timestamps();

            $table->unique(['entreprise_id', 'slug']);
            $table->index(['entreprise_id', 'est_actif', 'ordre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_web_pages');
    }
};
