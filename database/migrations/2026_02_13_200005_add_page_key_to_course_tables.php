<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->string('page_key')->nullable()->index()->after('est_actif');
        });

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->string('page_key')->nullable()->index()->after('est_actif');
        });
    }

    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropColumn('page_key');
        });

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropColumn('page_key');
        });
    }
};
