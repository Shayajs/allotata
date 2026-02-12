{{-- Onglet système : Agenda (même contenu que réservation) --}}
@include('components.site-web.system-tabs.reservation', [
    'entreprise' => $entreprise,
    'page' => $page,
    'slug' => $slug,
    'horaires' => $horaires,
    'jours' => $jours,
    'membres' => $membres,
    'aGestionMultiPersonnes' => $aGestionMultiPersonnes,
    'userInfo' => $userInfo,
])
