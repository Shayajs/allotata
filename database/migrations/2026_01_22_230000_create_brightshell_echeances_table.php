<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des échéances de paiement (paiement en plusieurs fois)
        Schema::create('brightshell_echeances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('brightshell_factures')->onDelete('cascade');
            $table->integer('numero'); // 1, 2, 3...
            $table->date('date_echeance'); // Date à laquelle le paiement est attendu
            $table->decimal('montant', 10, 2); // Montant de cette échéance
            $table->boolean('est_payee')->default(false);
            $table->date('date_paiement')->nullable(); // Date réelle du paiement
            $table->string('mode_paiement')->nullable(); // virement, chèque, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['facture_id', 'date_echeance']);
            $table->index('est_payee');
        });
        
        // Ajouter un champ pour indiquer si la facture a des facilités de paiement
        Schema::table('brightshell_factures', function (Blueprint $table) {
            $table->boolean('paiement_echelonne')->default(false)->after('mode_paiement');
            $table->integer('nombre_echeances')->nullable()->after('paiement_echelonne');
        });
    }

    public function down(): void
    {
        Schema::table('brightshell_factures', function (Blueprint $table) {
            $table->dropColumn(['paiement_echelonne', 'nombre_echeances']);
        });
        
        Schema::dropIfExists('brightshell_echeances');
    }
};
