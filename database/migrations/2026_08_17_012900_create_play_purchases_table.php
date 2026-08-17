<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entreprise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_id');
            $table->string('grants', 64);
            $table->string('purchase_token')->unique();
            $table->string('order_id')->nullable();
            $table->string('package_name')->default('fr.allotata.app');
            $table->string('kind', 32)->default('subscription');
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'grants', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_purchases');
    }
};
