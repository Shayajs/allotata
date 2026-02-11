<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncEcheancesRecettes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brightshell:sync-echeances-recettes {--dry-run : Afficher ce qui sera fait sans l\'exécuter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enregistre rétroactivement les recettes pour les échéances déjà payées qui n\'ont pas de recette associée';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Mode DRY-RUN : Aucune modification ne sera effectuée');
        } else {
            $this->info('🔄 Synchronisation des échéances payées avec les recettes...');
        }
        
        if (!Schema::hasTable('brightshell_echeances')) {
            $this->error('La table brightshell_echeances n\'existe pas.');
            return 1;
        }
        
        if (!Schema::hasTable('brightshell_recettes')) {
            $this->error('La table brightshell_recettes n\'existe pas.');
            return 1;
        }
        
        // Récupérer toutes les échéances payées
        $echeancesPayees = DB::table('brightshell_echeances')
            ->where('est_payee', true)
            ->whereNotNull('date_paiement')
            ->get();
        
        $this->info("Trouvé {$echeancesPayees->count()} échéance(s) payée(s)");
        
        $recettesCreees = 0;
        $recettesExistantes = 0;
        $erreurs = 0;
        
        foreach ($echeancesPayees as $echeance) {
            // Vérifier si une recette existe déjà pour cette échéance
            $recetteExistante = DB::table('brightshell_recettes')
                ->where('facture_id', $echeance->facture_id)
                ->where('reference', 'LIKE', '%(' . $echeance->numero . ')%')
                ->first();
            
            if ($recetteExistante) {
                $recettesExistantes++;
                $this->line("  ⏭ Échéance {$echeance->numero} de la facture #{$echeance->facture_id} : recette déjà existante");
                continue;
            }
            
            // Récupérer la facture
            $facture = DB::table('brightshell_factures')
                ->leftJoin('brightshell_clients', 'brightshell_factures.client_id', '=', 'brightshell_clients.id')
                ->select('brightshell_factures.*', 'brightshell_clients.nom as client_nom', 'brightshell_clients.societe as client_societe')
                ->where('brightshell_factures.id', $echeance->facture_id)
                ->first();
            
            if (!$facture) {
                $erreurs++;
                $this->error("  ✗ Facture #{$echeance->facture_id} introuvable pour l'échéance {$echeance->numero}");
                continue;
            }
            
            $datePaiement = $echeance->date_paiement ?? now()->format('Y-m-d');
            $montant = $echeance->montant;
            $modePaiement = $echeance->mode_paiement ?? 'virement';
            
            if ($dryRun) {
                $this->info("  📝 Créerait recette : Facture {$facture->numero} - Échéance {$echeance->numero} - {$montant}€ - {$datePaiement}");
            } else {
                try {
                    DB::table('brightshell_recettes')->insert([
                        'date' => $datePaiement,
                        'reference' => $facture->numero . ' (' . $echeance->numero . ')',
                        'client_id' => $facture->client_id,
                        'client_nom' => $facture->client_societe ?? $facture->client_nom ?? 'Client',
                        'nature' => $facture->objet . ' (échéance ' . $echeance->numero . ')',
                        'montant' => $montant,
                        'mode_reglement' => $modePaiement,
                        'facture_id' => $echeance->facture_id,
                        'created_at' => $datePaiement . ' ' . now()->format('H:i:s'),
                        'updated_at' => now(),
                    ]);
                    
                    // Mettre à jour la trésorerie
                    if (Schema::hasTable('brightshell_tresorerie')) {
                        $tresorerie = DB::table('brightshell_tresorerie')->first();
                        if ($tresorerie) {
                            DB::table('brightshell_tresorerie')->where('id', $tresorerie->id)->update([
                                'solde_courant' => $tresorerie->solde_courant + $montant,
                                'updated_at' => now()
                            ]);
                        }
                    }
                    
                    $recettesCreees++;
                    $this->info("  ✓ Recette créée : Facture {$facture->numero} - Échéance {$echeance->numero} - {$montant}€");
                } catch (\Exception $e) {
                    $erreurs++;
                    $this->error("  ✗ Erreur pour l'échéance {$echeance->numero} : " . $e->getMessage());
                }
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("📊 Résumé (DRY-RUN) :");
            $this->info("  - Recettes à créer : {$recettesCreees}");
            $this->info("  - Recettes déjà existantes : {$recettesExistantes}");
            $this->info("  - Erreurs : {$erreurs}");
            $this->newLine();
            $this->comment("Exécutez la commande sans --dry-run pour créer les recettes.");
        } else {
            $this->info("✅ Synchronisation terminée :");
            $this->info("  - Recettes créées : {$recettesCreees}");
            $this->info("  - Recettes déjà existantes : {$recettesExistantes}");
            $this->info("  - Erreurs : {$erreurs}");
        }
        
        return 0;
    }
}
