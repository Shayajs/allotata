{{-- Onglet Messagerie client : msgnav + chat dans le dashboard --}}
@include('messagerie.partials.shell', [
    'conversations' => $messagerieConversations ?? collect(),
    'isGerant' => false,
    'listUrl' => route('dashboard', ['tab' => 'messagerie']),
    'searchName' => 'search_client',
    'searchPlaceholder' => 'Rechercher une entreprise...',
    'conversation' => $messagerieConversation ?? null,
    'entreprise' => $messagerieEntreprise ?? null,
    'messages' => $messagerieMessages ?? collect(),
    'propositionActive' => $messageriePropositionActive ?? null,
    'prestations' => $messageriePrestations ?? collect(),
    'produits' => $messagerieProduits ?? collect(),
    'courseLinks' => $courseLinks ?? [],
])
