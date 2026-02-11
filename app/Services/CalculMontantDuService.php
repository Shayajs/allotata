<?php

namespace App\Services;

use App\Models\CustomPrice;
use App\Models\Echeance;
use App\Models\PromoCode;
use App\Models\Tarif;
use App\Models\User;
use Carbon\Carbon;

class CalculMontantDuService
{
    /**
     * Calcule le montant dû pour une échéance (scope: default seul, ou une option entreprise).
     *
     * @return array{montant_du: float, montant_final: float, reduction_promo: float, lignes: array, promo_code_id: int|null}
     */
    /**
     * @param bool $isNewSubscription Quand true, inclut le tarif même si l'abonnement n'existe pas encore (checkout initial).
     */
    public static function calculerPourEcheance(Echeance $echeance, ?string $codePromo = null, bool $isNewSubscription = false): array
    {
        $user = $echeance->user;
        $debut = $echeance->periode_debut;
        $fin = $echeance->periode_fin;
        $scopeType = $echeance->subscription_type ?? Echeance::TYPE_DEFAULT;
        $scopeEnt = $echeance->entreprise_id;

        $lignes = [];
        $montantDu = 0.0;

        if ($scopeType === Echeance::TYPE_DEFAULT && !$scopeEnt) {
            $tarif = self::tarifPourUser($user, 'default');
            $lignes[] = [
                'label' => 'Abonnement Premium',
                'type' => 'default',
                'quantite' => 1,
                'montant_unitaire' => $tarif,
                'montant' => $tarif,
            ];
            $montantDu += $tarif;
        } elseif ($scopeEnt) {
            $entreprise = $user->entreprises()->find($scopeEnt) ?? \App\Models\Entreprise::find($scopeEnt);
            if (!$entreprise) {
                return [
                    'montant_du' => 0,
                    'montant_final' => 0,
                    'reduction_promo' => 0,
                    'lignes' => [],
                    'promo_code_id' => null,
                ];
            }
            if ($scopeType === Echeance::TYPE_SITE_WEB) {
                $sub = $entreprise->abonnements()->where('type', 'site_web')->first();
                if ($isNewSubscription || ($sub && $sub->estActif())) {
                    $tarif = self::tarifPourEntreprise($entreprise, 'site_web');
                    $lignes[] = [
                        'label' => 'Site Web – ' . $entreprise->nom,
                        'type' => 'site_web',
                        'entreprise_id' => $entreprise->id,
                        'quantite' => 1,
                        'montant_unitaire' => $tarif,
                        'montant' => $tarif,
                    ];
                    $montantDu += $tarif;
                }
            } elseif ($scopeType === Echeance::TYPE_MULTI_PERSONNES) {
                $sub = $entreprise->abonnements()->where('type', 'multi_personnes')->first();
                if ($isNewSubscription || ($sub && $sub->estActif())) {
                    $tarif = self::tarifPourEntreprise($entreprise, 'multi_personnes');
                    $lignes[] = [
                        'label' => 'Multi-Personnes – ' . $entreprise->nom,
                        'type' => 'multi_personnes',
                        'entreprise_id' => $entreprise->id,
                        'quantite' => 1,
                        'montant_unitaire' => $tarif,
                        'montant' => $tarif,
                    ];
                    $montantDu += $tarif;
                }
            }
        }

        $reductionPromo = 0.0;
        $promoCodeId = null;
        $promo = $codePromo ? PromoCode::validateCode($codePromo, $user) : null;
        if ($promo) {
            $reductionPromo = $promo->calculateDiscount((float) $montantDu);
            $promoCodeId = $promo->id;
        }

        $reductionManuel = (float) ($echeance->reduction_manuel ?? 0);
        $montantFinal = max(0, $montantDu - $reductionPromo - $reductionManuel);

        return [
            'montant_du' => round($montantDu, 2),
            'montant_final' => round($montantFinal, 2),
            'reduction_promo' => round($reductionPromo, 2),
            'lignes' => $lignes,
            'promo_code_id' => $promoCodeId,
        ];
    }

    /**
     * Calcule le montant dû pour un user sur une période (toutes lignes: Premium + options).
     * Utilisé pour les échéances "groupées" ou le recap.
     *
     * @return array{montant_du: float, montant_final: float, reduction_promo: float, lignes: array, promo_code_id: int|null}
     */
    public static function calculerPourUser(User $user, Carbon $debut, Carbon $fin, ?string $codePromo = null): array
    {
        $lignes = [];
        $montantDu = 0.0;

        $tarifDefault = self::tarifPourUser($user, 'default');
        $lignes[] = [
            'label' => 'Abonnement Premium',
            'type' => 'default',
            'quantite' => 1,
            'montant_unitaire' => $tarifDefault,
            'montant' => $tarifDefault,
        ];
        $montantDu += $tarifDefault;

        foreach ($user->entreprises as $entreprise) {
            $subSiteWeb = $entreprise->abonnements()->where('type', 'site_web')->first();
            if ($subSiteWeb && $subSiteWeb->estActif()) {
                $tarif = self::tarifPourEntreprise($entreprise, 'site_web');
                $lignes[] = [
                    'label' => 'Site Web – ' . $entreprise->nom,
                    'type' => 'site_web',
                    'entreprise_id' => $entreprise->id,
                    'quantite' => 1,
                    'montant_unitaire' => $tarif,
                    'montant' => $tarif,
                ];
                $montantDu += $tarif;
            }

            $subMulti = $entreprise->abonnements()->where('type', 'multi_personnes')->first();
            if ($subMulti && $subMulti->estActif()) {
                $tarif = self::tarifPourEntreprise($entreprise, 'multi_personnes');
                $lignes[] = [
                    'label' => 'Multi-Personnes – ' . $entreprise->nom,
                    'type' => 'multi_personnes',
                    'entreprise_id' => $entreprise->id,
                    'quantite' => 1,
                    'montant_unitaire' => $tarif,
                    'montant' => $tarif,
                ];
                $montantDu += $tarif;
            }
        }

        $reductionPromo = 0.0;
        $promoCodeId = null;
        $promo = $codePromo ? PromoCode::validateCode($codePromo, $user) : null;
        if ($promo) {
            $reductionPromo = $promo->calculateDiscount((float) $montantDu);
            $promoCodeId = $promo->id;
        }

        $montantFinal = max(0, $montantDu - $reductionPromo);

        return [
            'montant_du' => round($montantDu, 2),
            'montant_final' => round($montantFinal, 2),
            'reduction_promo' => round($reductionPromo, 2),
            'lignes' => $lignes,
            'promo_code_id' => $promoCodeId,
        ];
    }

    protected static function tarifPourUser(User $user, string $type): float
    {
        $custom = CustomPrice::getForUser($user, $type);
        if ($custom && $custom->isValid()) {
            return (float) $custom->amount;
        }
        return Tarif::getAmount($type === 'default' ? 'default' : $type);
    }

    protected static function tarifPourEntreprise(\App\Models\Entreprise $entreprise, string $type): float
    {
        $custom = CustomPrice::getForEntreprise($entreprise, $type);
        if ($custom && $custom->isValid()) {
            return (float) $custom->amount;
        }
        return Tarif::getAmount($type);
    }
}
