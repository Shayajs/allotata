{{-- Onglet Notifications --}}
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h2>
        <a href="{{ route('notifications.index') }}" class="px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
            Voir tout →
        </a>
    </div>

    @php
        $userNotifications = \App\Models\Notification::where('user_id', $user->id)->latest()->take(15)->get();
        $nombreNonLues = $user->nombre_notifications_non_lues;
    @endphp

    @if($nombreNonLues > 0)
        <div class="mb-4 flex items-center justify-end">
            <form action="{{ route('notifications.marquer-toutes-lues') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition text-sm font-medium">
                    Tout marquer comme lu
                </button>
            </form>
        </div>
    @endif

    @if($userNotifications->count() > 0)
        <div class="space-y-3">
            @foreach($userNotifications as $notification)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 hover:border-green-500 dark:hover:border-green-500 transition-all {{ !$notification->est_lue ? 'ring-2 ring-green-500/20' : '' }}">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        <div class="flex items-start gap-4 flex-1">
                            <div class="flex-shrink-0">
                                @if($notification->type === 'reservation')
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                        <span class="text-xl sm:text-2xl">📅</span>
                                    </div>
                                @elseif($notification->type === 'paiement')
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                        <span class="text-xl sm:text-2xl">💳</span>
                                    </div>
                                @elseif($notification->type === 'rappel')
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                        <span class="text-xl sm:text-2xl">⏰</span>
                                    </div>
                                @else
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                        <span class="text-xl sm:text-2xl">📢</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ $notification->titre }}
                                    </h3>
                                    @if(!$notification->est_lue)
                                        <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                            Nouveau
                                        </span>
                                    @endif
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 mb-2 text-sm sm:text-base whitespace-pre-line">
                                    {{ $notification->message }}
                                </p>
                                <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ $notification->created_at->format('d/m/Y à H:i') }}</span>
                                    @if($notification->est_lue && $notification->lue_at)
                                        <span class="hidden sm:inline">Lu le {{ $notification->lue_at->format('d/m/Y à H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- Actions - empilées sur mobile -->
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-200 dark:border-slate-700">
                            @if($notification->lien)
                                <a href="{{ $notification->lien }}" class="flex-1 sm:flex-none text-center px-3 py-2 text-sm bg-green-100 dark:bg-green-900/30 hover:bg-green-200 dark:hover:bg-green-900/50 text-green-800 dark:text-green-400 rounded-lg transition">
                                    Voir →
                                </a>
                            @endif
                            @if(!$notification->est_lue)
                                <form action="{{ route('notifications.marquer-lue', $notification->id) }}" method="POST" class="flex-1 sm:flex-none">
                                    @csrf
                                    <button type="submit" class="w-full px-3 py-2 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition">
                                        ✓ Lu
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Supprimer cette notification ?');" class="flex-1 sm:flex-none">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-2 text-sm bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-6 text-center">
            <a href="{{ route('notifications.index') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                Voir toutes les notifications →
            </a>
        </div>
    @else
        <div class="text-center py-12 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune notification</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Vous êtes à jour ! Aucune notification pour le moment.
            </p>
        </div>
    @endif
</div>
