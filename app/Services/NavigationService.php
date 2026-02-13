<?php

namespace App\Services;

/**
 * Source unique de vérité pour tous les items de navigation.
 * Centralise labels, icônes SVG, onglets et badges pour sidebar, mobile tabs, PWA bottom bar et burger drawer.
 */
class NavigationService
{
    // =========================================================================
    // Icônes SVG (stroke paths réutilisables)
    // =========================================================================
    public const ICONS = [
        'home'          => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'book'          => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'building'      => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'credit-card'   => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'calendar'      => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'clipboard'     => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'document'      => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'chat'          => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'bell'          => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'lock'          => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'support'       => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
        'download'      => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
        'services'      => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        'cube'          => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'shopping-bag'  => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        'users'         => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'currency'      => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'chart-bar'     => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'beaker'        => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        'star'          => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'globe'         => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        'cog'           => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'cog-inner'     => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'dots-h'        => 'M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z',
        // Settings-specific
        'user'          => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'shield'        => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'eye'           => 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        'clock'         => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    ];

    // =========================================================================
    // Dashboard membre
    // =========================================================================
    public static function getDashboardItems($user, array $data = []): array
    {
        $items = [];

        // --- Primary ---
        $items[] = [
            'key'        => 'accueil',
            'label'      => 'Accueil',
            'short_label'=> 'Accueil',
            'icon'       => 'home',
            'tab'        => 'accueil',
            'group'      => 'primary',
            'pwa_bottom' => true,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'apprendre',
            'label'      => 'Apprendre',
            'icon'       => 'book',
            'tab'        => 'apprendre',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];

        $showEntreprise = $user->est_gerant || ($data['entreprises_count'] ?? 0) > 0;
        if ($showEntreprise) {
            $items[] = [
                'key'        => 'entreprises',
                'label'      => 'Mes entreprises',
                'short_label'=> 'Entreprises',
                'icon'       => 'building',
                'tab'        => 'entreprises',
                'group'      => 'primary',
                'pwa_bottom' => true,
                'visible'    => true,
                'badge'      => ($data['reservations_en_attente'] ?? 0) > 0 ? $data['reservations_en_attente'] : null,
                'badge_color'=> 'yellow',
            ];
            $items[] = [
                'key'        => 'abonnements',
                'label'      => 'Mes abonnements',
                'short_label'=> 'Abonnés',
                'icon'       => 'credit-card',
                'tab'        => 'abonnements',
                'group'      => 'primary',
                'pwa_bottom' => true,
                'visible'    => true,
                'badge'      => null,
            ];
        }

        if ($user->est_client) {
            $items[] = [
                'key'        => 'reservations',
                'label'      => 'Mes réservations',
                'short_label'=> 'Réserv.',
                'icon'       => 'calendar',
                'tab'        => 'reservations',
                'group'      => 'primary',
                'pwa_bottom' => true,
                'visible'    => true,
                'badge'      => null,
            ];
        }

        if ($showEntreprise) {
            $items[] = [
                'key'        => 'emploi-du-temps',
                'label'      => 'Emploi du temps',
                'short_label'=> 'Planning',
                'icon'       => 'clock',
                'tab'        => 'emploi-du-temps',
                'group'      => 'primary',
                'pwa_bottom' => false,
                'visible'    => true,
                'badge'      => null,
            ];
        }

        $items[] = [
            'key'        => 'factures',
            'label'      => 'Mes factures',
            'icon'       => 'document',
            'tab'        => 'factures',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];

        // --- separator (communication) ---
        $items[] = ['separator' => true];

        $items[] = [
            'key'        => 'messagerie',
            'label'      => 'Messagerie',
            'icon'       => 'chat',
            'tab'        => 'messagerie',
            'group'      => 'secondary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => ($data['non_lus'] ?? 0) > 0 ? $data['non_lus'] : null,
            'badge_color'=> 'green',
        ];
        $items[] = [
            'key'        => 'notifications',
            'label'      => 'Notifications',
            'icon'       => 'bell',
            'tab'        => 'notifications',
            'group'      => 'secondary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => ($data['notifications_non_lues'] ?? 0) > 0 ? $data['notifications_non_lues'] : null,
            'badge_color'=> 'orange',
        ];

        // --- separator (system) ---
        $items[] = ['separator' => true];

        $items[] = [
            'key'        => 'securite',
            'label'      => 'Sécurité',
            'icon'       => 'lock',
            'tab'        => 'securite',
            'group'      => 'system',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => ($data['has_suspicious'] ?? false) ? 'dot-red' : null,
        ];
        $items[] = [
            'key'        => 'support',
            'label'      => 'Support',
            'icon'       => 'support',
            'tab'        => 'support',
            'group'      => 'system',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];

        // --- separator ---
        $items[] = ['separator' => true];

        $items[] = [
            'key'        => 'installer',
            'label'      => 'Installer',
            'icon'       => 'download',
            'tab'        => 'installer',
            'group'      => 'system',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];

        return $items;
    }

    // =========================================================================
    // Dashboard entreprise
    // =========================================================================
    public static function getEntrepriseItems($entreprise, $user, array $data = []): array
    {
        $items = [];

        $items[] = [
            'key'        => 'accueil',
            'label'      => 'Accueil',
            'short_label'=> 'Accueil',
            'icon'       => 'home',
            'tab'        => 'accueil',
            'group'      => 'primary',
            'pwa_bottom' => true,
            'visible'    => true,
            'badge'      => ($data['reservations_en_attente'] ?? 0) > 0 ? $data['reservations_en_attente'] : null,
            'badge_color'=> 'yellow',
        ];
        $items[] = [
            'key'        => 'agenda',
            'label'      => 'Agenda',
            'short_label'=> 'Agenda',
            'icon'       => 'calendar',
            'tab'        => 'agenda',
            'group'      => 'primary',
            'pwa_bottom' => true,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'mes-services',
            'label'      => 'Services',
            'icon'       => 'services',
            'tab'        => 'mes-services',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'stock',
            'label'      => 'Stock',
            'icon'       => 'cube',
            'tab'        => 'stock',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'commandes',
            'label'      => 'Commandes',
            'icon'       => 'shopping-bag',
            'tab'        => 'commandes',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => ($data['commandes_en_attente'] ?? 0) > 0 ? $data['commandes_en_attente'] : null,
            'badge_color'=> 'red',
        ];

        if ($data['a_gestion_multi_personnes'] ?? false) {
            $items[] = [
                'key'        => 'equipe',
                'label'      => 'Équipe',
                'icon'       => 'users',
                'tab'        => 'equipe',
                'group'      => 'primary',
                'pwa_bottom' => false,
                'visible'    => true,
                'badge'      => null,
            ];
        }

        $items[] = [
            'key'        => 'reservations',
            'label'      => 'Réservations',
            'short_label'=> 'Réserv.',
            'icon'       => 'clipboard',
            'tab'        => 'reservations',
            'group'      => 'primary',
            'pwa_bottom' => true,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'factures',
            'label'      => 'Factures',
            'icon'       => 'document',
            'tab'        => 'factures',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'finances',
            'label'      => 'Recettes',
            'icon'       => 'currency',
            'tab'        => 'finances',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
            'label_class'=> 'text-green-600 dark:text-green-400 font-bold italic',
        ];
        $items[] = [
            'key'        => 'statistiques',
            'label'      => 'Statistiques',
            'icon'       => 'chart-bar',
            'tab'        => 'statistiques',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'outils',
            'label'      => 'Outils',
            'icon'       => 'beaker',
            'tab'        => 'outils',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'messagerie',
            'label'      => 'Messagerie',
            'short_label'=> 'Messages',
            'icon'       => 'chat',
            'tab'        => 'messagerie',
            'group'      => 'primary',
            'pwa_bottom' => true,
            'visible'    => true,
            'badge'      => ($data['messages_non_lus'] ?? 0) > 0 ? $data['messages_non_lus'] : null,
            'badge_color'=> 'green',
        ];
        $items[] = [
            'key'        => 'fidelisation',
            'label'      => 'Fidélisation',
            'icon'       => 'star',
            'tab'        => 'fidelisation',
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];

        // Site Web (special rendering)
        $items[] = [
            'key'        => 'site-web',
            'label'      => 'Site Web',
            'icon'       => 'globe',
            'tab'        => null, // uses route instead
            'group'      => 'primary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
            'is_link'    => true,
            'locked'     => !($data['a_site_web_actif'] ?? false),
            'url'        => ($data['a_site_web_actif'] ?? false) ? ($data['site_web_url'] ?? '#') : null,
        ];

        // --- separator ---
        $items[] = ['separator' => true];

        $items[] = [
            'key'        => 'abonnements',
            'label'      => 'Abonnements',
            'icon'       => 'credit-card',
            'tab'        => 'abonnements',
            'group'      => 'secondary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'parametres',
            'label'      => 'Paramètres',
            'icon'       => 'cog',
            'icon_extra' => 'cog-inner',
            'tab'        => 'parametres',
            'group'      => 'secondary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];
        $items[] = [
            'key'        => 'installer',
            'label'      => 'Installer',
            'icon'       => 'download',
            'tab'        => 'installer',
            'group'      => 'secondary',
            'pwa_bottom' => false,
            'visible'    => true,
            'badge'      => null,
        ];

        return $items;
    }

    // =========================================================================
    // Settings
    // =========================================================================
    public static function getSettingsItems($user, array $data = []): array
    {
        $items = [
            ['key' => 'account',       'label' => 'Mon compte',     'short_label' => 'Compte', 'icon' => 'user',        'tab' => 'account',       'group' => 'primary', 'pwa_bottom' => true, 'visible' => true, 'badge' => null],
        ];

        if ($user->est_gerant && ($data['entreprises_count'] ?? 0) > 0) {
            $items[] = ['key' => 'entreprise', 'label' => 'Mes entreprises', 'icon' => 'building', 'tab' => 'entreprise', 'group' => 'primary', 'pwa_bottom' => false, 'visible' => true, 'badge' => null];
        }

        $items[] = ['key' => 'notifications', 'label' => 'Notifications',  'icon' => 'bell',        'tab' => 'notifications', 'group' => 'primary', 'pwa_bottom' => false, 'visible' => true, 'badge' => null];
        $items[] = ['key' => 'security',      'label' => 'Sécurité',       'short_label' => 'Sécurité', 'icon' => 'lock',        'tab' => 'security',      'group' => 'primary', 'pwa_bottom' => true, 'visible' => true, 'badge' => null];

        if ($user->est_gerant) {
            $items[] = ['separator' => true];
            $items[] = ['key' => 'subscription', 'label' => 'Abonnement', 'icon' => 'credit-card', 'tab' => 'subscription', 'group' => 'primary', 'pwa_bottom' => false, 'visible' => true, 'badge' => null];
        }

        $items[] = ['separator' => true];
        $items[] = ['key' => 'preferences',     'label' => 'Préférences',     'icon' => 'cog',  'tab' => 'preferences',     'group' => 'secondary', 'pwa_bottom' => false, 'visible' => true, 'badge' => null, 'icon_extra' => 'cog-inner'];
        $items[] = ['key' => 'confidentialite', 'label' => 'Confidentialité', 'icon' => 'eye',  'tab' => 'confidentialite', 'group' => 'secondary', 'pwa_bottom' => false, 'visible' => true, 'badge' => null];

        return $items;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Retourne le path SVG pour une icône donnée.
     */
    public static function getIconPath(string $key): string
    {
        return self::ICONS[$key] ?? '';
    }

    /**
     * Filtre les items visibles (exclut separators).
     */
    public static function filterItems(array $items): array
    {
        return array_values(array_filter($items, fn($item) => !isset($item['separator']) && ($item['visible'] ?? true)));
    }

    /**
     * Retourne les items pour la bottom bar PWA (max 4 + "Plus").
     */
    public static function getPwaBottomItems(array $items): array
    {
        return array_values(array_filter(self::filterItems($items), fn($item) => $item['pwa_bottom'] ?? false));
    }
}
