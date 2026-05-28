<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\EntrepriseSubscription;
use App\Models\Facture;
use Carbon\Carbon;

class GenerateManualSubscriptionInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:generate-invoices {--force : Forcer la génération même si une facture existe déjà}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère automatiquement les factures pour les abonnements manuels selon leur jour de renouvellement';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📄 Génération des factures d\'abonnements manuels...');
        $force = $this->option('force');
        $jourActuel = now()->day;
        $dernierJourDuMois = now()->daysInMonth;
        $estDernierJour = $jourActuel === $dernierJourDuMois;
        $dateActuelle = now();

        $facturesGenerees = 0;
        $erreurs = 0;

        // Générer les factures pour les abonnements manuels utilisateurs
        $usersAvecAbonnementManuel = User::where('abonnement_manuel', true)
            ->whereNotNull('abonnement_manuel_type_renouvellement')
            ->whereNotNull('abonnement_manuel_jour_renouvellement')
            ->whereNotNull('abonnement_manuel_montant')
            ->where('abonnement_manuel_actif_jusqu', '>=', now())
            ->get();

        $this->info("Trouvé {$usersAvecAbonnementManuel->count()} utilisateurs avec abonnement manuel");

        foreach ($usersAvecAbonnementManuel as $user) {
            $billingDay = (int) $user->abonnement_manuel_jour_renouvellement;
            if (! $force && ! $this->shouldRunForDay($billingDay, $jourActuel, $dernierJourDuMois, $estDernierJour)) {
                continue;
            }

            try {
                    // Déterminer la date de facture
                    $dateFacture = $dateActuelle->copy();
                    
                    // Si c'est mensuel, on génère pour le mois en cours
                    // Si c'est annuel, on génère pour l'année en cours
                    if ($user->abonnement_manuel_type_renouvellement === 'mensuel') {
                        $periodeDebut = $dateFacture->copy()->startOfMonth();
                        $periodeFin = $dateFacture->copy()->endOfMonth();
                    } else {
                        $periodeDebut = $dateFacture->copy()->startOfYear();
                        $periodeFin = $dateFacture->copy()->endOfYear();
                    }

                    // Vérifier si une facture existe déjà pour cette période
                    if (!$force) {
                        $factureExistante = Facture::where('user_id', $user->id)
                            ->where('type_facture', 'abonnement_manuel')
                            ->whereBetween('date_facture', [$periodeDebut, $periodeFin])
                            ->first();

                        if ($factureExistante) {
                            $this->line("  ⏭ Facture déjà existante pour {$user->name} (période: {$periodeDebut->format('d/m/Y')} - {$periodeFin->format('d/m/Y')})");
                            continue;
                        }
                    }

                    // Générer la facture
                    $facture = Facture::generateFromManualSubscription($user, $dateFacture);
                    
                    if ($facture) {
                        $facturesGenerees++;
                        $this->info("  ✓ Facture générée pour {$user->name} - {$facture->numero_facture} ({$facture->montant_ttc}€)");
                    }
            } catch (\Exception $e) {
                $erreurs++;
                $this->error("  ✗ Erreur pour {$user->name}: " . $e->getMessage());
            }
        }

        // Générer les factures pour les abonnements manuels entreprises
        $entrepriseSubscriptions = EntrepriseSubscription::where('est_manuel', true)
            ->whereNotNull('type_renouvellement')
            ->whereNotNull('jour_renouvellement')
            ->whereNotNull('montant')
            ->where(function($query) {
                $query->whereNull('actif_jusqu')
                      ->orWhere('actif_jusqu', '>=', now());
            })
            ->with('entreprise')
            ->get();

        $this->info("Trouvé {$entrepriseSubscriptions->count()} abonnements manuels entreprises");

        foreach ($entrepriseSubscriptions as $subscription) {
            $billingDay = (int) $subscription->jour_renouvellement;
            if (! $force && ! $this->shouldRunForDay($billingDay, $jourActuel, $dernierJourDuMois, $estDernierJour)) {
                continue;
            }

            try {
                    // Déterminer la date de facture
                    $dateFacture = $dateActuelle->copy();
                    
                    // Si c'est mensuel, on génère pour le mois en cours
                    // Si c'est annuel, on génère pour l'année en cours
                    if ($subscription->type_renouvellement === 'mensuel') {
                        $periodeDebut = $dateFacture->copy()->startOfMonth();
                        $periodeFin = $dateFacture->copy()->endOfMonth();
                    } else {
                        $periodeDebut = $dateFacture->copy()->startOfYear();
                        $periodeFin = $dateFacture->copy()->endOfYear();
                    }

                    // Vérifier si une facture existe déjà pour cette période
                    if (!$force) {
                        $factureExistante = Facture::where('entreprise_subscription_id', $subscription->id)
                            ->where('type_facture', 'abonnement_entreprise')
                            ->whereBetween('date_facture', [$periodeDebut, $periodeFin])
                            ->first();

                        if ($factureExistante) {
                            $this->line("  ⏭ Facture déjà existante pour {$subscription->entreprise->nom} - {$subscription->type} (période: {$periodeDebut->format('d/m/Y')} - {$periodeFin->format('d/m/Y')})");
                            continue;
                        }
                    }

                    // Générer la facture
                    $facture = Facture::generateFromManualEntrepriseSubscription($subscription, $dateFacture);
                    
                    if ($facture) {
                        $facturesGenerees++;
                        $this->info("  ✓ Facture générée pour {$subscription->entreprise->nom} - {$subscription->type} - {$facture->numero_facture} ({$facture->montant_ttc}€)");
                    }
            } catch (\Exception $e) {
                $erreurs++;
                $this->error("  ✗ Erreur pour {$subscription->entreprise->nom} - {$subscription->type}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✅ Génération terminée !");
        $this->info("  - Factures générées: {$facturesGenerees}");
        if ($erreurs > 0) {
            $this->warn("  - Erreurs: {$erreurs}");
        }

        return 0;
    }

    private function shouldRunForDay(int $billingDay, int $today, int $daysInMonth, bool $isMonthEnd): bool
    {
        if ($billingDay === $today) {
            return true;
        }

        return $isMonthEnd && $billingDay > $daysInMonth;
    }
}
