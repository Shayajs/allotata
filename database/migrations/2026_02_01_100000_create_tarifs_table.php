<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->unique(); // default, site_web, multi_personnes
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('eur');
            $table->string('label')->nullable();
            $table->timestamps();
        });

        \DB::table('tarifs')->insert([
            ['type' => 'default', 'amount' => 14, 'currency' => 'eur', 'label' => 'Abonnement Premium', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'site_web', 'amount' => 2, 'currency' => 'eur', 'label' => 'Site Web Vitrine', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'multi_personnes', 'amount' => 20, 'currency' => 'eur', 'label' => 'Gestion Multi-Personnes', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifs');
    }
};
