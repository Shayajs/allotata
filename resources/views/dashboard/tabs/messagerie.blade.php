{{-- Onglet Messagerie --}}
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Messagerie</h2>
        <a href="{{ route('messagerie.index') }}" class="px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
            Ouvrir en plein écran →
        </a>
    </div>

    @php
        $userConversations = collect();
        
        // Conversations en tant que client
        if ($user->est_client) {
            $clientConversations = \App\Models\Conversation::where('user_id', $user->id)
                ->where('est_archivee', false)
                ->with(['entreprise', 'messages' => function($q) { $q->latest()->limit(1); }])
                ->get();
            $userConversations = $userConversations->merge($clientConversations);
        }
        
        // Conversations en tant que gérant d'entreprise
        if ($user->est_gerant) {
            $entreprisesIds = $user->entreprises()->pluck('id');
            $gerantConversations = \App\Models\Conversation::whereIn('entreprise_id', $entreprisesIds)
                ->where('est_archivee', false)
                ->with(['entreprise', 'user', 'messages' => function($q) { $q->latest()->limit(1); }])
                ->get();
            $userConversations = $userConversations->merge($gerantConversations);
        }
        
        $userConversations = $userConversations->unique('id')->sortByDesc(function($c) {
            return $c->messages->first()?->created_at ?? $c->created_at;
        })->take(10);
    @endphp

    @if($userConversations->count() > 0)
        <div class="space-y-2">
            @foreach($userConversations as $conversation)
                @php
                    $lastMessage = $conversation->messages->first();
                    $unreadCount = $conversation->messagesNonLus($user->id);
                @endphp
                <a href="{{ route('messagerie.show', $conversation->id) }}" class="block p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition-all {{ $unreadCount > 0 ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0">
                            @if($conversation->entreprise && $conversation->entreprise->logo)
                                <img src="{{ asset('media/' . $conversation->entreprise->logo) }}" alt="{{ $conversation->entreprise->nom }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($conversation->entreprise->nom ?? 'C', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-slate-900 dark:text-white truncate">
                                    {{ $conversation->entreprise->nom ?? 'Conversation' }}
                                </h3>
                                @if($lastMessage)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $lastMessage->created_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 truncate">
                                @if($lastMessage)
                                    {{ Str::limit($lastMessage->contenu, 60) }}
                                @else
                                    Aucun message
                                @endif
                            </p>
                        </div>
                        @if($unreadCount > 0)
                            <span class="px-2 py-1 text-xs font-semibold bg-green-500 text-white rounded-full">
                                {{ $unreadCount }}
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        
        @if($userConversations->count() >= 10)
            <div class="mt-6 text-center">
                <a href="{{ route('messagerie.index') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                    Voir toutes les conversations →
                </a>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune conversation</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Vos conversations avec les entreprises apparaîtront ici.
            </p>
        </div>
    @endif
</div>
