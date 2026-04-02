<?php

namespace App\Console\Commands;

use App\Models\Entreprise;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateGoogleMerchantFeed extends Command
{
    protected $signature = 'google:generate-merchant-feed {--output=google_merchant_feed.json : Nom du fichier de sortie}';

    protected $description = 'Génère le flux JSON Merchant Feed conforme à la spec Google Maps Booking API v3';

    /**
     * Mapping type_activite Allotata → category_type Google RwG.
     * @see https://developers.google.com/maps-booking/reference/rest-api-v3/feed-spec#category
     */
    protected const CATEGORY_MAP = [
        'coiffeuse'       => 'HAIR_CARE',
        'coiffeur'        => 'HAIR_CARE',
        'barbier'         => 'BARBER',
        'estheticienne'   => 'BEAUTY_SALON',
        'manucure'        => 'NAIL_SALON',
        'massage'         => 'SPA',
        'spa'             => 'SPA',
        'bien-etre'       => 'SPA',
        'fitness'         => 'GYM',
        'coach'           => 'GYM',
        'tatoueur'        => 'BEAUTY_SALON',
        'maquilleuse'     => 'BEAUTY_SALON',
        'cuisiniere'      => 'RESTAURANT',
        'traiteur'        => 'RESTAURANT',
        'photographe'     => 'PHOTOGRAPHER',
        'nettoyage'       => 'HOUSE_CLEANING',
        'menage'          => 'HOUSE_CLEANING',
    ];

    public function handle(): int
    {
        $this->info('Génération du feed Google Merchant (Maps Booking v3)…');

        $entreprises = Entreprise::where('est_verifiee', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('adresse_rue')
            ->whereNotNull('code_postal')
            ->whereNotNull('ville')
            ->with(['typesServices' => fn ($q) => $q->where('est_actif', true), 'horairesOuverture'])
            ->get();

        $this->info("Entreprises éligibles : {$entreprises->count()}");

        $merchants = [];

        foreach ($entreprises as $entreprise) {
            $merchant = $this->buildMerchant($entreprise);
            if ($merchant) {
                $merchants[] = $merchant;
            }
        }

        $feed = [
            'metadata' => [
                'processing_instruction' => 'PROCESS_AS_COMPLETE',
                'shard_number' => 0,
                'total_shards' => 1,
                'nonce' => (string) now()->timestamp,
                'generation_timestamp' => now()->toIso8601String(),
            ],
            'merchant' => $merchants,
        ];

        $filename = $this->option('output');
        Storage::disk('local')->put($filename, json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $path = Storage::disk('local')->path($filename);
        $this->info("Feed généré : {$path}");
        $this->info("Marchands exportés : " . count($merchants));

        return self::SUCCESS;
    }

    /**
     * Construit un objet Merchant conforme à la spec RwG v3.
     */
    protected function buildMerchant(Entreprise $entreprise): ?array
    {
        $services = $entreprise->typesServices
            ->filter(fn ($ts) => $ts->estPonctuel() && $ts->duree_minutes > 0)
            ->values();

        if ($services->isEmpty()) {
            return null;
        }

        $merchant = [
            'merchant_id' => (string) $entreprise->id,
            'name' => $entreprise->nom,
            'telephone' => $entreprise->telephone,
            'url' => route('public.entreprise', $entreprise->slug),
            'geo' => [
                'latitude' => (float) $entreprise->latitude,
                'longitude' => (float) $entreprise->longitude,
            ],
            'address' => [
                'street_address' => $entreprise->adresse_rue,
                'locality' => $entreprise->ville,
                'postal_code' => $entreprise->code_postal,
                'region' => 'FR', // ISO 3166-2 subdivision — à affiner si besoin
                'country' => 'FR',
            ],
            'category' => $this->resolveCategory($entreprise->type_activite),
        ];

        // Services (= les prestations réservables)
        $merchant['service'] = $services->map(fn ($ts) => [
            'service_id' => (string) $ts->id,
            'name' => $ts->nom,
            'description' => $ts->description ?? '',
            'price' => [
                'price_micros' => (int) ($ts->prix * 1_000_000),
                'currency_code' => 'EUR',
            ],
            'duration_sec' => $ts->duree_minutes * 60,
            'category' => $this->resolveCategory($entreprise->type_activite),
            'prepayment_type' => 'NOT_REQUIRED',
            'rules' => [
                'min_advance_online_canceling' => 7200, // 2h avant annulation possible
                'min_advance_booking' => 3600,          // 1h avant réservation minimum
            ],
        ])->values()->toArray();

        // Horaires d'ouverture réguliers
        $merchant['opening_hours'] = $this->buildOpeningHours($entreprise);

        return $merchant;
    }

    /**
     * Construit les horaires d'ouverture au format RwG.
     */
    protected function buildOpeningHours(Entreprise $entreprise): array
    {
        $hours = [];
        $jourMap = [0 => 'SUNDAY', 1 => 'MONDAY', 2 => 'TUESDAY', 3 => 'WEDNESDAY', 4 => 'THURSDAY', 5 => 'FRIDAY', 6 => 'SATURDAY'];

        $horaires = $entreprise->horairesOuverture
            ->where('est_exceptionnel', false)
            ->groupBy('jour_semaine');

        foreach ($horaires as $jour => $plages) {
            foreach ($plages as $plage) {
                if ($plage->estFerme()) {
                    continue;
                }

                $hours[] = [
                    'day_of_week' => $jourMap[$jour] ?? 'MONDAY',
                    'open_time' => $plage->heure_ouverture,   // "09:00"
                    'close_time' => $plage->heure_fermeture,  // "18:00"
                ];
            }
        }

        return $hours;
    }

    /**
     * Résout la catégorie Google à partir du type_activite Allotata.
     */
    protected function resolveCategory(string $typeActivite): string
    {
        $normalized = mb_strtolower(trim($typeActivite));

        // Chercher une correspondance directe
        if (isset(self::CATEGORY_MAP[$normalized])) {
            return self::CATEGORY_MAP[$normalized];
        }

        // Chercher une correspondance partielle
        foreach (self::CATEGORY_MAP as $keyword => $category) {
            if (str_contains($normalized, $keyword)) {
                return $category;
            }
        }

        // Fallback
        return 'BEAUTY_SALON';
    }
}
