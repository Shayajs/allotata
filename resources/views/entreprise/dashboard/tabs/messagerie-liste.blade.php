<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Messagerie</h2>
            <p class="text-slate-600 dark:text-slate-400">Conversations avec vos clients</p>
        </div>
    </div>

    @if($conversations->count() > 0)
        <div class="space-y-3">
            @foreach($conversations as $conversation)
                @php
                    $dernierMessage = $conversation->messages->first();
                    $messagesNonLus = $conversation->messagesNonLus($user->id);
                @endphp
                <a 
                    href="{{ route('messagerie.show-gerant', [$entreprise->slug, $conversation->id]) }}" 
                    class="block p-4 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-green-500 dark:hover:border-green-500 transition {{ $messagesNonLus > 0 ? 'border-l-4 border-l-green-500' : '' }}"
                >
                    <div class="flex items-start gap-4">
                        <!-- Avatar du client -->
                        <div class="relative flex-shrink-0">
                            <x-avatar :user="$conversation->user" size="lg" />
                            @if($messagesNonLus > 0)
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                    {{ $messagesNonLus }}
                                </span>
                            @endif
                        </div>

                        <!-- Contenu -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-slate-900 dark:text-white truncate">
                                    {{ $conversation->user->name }}
                                </h3>
                                @if($dernierMessage)
                                    <span class="text-xs text-slate-500 dark:text-slate-400 flex-shrink-0 ml-2">
                                        {{ $dernierMessage->created_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                                {{ $conversation->user->email }}
                            </p>
                            @if($dernierMessage)
                                <p class="text-sm text-slate-600 dark:text-slate-400 truncate {{ $messagesNonLus > 0 ? 'font-medium' : '' }}">
                                    @if($dernierMessage->user_id !== $conversation->user_id)
                                        <span class="text-slate-400 dark:text-slate-500">Vous : </span>
                                    @endif
                                    {{ Str::limit($dernierMessage->contenu, 60) }}
                                </p>
                            @endif
                        </div>

                        <!-- Flèche -->
                        <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucune conversation</h3>
            <p class="text-slate-600 dark:text-slate-400">
                Vous n'avez pas encore de conversations avec vos clients.
            </p>
        </div>
    @endif
</div>
