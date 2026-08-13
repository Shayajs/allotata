<?php

namespace App\Services\Facturation;

use App\Models\Entreprise;

class BillingProfileService
{
    public const COULEUR_PRIMAIRE_DEFAUT = '#059669';

    public const COULEUR_SECONDAIRE_DEFAUT = '#1f2937';

    /**
     * @return array<string, string> clé => libellé du champ manquant
     */
    public function champsManquants(Entreprise $entreprise): array
    {
        $manquants = [];

        if (blank($entreprise->nom)) {
            $manquants['nom'] = 'Nom de l\'entreprise';
        }

        if (! $this->siretEstValide((string) $entreprise->siret)) {
            $manquants['siret'] = 'SIRET (14 chiffres, clé de Luhn valide)';
        }

        $forme = $entreprise->status_juridique;
        if (blank($forme) || $forme === 'en_cours') {
            $manquants['status_juridique'] = 'Forme juridique (autre que « en cours de création »)';
        }

        if (blank($entreprise->adresse_rue) || blank($entreprise->code_postal) || blank($entreprise->ville)) {
            $manquants['adresse'] = 'Adresse complète (rue, code postal, ville)';
        }

        if (blank($entreprise->email)) {
            $manquants['email'] = 'Email de l\'entreprise';
        }

        if ($entreprise->assujetti_tva && blank($entreprise->tva_intracommunautaire)) {
            $manquants['tva_intracommunautaire'] = 'N° de TVA intracommunautaire';
        }

        if (in_array($forme, ['sarl', 'eurl', 'sas'], true)) {
            if (blank($entreprise->rcs_ville)) {
                $manquants['rcs_ville'] = 'Ville du RCS';
            }
            if ($entreprise->capital_social === null) {
                $manquants['capital_social'] = 'Capital social';
            }
        }

        return $manquants;
    }

    public function estComplet(Entreprise $entreprise): bool
    {
        return $this->champsManquants($entreprise) === [];
    }

    public function siretEstValide(?string $siret): bool
    {
        $siret = preg_replace('/\s+/', '', (string) $siret);

        if (! preg_match('/^\d{14}$/', $siret)) {
            return false;
        }

        return $this->luhnEstValide($siret);
    }

    public function mentionTva(Entreprise $entreprise): string
    {
        if ($entreprise->assujetti_tva) {
            $taux = number_format((float) ($entreprise->taux_tva_defaut ?? 20), 2, ',', ' ');

            return 'TVA au taux de '.$taux.' %.';
        }

        return 'TVA non applicable, article 293 B du CGI';
    }

    public function libelleFormeJuridique(?string $status): string
    {
        return match ($status) {
            'auto_entrepreneur' => 'Entrepreneur individuel',
            'sarl' => 'SARL',
            'eurl' => 'EURL',
            'sas' => 'SAS',
            default => $status ?: 'Entreprise',
        };
    }

    /**
     * @return array{primary: string, secondary: string, text: string, muted: string, background: string, border: string, success: string}
     */
    public function couleursPdf(Entreprise $entreprise): array
    {
        $primary = $this->hexValide($entreprise->pdf_couleur_primaire) ?? self::COULEUR_PRIMAIRE_DEFAUT;
        $secondary = $this->hexValide($entreprise->pdf_couleur_secondaire) ?? self::COULEUR_SECONDAIRE_DEFAUT;

        return [
            'primary' => $primary,
            'secondary' => $secondary,
            'text' => '#1a1a1a',
            'muted' => '#6b7280',
            'background' => '#f9fafb',
            'border' => '#e5e7eb',
            'success' => '#10b981',
        ];
    }

    public function hexValide(?string $value): ?string
    {
        if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return strtoupper($value);
        }

        return null;
    }

    private function luhnEstValide(string $number): bool
    {
        $sum = 0;
        $alt = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int) $number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = ! $alt;
        }

        return $sum % 10 === 0;
    }
}
