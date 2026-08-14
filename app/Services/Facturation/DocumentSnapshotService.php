<?php

namespace App\Services\Facturation;

use App\Models\Devis;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;

class DocumentSnapshotService
{
    public function __construct(
        private BillingProfileService $billing,
    ) {}

    /**
     * @param  array<int, Reservation>|\Illuminate\Support\Collection<int, Reservation>  $reservations
     * @return array<string, mixed>
     */
    public function pourFacture(
        Facture $facture,
        Entreprise $entreprise,
        iterable $reservations,
        ?User $client,
        ?Reservation $reservationPrincipale = null,
    ): array {
        $assujetti = (bool) $entreprise->assujetti_tva;
        $taux = $assujetti ? (float) ($entreprise->taux_tva_defaut ?? 20) : 0.0;

        $lignes = [];
        foreach ($reservations as $reservation) {
            $lignes[] = $this->ligneReservation($reservation, $taux);
        }

        if ($lignes === []) {
            $lignes[] = [
                'description' => $facture->notes ?: 'Prestation',
                'details' => null,
                'date' => optional($facture->date_facture)->format('d/m/Y'),
                'quantite' => 1,
                'prix_unitaire_ht' => (float) $facture->montant_ht,
                'montant_ht' => (float) $facture->montant_ht,
                'taux_tva' => $taux,
                'montant_tva' => (float) $facture->montant_tva,
                'montant_ttc' => (float) $facture->montant_ttc,
            ];
        }

        $ht = array_sum(array_column($lignes, 'montant_ht'));
        $tva = $assujetti ? round($ht * ($taux / 100), 2) : 0.0;
        $ttc = round($ht + $tva, 2);

        $datePrestation = $reservationPrincipale?->date_reservation
            ?? $reservationPrincipale?->date_butoire
            ?? $facture->date_facture;

        return [
            'type' => 'facture',
            'emetteur_kind' => 'prestataire',
            'numero' => $facture->numero_facture,
            'date_emission' => optional($facture->date_facture)->format('d/m/Y'),
            'date_prestation' => $datePrestation ? Carbon::parse($datePrestation)->format('d/m/Y') : null,
            'date_echeance' => optional($facture->date_echeance)->format('d/m/Y'),
            'emetteur' => $this->emetteur($entreprise),
            'client' => $this->client($client, $reservationPrincipale),
            'lignes' => $lignes,
            'totaux' => [
                'montant_ht' => $ht,
                'taux_tva' => $taux,
                'montant_tva' => $tva,
                'montant_ttc' => $ttc,
                'assujetti_tva' => $assujetti,
                'mention_tva' => $this->billing->mentionTva($entreprise),
            ],
            'mentions' => $this->mentionsFacture($assujetti),
            'couleurs' => $this->billing->couleursPdf($entreprise),
            'logo' => $entreprise->logo,
            'paiement' => [
                'acquittee' => $facture->statut === 'payee',
                'date_paiement' => $facture->date_paiement?->format('d/m/Y'),
            ],
            'notes' => $facture->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pourDevis(Devis $devis, Entreprise $entreprise): array
    {
        $assujetti = (bool) $entreprise->assujetti_tva;
        $taux = $assujetti ? (float) ($entreprise->taux_tva_defaut ?? 20) : 0.0;
        $ht = (float) $devis->montant_propose;
        $tva = $assujetti ? round($ht * ($taux / 100), 2) : 0.0;
        $ttc = round($ht + $tva, 2);

        $description = $devis->typeService?->nom ?? 'Prestation';
        $details = $devis->description_besoin;
        if ($devis->duree_proposee_minutes) {
            $description .= ' ('.$devis->duree_proposee_minutes.' min)';
        }

        return [
            'type' => 'devis',
            'emetteur_kind' => 'prestataire',
            'numero' => $devis->numero_devis,
            'date_emission' => now()->format('d/m/Y'),
            'date_validite' => optional($devis->date_validite)->format('d/m/Y'),
            'date_proposee' => optional($devis->date_proposee)->format('d/m/Y à H:i'),
            'emetteur' => $this->emetteur($entreprise),
            'client' => $this->client($devis->user, null, $devis),
            'lignes' => [[
                'description' => $description,
                'details' => $details,
                'date' => optional($devis->date_proposee)->format('d/m/Y'),
                'quantite' => 1,
                'prix_unitaire_ht' => $ht,
                'montant_ht' => $ht,
                'taux_tva' => $taux,
                'montant_tva' => $tva,
                'montant_ttc' => $ttc,
            ]],
            'totaux' => [
                'montant_ht' => $ht,
                'taux_tva' => $taux,
                'montant_tva' => $tva,
                'montant_ttc' => $ttc,
                'assujetti_tva' => $assujetti,
                'mention_tva' => $this->billing->mentionTva($entreprise),
            ],
            'mentions' => [
                'validite' => 'Ce devis est valable jusqu\'au '.(optional($devis->date_validite)->format('d/m/Y') ?? '—').'.',
                'acceptation' => 'Pour accepter ce devis, merci de le confirmer sur Allotata (mention « Bon pour accord »).',
                'escompte' => 'Pas d\'escompte pour paiement anticipé.',
            ],
            'couleurs' => $this->billing->couleursPdf($entreprise),
            'logo' => $entreprise->logo,
            'notes' => $devis->notes_prestataire,
        ];
    }

    /**
     * Snapshot Allotata → abonné (émetteur Lucas Espinar EI, jamais le prestataire).
     *
     * @return array<string, mixed>
     */
    public function pourAbonnementPlateforme(Facture $facture): array
    {
        $p = config('facturation.plateforme');
        $siret = preg_replace('/\s+/', '', (string) $p['siret']);
        $ht = (float) $facture->montant_ht;
        $mention = (string) $p['mention_tva'];

        $facture->loadMissing(['entreprise', 'user', 'entrepriseSubscription']);

        return [
            'type' => 'facture',
            'emetteur_kind' => 'plateforme',
            'bandeau' => $p['bandeau'],
            'numero' => $facture->numero_facture,
            'date_emission' => optional($facture->date_facture)->format('d/m/Y'),
            'date_prestation' => optional($facture->date_facture)->format('d/m/Y'),
            'date_echeance' => optional($facture->date_echeance)->format('d/m/Y'),
            'emetteur' => [
                'nom' => $p['nom'],
                'marque' => $p['marque'],
                'forme_juridique' => $p['forme_juridique'],
                'nom_commercial' => $p['nom_commercial'],
                'siret' => $siret,
                'siret_formate' => $this->formaterSiret($siret),
                'siren' => $p['siren'],
                'tva_intracommunautaire' => null,
                'adresse' => $p['adresse'],
                'email' => $p['email'],
                'telephone' => $p['telephone'],
                'responsable' => $p['nom'],
                'rcs' => $p['rcs'],
                'ape' => $p['ape'],
            ],
            'client' => $this->clientAbonne($facture),
            'lignes' => [[
                'description' => $this->descriptionAbonnement($facture),
                'details' => $facture->notes,
                'date' => optional($facture->date_facture)->format('d/m/Y'),
                'quantite' => 1,
                'prix_unitaire_ht' => $ht,
                'montant_ht' => $ht,
                'taux_tva' => 0,
                'montant_tva' => 0,
                'montant_ttc' => (float) $facture->montant_ttc,
            ]],
            'totaux' => [
                'montant_ht' => $ht,
                'taux_tva' => 0,
                'montant_tva' => 0,
                'montant_ttc' => (float) $facture->montant_ttc,
                'assujetti_tva' => false,
                'mention_tva' => $mention,
            ],
            'mentions' => $this->mentionsFacture(false),
            'couleurs' => $p['couleurs'],
            'logo' => $p['logo'] ?? 'allotata',
            'paiement' => [
                'acquittee' => $facture->statut === 'payee',
                'date_paiement' => $facture->date_paiement?->format('d/m/Y'),
            ],
            'notes' => $facture->notes,
        ];
    }

    /**
     * Reconstruit un snapshot pour une facture existante (backfill lazy).
     *
     * @return array<string, mixed>
     */
    public function ensureFacture(Facture $facture): array
    {
        if (is_array($facture->snapshot) && ($facture->snapshot['numero'] ?? null)) {
            return $this->fusionnerPaiement($facture, $facture->snapshot);
        }

        $facture->loadMissing(['entreprise', 'user', 'reservation', 'reservations', 'entrepriseSubscription']);

        if ($facture->estAbonnementPlateforme()) {
            $snapshot = $this->pourAbonnementPlateforme($facture);
        } else {
            $reservations = $facture->estGroupee()
                ? $facture->reservations
                : collect($facture->reservation ? [$facture->reservation] : []);

            $snapshot = $this->pourFacture(
                $facture,
                $facture->entreprise,
                $reservations,
                $facture->user,
                $facture->reservation,
            );
        }

        $facture->forceFill([
            'snapshot' => $snapshot,
            'verrouillee_at' => $facture->verrouillee_at ?? now(),
        ])->saveQuietly();

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function ensureDevis(Devis $devis): array
    {
        if (is_array($devis->snapshot) && ($devis->snapshot['numero'] ?? null)) {
            return $devis->snapshot;
        }

        $devis->loadMissing(['entreprise', 'user', 'typeService']);
        $snapshot = $this->pourDevis($devis, $devis->entreprise);
        $devis->forceFill([
            'snapshot' => $snapshot,
            'verrouille_at' => $devis->verrouille_at ?? now(),
        ])->saveQuietly();

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function fusionnerPaiement(Facture $facture, array $snapshot): array
    {
        $snapshot['paiement'] = [
            'acquittee' => $facture->statut === 'payee',
            'date_paiement' => $facture->date_paiement?->format('d/m/Y'),
        ];

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    private function emetteur(Entreprise $entreprise): array
    {
        $forme = $this->billing->libelleFormeJuridique($entreprise->status_juridique);
        $siret = preg_replace('/\s+/', '', (string) $entreprise->siret);

        $adresse = trim(implode("\n", array_filter([
            $entreprise->adresse_rue,
            trim(($entreprise->code_postal ?? '').' '.($entreprise->ville ?? '')),
        ])));

        $rcs = null;
        if (in_array($entreprise->status_juridique, ['sarl', 'eurl', 'sas'], true)) {
            $parts = ['RCS '.($entreprise->rcs_ville ?: '')];
            if ($entreprise->capital_social !== null) {
                $parts[] = 'capital '.number_format((float) $entreprise->capital_social, 2, ',', ' ').' €';
            }
            $rcs = implode(' — ', array_filter($parts));
        }

        return [
            'nom' => $entreprise->nom,
            'forme_juridique' => $forme,
            'siret' => $siret,
            'siren' => $entreprise->siren ?: substr($siret, 0, 9),
            'tva_intracommunautaire' => $entreprise->tva_intracommunautaire,
            'adresse' => $adresse,
            'email' => $entreprise->email,
            'telephone' => $entreprise->telephone,
            'responsable' => $entreprise->nom_responsable,
            'rcs' => $rcs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function client(?User $user, ?Reservation $reservation = null, ?Devis $devis = null): array
    {
        $nom = $user?->name
            ?? $reservation?->nom_client
            ?? $devis?->nom_client
            ?? 'Client';

        $email = $user?->email
            ?? $reservation?->email_client
            ?? $devis?->email_client;

        $telephone = $user?->telephone
            ?? $reservation?->telephone_client
            ?? $reservation?->telephone_client_non_inscrit
            ?? $devis?->telephone_client;

        $adresse = trim(implode("\n", array_filter([
            $user?->adresse,
            trim(($user?->code_postal ?? '').' '.($user?->ville ?? '')),
        ])));

        return [
            'nom' => $nom,
            'email' => $email,
            'telephone' => $telephone,
            'adresse' => $adresse !== '' ? $adresse : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clientAbonne(Facture $facture): array
    {
        if ($facture->type_facture === 'abonnement_entreprise' && $facture->entreprise) {
            $entreprise = $facture->entreprise;
            $adresse = trim(implode("\n", array_filter([
                $entreprise->adresse_rue,
                trim(($entreprise->code_postal ?? '').' '.($entreprise->ville ?? '')),
            ])));

            return [
                'nom' => $entreprise->nom,
                'email' => $entreprise->email ?: $facture->user?->email,
                'telephone' => $entreprise->telephone ?: $facture->user?->telephone,
                'adresse' => $adresse !== '' ? $adresse : null,
            ];
        }

        return $this->client($facture->user);
    }

    private function descriptionAbonnement(Facture $facture): string
    {
        if ($facture->entrepriseSubscription) {
            $offre = $facture->entrepriseSubscription->type === 'site_web'
                ? 'Site Web Vitrine'
                : 'Gestion Multi-Personnes';

            return 'Abonnement Allotata — '.$offre;
        }

        return 'Abonnement Allotata';
    }

    private function formaterSiret(string $siret): string
    {
        $siret = (string) preg_replace('/\s+/', '', $siret);

        if (strlen($siret) !== 14) {
            return $siret;
        }

        return substr($siret, 0, 3).' '.substr($siret, 3, 3).' '.substr($siret, 6, 3).' '.substr($siret, 9);
    }

    /**
     * @return array<string, mixed>
     */
    private function ligneReservation(Reservation $reservation, float $taux): array
    {
        $nom = $reservation->typeService?->nom ?? ($reservation->type_service ?? 'Prestation');
        $details = [];
        if ($reservation->duree_minutes) {
            $details[] = $reservation->duree_minutes.' min';
        }
        if ($reservation->lieu) {
            $details[] = $reservation->lieu;
        }

        $ht = (float) $reservation->prix;
        $tva = $taux > 0 ? round($ht * ($taux / 100), 2) : 0.0;

        $date = $reservation->date_reservation
            ? $reservation->date_reservation->format('d/m/Y H:i')
            : ($reservation->date_butoire ? Carbon::parse($reservation->date_butoire)->format('d/m/Y') : null);

        return [
            'description' => $nom,
            'details' => $details !== [] ? implode(' — ', $details) : null,
            'date' => $date,
            'quantite' => 1,
            'prix_unitaire_ht' => $ht,
            'montant_ht' => $ht,
            'taux_tva' => $taux,
            'montant_tva' => $tva,
            'montant_ttc' => round($ht + $tva, 2),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mentionsFacture(bool $assujetti): array
    {
        return [
            'escompte' => 'Pas d\'escompte pour paiement anticipé.',
            'penalites' => 'En cas de retard de paiement, des pénalités calculées sur la base du taux d\'intérêt légal sont exigibles. Indemnité forfaitaire de recouvrement de 40 € due de plein droit entre professionnels (art. L. 441-10 du Code de commerce).',
            'tva' => $assujetti
                ? 'TVA applicable selon le taux indiqué.'
                : 'TVA non applicable, article 293 B du CGI',
        ];
    }
}
