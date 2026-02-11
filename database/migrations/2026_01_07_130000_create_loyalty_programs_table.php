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
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->integer('total_points_earned')->default(0);
            $table->integer('total_points_spent')->default(0);
            $table->integer('level')->default(1); // 1 = Bronze, 2 = Silver, 3 = Gold, 4 = Platinum, 5 = VIP
            $table->json('badges')->nullable();
            $table->timestamps();
            
            $table->unique(['entreprise_id', 'user_id']);
            $table->index(['entreprise_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
