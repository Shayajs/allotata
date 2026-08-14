<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropForeign(['entreprise_id']);
        });

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropUnique(['entreprise_id', 'type', 'annee']);
        });

        DB::statement('ALTER TABLE document_sequences MODIFY entreprise_id BIGINT UNSIGNED NULL');

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->string('cle', 64)->nullable()->after('annee');
        });

        $rows = DB::table('document_sequences')->get();
        foreach ($rows as $row) {
            DB::table('document_sequences')->where('id', $row->id)->update([
                'cle' => ($row->entreprise_id ?? 'p').'|'.$row->type.'|'.$row->annee,
            ]);
        }

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->unique('cle');
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropForeign(['entreprise_id']);
            $table->dropUnique(['cle']);
            $table->dropColumn('cle');
        });

        DB::statement('ALTER TABLE document_sequences MODIFY entreprise_id BIGINT UNSIGNED NOT NULL');

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->cascadeOnDelete();
            $table->unique(['entreprise_id', 'type', 'annee']);
        });
    }
};
