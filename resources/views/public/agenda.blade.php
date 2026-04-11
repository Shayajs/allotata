<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agenda - {{ $entreprise->nom }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite('resources/js/tracking-visite.js')
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
                    <img src="/media/{{ $entreprise->logo }}" alt="{{ $entreprise->nom }}" class="w-16 h-16 rounded-xl object-cover shadow-md">
                @endif
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent">
                        Prendre rendez-vous
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $entreprise->nom }} • {{ $entreprise->type_activite }}</p>
                </div>
            </div>
        </header>

        <!-- Formulaire mobile (visible en haut sur mobile uniquement) -->
        <div class="xl:hidden mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6">
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

                @guest
                    <div class="mb-5 p-4 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/40 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <p class="text-sm text-slate-600 dark:text-slate-300">Vous avez un compte ? Connectez-vous pour pré-remplir vos infos.</p>
                            <a href="{{ route('login') }}" class="inline-flex justify-center px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 rounded-lg transition whitespace-nowrap">
                                Se connecter
                            </a>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-600 pt-3">
                            Pas le temps ? Réservez sans compte ci-dessous avec votre nom, e-mail et téléphone. La confirmation vous sera envoyée par e-mail.
                        </p>
                    </div>
                @endguest

                <form action="{{ route('public.reservation.store', $entreprise->slug) }}" method="POST" id="reservation-form-mobile">
                        @csrf
                        
                        <div class="space-y-5">
                            <!-- Service -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Service
                                </label>
                                <select 
                                    name="type_service_id" 
                                    id="type_service_id_mobile"
                                    required
                                    class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    onchange="handleServiceChange(this)"
                                >
                                    <option value="">Choisir un service</option>
                                    @foreach($entreprise->typesServices as $service)
                                        @php
                                            $optionsData = $service->options->map(function($opt) {
                                                return [
                                                    'id' => $opt->id,
                                                    'nom' => $opt->nom,
                                                    'obligatoire' => $opt->obligatoire,
                                                    'choices' => $opt->choices->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom, 'prix' => $c->prix_supplementaire, 'temps' => $c->temps_supplementaire])
                                                ];
                                            });
                                        @endphp
                                        <option 
                                            value="{{ $service->id }}" 
                                            data-duree="{{ $service->duree_minutes }}" 
                                            data-prix="{{ $service->prix }}"
                                            data-type-structure="{{ $service->type_structure ?? 'ponctuel' }}"
                                            data-options="{{ base64_encode(json_encode($optionsData)) }}"
                                            {{ request('service') == $service->id || request('service') == (string)$service->id ? 'selected' : '' }}
                                        >
                                            {{ $service->nom }} • {{ number_format($service->prix, 0, ',', ' ') }}€ @if(($service->type_structure ?? '') === 'date_butoire') (date butoire) @else • {{ $service->duree_minutes }}min @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('type_service_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Conteneur dynamique pour les options du service (Mobile) -->
                            <div id="service-options-container-mobile" class="space-y-4 hidden">
                                <!-- Rempli par JS -->
                            </div>

                            <!-- Date butoire (visible uniquement pour services à date butoire) -->
                            <div id="date-butoire-wrapper-mobile" class="hidden">
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date butoire souhaitée</label>
                                <input type="date" name="date_butoire" id="date_butoire_mobile" data-no-flatpickr min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors">
                                @error('date_butoire')
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
                                        id="membre_id_mobile"
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

                            <!-- Date et heure sélectionnées (masqués pour service à date butoire) -->
                            <div id="date-heure-wrapper-mobile">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date</label>
                                        <input 
                                            type="date" 
                                            name="date_reservation" 
                                            id="date_reservation_mobile"
                                            data-no-flatpickr
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
                                            id="heure_reservation_mobile"
                                            class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                        >
                                        @error('heure_reservation')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            @guest
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Votre nom</label>
                                    <input type="text" name="nom_client" id="nom_client_mobile" required value="{{ old('nom_client') }}" placeholder="Jean Dupont" autocomplete="name" class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors">
                                    @error('nom_client')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">E-mail</label>
                                    <input type="email" name="email_client" id="email_client_mobile" required value="{{ old('email_client') }}" placeholder="vous@exemple.com" autocomplete="email" class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors">
                                    @error('email_client')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endguest

                            <!-- Téléphone -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Téléphone
                                    @auth
                                        @if(isset($userInfo) && !empty($userInfo['telephone']))
                                            <span class="font-normal text-slate-500 text-xs">(pré-rempli depuis votre profil)</span>
                                        @endif
                                    @endauth
                                </label>
                                <input 
                                    type="tel" 
                                    name="telephone_client" 
                                    id="telephone_client_mobile"
                                    required
                                    value="{{ old('telephone_client', data_get($userInfo, 'telephone', '')) }}"
                                    placeholder="06 12 34 56 78"
                                    class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                                @error('telephone_client')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                @auth
                                    @if(!isset($userInfo) || empty($userInfo['telephone']))
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            💡 <a href="{{ route('settings.index', ['tab' => 'account']) }}" class="text-green-600 dark:text-green-400 hover:underline">Ajoutez votre téléphone dans vos paramètres</a> pour qu'il soit pré-rempli automatiquement.
                                        </p>
                                    @endif
                                @endauth
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
                                    id="lieu_mobile"
                                    placeholder="Adresse du rendez-vous"
                                    class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                >
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes <span class="font-normal text-slate-500">(optionnel)</span></label>
                                <textarea 
                                    name="notes" 
                                    id="notes_mobile"
                                    rows="2"
                                    placeholder="Informations complémentaires..."
                                    class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors resize-none"
                                ></textarea>
                            </div>

                            <!-- Récapitulatif mobile -->
                            <div id="recap-container-mobile" class="hidden p-4 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800">
                                <h3 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-2">Récapitulatif</h3>
                                <div class="space-y-1 text-sm text-green-700 dark:text-green-400 mb-4">
                                    <p id="recap-service-mobile"></p>
                                    <p id="recap-datetime-mobile"></p>
                                    <p id="recap-prix-mobile" class="font-bold"></p>
                                </div>
                            </div>

                            <!-- Bouton -->
                            <button 
                                type="submit" 
                                class="w-full px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                            >
                                Réserver
                            </button>
                        </div>
                    </form>
            </div>
        </div>

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
                            <div class="text-center px-2">
                                <h2 class="text-xl font-bold text-white" id="calendar-title">Chargement...</h2>
                                <p class="text-sm text-white/80" id="calendar-subtitle"></p>
                                <p id="calendar-week-hint" class="hidden text-xs text-white/95 mt-2 max-w-md mx-auto leading-snug" role="status"></p>
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
                            <span class="text-slate-600 dark:text-slate-400">Début (sélection)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-200 dark:bg-amber-700/50 ring-1 ring-amber-300/80 dark:ring-amber-600/40"></span>
                            <span class="text-slate-600 dark:text-slate-400">Durée de la prestation</span>
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

            <!-- Formulaire de réservation (masqué sur mobile, visible sur desktop) -->
            <div class="xl:col-span-1 hidden xl:block">
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

                    @guest
                        <div class="mb-5 p-4 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/40 space-y-3">
                            <div class="flex flex-col gap-2">
                                <p class="text-sm text-slate-600 dark:text-slate-300">Vous avez un compte ? Connectez-vous pour pré-remplir vos infos.</p>
                                <a href="{{ route('login') }}" class="inline-flex justify-center px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 rounded-lg transition">
                                    Se connecter
                                </a>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-600 pt-3">
                                Pas le temps ? Réservez sans compte avec nom, e-mail et téléphone. La confirmation vous sera envoyée par e-mail.
                            </p>
                        </div>
                    @endguest

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
                                        onchange="handleServiceChange(this)"
                                    >
                                        <option value="">Choisir un service</option>
                                        @foreach($entreprise->typesServices as $service)
                                            @php
                                                $optionsData = $service->options->map(function($opt) {
                                                    return [
                                                        'id' => $opt->id,
                                                        'nom' => $opt->nom,
                                                        'obligatoire' => $opt->obligatoire,
                                                        'choices' => $opt->choices->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom, 'prix' => $c->prix_supplementaire, 'temps' => $c->temps_supplementaire])
                                                    ];
                                                });
                                            @endphp
                                            <option 
                                                value="{{ $service->id }}" 
                                                data-duree="{{ $service->duree_minutes }}" 
                                                data-prix="{{ $service->prix }}"
                                                data-type-structure="{{ $service->type_structure ?? 'ponctuel' }}"
                                                data-options="{{ base64_encode(json_encode($optionsData)) }}"
                                                {{ request('service') == $service->id || request('service') == (string)$service->id ? 'selected' : '' }}
                                            >
                                                {{ $service->nom }} • {{ number_format($service->prix, 0, ',', ' ') }}€ @if(($service->type_structure ?? '') === 'date_butoire') (date butoire) @else • {{ $service->duree_minutes }}min @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type_service_id')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Conteneur dynamique pour les options du service -->
                                <div id="service-options-container" class="space-y-4 hidden">
                                    <!-- Rempli par JS -->
                                </div>

                                <!-- Date butoire (visible uniquement pour services à date butoire) -->
                                <div id="date-butoire-wrapper" class="hidden">
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date butoire souhaitée</label>
                                    <input type="date" name="date_butoire" id="date_butoire" data-no-flatpickr min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors">
                                    @error('date_butoire')
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

                                <!-- Date et heure sélectionnées (masqués pour service à date butoire) -->
                                <div id="date-heure-wrapper">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date</label>
                                            <input 
                                                type="date" 
                                                name="date_reservation" 
                                                id="date_reservation"
                                                data-no-flatpickr
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
                                                class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                            >
                                            @error('heure_reservation')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                @guest
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Votre nom</label>
                                        <input type="text" name="nom_client" id="nom_client" required value="{{ old('nom_client') }}" placeholder="Jean Dupont" autocomplete="name" class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors">
                                        @error('nom_client')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">E-mail</label>
                                        <input type="email" name="email_client" id="email_client" required value="{{ old('email_client') }}" placeholder="vous@exemple.com" autocomplete="email" class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors">
                                        @error('email_client')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endguest

                                <!-- Téléphone -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                        Téléphone
                                        @auth
                                            @if(isset($userInfo) && !empty($userInfo['telephone']))
                                                <span class="font-normal text-slate-500 text-xs">(pré-rempli depuis votre profil)</span>
                                            @endif
                                        @endauth
                                    </label>
                                    <input 
                                        type="tel" 
                                        name="telephone_client" 
                                        id="telephone_client"
                                        required
                                        value="{{ old('telephone_client', data_get($userInfo, 'telephone', '')) }}"
                                        placeholder="06 12 34 56 78"
                                        class="w-full px-4 py-3 text-sm border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:border-green-500 dark:focus:border-green-400 bg-white dark:bg-slate-700 text-slate-900 dark:text-white transition-colors"
                                    >
                                    @error('telephone_client')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    @auth
                                        @if(!isset($userInfo) || empty($userInfo['telephone']))
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                💡 <a href="{{ route('settings.index', ['tab' => 'account']) }}" class="text-green-600 dark:text-green-400 hover:underline">Ajoutez votre téléphone dans vos paramètres</a> pour qu'il soit pré-rempli automatiquement.
                                            </p>
                                        @endif
                                    @endauth
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

                                <!-- Récapitulatif (masqué sur mobile, visible sur desktop) -->
                                <div id="recap-container" class="hidden xl:block p-4 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800">
                                    <h3 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-2">Récapitulatif</h3>
                                    <div class="space-y-1 text-sm text-green-700 dark:text-green-400">
                                        <p id="recap-service"></p>
                                        <p id="recap-datetime"></p>
                                        <p id="recap-prix" class="font-bold"></p>
                                    </div>
                                </div>

                                <!-- Bouton (masqué sur mobile, visible sur desktop) -->
                                <button 
                                    type="submit" 
                                    class="hidden xl:block w-full px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                >
                                    Confirmer la réservation
                                </button>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Définir la fonction globalement pour l'accès depuis le HTML
        window.handleServiceChange = function(selectElement) {
            // Cette fonction sera écrasée une fois le DOM chargé
            console.log('DOM pas encore chargé');
        };

        document.addEventListener('DOMContentLoaded', async function() {
            // Données PHP
            const jours = @json($jours);
            const horaires = @json($horaires);
            const reservationsUrl = '{{ route("public.agenda.reservations", $entreprise->slug, false) }}';
            
            // État du calendrier
            let currentWeekOffset = 0;
            /** Si on a sauté des semaines au chargement, offset affiché où montrer le message explicatif */
            let showAutoAdvanceHintOffset = null;
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
            // Éléments du formulaire desktop
            const dateInput = document.getElementById('date_reservation');
            const heureInput = document.getElementById('heure_reservation');
            const serviceSelect = document.getElementById('type_service_id');
            const recapContainer = document.getElementById('recap-container');
            const serviceOptionsContainer = document.getElementById('service-options-container');
            
            // Éléments du formulaire mobile
            const dateInputMobile = document.getElementById('date_reservation_mobile');
            const heureInputMobile = document.getElementById('heure_reservation_mobile');
            const serviceSelectMobile = document.getElementById('type_service_id_mobile');
            const recapContainerMobile = document.getElementById('recap-container-mobile');
            const serviceOptionsContainerMobile = document.getElementById('service-options-container-mobile');

            /** Date native ou Flatpickr (app.js) : met à jour l'affichage correctement */
            function setDateFieldValue(el, ymd) {
                if (!el || !ymd) return;
                if (el._flatpickr) {
                    el._flatpickr.setDate(ymd, true);
                } else {
                    el.value = ymd;
                }
            }

            // Afficher/masquer les champs selon le type de service
            function toggleDateButoireFields(selectElement) {
                const isMobile = selectElement && selectElement.id.includes('mobile');
                const select = selectElement || serviceSelect;
                const opt = select && select.options[select.selectedIndex];
                const typeStructure = opt ? (opt.dataset.typeStructure || 'ponctuel') : 'ponctuel';
                const isDateButoire = typeStructure === 'date_butoire';
                const isRecurrent = typeStructure === 'recurrent';
                const isSurDevis = typeStructure === 'sur_devis';

                const dateButoireWrapper = document.getElementById(isMobile ? 'date-butoire-wrapper-mobile' : 'date-butoire-wrapper');
                const dateHeureWrapper = document.getElementById(isMobile ? 'date-heure-wrapper-mobile' : 'date-heure-wrapper');
                const dateButoireInput = document.getElementById(isMobile ? 'date_butoire_mobile' : 'date_butoire');
                const dateInput = document.getElementById(isMobile ? 'date_reservation_mobile' : 'date_reservation');
                const heureInput = document.getElementById(isMobile ? 'heure_reservation_mobile' : 'heure_reservation');

                if (!dateButoireWrapper || !dateHeureWrapper) return;

                // Masquer tout par défaut
                dateButoireWrapper.classList.add('hidden');
                dateHeureWrapper.classList.add('hidden');
                if (dateButoireInput) { dateButoireInput.required = false; dateButoireInput.setAttribute('disabled', 'disabled'); dateButoireInput.value = ''; }
                if (dateInput) { dateInput.required = false; dateInput.setAttribute('disabled', 'disabled'); }
                if (heureInput) { heureInput.required = false; heureInput.setAttribute('disabled', 'disabled'); }

                if (isDateButoire) {
                    dateButoireWrapper.classList.remove('hidden');
                    if (dateButoireInput) { dateButoireInput.required = true; dateButoireInput.removeAttribute('disabled'); }
                } else if (isSurDevis) {
                    // Sur devis : pas de date/heure (le formulaire de devis sera dans le site-web form)
                    // Rien de visible
                } else if (isRecurrent) {
                    // Récurrent : afficher l'heure mais pas la date (gérée par les champs récurrence)
                    dateHeureWrapper.classList.remove('hidden');
                    if (heureInput) { heureInput.required = true; heureInput.removeAttribute('disabled'); }
                    // date_reservation pas requise pour le récurrent
                } else {
                    // Ponctuel, multi_jours, multi_rendez_vous, evenement
                    dateHeureWrapper.classList.remove('hidden');
                    if (dateInput) { dateInput.required = true; dateInput.removeAttribute('disabled'); }
                    if (heureInput) { heureInput.required = true; heureInput.removeAttribute('disabled'); }
                }
            }

            // Fonction de gestion du changement de service
            window.handleServiceChange = function(selectElement) {
                const isMobile = selectElement.id.includes('mobile');
                const container = isMobile ? serviceOptionsContainerMobile : serviceOptionsContainer;
                const otherSelect = isMobile ? serviceSelect : serviceSelectMobile;
                const otherContainer = isMobile ? serviceOptionsContainer : serviceOptionsContainerMobile;
                
                // Synchroniser l'autre selecteur
                if (otherSelect && otherSelect.value !== selectElement.value) {
                    otherSelect.value = selectElement.value;
                    renderOptions(otherSelect, otherContainer);
                }

                renderOptions(selectElement, container);
                toggleDateButoireFields(selectElement);
                updateRecap();
                if (selectedSlot) {
                    void renderCalendar();
                }
            };

            // État initial : afficher date butoire ou date+heure selon le service sélectionné
            if (serviceSelect && serviceSelect.value) toggleDateButoireFields(serviceSelect);
            if (serviceSelectMobile && serviceSelectMobile.value) toggleDateButoireFields(serviceSelectMobile);

            function renderOptions(selectElement, container) {
                if (!container) return;
                
                container.innerHTML = '';
                container.classList.add('hidden');

                const selectedOption = selectElement.options[selectElement.selectedIndex];
                if (!selectedOption || !selectedOption.value) return;

                const optionsDataRaw = selectedOption.dataset.options;
                if (!optionsDataRaw) return;

                try {
                    const options = JSON.parse(atob(optionsDataRaw));
                    
                    if (options.length > 0) {
                        container.classList.remove('hidden');
                        
                        options.forEach(option => {
                            const optionGroup = document.createElement('div');
                            optionGroup.className = 'bg-slate-50 dark:bg-slate-700/50 p-4 rounded-xl border border-slate-200 dark:border-slate-600';
                            
                            const title = document.createElement('h4');
                            title.className = 'font-medium text-slate-900 dark:text-white mb-3 flex items-center justify-between';
                            title.innerHTML = `
                                <span>${option.nom}</span>
                                ${option.obligatoire ? '<span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Obligatoire</span>' : ''}
                            `;
                            optionGroup.appendChild(title);

                            const choicesContainer = document.createElement('div');
                            choicesContainer.className = 'space-y-2';

                            option.choices.forEach(choice => {
                                const choiceLabel = document.createElement('label');
                                choiceLabel.className = 'flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-600 cursor-pointer hover:border-green-500 transition-colors';
                                
                                const leftPart = document.createElement('div');
                                leftPart.className = 'flex items-center gap-3';
                                
                                const input = document.createElement('input');
                                input.type = 'radio';
                                input.name = `service_options[${option.id}]`;
                                input.value = choice.id;
                                input.className = 'w-4 h-4 text-green-600 focus:ring-green-500 border-gray-300';
                                if (option.obligatoire) {
                                    input.required = true;
                                }
                                
                                // Données pour le calcul
                                input.dataset.prix = choice.prix || 0;
                                input.dataset.temps = choice.temps || 0;
                                input.dataset.nom = choice.nom; // Pour l'affichage
                                input.dataset.optionNom = option.nom; // Pour l'affichage

                                // Écouteur pour mettre à jour le récap
                                input.addEventListener('change', () => {
                                    // Synchroniser avec l'autre vue (mobile/desktop)
                                    const isMobile = container.id.includes('mobile');
                                    const targetContainerId = isMobile ? 'service-options-container' : 'service-options-container-mobile';
                                    const targetContainer = document.getElementById(targetContainerId);
                                    
                                    if (targetContainer) {
                                        const targetInput = targetContainer.querySelector(`input[name="${input.name}"][value="${input.value}"]`);
                                        if (targetInput) {
                                            targetInput.checked = true;
                                        }
                                    }
                                    
                                    updateRecap();
                                    if (selectedSlot) {
                                        void renderCalendar();
                                    }
                                });
                                
                                leftPart.appendChild(input);
                                
                                const nameSpan = document.createElement('span');
                                nameSpan.className = 'text-sm text-slate-700 dark:text-slate-300';
                                nameSpan.textContent = choice.nom;
                                leftPart.appendChild(nameSpan);
                                
                                choiceLabel.appendChild(leftPart);
                                
                                const rightPart = document.createElement('div');
                                rightPart.className = 'text-xs font-medium text-slate-500 dark:text-slate-400 flex flex-col items-end';
                                
                                if (choice.prix > 0) {
                                    const prixSpan = document.createElement('span');
                                    prixSpan.className = 'text-green-600 dark:text-green-400';
                                    prixSpan.textContent = `+${parseFloat(choice.prix).toLocaleString('fr-FR')}€`;
                                    rightPart.appendChild(prixSpan);
                                }
                                
                                if (choice.temps > 0) {
                                    const tempsSpan = document.createElement('span');
                                    tempsSpan.textContent = `+${choice.temps} min`;
                                    rightPart.appendChild(tempsSpan);
                                }
                                
                                choiceLabel.appendChild(rightPart);
                                choicesContainer.appendChild(choiceLabel);
                            });

                            optionGroup.appendChild(choicesContainer);
                            container.appendChild(optionGroup);
                        });
                    }
                } catch (e) {
                    console.error("Erreur lors du parsing des options", e);
                }
            }
            
            // Fonction pour synchroniser les champs entre mobile et desktop
            function syncFields() {
                if (dateInput && dateInputMobile) {
                    dateInputMobile.value = dateInput.value;
                    dateInput.value = dateInputMobile.value;
                }
                if (heureInput && heureInputMobile) {
                    heureInputMobile.value = heureInput.value;
                    heureInput.value = heureInputMobile.value;
                }
                if (serviceSelect && serviceSelectMobile) {
                    serviceSelectMobile.value = serviceSelect.value;
                    serviceSelect.value = serviceSelectMobile.value;
                    
                    // Synchroniser aussi les options affichées
                    if (serviceSelect.value) {
                        window.handleServiceChange(serviceSelect);
                    }
                }
            }
            
            // Noms des jours
            const joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            const joursComplets = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            const mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
            
            // Horaires d'ouverture par jour de semaine
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
            
            function parseTimeToMinutes(timeStr) {
                const [h, m] = timeStr.split(':').map(Number);
                return (h || 0) * 60 + (m || 0);
            }

            /** Select avec une valeur (desktop prioritaire si les deux sont synchronisés) */
            function getActiveServiceSelect() {
                if (serviceSelect && serviceSelect.value) return serviceSelect;
                if (serviceSelectMobile && serviceSelectMobile.value) return serviceSelectMobile;
                return serviceSelect || serviceSelectMobile;
            }

            /** Durée totale affichée : prestation + options cochées (min 30 si applicable) */
            function getTotalDurationMinutes() {
                const sel = getActiveServiceSelect();
                if (!sel) return 0;
                const opt = sel.options[sel.selectedIndex];
                if (!opt || !opt.value) return 0;
                const typeStructure = opt.dataset.typeStructure || 'ponctuel';
                if (typeStructure === 'date_butoire' || typeStructure === 'sur_devis') return 0;

                let base = parseInt(opt.dataset.duree, 10);
                if (Number.isNaN(base) || base < 1) base = 30;

                [serviceOptionsContainer, serviceOptionsContainerMobile].forEach(container => {
                    if (!container) return;
                    container.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
                        base += parseInt(radio.dataset.temps, 10) || 0;
                    });
                });
                return base;
            }

            /** Le créneau [slotTime, slotTime+30) chevauche l'intervalle de la réservation en cours (même jour) */
            function isSlotInReservationSpan(dateStr, slotTime, sel, totalMinutes) {
                if (!sel || totalMinutes < 1) return false;
                if (sel.date !== dateStr) return false;
                const startMin = parseTimeToMinutes(sel.time);
                const endMin = startMin + totalMinutes;
                const slotStart = parseTimeToMinutes(slotTime);
                const slotEnd = slotStart + 30;
                return slotStart < endMin && slotEnd > startMin;
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
            
            // Vérifier si un créneau horaire est dans une des plages horaires
            function isTimeInPlages(timeStr, plages) {
                if (!plages || plages.length === 0) {
                    return false;
                }
                
                const [h, m] = timeStr.split(':').map(Number);
                const timeMinutes = h * 60 + m;
                
                return plages.some(plage => {
                    if (!plage.ouverture || !plage.fermeture) {
                        return false;
                    }
                    
                    const [startH, startM] = plage.ouverture.split(':').map(Number);
                    const [endH, endM] = plage.fermeture.split(':').map(Number);
                    
                    const startMinutes = startH * 60 + startM;
                    const endMinutes = endH * 60 + endM;
                    
                    // Le créneau est dans la plage s'il commence dans la plage et se termine avant la fin
                    // On vérifie si le début du créneau (timeMinutes) est >= startMinutes et < endMinutes
                    return timeMinutes >= startMinutes && (timeMinutes + 30) <= endMinutes;
                });
            }
            
            // Générer les créneaux pour une journée (tous les créneaux sont affichés, grisés si hors plage)
            function generateSlots(date, jourSemaine) {
                const plages = horairesParJour[jourSemaine] || [];
                const slots = [];
                
                // Si aucune plage, on peut quand même générer des créneaux grisés ou retourner vide
                // Pour l'instant, on génère quand même une plage standard pour l'affichage
                const hasValidPlages = plages.length > 0 && plages.some(p => p.ouverture && p.fermeture);
                
                if (!hasValidPlages) {
                    // Jour fermé : générer quand même quelques créneaux grisés pour l'affichage
                    // De 8h à 20h par défaut
                    for (let h = 8; h < 20; h++) {
                        for (let m = 0; m < 60; m += 30) {
                            const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                            slots.push({
                                time: timeStr,
                                available: false,
                                isPast: false,
                                isReserved: false,
                                isInPlage: false
                            });
                        }
                    }
                    return slots;
                }
                
                // Trouver la plage horaire globale (min et max de toutes les plages)
                let minHour = 23, maxHour = 0;
                plages.forEach(plage => {
                    if (plage.ouverture && plage.fermeture) {
                        const [startH] = plage.ouverture.split(':').map(Number);
                        const [endH] = plage.fermeture.split(':').map(Number);
                        minHour = Math.min(minHour, startH);
                        maxHour = Math.max(maxHour, endH);
                    }
                });
                
                // S'assurer d'avoir une plage d'affichage raisonnable (min 8h, max 20h)
                minHour = Math.min(minHour, 8);
                maxHour = Math.max(maxHour, 20);
                
                // Générer TOUS les créneaux de la journée (toutes les 30 minutes)
                for (let h = minHour; h <= maxHour; h++) {
                    for (let m = 0; m < 60; m += 30) {
                        // Ne pas générer après maxHour
                        if (h === maxHour && m > 0) {
                            break;
                        }
                        
                        const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                        const dateStr = formatDateISO(date);
                        const now = new Date();
                        const slotDate = new Date(dateStr + 'T' + timeStr + ':00');
                        
                        // Vérifier si le créneau est dans une plage horaire valide
                        const isInPlage = isTimeInPlages(timeStr, plages);
                        
                        // Vérifier si le créneau est dans le passé (+ 1h de marge)
                        const isPast = slotDate <= new Date(now.getTime() + 60 * 60 * 1000);
                        const isReserved = isSlotReserved(dateStr, timeStr);
                        
                        // Le créneau est disponible seulement s'il est dans une plage ET pas dans le passé ET pas réservé
                        const available = isInPlage && !isPast && !isReserved;
                        
                        slots.push({
                            time: timeStr,
                            available: available,
                            isPast: isPast,
                            isReserved: isReserved,
                            isInPlage: isInPlage
                        });
                    }
                }
                
                return slots;
            }

            /** Nombre de créneaux cliquables (dans les plages, futurs, non réservés) pour une semaine donnée */
            function countAvailableSlotsInWeek(weekOffset) {
                const today = new Date();
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + 1 + (weekOffset * 7));
                let total = 0;
                for (let i = 0; i < 7; i++) {
                    const date = new Date(startOfWeek);
                    date.setDate(startOfWeek.getDate() + i);
                    const jourSemaine = date.getDay();
                    const slots = generateSlots(date, jourSemaine);
                    total += slots.filter(s => s.available).length;
                }
                return total;
            }

            /** Première semaine (0–8) avec au moins un créneau ; sinon reste 0 */
            async function pickInitialWeekOffset() {
                await loadReservations();
                const MAX_WEEK = 8;
                showAutoAdvanceHintOffset = null;
                for (let o = 0; o <= MAX_WEEK; o++) {
                    if (countAvailableSlotsInWeek(o) > 0) {
                        currentWeekOffset = o;
                        if (o > 0) {
                            showAutoAdvanceHintOffset = o;
                        }
                        return;
                    }
                }
                currentWeekOffset = 0;
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

                const weekHintEl = document.getElementById('calendar-week-hint');
                if (weekHintEl) {
                    if (showAutoAdvanceHintOffset !== null && currentWeekOffset === showAutoAdvanceHintOffset) {
                        weekHintEl.textContent = 'Il ne restait plus de créneau libre sur la semaine en cours — voici les prochaines disponibilités.';
                        weekHintEl.classList.remove('hidden');
                    } else {
                        weekHintEl.textContent = '';
                        weekHintEl.classList.add('hidden');
                    }
                }
                
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
                            const totalDur = getTotalDurationMinutes();
                            const inSpan = selectedSlot && totalDur > 0
                                && isSlotInReservationSpan(dateStr, slot.time, selectedSlot, totalDur);
                            const isDurationTail = inSpan && !isSelected;

                            if (isSelected) {
                                slotEl.className += 'bg-amber-500 text-white shadow-md transform scale-105';
                            } else if (isDurationTail) {
                                slotEl.setAttribute('aria-label', slot.time + ' — suite de la prestation');
                                slotEl.title = 'Suite du créneau réservé';
                                if (slot.available) {
                                    slotEl.className += 'bg-amber-100/90 dark:bg-amber-900/20 text-amber-900 dark:text-amber-200 ring-1 ring-inset ring-amber-200/90 dark:ring-amber-600/40 cursor-default';
                                } else {
                                    slotEl.className += 'bg-amber-50 dark:bg-amber-950/25 text-amber-800/70 dark:text-amber-200/60 ring-1 ring-inset ring-amber-200/60 dark:ring-amber-800/35 cursor-not-allowed opacity-85';
                                }
                            } else if (slot.available) {
                                slotEl.className += 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50 hover:scale-105 cursor-pointer';
                            } else if (!slot.isInPlage) {
                                // Créneau hors plage horaire : grisé mais visible
                                slotEl.className += 'bg-slate-100 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500 cursor-not-allowed opacity-60';
                            } else {
                                // Créneau dans le passé ou réservé
                                slotEl.className += 'bg-slate-100 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500 cursor-not-allowed';
                            }
                            
                            slotEl.textContent = slot.time;
                            
                            if (slot.available && !isDurationTail) {
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
                
                // Mettre à jour les deux formulaires (mobile et desktop)
                setDateFieldValue(dateInput, date);
                setDateFieldValue(dateInputMobile, date);
                if (heureInput) heureInput.value = time;
                if (heureInputMobile) heureInputMobile.value = time;
                
                renderCalendar();
                updateRecap();
            }
            
            // Modifier updateRecap pour inclure les options
            function updateRecap() {
                const currentServiceSelect = serviceSelectMobile || serviceSelect;
                const currentDateInput = dateInputMobile || dateInput;
                const currentHeureInput = heureInputMobile || heureInput;
                
                if (!currentServiceSelect || !currentDateInput || !currentHeureInput) return;
                
                const option = currentServiceSelect.options[currentServiceSelect.selectedIndex];
                const date = currentDateInput.value;
                const heure = currentHeureInput.value;
                
                if (option && option.value && date && heure) {
                    // new Date('YYYY-MM-DD') est interprété en UTC → jour/semaine faux hors UTC ; parser en date locale
                    let dateObj;
                    if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
                        const [yy, mm, dd] = date.split('-').map(Number);
                        dateObj = new Date(yy, mm - 1, dd);
                    } else {
                        dateObj = new Date(date);
                    }
                    const jourNom = joursComplets[dateObj.getDay()];
                    const jour = dateObj.getDate();
                    const moisNom = mois[dateObj.getMonth()];
                    
                    let basePrix = parseFloat(option.dataset.prix);
                    let baseTemps = parseInt(option.dataset.duree);
                    let optionsDetails = [];

                    // Récupérer les options sélectionnées (depuis le formulaire actif)
                    const isMobile = window.innerWidth < 1280;
                    const containerId = isMobile ? 'service-options-container-mobile' : 'service-options-container';
                    const container = document.getElementById(containerId);
                    
                    if (container) {
                        const selectedRadios = container.querySelectorAll('input[type="radio"]:checked');
                        selectedRadios.forEach(radio => {
                            basePrix += parseFloat(radio.dataset.prix);
                            baseTemps += parseInt(radio.dataset.temps);
                            optionsDetails.push(radio.dataset.nom);
                        });
                    }

                    const serviceText = `📋 ${option.text.split('•')[0].trim()}${optionsDetails.length ? ' (' + optionsDetails.join(', ') + ')' : ''}`;
                    const datetimeText = `📅 ${jourNom} ${jour} ${moisNom} à ${heure}`;
                    const durationText = `⏱️ Durée : ${baseTemps} minutes`;
                    const prixText = `💰 Total : ${basePrix}€`;
                    
                    // Mettre à jour le récapitulatif desktop
                    if (recapContainer) {
                        recapContainer.classList.remove('hidden');
                        document.getElementById('recap-service').innerHTML = `${serviceText}<br><span class="text-xs opacity-75">${durationText}</span>`;
                        document.getElementById('recap-datetime').textContent = datetimeText;
                        document.getElementById('recap-prix').textContent = prixText;
                    }
                    
                    // Mettre à jour le récapitulatif mobile
                    if (recapContainerMobile) {
                        recapContainerMobile.classList.remove('hidden');
                        document.getElementById('recap-service-mobile').innerHTML = `${serviceText}<br><span class="text-xs opacity-75">${durationText}</span>`;
                        document.getElementById('recap-datetime-mobile').textContent = datetimeText;
                        document.getElementById('recap-prix-mobile').textContent = prixText;
                    }
                } else {
                    if (recapContainer) recapContainer.classList.add('hidden');
                    if (recapContainerMobile) recapContainerMobile.classList.add('hidden');
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
                showAutoAdvanceHintOffset = null;
                renderCalendar();
            });
            
            // Écouter les changements sur les deux formulaires
            // serviceSelect?.addEventListener('change', updateRecap); // Remplacé par onchange="handleServiceChange(this)" dans le HTML
            dateInput?.addEventListener('change', updateRecap);
            heureInput?.addEventListener('change', updateRecap);
            
            // if (serviceSelectMobile) {
            //     serviceSelectMobile.addEventListener('change', updateRecap); // Remplacé par onchange="handleServiceChange(this)" dans le HTML
            // }
            if (dateInputMobile) {
                dateInputMobile.addEventListener('change', updateRecap);
            }
            if (heureInputMobile) {
                heureInputMobile.addEventListener('change', updateRecap);
            }
            
            // Si un service est pré-sélectionné via l'URL, déclencher la mise à jour
            const serviceParam = new URLSearchParams(window.location.search).get('service');
            if (serviceParam) {
                // Attendre que le DOM soit complètement chargé
                setTimeout(() => {
                    if (serviceSelect && serviceSelect.value === serviceParam) {
                        // Utiliser la nouvelle fonction handleServiceChange
                        window.handleServiceChange(serviceSelect);
                    }
                    if (serviceSelectMobile && serviceSelectMobile.value === serviceParam) {
                        // Utiliser la nouvelle fonction handleServiceChange
                        window.handleServiceChange(serviceSelectMobile);
                    }
                }, 100);
            }
            
            // Charger la première semaine où il reste au moins un créneau (ex. samedi 20h → semaine suivante)
            await pickInitialWeekOffset();
            await renderCalendar();
        });
    </script>

    @include('partials.footer')
    @include('partials.cookie-banner')
</body>
</html>
