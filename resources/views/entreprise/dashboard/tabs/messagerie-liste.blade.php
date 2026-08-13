{{-- Onglet Messagerie gérant : msgnav + chat dans le dashboard entreprise --}}
@include('messagerie.partials.shell', [
    'conversations' => $conversations ?? collect(),
    'isGerant' => true,
    'listUrl' => route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'messagerie']),
    'searchName' => 'search_gerant',
    'searchPlaceholder' => 'Rechercher un client...',
    'conversation' => $messagerieConversation ?? null,
    'entreprise' => $entreprise,
    'messages' => $messagerieMessages ?? collect(),
    'propositionActive' => $messageriePropositionActive ?? null,
    'prestations' => $messageriePrestations ?? collect(),
    'produits' => $messagerieProduits ?? collect(),
    'courseLinks' => $courseLinks ?? [],
])
