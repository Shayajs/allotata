<?php

namespace App\Services;

use App\Models\Entreprise;

/**
 * Service de calcul fiscal pour les micro-entreprises en France
 * 
 * Gère le calcul de l'impôt sur le revenu selon deux régimes :
 * 1. Régime classique avec abattement forfaitaire et barème progressif
 * 2. Prélèvement libératoire (versement forfaitaire)
 * 
 * Barème 2024 (revenus déclarés en 2025)
 */
class FiscalCalculatorService
{
    // Barème progressif de l'impôt sur le revenu 2024
    const TRANCHES_IR_2024 = [
        ['min' => 0, 'max' => 11294, 'taux' => 0],
        ['min' => 11295, 'max' => 28797, 'taux' => 0.11],
        ['min' => 28798, 'max' => 82341, 'taux' => 0.30],
        ['min' => 82342, 'max' => 177106, 'taux' => 0.41],
        ['min' => 177107, 'max' => PHP_INT_MAX, 'taux' => 0.45],
    ];

    // Abattements forfaitaires selon le type d'activité
    const ABATTEMENTS = [
        'vente' => 0.71, // BIC - Vente de marchandises : 71%
        'service_bic' => 0.50, // BIC - Prestations de services : 50%
        'liberal_bnc' => 0.34, // BNC - Professions libérales : 34%
    ];

    // Taux du prélèvement libératoire
    const TAUX_PRELEVEMENT_LIBERATOIRE = [
        'vente' => 0.01, // 1%
        'service_bic' => 0.017, // 1.7%
        'liberal_bnc' => 0.022, // 2.2%
    ];

    // Taux URSSAF par type d'activité (2024)
    const TAUX_URSSAF = [
        'vente' => 0.123, // 12.3%
        'service_bic' => 0.212, // 21.2%
        'liberal_bnc' => 0.211, // 21.1%
    ];

    // Plafonds pour le prélèvement libératoire (RFR N-2 par part)
    const PLAFOND_PL_PAR_PART = 27478; // 2024

    // Décote 2024
    const DECOTE_SEUIL_CELIBATAIRE = 1964;
    const DECOTE_SEUIL_COUPLE = 3248;
    const DECOTE_FORFAIT_CELIBATAIRE = 889;
    const DECOTE_FORFAIT_COUPLE = 1470;
    const DECOTE_TAUX = 0.4525;

    // Plafonnement du quotient familial
    const PLAFOND_DEMI_PART = 1791;
    const PLAFOND_QUART_PART = 880;

    /**
     * Déterminer le type d'activité fiscale à partir du type d'entreprise
     */
    public function determinerTypeActivite(string $typeEntreprise): string
    {
        $type = strtolower($typeEntreprise);
        
        if (str_contains($type, 'vente') || str_contains($type, 'commerce') || str_contains($type, 'achat')) {
            return 'vente';
        }
        
        if (str_contains($type, 'libéral') || str_contains($type, 'liberal') || str_contains($type, 'bnc')) {
            return 'liberal_bnc';
        }
        
        // Par défaut : prestation de services BIC
        return 'service_bic';
    }

    /**
     * Calculer le nombre de parts fiscales
     */
    public function calculerNombreParts(Entreprise $entreprise): array
    {
        $situation = $entreprise->fiscal_situation_familiale ?? 'celibataire';
        $nbEnfants = $entreprise->fiscal_nombre_enfants ?? 0;
        $gardeAlternee = $entreprise->fiscal_enfants_garde_alternee ?? 0;
        $parentIsole = $entreprise->fiscal_parent_isole ?? false;
        $invaliditeContribuable = $entreprise->fiscal_invalidite_contribuable ?? false;
        $invaliditeConjoint = $entreprise->fiscal_invalidite_conjoint ?? false;
        $ancienCombattant = $entreprise->fiscal_ancien_combattant ?? false;

        // Parts de base selon situation
        $partsBase = 1;
        if (in_array($situation, ['marie', 'pacse'])) {
            $partsBase = 2;
        }

        $details = [
            'base' => $partsBase,
            'enfants' => 0,
            'garde_alternee' => 0,
            'parent_isole' => 0,
            'invalidite' => 0,
            'ancien_combattant' => 0,
        ];

        // Parts pour enfants (hors garde alternée)
        $enfantsPleinePart = $nbEnfants - $gardeAlternee;
        if ($enfantsPleinePart > 0) {
            if ($enfantsPleinePart >= 3) {
                // 2 premiers = 0.5 chacun, à partir du 3ème = 1 chacun
                $details['enfants'] = 0.5 + 0.5 + ($enfantsPleinePart - 2);
            } else {
                $details['enfants'] = $enfantsPleinePart * 0.5;
            }
        }

        // Quarts de parts pour garde alternée
        if ($gardeAlternee > 0) {
            $details['garde_alternee'] = $gardeAlternee * 0.25;
        }

        // Demi-part parent isolé
        if ($parentIsole && $nbEnfants > 0) {
            $details['parent_isole'] = 0.5;
        }

        // Demi-parts pour invalidité
        if ($invaliditeContribuable) {
            $details['invalidite'] += 0.5;
        }
        if ($invaliditeConjoint && in_array($situation, ['marie', 'pacse'])) {
            $details['invalidite'] += 0.5;
        }

        // Demi-part ancien combattant (>74 ans)
        if ($ancienCombattant) {
            $details['ancien_combattant'] = 0.5;
        }

        $total = $partsBase 
            + $details['enfants'] 
            + $details['garde_alternee'] 
            + $details['parent_isole'] 
            + $details['invalidite'] 
            + $details['ancien_combattant'];

        return [
            'total' => $total,
            'details' => $details,
        ];
    }

    /**
     * Calculer l'abattement forfaitaire
     */
    public function calculerAbattement(float $ca, string $typeActivite): array
    {
        $tauxAbattement = self::ABATTEMENTS[$typeActivite] ?? 0.50;
        $abattement = $ca * $tauxAbattement;
        
        // Abattement minimum de 305€
        if ($abattement < 305 && $ca > 0) {
            $abattement = min(305, $ca);
        }

        return [
            'taux' => $tauxAbattement * 100,
            'montant' => $abattement,
            'revenu_imposable' => max(0, $ca - $abattement),
        ];
    }

    /**
     * Calculer l'impôt selon le barème progressif
     */
    public function calculerImpotBaremeProgressif(float $revenuImposable, float $nombreParts): array
    {
        // Quotient familial
        $quotient = $revenuImposable / $nombreParts;
        
        $impotParPart = 0;
        $detailTranches = [];

        foreach (self::TRANCHES_IR_2024 as $tranche) {
            if ($quotient > $tranche['min']) {
                $partImposable = min($quotient, $tranche['max']) - $tranche['min'];
                if ($partImposable > 0) {
                    $impotTranche = $partImposable * $tranche['taux'];
                    $impotParPart += $impotTranche;
                    
                    $detailTranches[] = [
                        'tranche' => $tranche['min'] . '€ - ' . ($tranche['max'] == PHP_INT_MAX ? '∞' : number_format($tranche['max'], 0, ',', ' ') . '€'),
                        'taux' => $tranche['taux'] * 100,
                        'base' => $partImposable,
                        'impot' => $impotTranche,
                    ];
                }
            }
        }

        $impotBrut = $impotParPart * $nombreParts;

        return [
            'quotient' => $quotient,
            'impot_par_part' => $impotParPart,
            'impot_brut' => $impotBrut,
            'tranches' => $detailTranches,
        ];
    }

    /**
     * Appliquer la décote si éligible
     */
    public function calculerDecote(float $impotBrut, string $situation): array
    {
        $estCouple = in_array($situation, ['marie', 'pacse']);
        $seuil = $estCouple ? self::DECOTE_SEUIL_COUPLE : self::DECOTE_SEUIL_CELIBATAIRE;
        $forfait = $estCouple ? self::DECOTE_FORFAIT_COUPLE : self::DECOTE_FORFAIT_CELIBATAIRE;

        if ($impotBrut <= 0 || $impotBrut > $seuil) {
            return [
                'eligible' => false,
                'montant' => 0,
                'impot_apres_decote' => $impotBrut,
            ];
        }

        $decote = $forfait - ($impotBrut * self::DECOTE_TAUX);
        $decote = max(0, $decote);

        return [
            'eligible' => true,
            'montant' => $decote,
            'impot_apres_decote' => max(0, $impotBrut - $decote),
        ];
    }

    /**
     * Vérifier l'éligibilité au prélèvement libératoire
     */
    public function verifierEligibilitePL(Entreprise $entreprise): array
    {
        $rfr = $entreprise->fiscal_revenu_fiscal_reference ?? 0;
        $parts = $this->calculerNombreParts($entreprise);
        $plafond = self::PLAFOND_PL_PAR_PART * $parts['total'];

        return [
            'eligible' => $rfr <= $plafond,
            'rfr' => $rfr,
            'plafond' => $plafond,
            'message' => $rfr <= $plafond 
                ? "Éligible au prélèvement libératoire (RFR {$rfr}€ ≤ {$plafond}€)"
                : "Non éligible au prélèvement libératoire (RFR {$rfr}€ > {$plafond}€)",
        ];
    }

    /**
     * Calculer le prélèvement libératoire
     */
    public function calculerPrelevementLiberatoire(float $ca, string $typeActivite): array
    {
        $taux = self::TAUX_PRELEVEMENT_LIBERATOIRE[$typeActivite] ?? 0.022;
        $montant = $ca * $taux;

        return [
            'taux' => $taux * 100,
            'montant' => $montant,
        ];
    }

    /**
     * Calculer les cotisations URSSAF
     */
    public function calculerURSSAF(float $ca, string $typeActivite): array
    {
        $taux = self::TAUX_URSSAF[$typeActivite] ?? 0.212;
        $montant = $ca * $taux;

        return [
            'taux' => $taux * 100,
            'montant' => $montant,
        ];
    }

    /**
     * Calcul complet de l'impôt et des charges
     */
    public function calculerTout(Entreprise $entreprise, float $chiffreAffaires): array
    {
        $typeActivite = $this->determinerTypeActivite($entreprise->type_activite ?? 'service');
        $situation = $entreprise->fiscal_situation_familiale ?? 'celibataire';
        $autresRevenus = $entreprise->fiscal_revenus_autres_foyer ?? 0;
        $usePL = $entreprise->fiscal_prelevement_liberatoire ?? false;

        // URSSAF
        $urssaf = $this->calculerURSSAF($chiffreAffaires, $typeActivite);

        // Nombre de parts
        $parts = $this->calculerNombreParts($entreprise);

        // Éligibilité PL
        $eligibilitePL = $this->verifierEligibilitePL($entreprise);

        // Si PL activé et éligible
        if ($usePL && $eligibilitePL['eligible']) {
            $pl = $this->calculerPrelevementLiberatoire($chiffreAffaires, $typeActivite);
            
            return [
                'regime' => 'prelevement_liberatoire',
                'type_activite' => $typeActivite,
                'chiffre_affaires' => $chiffreAffaires,
                'urssaf' => $urssaf,
                'impot' => [
                    'methode' => 'Prélèvement Libératoire',
                    'taux' => $pl['taux'],
                    'montant' => $pl['montant'],
                ],
                'total_charges' => $urssaf['montant'] + $pl['montant'],
                'taux_global' => (($urssaf['montant'] + $pl['montant']) / $chiffreAffaires) * 100,
                'net_apres_charges' => $chiffreAffaires - $urssaf['montant'] - $pl['montant'],
                'parts' => $parts,
                'eligibilite_pl' => $eligibilitePL,
            ];
        }

        // Régime classique avec barème progressif
        $abattement = $this->calculerAbattement($chiffreAffaires, $typeActivite);
        
        // Revenu imposable total du foyer (micro + autres revenus)
        $revenuImposableTotal = $abattement['revenu_imposable'] + $autresRevenus;
        
        // Calcul du barème progressif
        $bareme = $this->calculerImpotBaremeProgressif($revenuImposableTotal, $parts['total']);
        
        // Décote
        $decote = $this->calculerDecote($bareme['impot_brut'], $situation);
        
        // Impôt final
        $impotFinal = $decote['impot_apres_decote'];

        // Si cumul avec autres revenus, on calcule la part de l'impôt attribuable à la micro
        $partMicro = $revenuImposableTotal > 0 
            ? $abattement['revenu_imposable'] / $revenuImposableTotal 
            : 1;
        $impotMicro = $impotFinal * $partMicro;

        return [
            'regime' => 'bareme_progressif',
            'type_activite' => $typeActivite,
            'chiffre_affaires' => $chiffreAffaires,
            'urssaf' => $urssaf,
            'abattement' => $abattement,
            'revenus_autres_foyer' => $autresRevenus,
            'revenu_imposable_total' => $revenuImposableTotal,
            'parts' => $parts,
            'bareme' => $bareme,
            'decote' => $decote,
            'impot' => [
                'methode' => 'Barème Progressif',
                'montant_total' => $impotFinal,
                'part_micro' => $impotMicro,
            ],
            'total_charges' => $urssaf['montant'] + $impotMicro,
            'taux_global' => $chiffreAffaires > 0 ? (($urssaf['montant'] + $impotMicro) / $chiffreAffaires) * 100 : 0,
            'net_apres_charges' => $chiffreAffaires - $urssaf['montant'] - $impotMicro,
            'eligibilite_pl' => $eligibilitePL,
        ];
    }
}
