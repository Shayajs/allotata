@php
    $isGerant = $isGerant ?? false;
    $hasConversation = isset($conversation) && $conversation;
    $listUrl = $listUrl ?? ($isGerant
        ? route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'messagerie'])
        : route('dashboard', ['tab' => 'messagerie']));
@endphp

<div class="messagerie-shell flex h-[calc(100vh-11rem)] min-h-[28rem]">
    <aside class="msgnav {{ $hasConversation ? 'hidden md:flex' : 'flex' }} flex-col w-full md:w-[30%] md:max-w-sm md:min-w-[16rem] flex-shrink-0 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
        @include('messagerie.partials.liste', [
            'conversations' => $conversations,
            'isGerant' => $isGerant,
            'listUrl' => $listUrl,
            'searchName' => $searchName ?? ($isGerant ? 'search_gerant' : 'search_client'),
            'searchPlaceholder' => $searchPlaceholder ?? ($isGerant ? 'Rechercher un client...' : 'Rechercher une entreprise...'),
            'conversation' => $conversation ?? null,
            'entreprise' => $entreprise ?? null,
            'courseLinks' => $courseLinks ?? [],
        ])
    </aside>

    <section class="msgchat {{ $hasConversation ? 'flex' : 'hidden md:flex' }} flex-1 min-w-0 flex-col bg-white dark:bg-slate-800">
        @if($hasConversation)
            @include('messagerie.partials.chat', [
                'conversation' => $conversation,
                'entreprise' => $entreprise,
                'messages' => $messages,
                'isGerant' => $isGerant,
                'propositionActive' => $propositionActive ?? null,
                'prestations' => $prestations ?? collect(),
                'produits' => $produits ?? collect(),
                'listUrl' => $listUrl,
            ])
        @else
            <div class="hidden md:flex flex-1 items-center justify-center p-8 text-center">
                <div>
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Choisissez une conversation</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Sélectionnez un fil à gauche pour afficher les messages.
                    </p>
                </div>
            </div>
        @endif
    </section>
</div>
