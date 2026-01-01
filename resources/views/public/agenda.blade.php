<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda - {{ $entreprise->nom }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Navigation -->
        <nav class="mb-6 flex items-center justify-between">
            <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="font-medium">Retour à {{ $entreprise->nom }}</span>
            </a>
            <button 
                id="theme-toggle"
                class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                aria-label="Basculer le thème"
            >
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </button>
        </nav>

        <!-- En-tête -->
        <header class="mb-8">
            <div class="flex items-center gap-4">
                @if($entreprise->logo)
                    <img src="{{ asset('media/' . $entreprise->logo) }}" alt="{{ $entreprise->nom }}" class="w-16 h-16 rounded-xl object-cover shadow-md">
                @endif
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent">
                        Prendre rendez-vous
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $entreprise->nom }} • {{ $entreprise->type_activite }}</p>
                </div>
            </div>
        </header>

        <!-- Messages -->
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

        @if(session('info'))
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-blue-800 dark:text-blue-300 font-medium">{{ session('info') }}</p>
                </div>
            </div>
        @endif

        @if(!$entreprise->est_verifiee)
            <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-amber-800 dark:text-amber-300 text-sm">Cette entreprise est en cours de création</p>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">Vous pouvez tout de même prendre rendez-vous.</p>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($isOwner) && $isOwner && !$entreprise->aAbonnementActif())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-red-800 dark:text-red-300 text-sm">⚠️ Votre entreprise n'est pas visible en ligne</p>
                        <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                            <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="underline font-semibold">Souscrivez à un abonnement</a> pour être visible.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Calendrier Tailwind -->
            <div class="xl:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <!-- Header du calendrier -->
                    <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <button 
                                type="button" 
                                id="prev-week" 
                                class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <div class="text-center">
                                <h2 class="text-xl font-bold text-white" id="calendar-title">Chargement...</h2>
                                <p class="text-sm text-white/80" id="calendar-subtitle"></p>
                            </div>
                            <button 
                                type="button" 
                                id="next-week" 
                                class="p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Légende -->
                    <div class="px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                            <span class="text-slate-600 dark:text-slate-400">Disponible</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                            <span class="text-slate-600 dark:text-slate-400">Indisponible</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="text-slate-600 dark:text-slate-400">Sélectionné</span>
                        </div>
                    </div>

                    <!-- Corps du calendrier -->
                    <div class="p-4 sm:p-6">
                        <!-- En-têtes des jours -->
                        <div class="grid grid-cols-7 gap-2 mb-4" id="calendar-headers">
                            <!-- Rempli par JS -->
                        </div>

                        <!-- Grille des créneaux -->
                        <div class="grid grid-cols-7 gap-2" id="calendar-grid">
                            <!-- Rempli par JS -->
                        </div>
                    </div>

                    <!-- Footer avec bouton aujourd'hui -->
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex justify-center">
                        <button 
                            type="button" 
                            id="today-btn" 
                            class="px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                        >
                            Aujourd'hui
                        </button>
                    </div>
                </div>
            </div>

            <!-- Formulaire de réservation -->
            <div class="xl:col-span-1">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6 sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Réserver</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Sélectionnez un créneau</p>
                        </div>
                    </div>
                    
                    @auth
                        <form action="{{ route('public.reservation.store', $entreprise->slug) }}" method="POST" id="reservation-form">
                            @csrf
                            
                            <div class="space-y-5">
                                <!-- Service -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                        Service
                                    </label>
                                    <select 
                                        name="type_service_id" 
                                        id="type_service_id"
                                        required
                                        class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                        <option value="">Choisir un service</option>
                                        @foreach($entreprise->typesServices as $service)
                                            <option value="{{ $service->id }}" data-duree="{{ $service->duree_minutes }}" data-prix="{{ $service->prix }}">
                                                {{ $service->nom }} • {{ number_format($service->prix, 0, ',', ' ') }}€ • {{ $service->duree_minutes }}min
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type_service_id')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Sélection de la personne (si multi-personnes) -->
                                @if($aGestionMultiPersonnes && $membres->count() > 0)
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                            Personne
                                        </label>
                                        <select 
                                            name="membre_id" 
                                            id="membre_id"
                                            class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                        >
                                            <option value="">Qu'importe (sélection automatique)</option>
                                            @foreach($membres as $membre)
                                                <option value="{{ $membre->id }}" {{ old('membre_id') == $membre->id ? 'selected' : '' }}>
                                                    {{ $membre->user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Si "Qu'importe" est sélectionné, le système choisira automatiquement la personne la moins chargée.
                                        </p>
                                        @error('membre_id')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                <!-- Date et heure sélectionnées -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date</label>
                                        <input 
                                            type="date" 
                                            name="date_reservation" 
                                            id="date_reservation"
                                            required
                                            min="{{ date('Y-m-d') }}"
                                            class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                        >
                                        @error('date_reservation')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Heure</label>
                                        <input 
                                            type="time" 
                                            name="heure_reservation" 
                                            id="heure_reservation"
                                            required
                                            class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                        >
                                        @error('heure_reservation')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Téléphone -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                        Téléphone
                                        @if(auth()->user() && auth()->user()->telephone)
                                            <span class="font-normal text-slate-500 text-xs">(pré-rempli depuis votre profil)</span>
                                        @endif
                                    </label>
                                    <input 
                                        type="tel" 
                                        name="telephone_client" 
                                        id="telephone_client"
                                        required
                                        value="{{ old('telephone_client', auth()->user()?->telephone ?? '') }}"
                                        placeholder="06 12 34 56 78"
                                        class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                    @error('telephone_client')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    @if(auth()->user() && !auth()->user()->telephone)
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            💡 <a href="{{ route('settings.index', ['tab' => 'account']) }}" class="text-green-600 dark:text-green-400 hover:underline">Ajoutez votre téléphone dans vos paramètres</a> pour qu'il soit pré-rempli automatiquement.
                                        </p>
                                    @endif
                                </div>

                                <!-- Option téléphone caché -->
                                <label class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                    <input 
                                        type="checkbox" 
                                        name="telephone_cache" 
                                        value="1"
                                        class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                                    >
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Masquer mon numéro</span>
                                </label>

                                <!-- Lieu -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Lieu <span class="font-normal text-slate-500">(optionnel)</span></label>
                                    <input 
                                        type="text" 
                                        name="lieu" 
                                        id="lieu"
                                        placeholder="Adresse du rendez-vous"
                                        class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes <span class="font-normal text-slate-500">(optionnel)</span></label>
                                    <textarea 
                                        name="notes" 
                                        id="notes"
                                        rows="2"
                                        placeholder="Informations complémentaires..."
                                        class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors resize-none"
                                    ></textarea>
                                </div>

                                <!-- Récapitulatif -->
                                <div id="recap-container" class="hidden p-4 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800">
                                    <h3 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-2">Récapitulatif</h3>
                                    <div class="space-y-1 text-sm text-green-700 dark:text-green-400">
                                        <p id="recap-service"></p>
                                        <p id="recap-datetime"></p>
                                        <p id="recap-prix" class="font-bold"></p>
                                    </div>
                                </div>

                                <!-- Bouton -->
                                <button 
                                    type="submit" 
                                    class="w-full px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                >
                                    Confirmer la réservation
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 mb-4">Connectez-vous pour réserver</p>
                            <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl">
                                Se connecter
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Données PHP
            const jours = @json($jours);
            const horaires = @json($horaires);
            const reservationsUrl = '{{ route("public.agenda.reservations", $entreprise->slug) }}';
            
            // État du calendrier
            let currentWeekOffset = 0;
            let selectedSlot = null;
            let reservations = [];
            
            // Éléments DOM
            const calendarHeaders = document.getElementById('calendar-headers');
            const calendarGrid = document.getElementById('calendar-grid');
            const calendarTitle = document.getElementById('calendar-title');
            const calendarSubtitle = document.getElementById('calendar-subtitle');
            const prevWeekBtn = document.getElementById('prev-week');
            const nextWeekBtn = document.getElementById('next-week');
            const todayBtn = document.getElementById('today-btn');
            const dateInput = document.getElementById('date_reservation');
            const heureInput = document.getElementById('heure_reservation');
            const serviceSelect = document.getElementById('type_service_id');
            const recapContainer = document.getElementById('recap-container');
            
            // Noms des jours
            const joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            const joursComplets = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            const mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
            
            // Horaires d'ouverture par jour de semaine
            const horairesParJour = {};
            horaires.forEach(h => {
                if (!h.est_exceptionnel) {
                    horairesParJour[h.jour_semaine] = {
                        ouverture: h.heure_ouverture,
                        fermeture: h.heure_fermeture
                    };
                }
            });
            
            // Charger les réservations
            async function loadReservations() {
                try {
                    const response = await fetch(reservationsUrl);
                    reservations = await response.json();
                } catch (error) {
                    console.error('Erreur lors du chargement des réservations:', error);
                    reservations = [];
                }
            }
            
            // Vérifier si un créneau est réservé
            function isSlotReserved(dateStr, time) {
                const slotStart = new Date(dateStr + 'T' + time + ':00');
                const slotEnd = new Date(slotStart.getTime() + 30 * 60 * 1000); // +30 min
                
                return reservations.some(res => {
                    const resStart = new Date(res.start);
                    const resEnd = new Date(res.end);
                    return (slotStart < resEnd && slotEnd > resStart);
                });
            }
            
            // Générer les créneaux pour une journée
            function generateSlots(date, jourSemaine) {
                const horaire = horairesParJour[jourSemaine];
                const slots = [];
                
                if (!horaire || !horaire.ouverture || !horaire.fermeture) {
                    return slots; // Fermé
                }
                
                const [startH, startM] = horaire.ouverture.split(':').map(Number);
                const [endH, endM] = horaire.fermeture.split(':').map(Number);
                
                let current = startH * 60 + startM;
                const end = endH * 60 + endM;
                
                while (current < end) {
                    const h = Math.floor(current / 60);
                    const m = current % 60;
                    const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                    
                    const dateStr = formatDateISO(date);
                    const now = new Date();
                    const slotDate = new Date(dateStr + 'T' + timeStr + ':00');
                    
                    // Vérifier si le créneau est dans le passé (+ 1h de marge)
                    const isPast = slotDate <= new Date(now.getTime() + 60 * 60 * 1000);
                    const isReserved = isSlotReserved(dateStr, timeStr);
                    
                    slots.push({
                        time: timeStr,
                        available: !isPast && !isReserved,
                        isPast,
                        isReserved
                    });
                    
                    current += 30; // Créneaux de 30 min
                }
                
                return slots;
            }
            
            // Formater une date en ISO
            function formatDateISO(date) {
                const d = new Date(date);
                return d.getFullYear() + '-' + 
                       String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(d.getDate()).padStart(2, '0');
            }
            
            // Générer le calendrier
            async function renderCalendar() {
                await loadReservations();
                
                const today = new Date();
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + 1 + (currentWeekOffset * 7)); // Lundi
                
                // Mise à jour du titre
                const endOfWeek = new Date(startOfWeek);
                endOfWeek.setDate(startOfWeek.getDate() + 6);
                
                if (startOfWeek.getMonth() === endOfWeek.getMonth()) {
                    calendarTitle.textContent = `${startOfWeek.getDate()} - ${endOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} ${startOfWeek.getFullYear()}`;
                } else {
                    calendarTitle.textContent = `${startOfWeek.getDate()} ${mois[startOfWeek.getMonth()]} - ${endOfWeek.getDate()} ${mois[endOfWeek.getMonth()]}`;
                }
                calendarSubtitle.textContent = currentWeekOffset === 0 ? 'Cette semaine' : (currentWeekOffset > 0 ? `Dans ${currentWeekOffset} semaine(s)` : '');
                
                // Générer les en-têtes
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
                
                // Générer la grille des créneaux
                calendarGrid.innerHTML = '';
                for (let i = 0; i < 7; i++) {
                    const date = new Date(startOfWeek);
                    date.setDate(startOfWeek.getDate() + i);
                    const jourSemaine = date.getDay();
                    const dateStr = formatDateISO(date);
                    const slots = generateSlots(date, jourSemaine);
                    
                    const dayColumn = document.createElement('div');
                    dayColumn.className = 'space-y-1';
                    
                    if (slots.length === 0) {
                        // Jour fermé
                        dayColumn.innerHTML = `
                            <div class="h-full min-h-[200px] flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700/50">
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">Fermé</span>
                            </div>
                        `;
                    } else {
                        slots.forEach(slot => {
                            const slotEl = document.createElement('button');
                            slotEl.type = 'button';
                            slotEl.className = 'w-full px-2 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 ';
                            
                            const isSelected = selectedSlot && selectedSlot.date === dateStr && selectedSlot.time === slot.time;
                            
                            if (isSelected) {
                                slotEl.className += 'bg-amber-500 text-white shadow-md transform scale-105';
                            } else if (slot.available) {
                                slotEl.className += 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50 hover:scale-105 cursor-pointer';
                            } else {
                                slotEl.className += 'bg-slate-100 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500 cursor-not-allowed';
                            }
                            
                            slotEl.textContent = slot.time;
                            
                            if (slot.available) {
                                slotEl.addEventListener('click', () => selectSlot(dateStr, slot.time));
                            }
                            
                            dayColumn.appendChild(slotEl);
                        });
                    }
                    
                    calendarGrid.appendChild(dayColumn);
                }
            }
            
            // Sélectionner un créneau
            function selectSlot(date, time) {
                selectedSlot = { date, time };
                
                if (dateInput) dateInput.value = date;
                if (heureInput) heureInput.value = time;
                
                renderCalendar();
                updateRecap();
                
                // Scroll vers le formulaire sur mobile
                if (window.innerWidth < 1280) {
                    document.getElementById('reservation-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
            
            // Mettre à jour le récapitulatif
            function updateRecap() {
                if (!recapContainer || !serviceSelect || !dateInput || !heureInput) return;
                
                const service = serviceSelect.options[serviceSelect.selectedIndex];
                const date = dateInput.value;
                const heure = heureInput.value;
                
                if (service.value && date && heure) {
                    recapContainer.classList.remove('hidden');
                    
                    const dateObj = new Date(date);
                    const jourNom = joursComplets[dateObj.getDay()];
                    const jour = dateObj.getDate();
                    const moisNom = mois[dateObj.getMonth()];
                    
                    document.getElementById('recap-service').textContent = `📋 ${service.text.split('•')[0].trim()}`;
                    document.getElementById('recap-datetime').textContent = `📅 ${jourNom} ${jour} ${moisNom} à ${heure}`;
                    document.getElementById('recap-prix').textContent = `💰 ${service.dataset.prix}€`;
                } else {
                    recapContainer.classList.add('hidden');
                }
            }
            
            // Événements
            prevWeekBtn?.addEventListener('click', () => {
                if (currentWeekOffset > 0) {
                    currentWeekOffset--;
                    renderCalendar();
                }
            });
            
            nextWeekBtn?.addEventListener('click', () => {
                if (currentWeekOffset < 8) { // Max 8 semaines à l'avance
                    currentWeekOffset++;
                    renderCalendar();
                }
            });
            
            todayBtn?.addEventListener('click', () => {
                currentWeekOffset = 0;
                renderCalendar();
            });
            
            serviceSelect?.addEventListener('change', updateRecap);
            dateInput?.addEventListener('change', updateRecap);
            heureInput?.addEventListener('change', updateRecap);
            
            // Initialiser
            renderCalendar();
        });
    </script>
</body>
</html>
