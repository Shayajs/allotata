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
        // Drop if exists to be clean (since we are iterating)
        Schema::dropIfExists('service_option_choices');
        Schema::dropIfExists('service_options');

        Schema::create('service_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_service_id')->constrained('types_services')->onDelete('cascade');
            $table->string('nom');
            $table->string('type')->default('choix_unique'); // choix_unique, choix_multiple
            $table->boolean('obligatoire')->default(false);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('service_option_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_option_id')->constrained('service_options')->onDelete('cascade');
            $table->string('nom');
            $table->decimal('prix_supplementaire', 10, 2)->default(0);
            $table->integer('temps_supplementaire')->default(0);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_option_choices');
        Schema::dropIfExists('service_options');
    }
};
