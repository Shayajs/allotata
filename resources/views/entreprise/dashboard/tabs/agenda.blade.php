
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-green-800 dark:text-green-300 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Navigation des sous-onglets -->
            <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
                <nav class="flex flex-col sm:flex-row gap-2 sm:gap-0 -mb-px" aria-label="Sous-onglets Agenda">
                    <button 
                        onclick="showAgendaSubTab('reservations')"
                        id="agenda-subtab-reservations"
                        class="agenda-subtab-btn flex-1 sm:flex-initial px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-500 dark:border-green-400"
                        data-subtab="reservations"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Réservations</span>
                        </span>
                    </button>
                    <button 
                        onclick="showAgendaSubTab('parametres')"
                        id="agenda-subtab-parametres"
                        class="agenda-subtab-btn flex-1 sm:flex-initial px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-transparent"
                        data-subtab="parametres"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Paramètres</span>
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Contenu de l'onglet Réservations -->
            <div id="agenda-subtab-content-reservations" class="agenda-subtab-content">
                <!-- Calendrier Tailwind -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-8">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-4 sm:px-6 py-4">
                    <div class="flex items-center justify-between">
                        <button type="button" id="prev-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div class="text-center">
                            <h2 class="text-lg sm:text-xl font-bold text-white" id="calendar-title">Chargement...</h2>
                            <p class="text-xs sm:text-sm text-white/80" id="calendar-subtitle"></p>
                        </div>
                        <button type="button" id="next-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Légende -->
                <div class="px-4 sm:px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 sm:gap-4 text-xs sm:text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">En attente</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Confirmée</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Terminée</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Annulée</span>
                    </div>
                </div>

                <!-- Corps -->
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-7 gap-2 mb-4" id="calendar-headers"></div>
                    <div class="grid grid-cols-7 gap-2" id="calendar-grid"></div>
                </div>

                <!-- Footer -->
                <div class="px-4 sm:px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-center gap-3">
                    <button type="button" id="today-btn" class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors">
                        Aujourd'hui
                    </button>
                </div>
            </div>
            </div>

            <!-- Contenu de l'onglet Paramètres -->
            <div id="agenda-subtab-content-parametres" class="agenda-subtab-content hidden">
                <!-- Section Horaires d'ouverture -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-6 sm:mb-8">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-4 sm:mb-6 flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                        Horaires d'ouverture
                    </h2>
                    
                    <form action="{{ route('agenda.horaires.store', $entreprise->slug) }}" method="POST">
                        @csrf
                        <div class="space-y-3">
                            @php
                                $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                            @endphp
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $horairesJour = $horaires->where('jour_semaine', $i)->sortBy('ordre_plage');
                                    $isFerme = $horairesJour->isEmpty();
                                @endphp
                                <div class="jour-horaires p-3 sm:p-4 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" data-jour="{{ $i }}">
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 mb-3">
                                        <div class="w-24 sm:w-28 flex-shrink-0">
                                            <span class="font-semibold text-sm sm:text-base text-slate-900 dark:text-white">{{ $jours[$i] }}</span>
                                        </div>
                                        <label class="flex items-center gap-2 cursor-pointer flex-shrink-0">
                                            <input 
                                                type="checkbox" 
                                                name="horaires[{{ $i }}][ferme]" 
                                                value="1"
                                                class="horaire-ferme-checkbox w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500"
                                                data-index="{{ $i }}"
                                                {{ $isFerme ? 'checked' : '' }}
                                            >
                                            <span class="text-xs sm:text-sm text-red-600 dark:text-red-400 font-medium">Fermé</span>
                                        </label>
                                        <input type="hidden" name="horaires[{{ $i }}][jour_semaine]" value="{{ $i }}">
                                        @if($isFerme)
                                            <input type="hidden" name="horaires[{{ $i }}][plages]" value="">
                                        @endif
                                        <button 
                                            type="button" 
                                            class="ml-auto px-2 sm:px-3 py-1 text-xs sm:text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors add-plage-btn"
                                            data-jour="{{ $i }}"
                                            style="{{ $isFerme ? 'display: none;' : '' }}"
                                        >
                                            + Ajouter une plage
                                        </button>
                                    </div>
                                    <div class="plages-container" data-jour="{{ $i }}">
                                        @if($isFerme)
                                            <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 italic">Jour fermé</div>
                                        @else
                                            @foreach($horairesJour as $plage)
                                                <div class="plage-item flex flex-wrap items-center gap-2 sm:gap-3 mb-2">
                                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                                        <span class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 flex-shrink-0">De</span>
                                                        <input 
                                                            type="time" 
                                                            name="horaires[{{ $i }}][plages][{{ $loop->index }}][heure_ouverture]" 
                                                            value="{{ $plage->heure_ouverture ? \Carbon\Carbon::parse($plage->heure_ouverture)->format('H:i') : '' }}"
                                                            class="px-2 sm:px-3 py-1.5 sm:py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm sm:text-base flex-1 min-w-0"
                                                            required
                                                        >
                                                        <span class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 flex-shrink-0">à</span>
                                                        <input 
                                                            type="time" 
                                                            name="horaires[{{ $i }}][plages][{{ $loop->index }}][heure_fermeture]" 
                                                            value="{{ $plage->heure_fermeture ? \Carbon\Carbon::parse($plage->heure_fermeture)->format('H:i') : '' }}"
                                                            class="px-2 sm:px-3 py-1.5 sm:py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm sm:text-base flex-1 min-w-0"
                                                            required
                                                        >
                                                    </div>
                                                    <button 
                                                        type="button" 
                                                        class="px-2 sm:px-3 py-1.5 sm:py-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors remove-plage-btn flex-shrink-0"
                                                        title="Supprimer cette plage"
                                                    >
                                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <div class="mt-4 sm:mt-6">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                                Enregistrer les horaires
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section Jours exceptionnels -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 sm:mb-6 gap-3">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            Jours exceptionnels
                        </h2>
                        <button 
                            onclick="document.getElementById('modal-jour-exceptionnel').classList.remove('hidden')"
                            class="w-full sm:w-auto px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg text-sm sm:text-base"
                        >
                            + Ajouter
                        </button>
                    </div>
                    
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mb-4">
                        Les jours exceptionnels sont prioritaires sur les horaires réguliers.
                    </p>

                    @php
                        $joursExceptionnels = $entreprise->horairesOuverture()
                            ->where('est_exceptionnel', true)
                            ->where('date_exception', '>=', now()->format('Y-m-d'))
                            ->orderBy('date_exception')
                            ->get();
                    @endphp

                    @if($joursExceptionnels->count() > 0)
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($joursExceptionnels as $horaire)
                                <div class="p-3 sm:p-4 border border-slate-200 dark:border-slate-700 rounded-xl flex items-center justify-between bg-slate-50 dark:bg-slate-700/50">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-sm sm:text-base text-slate-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($horaire->date_exception)->locale('fr')->isoFormat('dddd D MMM') }}
                                        </p>
                                        <p class="text-xs sm:text-sm {{ $horaire->heure_ouverture ? 'text-slate-600 dark:text-slate-400' : 'text-red-600 dark:text-red-400' }}">
                                            @if($horaire->heure_ouverture && $horaire->heure_fermeture)
                                                {{ \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') }} - 
                                                {{ \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') }}
                                            @else
                                                Fermé
                                            @endif
                                        </p>
                                    </div>
                                    <form action="{{ route('agenda.jour-exceptionnel.delete', [$entreprise->slug, $horaire->id]) }}" method="POST" onsubmit="return confirm('Supprimer ce jour ?');" class="ml-2 flex-shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition">
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-sm sm:text-base">Aucun jour exceptionnel configuré</p>
                        </div>
                    @endif
                </div>
            </div>


            <!-- Modal pour ajouter un jour exceptionnel -->
            <div id="modal-jour-exceptionnel" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-md w-full">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Jour exceptionnel</h3>
                        <button onclick="document.getElementById('modal-jour-exceptionnel').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form action="{{ route('agenda.jour-exceptionnel.store', $entreprise->slug) }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date *</label>
                                <input 
                                    type="date" 
                                    name="date_exception"
                                    required
                                    min="{{ now()->format('Y-m-d') }}"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>
                            <label class="flex items-center gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 cursor-pointer hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                                <input 
                                    type="checkbox" 
                                    name="est_ferme"
                                    id="est_ferme"
                                    value="1"
                                    checked
                                    onchange="toggleHorairesExceptionnel()"
                                    class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500"
                                >
                                <span class="text-sm font-medium text-red-700 dark:text-red-400">Fermé ce jour</span>
                            </label>
                            <div id="horaires-exceptionnel" class="grid grid-cols-2 gap-4 opacity-50">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Ouverture</label>
                                    <input 
                                        type="time" 
                                        name="heure_ouverture"
                                        disabled
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Fermeture</label>
                                    <input 
                                        type="time" 
                                        name="heure_fermeture"
                                        disabled
                                        class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button type="button" onclick="document.getElementById('modal-jour-exceptionnel').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                                Annuler
                            </button>
                            <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                                Ajouter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal détails réservation -->
            <div id="modal-reservation" class="hidden fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
                <div class="modal-content rounded-2xl shadow-2xl p-6 max-w-md w-full">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Détails de la réservation</h3>
                        <button onclick="document.getElementById('modal-reservation').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="reservation-details" class="space-y-4"></div>
                </div>
            </div>

<script>
    // Gestion des sous-onglets Agenda
    function showAgendaSubTab(subtabName) {
        // Masquer tous les contenus de sous-onglets
        document.querySelectorAll('.agenda-subtab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Réinitialiser tous les boutons
        document.querySelectorAll('.agenda-subtab-btn').forEach(button => {
            button.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400', 'border-green-500', 'dark:border-green-400');
            button.classList.add('text-slate-500', 'dark:text-slate-400', 'border-transparent');
        });

        // Afficher le contenu sélectionné
        const subtabContent = document.getElementById('agenda-subtab-content-' + subtabName);
        if (subtabContent) {
            subtabContent.classList.remove('hidden');
        }

        // Activer le bouton sélectionné
        const activeButton = document.getElementById('agenda-subtab-' + subtabName);
        if (activeButton) {
            activeButton.classList.remove('text-slate-500', 'dark:text-slate-400', 'border-transparent');
            activeButton.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400', 'border-green-500', 'dark:border-green-400');
        }

        // Initialiser le calendrier si on affiche l'onglet réservations
        if (subtabName === 'reservations' && typeof renderCalendar === 'function') {
            setTimeout(() => {
                renderCalendar();
            }, 100);
        }
    }

    // Initialiser avec l'onglet réservations par défaut
    showAgendaSubTab('reservations');

    // Données PHP
    const horaires = @json($horaires);
    const reservationsUrl = '{{ route("agenda.reservations", $entreprise->slug, false) }}';
    
    // État
    let currentWeekOffset = 0;
    let reservations = [];
    
    // Éléments
    const calendarHeaders = document.getElementById('calendar-headers');
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarTitle = document.getElementById('calendar-title');
    const calendarSubtitle = document.getElementById('calendar-subtitle');
    
    // Noms
    const joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    const mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    
    // Horaires par jour (tableau de plages pour chaque jour)
    const horairesParJour = {};
    horaires.forEach(h => {
        if (!h.est_exceptionnel) {
            if (!horairesParJour[h.jour_semaine]) {
                horairesParJour[h.jour_semaine] = [];
            }
            horairesParJour[h.jour_semaine].push({
                ouverture: h.heure_ouverture,
                fermeture: h.heure_fermeture
            });
        }
    });
    
    // Couleurs
    const statutColors = {
        'en_attente': 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700',
        'confirmee': 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-300 dark:border-green-700',
        'terminee': 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-300 dark:border-blue-700',
        'annulee': 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-300 dark:border-red-700'
    };
    
    const statutLabels = {
        'en_attente': '⏳ En attente',
        'confirmee': '✓ Confirmée',
        'terminee': '✓ Terminée',
        'annulee': '✗ Annulée'
    };
    
    async function loadReservations() {
        try {
            const response = await fetch(reservationsUrl);
            reservations = await response.json();
        } catch (error) {
            reservations = [];
        }
    }
    
    function formatDateISO(date) {
        const d = new Date(date);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    
    function getReservationsForDay(dateStr) {
        return reservations.filter(r => formatDateISO(new Date(r.start)) === dateStr).sort((a, b) => new Date(a.start) - new Date(b.start));
    }
    
    async function renderCalendar() {
        await loadReservations();
        
        const today = new Date();
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay() + 1 + (currentWeekOffset * 7));
        
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        
        if (startOfWeek.getMonth() === endOfWeek.getMonth()) {
            calendarTitle.textContent = `${startOfWeek.getDate()} - ${endOfWeek.getDate()} ${mois[startOfWeek.getMonth()]}`;
        } else {
            calendarTitle.textContent = `${startOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} - ${endOfWeek.getDate()} ${mois[endOfWeek.getMonth()]}`;
        }
        calendarSubtitle.textContent = currentWeekOffset === 0 ? 'Cette semaine' : '';
        
        calendarHeaders.innerHTML = '';
        for (let i = 0; i < 7; i++) {
            const date = new Date(startOfWeek);
            date.setDate(startOfWeek.getDate() + i);
            const isToday = formatDateISO(date) === formatDateISO(today);
            
            const header = document.createElement('div');
            header.className = `text-center p-2 rounded-xl ${isToday ? 'bg-green-100 dark:bg-green-900/30' : ''}`;
            header.innerHTML = `
                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">${joursSemaine[(date.getDay() + 7) % 7]}</div>
                <div class="text-lg font-bold ${isToday ? 'text-green-600 dark:text-green-400' : 'text-slate-900 dark:text-white'}">${date.getDate()}</div>
            `;
            calendarHeaders.appendChild(header);
        }
        
        calendarGrid.innerHTML = '';
        for (let i = 0; i < 7; i++) {
            const date = new Date(startOfWeek);
            date.setDate(startOfWeek.getDate() + i);
            const dateStr = formatDateISO(date);
            const jourSemaine = date.getDay();
            const plagesJour = horairesParJour[jourSemaine] || [];
            const dayReservations = getReservationsForDay(dateStr);
            
            const dayColumn = document.createElement('div');
            dayColumn.className = 'space-y-1 min-h-[120px]';
            
            if (!plagesJour || plagesJour.length === 0 || !plagesJour.some(p => p.ouverture)) {
                dayColumn.innerHTML = `<div class="h-full min-h-[120px] flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700/50 border-2 border-dashed border-slate-200 dark:border-slate-600"><span class="text-xs text-slate-400 font-medium">Fermé</span></div>`;
            } else if (dayReservations.length === 0) {
                dayColumn.innerHTML = `<div class="h-full min-h-[120px] flex items-center justify-center rounded-xl bg-green-50 dark:bg-green-900/10 border-2 border-dashed border-green-200 dark:border-green-800"><span class="text-xs text-green-500 font-medium">Libre</span></div>`;
            } else {
                dayReservations.forEach(res => {
                    const time = new Date(res.start).toTimeString().substring(0, 5);
                    const colorClass = statutColors[res.extendedProps?.statut] || statutColors['en_attente'];
                    
                    const resEl = document.createElement('button');
                    resEl.type = 'button';
                    resEl.className = `w-full p-2 text-left rounded-lg border-l-4 ${colorClass} hover:shadow-md transition-all cursor-pointer`;
                    resEl.innerHTML = `<div class="text-xs font-bold">${time}</div><div class="text-xs truncate">${res.title}</div>`;
                    resEl.onclick = () => showReservationDetails(res);
                    dayColumn.appendChild(resEl);
                });
            }
            
            calendarGrid.appendChild(dayColumn);
        }
    }
    
    function showReservationDetails(res) {
        const props = res.extendedProps || {};
        const startTime = new Date(res.start);
        // Utiliser le hash complet pour les liens web (emails, etc.)
        // L'alias court est uniquement pour les SMS
        const reservationHash = res.extendedProps?.hash || res.hash;
        const reservationUrl = reservationHash ? `/r/${reservationHash}` : `/r/${res.id}`;
        
        document.getElementById('reservation-details').innerHTML = `
            <div class="p-4 rounded-xl ${statutColors[props.statut] || 'bg-slate-100 dark:bg-slate-700'}">
                <span class="text-sm font-bold">${statutLabels[props.statut] || props.statut}</span>
            </div>
            <div class="space-y-3">
                <div><span class="text-xs text-slate-500 uppercase">Service</span><p class="font-semibold text-slate-900 dark:text-white">${props.type_service || res.title}</p></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="text-xs text-slate-500 uppercase">Date</span><p class="font-semibold text-slate-900 dark:text-white">${startTime.toLocaleDateString('fr-FR')}</p></div>
                    <div><span class="text-xs text-slate-500 uppercase">Heure</span><p class="font-semibold text-slate-900 dark:text-white">${startTime.toTimeString().substring(0, 5)}</p></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="text-xs text-slate-500 uppercase">Durée</span><p class="font-semibold text-slate-900 dark:text-white">${props.duree || '-'} min</p></div>
                    <div><span class="text-xs text-slate-500 uppercase">Prix</span><p class="font-semibold text-green-600">${props.prix || '-'} €</p></div>
                </div>
                <div><span class="text-xs text-slate-500 uppercase">Client</span><p class="font-semibold text-slate-900 dark:text-white">${props.client || '-'}</p><p class="text-sm text-slate-600">${props.client_email || ''}</p>${props.telephone ? `<p class="text-sm text-slate-600">📞 ${props.telephone}</p>` : ''}</div>
                <div><span class="text-xs text-slate-500 uppercase">Payé</span><p class="font-semibold ${props.est_paye ? 'text-green-600' : 'text-red-600'}">${props.est_paye ? '✓ Oui' : '✗ Non'}</p></div>
                ${props.lieu ? `<div><span class="text-xs text-slate-500 uppercase">Lieu</span><p class="text-slate-900 dark:text-white">${props.lieu}</p></div>` : ''}
                ${props.notes ? `<div><span class="text-xs text-slate-500 uppercase">Notes</span><p class="text-slate-600">${props.notes}</p></div>` : ''}
                <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                    <a href="${reservationUrl}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Voir côté client
                    </a>
                </div>
            </div>
        `;
        
        document.getElementById('modal-reservation').classList.remove('hidden');
    }
    
    document.getElementById('prev-week')?.addEventListener('click', () => { currentWeekOffset--; renderCalendar(); });
    document.getElementById('next-week')?.addEventListener('click', () => { currentWeekOffset++; renderCalendar(); });
    document.getElementById('today-btn')?.addEventListener('click', () => { currentWeekOffset = 0; renderCalendar(); });
    
    // Gestion des horaires - Checkbox fermé
    document.querySelectorAll('.horaire-ferme-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const jourIndex = this.dataset.index;
            const jourContainer = document.querySelector(`.jour-horaires[data-jour="${jourIndex}"]`);
            const plagesContainer = jourContainer.querySelector('.plages-container');
            const addPlageBtn = jourContainer.querySelector('.add-plage-btn');
            
            if (this.checked) {
                // Jour fermé : vider les plages et les cacher
                plagesContainer.innerHTML = '<div class="text-sm text-slate-500 dark:text-slate-400 italic">Jour fermé</div>';
                if (addPlageBtn) addPlageBtn.style.display = 'none';
            } else {
                // Jour ouvert : afficher le bouton d'ajout et ajouter une plage par défaut si vide
                if (addPlageBtn) addPlageBtn.style.display = 'block';
                if (plagesContainer.querySelectorAll('.plage-item').length === 0) {
                    addPlage(jourIndex);
                }
            }
        });
    });
    
    // Ajouter une plage horaire
    function addPlage(jourIndex) {
        const plagesContainer = document.querySelector(`.plages-container[data-jour="${jourIndex}"]`);
        if (!plagesContainer) return;
        
        // Compter les plages existantes pour l'index
        const plageCount = plagesContainer.querySelectorAll('.plage-item').length;
        const plageIndex = plageCount;
        
        // Supprimer le message "Jour fermé" s'il existe
        const fermeMsg = plagesContainer.querySelector('.text-slate-500');
        if (fermeMsg && fermeMsg.textContent.includes('fermé')) {
            fermeMsg.remove();
        }
        
        const plageHtml = `
            <div class="plage-item flex items-center gap-3 mb-2">
                <div class="flex items-center gap-2 flex-1">
                    <span class="text-sm text-slate-500 dark:text-slate-400">De</span>
                    <input 
                        type="time" 
                        name="horaires[${jourIndex}][plages][${plageIndex}][heure_ouverture]" 
                        class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        required
                    >
                    <span class="text-sm text-slate-500 dark:text-slate-400">à</span>
                    <input 
                        type="time" 
                        name="horaires[${jourIndex}][plages][${plageIndex}][heure_fermeture]" 
                        class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        required
                    >
                </div>
                <button 
                    type="button" 
                    class="px-3 py-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors remove-plage-btn"
                    title="Supprimer cette plage"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        `;
        
        plagesContainer.insertAdjacentHTML('beforeend', plageHtml);
        
        // Réindexer les inputs pour éviter les trous dans les indices
        reindexPlages(jourIndex);
    }
    
    // Supprimer une plage horaire
    function removePlage(button) {
        const plageItem = button.closest('.plage-item');
        if (plageItem) {
            const jourContainer = plageItem.closest('.jour-horaires');
            const jourIndex = jourContainer.dataset.jour;
            plageItem.remove();
            reindexPlages(jourIndex);
            
            // Si plus de plages, afficher le message "Jour fermé"
            const plagesContainer = jourContainer.querySelector('.plages-container');
            if (plagesContainer.querySelectorAll('.plage-item').length === 0) {
                plagesContainer.innerHTML = '<div class="text-sm text-slate-500 dark:text-slate-400 italic">Jour fermé</div>';
                const checkbox = jourContainer.querySelector('.horaire-ferme-checkbox');
                if (checkbox) checkbox.checked = true;
            }
        }
    }
    
    // Réindexer les plages pour avoir des indices consécutifs (0, 1, 2, ...)
    function reindexPlages(jourIndex) {
        const plagesContainer = document.querySelector(`.plages-container[data-jour="${jourIndex}"]`);
        if (!plagesContainer) return;
        
        const plages = plagesContainer.querySelectorAll('.plage-item');
        plages.forEach((plage, index) => {
            const ouvertureInput = plage.querySelector('input[name*="[heure_ouverture]"]');
            const fermetureInput = plage.querySelector('input[name*="[heure_fermeture]"]');
            
            if (ouvertureInput) {
                ouvertureInput.name = `horaires[${jourIndex}][plages][${index}][heure_ouverture]`;
            }
            if (fermetureInput) {
                fermetureInput.name = `horaires[${jourIndex}][plages][${index}][heure_fermeture]`;
            }
        });
    }
    
    // Event listeners pour ajouter/supprimer des plages
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-plage-btn')) {
            const btn = e.target.closest('.add-plage-btn');
            const jourIndex = btn.dataset.jour;
            addPlage(jourIndex);
        }
        
        if (e.target.closest('.remove-plage-btn')) {
            const btn = e.target.closest('.remove-plage-btn');
            removePlage(btn);
        }
    });
    
    // Initialiser : ajouter une plage pour les jours ouverts qui n'en ont pas
    document.querySelectorAll('.jour-horaires').forEach(jourContainer => {
        const checkbox = jourContainer.querySelector('.horaire-ferme-checkbox');
        const plagesContainer = jourContainer.querySelector('.plages-container');
        if (!checkbox.checked && plagesContainer.querySelectorAll('.plage-item').length === 0) {
            const jourIndex = jourContainer.dataset.jour;
            addPlage(jourIndex);
        }
    });
    
    // Avant la soumission du formulaire, s'assurer que tous les jours ont un champ plages
    document.querySelector('form[action*="horaires.store"]')?.addEventListener('submit', function(e) {
        document.querySelectorAll('.jour-horaires').forEach(jourContainer => {
            const jourIndex = jourContainer.dataset.jour;
            const checkbox = jourContainer.querySelector('.horaire-ferme-checkbox');
            const hasPlagesInput = jourContainer.querySelector('input[name*="[plages]"]');
            
            // Si le jour est fermé et qu'il n'y a pas de champ plages, en ajouter un pour créer un tableau vide
            if (checkbox.checked && !hasPlagesInput) {
                // Créer un input avec un nom qui indique un tableau vide
                // Laravel interprétera cela comme un tableau vide []
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `horaires[${jourIndex}][plages][]`;
                hiddenInput.value = '';
                hiddenInput.style.display = 'none';
                jourContainer.appendChild(hiddenInput);
            }
        });
    });
    
    document.getElementById('modal-reservation')?.addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); });
    
    // Modal jour exceptionnel - Toggle horaires
    function toggleHorairesExceptionnel() {
        const estFerme = document.getElementById('est_ferme').checked;
        const horairesDiv = document.getElementById('horaires-exceptionnel');
        const inputs = horairesDiv.querySelectorAll('input[type="time"]');
        
        if (estFerme) {
            horairesDiv.style.opacity = '0.5';
            inputs.forEach(input => {
                input.disabled = true;
                input.value = '';
            });
        } else {
            horairesDiv.style.opacity = '1';
            inputs.forEach(input => input.disabled = false);
        }
    }
    
    // Fermer le modal jour exceptionnel en cliquant dehors
    document.getElementById('modal-jour-exceptionnel')?.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
    
    // Ne pas initialiser le calendrier au chargement, seulement quand on clique sur l'onglet Réservations
    // renderCalendar();
</script>
