<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brightshell_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        
        // Insérer les couleurs par défaut
        \DB::table('brightshell_settings')->insert([
            ['key' => 'pdf_color_primary', 'value' => '#5bbce4', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pdf_color_secondary', 'value' => '#0a0e1a', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pdf_color_text', 'value' => '#1a1a1a', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pdf_color_muted', 'value' => '#6b7280', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pdf_color_background', 'value' => '#f9fafb', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pdf_color_border', 'value' => '#e5e7eb', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'pdf_color_success', 'value' => '#10b981', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('brightshell_settings');
    }
};
