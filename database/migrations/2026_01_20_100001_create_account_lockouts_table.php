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
        Schema::create('account_lockouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_failed_attempt')->nullable();
            $table->string('locking_ip_address', 45)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('user_id');
            $table->index('is_locked');
            $table->index('locked_until');
            $table->unique('user_id');
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_lockouts');
    }
};
