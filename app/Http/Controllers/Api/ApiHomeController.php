<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SubdomainHost;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Page de garde de api.allotata.* et index de la v1.
 *
 * Les deux catalogues ci-dessous sont la seule source de verite : ils alimentent
 * a la fois la page HTML et l'index JSON, pour qu'ils ne puissent pas divergrer.
 */
class ApiHomeController extends Controller
{
    private const LIMITE_PAR_MINUTE = 60;

    private const LIMITE_GESTION_PAR_MINUTE = 120;

    public function show(): View
    {
        return view('api.home', [
            'baseUrl' => $this->baseUrl(),
            // La page repond aussi sur l'apex (/api est partage) : la canonique evite
            // de faire indexer deux fois le meme contenu.
            'canonicalUrl' => $this->documentationUrl(),
            'limiteParMinute' => self::LIMITE_PAR_MINUTE,
            'limiteGestionParMinute' => self::LIMITE_GESTION_PAR_MINUTE,
            'jetonsUrl' => $this->jetonsUrl(),
            'endpoints' => $this->endpointsPublics(),
            'endpointsGestion' => $this->endpointsGestion(),
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'version' => 'v1',
            'base_url' => $this->baseUrl(),
            'documentation' => $this->documentationUrl(),
            'authentification' => 'aucune',
            'limite_par_minute' => self::LIMITE_PAR_MINUTE,
            'endpoints' => $this->pourJson($this->endpointsPublics()),
            'gestion' => [
                'authentification' => 'Authorization: Bearer <jeton>',
                'jetons' => $this->jetonsUrl(),
                'ecriture' => 'non : la v1 de gestion est en lecture seule',
                'limite_par_minute' => self::LIMITE_GESTION_PAR_MINUTE,
                'endpoints' => $this->pourJson($this->endpointsGestion()),
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $endpoints
     * @return list<array<string, mixed>>
     */
    private function pourJson(array $endpoints): array
    {
        return array_map(fn (array $endpoint) => [
            'methode' => $endpoint['methode'],
            'url' => $this->baseUrl().$endpoint['chemin'],
            'description' => $endpoint['description'],
            'parametres' => $endpoint['parametres'],
        ], $endpoints);
    }

    private function baseUrl(): string
    {
        return SubdomainHost::enabled()
            ? SubdomainHost::ownerUrl('/api/v1')
            : rtrim(url('/api/v1'), '/');
    }

    private function documentationUrl(): string
    {
        return SubdomainHost::enabled()
            ? SubdomainHost::ownerUrl('/api')
            : url('/api');
    }

    private function jetonsUrl(): string
    {
        return route('settings.api.index');
    }

    /**
     * Endpoints ouverts a tous : ils ne renvoient que des donnees deja publiques.
     *
     * @return list<array<string, mixed>>
     */
    private function endpointsPublics(): array
    {
        return [
            [
                'methode' => 'GET',
                'chemin' => '/search/autocomplete',
                'titre' => 'Recherche d\'entreprises',
                'description' => 'Cherche parmi les entreprises publiées, sur le nom, l\'activité, la ville, les mots-clés et les services proposés.',
                'parametres' => [
                    'q' => 'Texte recherché, 2 caractères minimum. Requis, sinon la réponse est une liste vide.',
                ],
                'exemple' => '/search/autocomplete?q=coiffeur',
                'reponse' => <<<'JSON'
                [
                  {
                    "id": 12,
                    "nom": "Salon Lumiere",
                    "type_activite": "Coiffure",
                    "ville": "Lyon",
                    "slug": "salon-lumiere",
                    "logo": "https://allotata.fr/media/logos/xxx.png",
                    "est_verifiee": true,
                    "services": ["Coupe femme", "Coloration"]
                  }
                ]
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/address/search',
                'titre' => 'Autocomplétion d\'adresse',
                'description' => 'Complète une adresse française : rue, numéro ou commune, avec ses coordonnées.',
                'parametres' => [
                    'q' => 'Début d\'adresse. Requis.',
                    'type' => 'Filtre optionnel : municipality, street ou housenumber.',
                    'limit' => 'Nombre de résultats, 5 par défaut, 10 au maximum.',
                ],
                'exemple' => '/address/search?q=10+rue+de+la+paix&limit=3',
                'reponse' => <<<'JSON'
                {
                  "success": true,
                  "results": [
                    {
                      "label": "10 Rue de la Paix 75002 Paris",
                      "street": "Rue de la Paix",
                      "housenumber": "10",
                      "city": "Paris",
                      "postcode": "75002",
                      "type": "housenumber",
                      "latitude": 48.8697,
                      "longitude": 2.3316
                    }
                  ]
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/address/cities',
                'titre' => 'Recherche de communes',
                'description' => 'Même moteur que l\'autocomplétion, restreint aux communes.',
                'parametres' => [
                    'q' => 'Début du nom de commune. Requis.',
                    'limit' => 'Nombre de résultats, 5 par défaut, 10 au maximum.',
                ],
                'exemple' => '/address/cities?q=lyon&limit=2',
                'reponse' => <<<'JSON'
                {
                  "success": true,
                  "results": [
                    {
                      "label": "Lyon",
                      "city": "Lyon",
                      "postcode": "69001",
                      "type": "municipality",
                      "latitude": 45.758,
                      "longitude": 4.835,
                      "context": "69, Rhone, Auvergne-Rhone-Alpes"
                    }
                  ]
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/address/geocode',
                'titre' => 'Géocodage',
                'description' => 'Transforme une adresse complète en coordonnées. Répond 404 si l\'adresse est introuvable.',
                'parametres' => [
                    'address' => 'Adresse complète. Requis.',
                ],
                'exemple' => '/address/geocode?address=1+place+Bellecour+Lyon',
                'reponse' => <<<'JSON'
                {
                  "success": true,
                  "data": {
                    "label": "1 Place Bellecour 69002 Lyon",
                    "city": "Lyon",
                    "postcode": "69002",
                    "latitude": 45.7578,
                    "longitude": 4.8320
                  }
                }
                JSON,
            ],
        ];
    }

    /**
     * Endpoints de gestion : meme v1, mais derriere un jeton personnel.
     *
     * @return list<array<string, mixed>>
     */
    private function endpointsGestion(): array
    {
        return [
            [
                'methode' => 'GET',
                'chemin' => '/moi',
                'titre' => 'Compte et périmètre du jeton',
                'description' => 'Le compte porteur du jeton et les entreprises qu\'il peut gérer. À appeler en premier pour connaître les slugs à utiliser ensuite.',
                'parametres' => [],
                'exemple' => '/moi',
                'reponse' => <<<'JSON'
                {
                  "compte": {
                    "id": 42,
                    "nom": "Awa",
                    "email": "awa@example.fr",
                    "est_gerant": true
                  },
                  "entreprises": [
                    {"slug": "salon-lumiere", "nom": "Salon Lumiere", "role": "proprietaire"}
                  ],
                  "jeton": {"nom": "Tableau de bord", "expire_le": null}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises',
                'titre' => 'Mes entreprises',
                'description' => 'Liste paginée des entreprises accessibles avec ce jeton.',
                'parametres' => [
                    'page' => 'Numéro de page, 1 par défaut.',
                    'par_page' => 'Taille de page, 25 par défaut, 100 au maximum.',
                ],
                'exemple' => '/entreprises',
                'reponse' => <<<'JSON'
                {
                  "donnees": [
                    {"id": 7, "slug": "salon-lumiere", "nom": "Salon Lumiere", "ville": "Lyon"}
                  ],
                  "pagination": {"page": 1, "par_page": 25, "total": 1, "total_pages": 1}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}',
                'titre' => 'Fiche d\'une entreprise',
                'description' => 'Fiche complète : contact, adresse, réglages de réservation, facturation et compteurs du moment.',
                'parametres' => [
                    'slug' => 'Identifiant de l\'entreprise, dans l\'URL.',
                ],
                'exemple' => '/entreprises/salon-lumiere',
                'reponse' => <<<'JSON'
                {
                  "slug": "salon-lumiere",
                  "nom": "Salon Lumiere",
                  "reservation": {"acceptation_automatique": true, "intervalle_creneaux_minutes": 30},
                  "compteurs": {"services_actifs": 6, "reservations_en_attente": 2}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/reservations',
                'titre' => 'Réservations',
                'description' => 'Réservations de l\'entreprise, de la plus récente à la plus ancienne, avec le client, le service et le paiement.',
                'parametres' => [
                    'statut' => 'en_attente, confirmee, terminee ou annulee.',
                    'du' => 'Date de début, format AAAA-MM-JJ.',
                    'au' => 'Date de fin, format AAAA-MM-JJ.',
                    'service_id' => 'Restreint à un service.',
                    'payees' => '1 pour les réservations payées, 0 pour les impayées.',
                    'par_page' => 'Taille de page, 25 par défaut, 100 au maximum.',
                ],
                'exemple' => '/entreprises/salon-lumiere/reservations?statut=confirmee&du=2026-08-01',
                'reponse' => <<<'JSON'
                {
                  "donnees": [
                    {
                      "id": 1841,
                      "statut": "confirmee",
                      "date_debut": "2026-08-14T10:00:00+02:00",
                      "duree_minutes": 60,
                      "service": {"id": 3, "nom": "Coupe femme"},
                      "client": {"nom": "Awa", "email": "awa@example.fr", "inscrit": true},
                      "paiement": {"prix": 45.0, "est_paye": false}
                    }
                  ],
                  "pagination": {"page": 1, "par_page": 25, "total": 132, "total_pages": 6}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/reservations/{id}',
                'titre' => 'Une réservation',
                'description' => 'Détail d\'une réservation. Répond 404 si elle appartient à une autre entreprise.',
                'parametres' => [
                    'id' => 'Identifiant de la réservation, dans l\'URL.',
                ],
                'exemple' => '/entreprises/salon-lumiere/reservations/1841',
                'reponse' => <<<'JSON'
                {
                  "id": 1841,
                  "reference": "a1b2c3d4",
                  "statut": "confirmee",
                  "lieu": "Salon",
                  "notes": "Cliente allergique aux sulfates"
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/disponibilites',
                'titre' => 'Créneaux libres',
                'description' => 'Créneaux encore libres pour une date, horaires exceptionnels compris. L\'occupation est tranchée par le même service que la prise de rendez-vous.',
                'parametres' => [
                    'date' => 'Jour demandé, format AAAA-MM-JJ. Aujourd\'hui par défaut.',
                    'service_id' => 'Cadre la durée du créneau sur celle du service.',
                    'membre_id' => 'Restreint aux disponibilités d\'un membre de l\'équipe.',
                ],
                'exemple' => '/entreprises/salon-lumiere/disponibilites?date=2026-08-20&service_id=3',
                'reponse' => <<<'JSON'
                {
                  "date": "2026-08-20",
                  "duree_minutes": 60,
                  "creneaux": [
                    {"debut": "2026-08-20T09:00:00+02:00", "fin": "2026-08-20T10:00:00+02:00", "heure": "09:00"}
                  ]
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/services',
                'titre' => 'Services proposés',
                'description' => 'Prestations de l\'entreprise : durée, prix, structure (ponctuel, événement, récurrent, sur devis...).',
                'parametres' => [
                    'actifs' => '1 pour les seuls services actifs, 0 pour les inactifs.',
                    'type_structure' => 'ponctuel, multi_jours, multi_rendez_vous, date_butoire, recurrent, evenement ou sur_devis.',
                ],
                'exemple' => '/entreprises/salon-lumiere/services?actifs=1',
                'reponse' => <<<'JSON'
                {
                  "donnees": [
                    {"id": 3, "nom": "Coupe femme", "prix": 45.0, "duree_minutes": 60, "type_structure": "ponctuel", "est_actif": true}
                  ],
                  "pagination": {"page": 1, "par_page": 50, "total": 6, "total_pages": 1}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/produits',
                'titre' => 'Produits en vente',
                'description' => 'Produits de l\'entreprise, avec leur mode de gestion de stock et les modes de vente ouverts.',
                'parametres' => [
                    'actifs' => '1 pour les seuls produits actifs, 0 pour les inactifs.',
                ],
                'exemple' => '/entreprises/salon-lumiere/produits',
                'reponse' => <<<'JSON'
                {
                  "donnees": [
                    {"id": 11, "nom": "Shampoing doux", "prix": 12.5, "gestion_stock": "disponible_immediatement", "est_actif": true}
                  ],
                  "pagination": {"page": 1, "par_page": 50, "total": 1, "total_pages": 1}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/clients',
                'titre' => 'Clientèle',
                'description' => 'Clientèle reconstituée depuis les réservations : comptes inscrits et invités, avec leur historique agrégé.',
                'parametres' => [
                    'q' => 'Filtre sur le nom ou l\'email.',
                    'par_page' => 'Taille de page, 25 par défaut, 100 au maximum.',
                ],
                'exemple' => '/entreprises/salon-lumiere/clients?q=awa',
                'reponse' => <<<'JSON'
                {
                  "donnees": [
                    {
                      "nom": "Awa",
                      "email": "awa@example.fr",
                      "inscrit": true,
                      "reservations": 9,
                      "annulees": 1,
                      "total_encaisse": 385.0,
                      "derniere_reservation": "2026-08-14T10:00:00+02:00"
                    }
                  ],
                  "total_clients": 1,
                  "pagination": {"page": 1, "par_page": 25, "total": 1, "total_pages": 1}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/finances',
                'titre' => 'Écritures de trésorerie',
                'description' => 'Recettes et dépenses saisies dans l\'onglet Finances, avec les totaux de la période.',
                'parametres' => [
                    'du' => 'Date de début, format AAAA-MM-JJ. Un an en arrière par défaut.',
                    'au' => 'Date de fin, format AAAA-MM-JJ.',
                    'type' => 'income ou expense.',
                    'categorie' => 'Catégorie exacte telle que saisie.',
                ],
                'exemple' => '/entreprises/salon-lumiere/finances?type=expense&du=2026-01-01',
                'reponse' => <<<'JSON'
                {
                  "donnees": [
                    {"id": 88, "type": "expense", "categorie": "Fournitures", "montant": 120.0, "date": "2026-08-02"}
                  ],
                  "totaux": {"recettes": 0.0, "depenses": 120.0, "solde": -120.0},
                  "pagination": {"page": 1, "par_page": 50, "total": 1, "total_pages": 1}
                }
                JSON,
            ],
            [
                'methode' => 'GET',
                'chemin' => '/entreprises/{slug}/statistiques',
                'titre' => 'Statistiques de période',
                'description' => 'Réservations par statut, chiffre d\'affaires encaissé et attendu, visites du site et services les plus demandés.',
                'parametres' => [
                    'du' => 'Date de début, format AAAA-MM-JJ. 30 jours en arrière par défaut.',
                    'au' => 'Date de fin, format AAAA-MM-JJ.',
                ],
                'exemple' => '/entreprises/salon-lumiere/statistiques?du=2026-07-01&au=2026-07-31',
                'reponse' => <<<'JSON'
                {
                  "periode": {"du": "2026-07-01", "au": "2026-07-31"},
                  "reservations": {"total": 48, "en_attente": 3, "confirmees": 30, "terminees": 12, "annulees": 3},
                  "chiffre_affaires": {"encaisse": 1420.0, "a_encaisser": 380.0},
                  "visites": 512,
                  "top_services": [{"service": "Coupe femme", "reservations": 21}]
                }
                JSON,
            ],
        ];
    }
}
