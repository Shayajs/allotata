<div>
    <p class="text-slate-600 dark:text-slate-400 mb-4">
        <a href="{{ route('messagerie.index') }}" class="text-green-600 dark:text-green-400 hover:underline">
            ← Retour à la liste des conversations
        </a>
    </p>
    <!-- En-tête de conversation amélioré -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 p-6 mb-6">
                <div class="flex items-center gap-4">
                    @if(isset($isGerant) && $isGerant)
                        <div class="relative">
                            <x-avatar :user="$conversation->user" size="2xl" class="shadow-lg" />
                            <div class="absolute bottom-0 right-0 w-5 h-5 bg-green-500 rounded-full border-3 border-white dark:border-slate-800"></div>
                        </div>
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">
                                {{ $conversation->user->name }}
                            </h1>
                            <p class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ $conversation->user->email }}
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                {{ $entreprise->nom }}
                            </p>
                        </div>
                    @else
                        <div class="relative">
                            @if($entreprise->logo)
                                <img 
                                    src="{{ asset('media/' . $entreprise->logo) }}" 
                                    alt="{{ $entreprise->nom }}"
                                    class="w-20 h-20 rounded-2xl object-cover border-3 border-slate-200 dark:border-slate-700 shadow-lg"
                                >
                            @else
                                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-2xl border-3 border-slate-200 dark:border-slate-700 shadow-lg">
                                    {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                </div>
                            @endif
                            <div class="absolute bottom-0 right-0 w-5 h-5 bg-green-500 rounded-full border-3 border-white dark:border-slate-800"></div>
                        </div>
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">
                                {{ $entreprise->nom }}
                            </h1>
                            <p class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ $entreprise->type_activite }}
                            </p>
                            @if($entreprise->afficher_nom_gerant && $entreprise->user)
                                <p class="text-sm text-slate-500 dark:text-slate-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Gérée par {{ $entreprise->user->name }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 border border-green-200 dark:border-green-800 rounded-xl shadow-sm">
                    <p class="text-green-800 dark:text-green-400 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('success') }}
                    </p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 border border-red-200 dark:border-red-800 rounded-xl shadow-sm">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400 font-medium flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>
            @endif

            <!-- Récapitulatif de proposition de RDV active amélioré -->
            @if(isset($propositionActive) && $propositionActive)
                <div class="bg-gradient-to-br from-green-50 via-orange-50 to-green-50 dark:from-green-900/20 dark:via-orange-900/20 dark:to-green-900/20 border-2 border-green-300 dark:border-green-700 rounded-2xl p-6 mb-6 shadow-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white text-2xl shadow-md">
                            📅
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Proposition de rendez-vous</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Date</p>
                            <p class="font-bold text-lg text-slate-900 dark:text-white">{{ $propositionActive->date_rdv->format('d/m/Y') }}</p>
                        </div>
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Heure</p>
                            <p class="font-bold text-lg text-slate-900 dark:text-white">{{ \Carbon\Carbon::parse($propositionActive->heure_debut)->format('H:i') }} - {{ \Carbon\Carbon::parse($propositionActive->heure_fin)->format('H:i') }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">({{ $propositionActive->duree_minutes }} min)</p>
                        </div>
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Prix proposé</p>
                            <p class="font-bold text-lg text-green-600 dark:text-green-400">{{ number_format($propositionActive->prix_propose, 2, ',', ' ') }} €</p>
                        </div>
                        @if($propositionActive->prix_final && $propositionActive->prix_final != $propositionActive->prix_propose)
                            <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Prix négocié</p>
                                <p class="font-bold text-lg text-orange-600 dark:text-orange-400">{{ number_format($propositionActive->prix_final, 2, ',', ' ') }} €</p>
                            </div>
                        @endif
                        @if($propositionActive->lieu)
                            <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4 md:col-span-2">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Lieu</p>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $propositionActive->lieu }}</p>
                            </div>
                        @endif
                    </div>
                    @if($propositionActive->notes)
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4 mb-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Notes</p>
                            <p class="text-slate-900 dark:text-white">{{ $propositionActive->notes }}</p>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-3">
                        @if($propositionActive->statut === 'proposee' || $propositionActive->statut === 'negociee')
                            @if(!isset($isGerant) || !$isGerant)
                                <!-- Actions client -->
                                @if($propositionActive->peutEtreNegociee())
                                    <button 
                                        onclick="document.getElementById('modal-negocier-{{ $propositionActive->id }}').classList.remove('hidden')"
                                        class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center gap-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Négocier le prix
                                    </button>
                                @endif
                                <form action="{{ route('messagerie.accepter-proposition', [$entreprise->slug, $propositionActive->id]) }}" method="POST" class="inline">
                                    @csrf
</div>
