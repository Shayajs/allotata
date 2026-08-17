<?php

return [

    'package_name' => env('PLAY_PACKAGE_NAME', 'fr.allotata.app'),

    'store_url' => env('PLAY_STORE_URL', 'https://play.google.com/store/apps/details?id=fr.allotata.app'),

    'apk_filename' => env('PLAY_APK_FILENAME', 'AlloTata.apk'),

    /*
    | Compte de service Google Play Android Publisher (JSON).
    | Ne jamais committer ce fichier.
    */
    'service_account_json' => env(
        'PLAY_SERVICE_ACCOUNT_JSON',
        storage_path('app/google/play-service-account.json')
    ),

    /*
    | Empreintes SHA-256 du certificat d'upload (et éventuellement du Play App Signing).
    | Séparées par des virgules dans PLAY_SHA256_FINGERPRINTS.
    */
    'sha256_fingerprints' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'PLAY_SHA256_FINGERPRINTS',
            'DC:1E:5F:4A:B3:13:C9:7A:05:3D:6A:88:B6:36:27:4F:8F:70:11:BE:3E:4F:AF:41:24:2E:43:8B:43:08:6F:D9'
        ))
    ))),

    'products' => [
        'premium' => [
            'id' => env('PLAY_PRODUCT_PREMIUM', 'fr.allotata.premium'),
            'kind' => 'subscription',
            'grants' => 'premium',
        ],
        'site_web' => [
            'id' => env('PLAY_PRODUCT_SITE_WEB', 'fr.allotata.site_web'),
            'kind' => 'subscription',
            'grants' => 'site_web',
        ],
        'multi_personnes' => [
            'id' => env('PLAY_PRODUCT_MULTI_PERSONNES', 'fr.allotata.multi_personnes'),
            'kind' => 'subscription',
            'grants' => 'multi_personnes',
        ],
    ],

];
