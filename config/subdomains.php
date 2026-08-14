<?php

$appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
$appHost = preg_replace('/^www\./i', '', strtolower((string) $appHost)) ?: 'localhost';

return [

    /*
    |--------------------------------------------------------------------------
    | Routage par sous-domaine (test PWA)
    |--------------------------------------------------------------------------
    |
    | Désactivé : aucun cloisonnement, l'apex sert tout.
    | Activé : chaque host a un périmètre. Hors périmètre = 302 vers le
    | propriétaire, ou 404 si le chemin n'existe nulle part.
    |
    */

    'enabled' => (bool) env('SUBDOMAIN_ROUTING', false),

    /*
    |--------------------------------------------------------------------------
    | Redirections implicites depuis l'apex
    |--------------------------------------------------------------------------
    |
    | Les anciens liens de l'apex restent fonctionnels mais renvoient vers le
    | sous-domaine propriétaire : /dashboard -> dash.*, /m/{slug} -> {slug}.*
    | Les chemins « shared » (assets, /api, /logout, webhooks) ne bougent pas.
    | Statut 302 pendant la phase de test : passer à 301 une fois stabilisé.
    |
    */

    'legacy_redirect' => (bool) env('SUBDOMAIN_LEGACY_REDIRECT', true),

    'redirect_status' => (int) env('SUBDOMAIN_REDIRECT_STATUS', 302),

    'base_domain' => env('APP_BASE_DOMAIN', $appHost),

    'reserved' => [
        'admin',
        'dash',
        'sign',
        'api',
        'www',
        'mail',
        'app',
        'cdn',
        'media',
        'static',
        'ws',
        'reverb',
        'smtp',
        'imap',
    ],

    'hosts' => [
        'admin' => [
            'type' => 'prefix',
            'root' => '/admin',
        ],
        'dash' => [
            'type' => 'space',
            'root' => '/dashboard',
            'segments' => [
                'dashboard',
                'settings',
                'notifications',
                'messagerie',
                'tickets',
                'factures',
                'checkout',
                'payment',
                'abonnement',
                'essai-gratuit',
                'entreprise',
                'apprendre',
                'gdpr',
                'stop-impersonating',
            ],
        ],
        'sign' => [
            'type' => 'space',
            'root' => '/signin',
            'segments' => [
                'signin',
                'signup',
                'password',
                'two-factor',
                'verification',
                'security',
                'auth/popup',
                'invitations',
                'logout',
            ],
        ],
        'api' => [
            'type' => 'prefix',
            'root' => '/api',
        ],
    ],

    /*
    | Fichiers physiques de public/ : nginx les sert sur tous les hosts avant
    | meme d'atteindre Laravel. Un chemin interne ne doit donc jamais etre
    | traduit vers l'un d'eux (sinon /admin/media deviendrait le dossier /media).
    */

    'static' => [
        'build',
        'media',
        'storage',
        'icons',
        'fonts',
        'js',
        'sw.js',
        'workbox',
        'offline.html',
        'robots.txt',
        'favicon.ico',
        'favicon.png',
    ],

    /*
    | Routes Laravel accessibles depuis n'importe quel host.
    */

    'shared' => [
        'manifest.json',
        'up',
        'broadcasting',
        'push-subscription',
        'api',
        'logout',
    ],

];
