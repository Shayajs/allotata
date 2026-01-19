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
        Schema::create('user_ip_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ip_address', 45);
            $table->string('location')->nullable(); // Pays/Ville
            $table->string('country_code', 2)->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->integer('login_count')->default(1);
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('user_id');
            $table->index('ip_address');
            $table->index(['user_id', 'ip_address']);
            $table->index('last_seen_at');
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ip_history');
    }
};
