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
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->json('contenu_blocks_json')->nullable()->after('contenu_rich_html');
            $table->boolean('is_draft')->default(true)->after('est_actif');
            $table->timestamp('published_at')->nullable()->after('is_draft');
            
            $table->index('is_draft');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropIndex(['is_draft']);
            $table->dropIndex(['published_at']);
            $table->dropColumn(['contenu_blocks_json', 'is_draft', 'published_at']);
        });
    }
};
