<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Gestion de l'agenda - {{ $entreprise->nom }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            ← Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent mb-2">
                    Gestion de l'agenda
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    {{ $entreprise->nom }} • Configurez vos horaires et vos services
                </p>
            </div>

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

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-800 dark:text-red-300 font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Calendrier Tailwind pour le gérant -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-8">
                <!-- Header du calendrier -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <button type="button" id="prev-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div class="text-center">
                            <h2 class="text-xl font-bold text-white" id="calendar-title">Chargement...</h2>
                            <p class="text-sm text-white/80" id="calendar-subtitle"></p>
                        </div>
                        <button type="button" id="next-week" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Légende -->
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-4 text-sm">
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

                <!-- Corps du calendrier -->
                <div class="p-4 sm:p-6">
                    <!-- En-têtes des jours -->
                    <div class="grid grid-cols-7 gap-2 mb-4" id="calendar-headers"></div>

                    <!-- Grille des réservations -->
                    <div class="grid grid-cols-7 gap-2" id="calendar-grid"></div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <button type="button" id="today-btn" class="px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors">
                        Aujourd'hui
                    </button>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        <span id="reservations-count">0</span> réservation(s) cette semaine
                    </div>
                </div>
            </div>

            <!-- Section Horaires d'ouverture -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6 mb-8">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
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
                            <div class="jour-horaires p-4 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" data-jour="{{ $i }}">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-28">
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ $jours[$i] }}</span>
                                    </div>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            name="horaires[{{ $i }}][ferme]" 
                                            value="1"
                                            class="horaire-ferme-checkbox w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500"
                                            data-index="{{ $i }}"
                                            {{ $isFerme ? 'checked' : '' }}
                                        >
                                        <span class="text-sm text-red-600 dark:text-red-400 font-medium">Fermé</span>
                                    </label>
                                    <input type="hidden" name="horaires[{{ $i }}][jour_semaine]" value="{{ $i }}">
                                    @if($isFerme)
                                        <input type="hidden" name="horaires[{{ $i }}][plages]" value="">
                                    @endif
                                    <button 
                                        type="button" 
                                        class="ml-auto px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors add-plage-btn"
                                        data-jour="{{ $i }}"
                                        style="{{ $isFerme ? 'display: none;' : '' }}"
                                    >
                                        + Ajouter une plage
                                    </button>
                                </div>
                                <div class="plages-container" data-jour="{{ $i }}">
                                    @if($isFerme)
                                        <div class="text-sm text-slate-500 dark:text-slate-400 italic">Jour fermé</div>
                                    @else
                                        @foreach($horairesJour as $plage)
                                            <div class="plage-item flex items-center gap-3 mb-2">
                                                <div class="flex items-center gap-2 flex-1">
                                                    <span class="text-sm text-slate-500 dark:text-slate-400">De</span>
                                                    <input 
                                                        type="time" 
                                                        name="horaires[{{ $i }}][plages][{{ $loop->index }}][heure_ouverture]" 
                                                        value="{{ $plage->heure_ouverture ? \Carbon\Carbon::parse($plage->heure_ouverture)->format('H:i') : '' }}"
                                                        class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                        required
                                                    >
                                                    <span class="text-sm text-slate-500 dark:text-slate-400">à</span>
                                                    <input 
                                                        type="time" 
                                                        name="horaires[{{ $i }}][plages][{{ $loop->index }}][heure_fermeture]" 
                                                        value="{{ $plage->heure_fermeture ? \Carbon\Carbon::parse($plage->heure_fermeture)->format('H:i') : '' }}"
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
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            Enregistrer les horaires
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Jours exceptionnels -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </span>
                        Jours exceptionnels
                    </h2>
                    <button 
                        onclick="document.getElementById('modal-jour-exceptionnel').classList.remove('hidden')"
                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
                    >
                        + Ajouter
                    </button>
                </div>
                
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
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
                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-xl flex items-center justify-between bg-slate-50 dark:bg-slate-700/50">
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($horaire->date_exception)->locale('fr')->isoFormat('dddd D MMM') }}
                                    </p>
                                    <p class="text-sm {{ $horaire->heure_ouverture ? 'text-slate-600 dark:text-slate-400' : 'text-red-600 dark:text-red-400' }}">
                                        @if($horaire->heure_ouverture && $horaire->heure_fermeture)
                                            {{ \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') }}
                                        @else
                                            Fermé
                                        @endif
                                    </p>
                                </div>
                                <form action="{{ route('agenda.jour-exceptionnel.delete', [$entreprise->slug, $horaire->id]) }}" method="POST" onsubmit="return confirm('Supprimer ce jour ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <p>Aucun jour exceptionnel configuré</p>
                    </div>
                @endif
            </div>

            <!-- Section Types de services -->
            <div id="section-services" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </span>
                        Types de services
                    </h2>
                    <button 
                        onclick="openServiceModal()"
                        class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
                    >
                        + Ajouter
                    </button>
                </div>

                @if($typesServices->count() > 0)
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach($typesServices as $service)
                            <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow {{ $service->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75' }}">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $service->nom }}</h3>
                                        @if($service->images->count() > 0)
                                            <span class="text-xs text-slate-500 dark:text-slate-400">📷 {{ $service->images->count() }} image(s)</span>
                                        @endif
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $service->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                        {{ $service->est_actif ? 'Actif' : 'Inactif' }}
                                    </span>
                                </div>
                                @if($service->description)
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2">{{ $service->description }}</p>
                                @endif
                                <div class="flex items-center gap-4 text-sm mb-4">
                                    <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $service->duree_minutes }} min
                                    </span>
                                    <span class="flex items-center gap-1 font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($service->prix, 0, ',', ' ') }} €
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <button 
                                        onclick="editService({{ $service->id }}, '{{ addslashes($service->nom) }}', '{{ addslashes($service->description ?? '') }}', {{ $service->duree_minutes }}, {{ $service->prix }}, {{ $service->est_actif ? 'true' : 'false' }})"
                                        class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                                    >
                                        Modifier
                                    </button>
                                    <form action="{{ route('agenda.service.delete', [$entreprise->slug, $service->id]) }}" method="POST" onsubmit="return confirm('Supprimer ce service ?');" class="flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full px-3 py-2 text-sm font-medium bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <p class="mb-4">Aucun service configuré</p>
                        <button 
                            onclick="openServiceModal()"
                            class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all"
                        >
                            Créer votre premier service
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Modal pour ajouter/modifier un service -->
        <div id="modal-service" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modal-title">Ajouter un service</h3>
                    <button onclick="document.getElementById('modal-service').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('agenda.service.store', $entreprise->slug) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type_service_id" id="type_service_id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Nom du service *</label>
                            <input 
                                type="text" 
                                name="nom" 
                                id="service_nom"
                                required
                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description</label>
                            <textarea 
                                name="description" 
                                id="service_description"
                                rows="3"
                                class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors resize-none"
                            ></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Durée (min) *</label>
                                <input 
                                    type="number" 
                                    name="duree_minutes" 
                                    id="service_duree"
                                    required
                                    min="1"
                                    value="30"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Prix (€) *</label>
                                <input 
                                    type="number" 
                                    name="prix" 
                                    id="service_prix"
                                    required
                                    min="0"
                                    step="0.01"
                                    value="25"
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>
                        </div>
                        <label class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <input 
                                type="checkbox" 
                                name="est_actif" 
                                id="service_actif"
                                value="1"
                                checked
                                class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                            >
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Service actif</span>
                        </label>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="document.getElementById('modal-service').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition">
                            Annuler
                        </button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                            Enregistrer
                        </button>
                    </div>
                </form>
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
        <div id="modal-reservation" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-md w-full">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Détails de la réservation</h3>
                    <button onclick="document.getElementById('modal-reservation').classList.add('hidden')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="reservation-details" class="space-y-4">
                    <!-- Rempli par JS -->
                </div>
            </div>
        </div>

        <script>
            // Données PHP
            const horaires = @json($horaires);
            const reservationsUrl = '{{ route("agenda.reservations", $entreprise->slug, false) }}';
            
            // État du calendrier
            let currentWeekOffset = 0;
            let reservations = [];
            
            // Éléments DOM
            const calendarHeaders = document.getElementById('calendar-headers');
            const calendarGrid = document.getElementById('calendar-grid');
            const calendarTitle = document.getElementById('calendar-title');
            const calendarSubtitle = document.getElementById('calendar-subtitle');
            const reservationsCount = document.getElementById('reservations-count');
            
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
            
            // Couleurs par statut
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
            
            // Charger les réservations
            async function loadReservations() {
                try {
                    const response = await fetch(reservationsUrl);
                    reservations = await response.json();
                } catch (error) {
                    console.error('Erreur:', error);
                    reservations = [];
                }
            }
            
            // Formater une date en ISO
            function formatDateISO(date) {
                const d = new Date(date);
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }
            
            // Obtenir les réservations d'un jour
            function getReservationsForDay(dateStr) {
                return reservations.filter(r => {
                    const resDate = new Date(r.start);
                    return formatDateISO(resDate) === dateStr;
                }).sort((a, b) => new Date(a.start) - new Date(b.start));
            }
            
            // Générer le calendrier
            async function renderCalendar() {
                await loadReservations();
                
                const today = new Date();
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + 1 + (currentWeekOffset * 7));
                
                const endOfWeek = new Date(startOfWeek);
                endOfWeek.setDate(startOfWeek.getDate() + 6);
                
                // Titre
                if (startOfWeek.getMonth() === endOfWeek.getMonth()) {
                    calendarTitle.textContent = `${startOfWeek.getDate()} - ${endOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} ${startOfWeek.getFullYear()}`;
                } else {
                    calendarTitle.textContent = `${startOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} - ${endOfWeek.getDate()} ${mois[endOfWeek.getMonth()]}`;
                }
                calendarSubtitle.textContent = currentWeekOffset === 0 ? 'Cette semaine' : '';
                
                // Compter les réservations de la semaine
                let weekReservations = 0;
                
                // En-têtes
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
                
                // Grille
                calendarGrid.innerHTML = '';
                for (let i = 0; i < 7; i++) {
                    const date = new Date(startOfWeek);
                    date.setDate(startOfWeek.getDate() + i);
                    const dateStr = formatDateISO(date);
                    const jourSemaine = date.getDay();
                    const plagesJour = horairesParJour[jourSemaine] || [];
                    const dayReservations = getReservationsForDay(dateStr);
                    
                    weekReservations += dayReservations.length;
                    
                    const dayColumn = document.createElement('div');
                    dayColumn.className = 'space-y-1 min-h-[150px]';
                    
                    if (!plagesJour || plagesJour.length === 0 || !plagesJour.some(p => p.ouverture)) {
                        dayColumn.innerHTML = `
                            <div class="h-full min-h-[150px] flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700/50 border-2 border-dashed border-slate-200 dark:border-slate-600">
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">Fermé</span>
                            </div>
                        `;
                    } else if (dayReservations.length === 0) {
                        dayColumn.innerHTML = `
                            <div class="h-full min-h-[150px] flex items-center justify-center rounded-xl bg-green-50 dark:bg-green-900/10 border-2 border-dashed border-green-200 dark:border-green-800">
                                <span class="text-xs text-green-500 dark:text-green-400 font-medium">Libre</span>
                            </div>
                        `;
                    } else {
                        dayReservations.forEach(res => {
                            const startTime = new Date(res.start);
                            const time = startTime.toTimeString().substring(0, 5);
                            const colorClass = statutColors[res.extendedProps?.statut] || statutColors['en_attente'];
                            
                            const resEl = document.createElement('button');
                            resEl.type = 'button';
                            resEl.className = `w-full p-2 text-left rounded-lg border-l-4 ${colorClass} hover:shadow-md transition-all cursor-pointer`;
                            resEl.innerHTML = `
                                <div class="text-xs font-bold">${time}</div>
                                <div class="text-xs truncate">${res.title}</div>
                            `;
                            resEl.onclick = () => showReservationDetails(res);
                            dayColumn.appendChild(resEl);
                        });
                    }
                    
                    calendarGrid.appendChild(dayColumn);
                }
                
                reservationsCount.textContent = weekReservations;
            }
            
            // Afficher les détails d'une réservation
            function showReservationDetails(res) {
                const props = res.extendedProps || {};
                const startTime = new Date(res.start);
                const detailsEl = document.getElementById('reservation-details');
                
                detailsEl.innerHTML = `
                    <div class="p-4 rounded-xl ${statutColors[props.statut] || 'bg-slate-100 dark:bg-slate-700'}">
                        <span class="text-sm font-bold">${statutLabels[props.statut] || props.statut}</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Service</span>
                            <p class="font-semibold text-slate-900 dark:text-white">${props.type_service || res.title}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Date</span>
                                <p class="font-semibold text-slate-900 dark:text-white">${startTime.toLocaleDateString('fr-FR')}</p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Heure</span>
                                <p class="font-semibold text-slate-900 dark:text-white">${startTime.toTimeString().substring(0, 5)}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Durée</span>
                                <p class="font-semibold text-slate-900 dark:text-white">${props.duree || '-'} min</p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Prix</span>
                                <p class="font-semibold text-green-600 dark:text-green-400">${props.prix || '-'} €</p>
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Client</span>
                            <p class="font-semibold text-slate-900 dark:text-white">${props.client || '-'}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">${props.client_email || ''}</p>
                            ${props.telephone ? `<p class="text-sm text-slate-600 dark:text-slate-400">📞 ${props.telephone}</p>` : ''}
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Payé</span>
                            <p class="font-semibold ${props.est_paye ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${props.est_paye ? '✓ Oui' : '✗ Non'}</p>
                        </div>
                        ${props.lieu ? `<div><span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Lieu</span><p class="text-slate-900 dark:text-white">${props.lieu}</p></div>` : ''}
                        ${props.notes ? `<div><span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Notes</span><p class="text-slate-600 dark:text-slate-400">${props.notes}</p></div>` : ''}
                    </div>
                `;
                
                document.getElementById('modal-reservation').classList.remove('hidden');
            }
            
            // Navigation
            document.getElementById('prev-week')?.addEventListener('click', () => {
                currentWeekOffset--;
                renderCalendar();
            });
            
            document.getElementById('next-week')?.addEventListener('click', () => {
                currentWeekOffset++;
                renderCalendar();
            });
            
            document.getElementById('today-btn')?.addEventListener('click', () => {
                currentWeekOffset = 0;
                renderCalendar();
            });
            
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
                    const plagesContainer = jourContainer.querySelector('.plages-container');
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
            
            // Modal service
            function openServiceModal() {
                document.getElementById('modal-title').textContent = 'Ajouter un service';
                document.getElementById('type_service_id').value = '';
                document.getElementById('service_nom').value = '';
                document.getElementById('service_description').value = '';
                document.getElementById('service_duree').value = '30';
                document.getElementById('service_prix').value = '25';
                document.getElementById('service_actif').checked = true;
                document.getElementById('modal-service').classList.remove('hidden');
            }
            
            function editService(id, nom, description, duree, prix, actif) {
                document.getElementById('modal-title').textContent = 'Modifier le service';
                document.getElementById('type_service_id').value = id;
                document.getElementById('service_nom').value = nom;
                document.getElementById('service_description').value = description;
                document.getElementById('service_duree').value = duree;
                document.getElementById('service_prix').value = prix;
                document.getElementById('service_actif').checked = actif;
                document.getElementById('modal-service').classList.remove('hidden');
            }
            
            // Modal jour exceptionnel
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
            
            // Fermer les modals en cliquant dehors
            ['modal-service', 'modal-jour-exceptionnel', 'modal-reservation'].forEach(id => {
                document.getElementById(id)?.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.add('hidden');
                    }
                });
            });
            
            // Initialiser
            renderCalendar();
        </script>
    </body>
</html>
