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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_program_id')->constrained()->onDelete('cascade');
            $table->integer('points'); // Positif pour earned, négatif pour spent
            $table->enum('type', ['earned', 'spent']);
            $table->string('reason')->nullable();
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index(['loyalty_program_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
