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
        Schema::create('entreprise_finances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->onDelete('cascade');
            $table->enum('type', ['income', 'expense']);
            $table->string('category')->nullable(); // e.g., 'Vente', 'Prestation', 'Loyer', etc.
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->date('date_record');
            $table->json('metadata')->nullable(); // For storing extra info like tax rates
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprise_finances');
    }
};
