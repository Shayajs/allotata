<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tarifs par défaut (€/mois)
    |--------------------------------------------------------------------------
    | Utilisés pour le calcul des échéances. CustomPrice écrase par user/entreprise.
    */

    'default' => (float) (env('TARIF_DEFAULT', 14)),
    'site_web' => (float) (env('TARIF_SITE_WEB', 2)),
    'multi_personnes' => (float) (env('TARIF_MULTI_PERSONNES', 20)),

    'currency' => 'eur',

];
