{{-- Onglet Entreprises --}}
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
            @if($user->est_gerant)
                Mes Entreprises
            @else
                Créer mon entreprise
            @endif
        </h2>
        <div class="flex gap-3">
            @if($entreprisesAutres && $entreprisesAutres->count() > 0)
                <a href="{{ route('dashboard.entreprises-autres') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all text-sm">
                    Entreprises autres
                </a>
            @endif
            <a href="{{ route('entreprise.create') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all text-sm">
                + {{ $user->est_gerant ? 'Ajouter' : 'Créer' }}
            </a>
        </div>
    </div>

    @if($entreprises->count() > 0)
        <div class="space-y-6">
            @foreach($entreprises as $entreprise)
                @if($entreprise->trashed())
                    <div class="group relative p-6 border border-slate-200 dark:border-slate-700 rounded-xl transition-all cursor-pointer">
                        {{-- Contenu normal de la carte (visible par défaut) --}}
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 group-hover:opacity-30 lg:group-hover:grayscale transition-all duration-300">
                            <div class="flex items-start gap-4 flex-1">
                                @if($entreprise->logo)
                                    <img src="{{ asset('media/' . $entreprise->logo) }}" alt="Logo {{ $entreprise->nom }}" class="w-16 h-16 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 flex-shrink-0">
                                @else
                                    <div class="w-16 h-16 rounded-xl bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-2xl font-bold">{{ strtoupper(substr($entreprise->nom, 0, 1)) }}</div>
                                @endif
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">{{ $entreprise->nom }}</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                                        {{ $entreprise->type_activite }}
                                        @if($entreprise->ville) • {{ $entreprise->ville }} @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Overlay au hover (visible seulement sur PC au hover) --}}
                        <div class="hidden lg:flex absolute inset-0 items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 bg-white/80 dark:bg-slate-900/80 rounded-xl">
                            <div class="text-center p-4">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 rounded-full text-sm font-semibold mb-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                    Entreprise archivée
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Suppression définitive dans <strong>{{ $entreprise->daysUntilPermanentDeletion() }} jours</strong></p>
                                <form action="{{ route('settings.entreprise.restore', $entreprise->slug) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition shadow-sm inline-flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Restaurer
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        {{-- Bandeau mobile (toujours visible sur mobile) --}}
                        <div class="lg:hidden mt-4 pt-4 border-t border-red-200 dark:border-red-800">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 rounded-full text-xs font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                        Archivée
                                    </span>
                                    <p class="text-xs text-slate-500 mt-1">Suppression dans {{ $entreprise->daysUntilPermanentDeletion() }}j</p>
                                </div>
                                <form action="{{ route('settings.entreprise.restore', $entreprise->slug) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Restaurer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-green-500 dark:hover:border-green-500 transition-all">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-start gap-4 flex-1">
                                        @if($entreprise->logo)
                                            <img src="{{ asset('media/' . $entreprise->logo) }}" alt="Logo {{ $entreprise->nom }}" class="w-16 h-16 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 flex-shrink-0">
                                        @else
                                            <div class="w-16 h-16 rounded-xl bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-2xl font-bold">{{ strtoupper(substr($entreprise->nom, 0, 1)) }}</div>
                                        @endif
                                        <div class="flex-1">
                                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">{{ $entreprise->nom }}</h3>
                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                                                {{ $entreprise->type_activite }}
                                                @if($entreprise->ville) • {{ $entreprise->ville }} @endif
                                            </p>
                                            @if($entreprise->est_verifiee)
                                                <span class="inline-block px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">✓ Vérifiée</span>
                                            @else
                                                <span class="inline-block px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">⏳ En attente</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if(isset($entreprise->stats))
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                        <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Réservations</p>
                                            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $entreprise->stats['total_reservations'] }}</p>
                                        </div>
                                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Revenu total</p>
                                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($entreprise->stats['revenu_total'], 2, ',', ' ') }} €</p>
                                        </div>
                                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Revenu payé</p>
                                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($entreprise->stats['revenu_paye'], 2, ',', ' ') }} €</p>
                                        </div>
                                        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Ce mois</p>
                                            <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $entreprise->stats['reservations_ce_mois'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-center bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white rounded-lg transition-all shadow-sm hover:shadow-md relative">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Gérer
                                    @if(isset($entreprise->stats['reservations_en_attente']) && $entreprise->stats['reservations_en_attente'] > 0)
                                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-yellow-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse">
                                            {{ $entreprise->stats['reservations_en_attente'] }}
                                        </span>
                                    @endif
                                </a>
                                <a href="{{ route('public.entreprise', $entreprise->slug) }}" target="_blank" class="px-4 py-2 text-sm font-medium text-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Voir
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune entreprise</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                @if($user->est_gerant)
                    Ajoutez une nouvelle entreprise pour commencer.
                @else
                    Créez votre première entreprise pour proposer vos services.
                @endif
            </p>
            <div class="mt-6">
                <a href="{{ route('entreprise.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                    + Créer mon entreprise
                </a>
            </div>
        </div>
    @endif
</div>
