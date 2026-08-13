@php
    $isGerant = $isGerant ?? false;
    $searchName = $searchName ?? ($isGerant ? 'search_gerant' : 'search_client');
    $searchPlaceholder = $searchPlaceholder ?? ($isGerant ? 'Rechercher un client...' : 'Rechercher une entreprise...');
    $listUrl = $listUrl ?? ($isGerant
        ? route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'messagerie'])
        : route('dashboard', ['tab' => 'messagerie']));
    $selectedId = isset($conversation) && $conversation ? $conversation->id : null;
    $conversations = $conversations ?? collect();
@endphp

<div class="flex flex-col h-full min-h-0">
    <div class="flex-shrink-0 p-4 border-b border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ $isGerant ? 'Conversations clients' : 'Mes conversations' }}
            </h2>
            <x-course-link-badge :page-key="$isGerant ? 'entreprise.messagerie' : 'dashboard.messagerie'" :course-links="$courseLinks ?? []" />
        </div>
        <form method="GET" action="{{ $listUrl }}" class="relative">
            <input type="hidden" name="tab" value="messagerie">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input
                type="text"
                name="{{ $searchName }}"
                value="{{ request($searchName) }}"
                placeholder="{{ $searchPlaceholder }}"
                class="no-ui-input w-full pl-10 pr-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
            >
        </form>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto custom-scrollbar p-2 space-y-1">
        @forelse($conversations as $item)
            @php
                $lastMessage = $item->dernierMessage ?? $item->messages->first();
                $unreadCount = $item->messagesNonLus(auth()->id());
                $isActive = $selectedId === $item->id;
            @endphp
            <a
                href="{{ $item->dashboardUrl($isGerant) }}"
                class="block p-3 rounded-xl border transition-all {{ $isActive ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ($unreadCount > 0 ? 'border-slate-200 dark:border-slate-700 bg-green-50/50 dark:bg-green-900/10' : 'border-transparent hover:bg-slate-50 dark:hover:bg-slate-700/50') }}"
            >
                <div class="flex items-start gap-3">
                    <div class="relative flex-shrink-0">
                        @if($isGerant)
                            <x-avatar :user="$item->user" size="lg" />
                        @elseif($item->entreprise && $item->entreprise->logo)
                            <img src="{{ asset('media/' . $item->entreprise->logo) }}" alt="{{ $item->entreprise->nom }}" class="w-12 h-12 rounded-lg object-cover">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($item->entreprise->nom ?? 'C', 0, 1)) }}
                            </div>
                        @endif
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-green-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-0.5">
                            <h3 class="font-semibold text-slate-900 dark:text-white truncate text-sm">
                                {{ $isGerant ? ($item->user->name ?? 'Client') : ($item->entreprise->nom ?? 'Conversation') }}
                            </h3>
                            @if($lastMessage)
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 flex-shrink-0">
                                    {{ $lastMessage->created_at->diffForHumans(null, true) }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate {{ $unreadCount > 0 ? 'font-medium text-slate-800 dark:text-slate-200' : '' }}">
                            @if($lastMessage)
                                @if($isGerant && $lastMessage->user_id !== $item->user_id)
                                    <span class="text-slate-400">Vous : </span>
                                @endif
                                {{ method_exists($lastMessage, 'apercuListeConversation') ? $lastMessage->apercuListeConversation() : Str::limit($lastMessage->contenu, 50) }}
                            @else
                                Aucun message
                            @endif
                        </p>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-12 px-4">
                <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <h3 class="mt-3 text-sm font-medium text-slate-900 dark:text-white">Aucune conversation</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $isGerant ? 'Les messages de vos clients apparaîtront ici.' : 'Vos conversations avec les entreprises apparaîtront ici.' }}
                </p>
            </div>
        @endforelse
    </div>
</div>
