<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            // Configuration Reverb pour la présence en temps réel
            window.REVERB_APP_ID = '{{ env("REVERB_APP_ID", "reverb-app") }}';
            window.REVERB_APP_KEY = '{{ env("REVERB_APP_KEY", "reverb-key") }}';
            window.REVERB_HOST = '{{ env("REVERB_HOST", "127.0.0.2") }}';
            window.REVERB_PORT = '{{ env("REVERB_PORT", "8080") }}';
            window.REVERB_SCHEME = '{{ env("REVERB_SCHEME", "http") }}';
            window.currentUserId = {{ auth()->id() ?? 'null' }};
        </script>
        <title>{{ $entreprise->nom }} - Dashboard - Allo Tata</title>
        @include('partials.favicon')
        
        <!-- PWA / Manifest Dynamique -->
        <link rel="manifest" href="{{ route('manifest.show', $entreprise->slug) }}">
        <meta name="theme-color" content="#0f172a">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ $entreprise->nom }}">
        <link rel="apple-touch-icon" href="{{ route('manifest.icon', ['slug' => $entreprise->slug, 'size' => 192]) }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/emploi-du-temps.js'])
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
        @include('partials.theme-script')
        <script>
            window.VAPID_PUBLIC_KEY = '{{ config("webpush.vapid.public_key") }}';
        </script>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex flex-col">
        @include('partials.super-user-banner')
        @include('partials.push-notifications-banner')
        <!-- Navigation -->
        <nav class="pwa-desktop-header bg-slate-100/90 dark:bg-slate-800 border-b border-orange-300/80 dark:border-orange-700/40 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-2 sm:gap-4">
                        <a href="{{ route('dashboard') }}" class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                        <span class="hidden md:inline-flex items-center pl-4 ml-1 border-l border-orange-400 dark:border-orange-500/70">
                            <span class="text-sm font-semibold tracking-wide text-indigo-700 dark:text-indigo-300">
                                Mon entreprise
                            </span>
                        </span>
                        
                        <!-- Menu Burger pour mobile web -->
                        @include('components.mobile-nav', ['navType' => 'entreprise', 'entreprise' => $entreprise, 'aGestionMultiPersonnes' => $aGestionMultiPersonnes ?? false, 'activeTab' => $activeTab ?? 'accueil'])
                        
                        <!-- Sélecteur d'entreprise -->
                        <div class="relative hidden md:block">
                            <button 
                                onclick="toggleEntrepriseSelector()"
                                class="flex items-center gap-2 px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                            >
                                @if($entreprise->logo)
                                    <img src="{{ asset('media/' . $entreprise->logo) }}" alt="" class="w-6 h-6 rounded object-cover">
                                @else
                                    <div class="w-6 h-6 rounded bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-medium text-slate-900 dark:text-white max-w-32 truncate">{{ $entreprise->nom }}</span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown des entreprises -->
                            <div id="entreprise-selector" class="hidden absolute left-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50">
                                <div class="px-3 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Mes entreprises</div>
                                <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="flex items-center gap-3 px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400">
                                    @if($entreprise->logo)
                                        <img src="{{ asset('media/' . $entreprise->logo) }}" alt="" class="w-8 h-8 rounded object-cover">
                                    @else
                                        <div class="w-8 h-8 rounded bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-sm font-bold">
                                            {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium truncate">{{ $entreprise->nom }}</p>
                                        <p class="text-xs text-green-600 dark:text-green-500">Entreprise actuelle</p>
                                    </div>
                                </a>
                                @foreach($autresEntreprises as $autre)
                                    <a href="{{ route('entreprise.dashboard', $autre->slug) }}" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-white transition">
                                        @if($autre->logo)
                                            <img src="{{ asset('media/' . $autre->logo) }}" alt="" class="w-8 h-8 rounded object-cover">
                                        @else
                                            <div class="w-8 h-8 rounded bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-sm font-bold">
                                                {{ strtoupper(substr($autre->nom, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium truncate">{{ $autre->nom }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $autre->type_activite }}</p>
                                        </div>
                                    </a>
                                @endforeach
                                <div class="border-t border-slate-200 dark:border-slate-700 mt-2 pt-2">
                                    <a href="{{ route('entreprise.create') }}" class="flex items-center gap-2 px-3 py-2 text-green-600 dark:text-green-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Ajouter une entreprise
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 sm:gap-3 desktop-nav-links">
                        <a href="{{ route('public.entreprise', $entreprise->slug) }}" target="_blank" class="hidden lg:flex items-center px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition touch-target">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <span class="hidden xl:inline ml-1">Page publique</span>
                        </a>
                        <a href="{{ route('tickets.create') }}" class="hidden lg:inline-flex items-center px-3 py-2 text-sm font-medium bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-400 rounded-lg transition touch-target">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                            <span class="hidden xl:inline ml-1">Support</span>
                        </a>
                        <a href="{{ route('dashboard') }}" class="hidden xl:inline-flex items-center px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition touch-target">
                            Mon compte
                        </a>
                        <span class="hidden xl:inline text-sm text-slate-500 dark:text-slate-400">{{ $user->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="hidden xl:inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition touch-target">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 main-content flex-1 w-full">
            <!-- Messages de succès -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-green-800 dark:text-green-400 font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Messages d'erreur flash -->
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-800 dark:text-red-400 font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Erreurs de validation -->
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- En-tête de l'entreprise -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-4 sm:mb-6">
                <div class="flex items-center gap-3 sm:gap-4">
                    @if($entreprise->logo)
                        <img src="{{ asset('media/' . $entreprise->logo) }}" alt="{{ $entreprise->nom }}" class="w-16 h-16 rounded-xl object-cover border-2 border-slate-200 dark:border-slate-700">
                    @else
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white truncate">{{ $entreprise->nom }}</h1>
                            @if($entreprise->est_verifiee)
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">Vérifiée</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full">En attente</span>
                            @endif
                        </div>
                        <p class="text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }} @if($entreprise->ville) • {{ $entreprise->ville }} @endif</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($stats['nombre_avis'] > 0)
                            <div class="text-right">
                                <div class="flex items-center gap-1 text-yellow-500">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <span class="font-bold">{{ $stats['note_moyenne'] }}</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $stats['nombre_avis'] }} avis</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Header PWA (visible uniquement en PWA mobile) --}}
            <x-nav.pwa-header :title="$entreprise->nom" :show-back="true" :back-url="route('dashboard')" />

            <!-- Layout avec Sidebar -->
            <div class="flex gap-6 pwa-flex-layout">
                {{-- Sidebar (composant centralisé) --}}
                <x-nav.sidebar :items="$navItems" :active-tab="$activeTab" context="entreprise" />

                <!-- Main Content Area -->
                <main class="flex-1 min-w-0">
                    {{-- Barre onglets mobile (composant centralisé) --}}
                    <x-nav.mobile-tabs :items="$navItems" :active-tab="$activeTab" />
                    <div id="dashboard-main-card" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 {{ $activeTab === 'messagerie' ? 'p-0 overflow-hidden' : 'p-4 sm:p-6' }}">
                        <!-- Onglet Accueil -->
                        <div id="tab-accueil" class="tab-content {{ $activeTab !== 'accueil' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.accueil')
                        </div>

                        <!-- Onglet Agenda -->
                        <div id="tab-agenda" class="tab-content {{ $activeTab !== 'agenda' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.agenda')
                        </div>

                        <!-- Onglet Équipe (multi-personnes) -->
                        @if($aGestionMultiPersonnes)
                            <div id="tab-equipe" class="tab-content {{ $activeTab !== 'equipe' ? 'hidden' : '' }}">
                                @include('entreprise.dashboard.tabs.equipe')
                            </div>
                        @endif

                        <!-- Onglet Réservations -->
                        <div id="tab-reservations" class="tab-content {{ $activeTab !== 'reservations' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.reservations')
                        </div>

                        <!-- Onglet Factures -->
                        <div id="tab-factures" class="tab-content {{ $activeTab !== 'factures' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.factures')
                        </div>

                        <!-- Onglet Finances (Recettes) -->
                        <div id="tab-finances" class="tab-content {{ $activeTab !== 'finances' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.finances')
                        </div>

                        <!-- Onglet Statistiques -->
                        <div id="tab-statistiques" class="tab-content {{ $activeTab !== 'statistiques' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.statistiques', [
                                'entreprise' => $entreprise,
                                'stats' => $statsStatistiques ?? [
                                    'total_visites' => 0,
                                    'visites_exploration' => 0,
                                    'visites_rapides' => 0,
                                    'reservations' => 0,
                                    'taux_conversion' => 0,
                                    'temps_moyen_avant_reservation' => 0,
                                    'evolution_visites' => [],
                                    'repartition_pages' => ['accueil' => 0, 'agenda' => 0, 'store' => 0, 'services' => 0, 'produits' => 0],
                                    'temps_moyen_par_page' => ['accueil' => 0, 'agenda' => 0, 'store' => 0, 'services' => 0, 'produits' => 0],
                                    'taux_rebond' => 0
                                ],
                                'visiteursSansReservation' => $visiteursSansReservation ?? collect([]),
                                'topServices' => $topServices ?? [],
                                'topProduits' => $topProduits ?? []
                            ])
                        </div>

                        <!-- Onglet Outils -->
                        <div id="tab-outils" class="tab-content {{ $activeTab !== 'outils' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.outils')
                        </div>

                        <!-- Onglet Messagerie -->
                        <div id="tab-messagerie" class="tab-content {{ $activeTab !== 'messagerie' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.messagerie-liste')
                        </div>

                        <!-- Onglet Fidélisation -->
                        <div id="tab-fidelisation" class="tab-content {{ $activeTab !== 'fidelisation' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.fidelisation')
                        </div>

                        <!-- Onglet Abonnements -->
                        <div id="tab-abonnements" class="tab-content {{ $activeTab !== 'abonnements' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.abonnements')
                        </div>

                        <!-- Onglet Services -->
                        <div id="tab-mes-services" class="tab-content {{ $activeTab !== 'services' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.services')
                        </div>

                        <!-- Onglet Stock -->
                        <div id="tab-stock" class="tab-content {{ $activeTab !== 'stock' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.stock')
                        </div>

                        <!-- Onglet Commandes Produits -->
                        <div id="tab-commandes" class="tab-content {{ $activeTab !== 'commandes' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.commandes')
                        </div>
                        
                        <!-- Onglet Paramètres -->
                        <div id="tab-parametres" class="tab-content {{ $activeTab !== 'parametres' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.parametres')
                        </div>
                        
                        <!-- Onglet Installer -->
                        <div id="tab-installer" class="tab-content {{ $activeTab !== 'installer' ? 'hidden' : '' }}">
                            @include('entreprise.dashboard.tabs.installer')
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            // Gestion des onglets
            async function showTab(tabName) {
                // Masquer tous les contenus
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // --- Sidebar & mobile tabs ---
                document.querySelectorAll('.sidebar-tab').forEach(btn => {
                    btn.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
                    btn.classList.add('text-slate-600', 'dark:text-slate-400');
                });

                // --- PWA Bottom Bar ---
                document.querySelectorAll('.pwa-tab-btn[data-tab]').forEach(btn => {
                    btn.classList.remove('text-green-600', 'dark:text-green-400');
                    btn.classList.add('text-slate-400', 'dark:text-slate-500');
                    const ind = btn.querySelector('.pwa-active-indicator');
                    if (ind) ind.remove();
                    const svg = btn.querySelector('svg');
                    if (svg) svg.setAttribute('stroke-width', '1.5');
                });

                // Afficher le contenu sélectionné
                const tabContent = document.getElementById('tab-' + tabName);
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                    
            // Recharger le contenu via Ajax (surtout pour les stocks)
            // Liste des onglets qui peuvent être rechargés
            // Exclure mes-services et services de reloadableTabs car la modal est incluse dans index.blade.php
            // et recharger l'onglet briserait les événements JS de la modal s'ils étaient dans l'onglet
            const reloadableTabs = ['stock', 'commandes', 'reservations', 'agenda'];
                    
                    if (reloadableTabs.includes(tabName)) {
                        // Cache pour éviter les rechargements trop fréquents (délai de 2 secondes)
                        const now = Date.now();
                        const lastReload = window.tabReloadCache = window.tabReloadCache || {};
                        const lastReloadTime = lastReload[tabName] || 0;
                        const RELOAD_DELAY = 2000; // 2 secondes
                        
                        // Ne recharger que si ça fait plus de 2 secondes depuis le dernier rechargement
                        if (now - lastReloadTime > RELOAD_DELAY) {
                            lastReload[tabName] = now;
                            
                            // Afficher un indicateur de chargement discret (petit point vert en haut à droite)
                            const existingIndicator = tabContent.querySelector('#tab-loading-indicator');
                            if (!existingIndicator) {
                                const loadingIndicator = document.createElement('div');
                                loadingIndicator.id = 'tab-loading-indicator';
                                loadingIndicator.className = 'absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full animate-pulse opacity-75';
                                loadingIndicator.style.zIndex = '1000';
                                if (getComputedStyle(tabContent).position === 'static') {
                                    tabContent.style.position = 'relative';
                                }
                                tabContent.appendChild(loadingIndicator);
                            }
                            
                            // Recharger le contenu en arrière-plan
                            fetch(`/m/{{ $entreprise->slug }}/reload-tab/${tabName}`, {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success && data.html) {
                                    // Remplacer le contenu de l'onglet
                                    tabContent.innerHTML = data.html;
                                    
                                    // Réexécuter les scripts dans le nouveau contenu
                                    const scripts = tabContent.querySelectorAll('script');
                                    scripts.forEach(oldScript => {
                                        const newScript = document.createElement('script');
                                        Array.from(oldScript.attributes).forEach(attr => {
                                            newScript.setAttribute(attr.name, attr.value);
                                        });
                                        newScript.textContent = oldScript.textContent;
                                        oldScript.parentNode.replaceChild(newScript, oldScript);
                                    });
                                    
                                    // Réinitialiser les fonctions spécifiques selon l'onglet
                                    if (tabName === 'agenda' && typeof initCalendar === 'function') {
                                        setTimeout(initCalendar, 100);
                                    }
                                    
                                    // Déclencher un événement personnalisé pour permettre aux scripts de se réinitialiser
                                    const reloadEvent = new CustomEvent('tabReloaded', { detail: { tab: tabName } });
                                    window.dispatchEvent(reloadEvent);
                                }
                            })
                            .catch(error => {
                                console.error('Erreur lors du rechargement de l\'onglet:', error);
                                // En cas d'erreur, on garde le contenu existant (silencieux)
                            })
                            .finally(() => {
                                // Retirer l'indicateur de chargement
                                const indicator = tabContent.querySelector('#tab-loading-indicator');
                                if (indicator) {
                                    indicator.remove();
                                }
                            });
                        }
                    }
                }

                // Activer sidebar & mobile tabs
                document.querySelectorAll(`.sidebar-tab[data-tab="${tabName}"]`).forEach(btn => {
                    btn.classList.remove('text-slate-600', 'dark:text-slate-400');
                    btn.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
                });

                // Activer PWA Bottom Bar
                document.querySelectorAll(`.pwa-tab-btn[data-tab="${tabName}"]`).forEach(btn => {
                    btn.classList.remove('text-slate-400', 'dark:text-slate-500');
                    btn.classList.add('text-green-600', 'dark:text-green-400');
                    if (!btn.querySelector('.pwa-active-indicator')) {
                        const ind = document.createElement('span');
                        ind.className = 'pwa-active-indicator absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-green-500 rounded-full';
                        btn.insertBefore(ind, btn.firstChild);
                    }
                    const svg = btn.querySelector('svg');
                    if (svg) svg.setAttribute('stroke-width', '2.5');
                });

                // Mettre à jour l'URL sans recharger la page
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                if (tabName !== 'messagerie') {
                    url.searchParams.delete('conversation');
                }
                window.history.replaceState({}, '', url);

                const card = document.getElementById('dashboard-main-card');
                if (card) {
                    if (tabName === 'messagerie') {
                        card.classList.remove('p-4', 'sm:p-6');
                        card.classList.add('p-0', 'overflow-hidden');
                    } else {
                        card.classList.add('p-4', 'sm:p-6');
                        card.classList.remove('p-0', 'overflow-hidden');
                    }
                }

                // Initialiser le calendrier si on affiche l'onglet agenda
                if (tabName === 'agenda' && typeof initCalendar === 'function') {
                    setTimeout(initCalendar, 100);
                }
            }

            // Sélecteur d'entreprise
            function toggleEntrepriseSelector() {
                const selector = document.getElementById('entreprise-selector');
                selector.classList.toggle('hidden');
            }

            // Fermer le sélecteur quand on clique ailleurs
            document.addEventListener('click', function(e) {
                const selector = document.getElementById('entreprise-selector');
                const button = e.target.closest('button');
                if (!e.target.closest('#entreprise-selector') && (!button || !button.onclick?.toString().includes('toggleEntrepriseSelector'))) {
                    selector?.classList.add('hidden');
                }
            });

            // Afficher l'onglet depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            let tab = urlParams.get('tab') || '{{ $activeTab }}';
            
            // Correction pour l'onglet services
            if (tab === 'services') tab = 'mes-services';
            
            if (tab) {
                showTab(tab);
            }
        </script>

        <!-- Modal Services (Déplacé ici pour éviter les problèmes de z-index/transform) -->
        @include('entreprise.dashboard.tabs.services-modal-content')
        
        <!-- Modal Produits (Déplacé ici pour éviter les problèmes de z-index/transform) -->
        @include('entreprise.dashboard.tabs.stock-modal-content')

        <!-- Overlay Upsell Site Web Vitrine -->
        @if(!$aSiteWebActif)
        @php
            $userHasPremium = auth()->user()->aAbonnementActif();
            $peutEssayerSiteWeb = $entreprise->peutDemarrerEssai('site_web');
        @endphp
        <div id="site-web-upsell-overlay" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('site-web-upsell-overlay').classList.add('hidden')"></div>
            
            <!-- Card -->
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full overflow-hidden">
                <!-- Header gradient -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8 text-center">
                    <div class="w-16 h-16 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Votre site vitrine professionnel</h3>
                    <p class="text-blue-100 mt-2 text-sm">Donnez une vitrine en ligne a votre activite</p>
                </div>

                <!-- Content -->
                <div class="px-6 py-6 space-y-4">
                    <!-- Avertissement Premium requis -->
                    @if(!$userHasPremium)
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-400">Abonnement Premium requis</p>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">Les options d'entreprise necessitent un abonnement Premium actif.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Prix -->
                    <div class="flex items-center justify-center gap-2 bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                        <span class="text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $subscriptionPrices['site_web']['formatted'] }}</span>
                        <span class="text-slate-500 dark:text-slate-400 text-sm">{{ $subscriptionPrices['site_web']['period'] ?? '/mois' }}</span>
                    </div>

                    <!-- Avantages -->
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Page vitrine personnalisee accessible via /w/{{ $entreprise->slug_web ?? $entreprise->slug }}</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Visible par tous vos clients et prospects</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Agenda, reservation et contact integres</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-slate-700 dark:text-slate-300">Aucune connaissance technique requise</span>
                        </li>
                    </ul>

                    <!-- Boutons -->
                    <div class="space-y-3 pt-2">
                        @if(!$userHasPremium)
                            <!-- Pas de Premium : rediriger vers l'abonnement Premium -->
                            <a href="{{ route('subscription.index') }}" class="w-full py-3 px-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                                <span>🚀</span>
                                Obtenir l'abonnement Premium
                            </a>
                        @else
                            <!-- Essai gratuit si eligible -->
                            @if($peutEssayerSiteWeb)
                                <form action="{{ route('essai-gratuit.entreprise', $entreprise->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="site_web">
                                    <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white font-semibold rounded-xl shadow-lg shadow-orange-500/25 transition-all duration-200 transform hover:scale-[1.02] flex items-center justify-center gap-2">
                                        <span class="text-lg">🎁</span>
                                        Essayer gratuitement pendant 7 jours
                                    </button>
                                </form>
                                <p class="text-xs text-center text-slate-400 dark:text-slate-500">Sans engagement &bull; Sans carte bancaire</p>
                                <div class="relative flex items-center justify-center py-1">
                                    <span class="absolute inset-x-0 h-px bg-slate-200 dark:bg-slate-700"></span>
                                    <span class="relative px-4 bg-white dark:bg-slate-800 text-xs text-slate-400 dark:text-slate-500">ou</span>
                                </div>
                            @endif

                            <!-- Bouton souscrire -->
                            <form action="{{ route('entreprise.subscriptions.checkout', $entreprise->slug) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="site_web">
                                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/25 transition-all duration-200 transform hover:scale-[1.02] flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    S'abonner a Site Web Vitrine ({{ $subscriptionPrices['site_web']['formatted'] }}{{ $subscriptionPrices['site_web']['period'] ?? '/mois' }})
                                </button>
                            </form>
                        @endif

                        <!-- Separateur -->
                        <div class="relative flex items-center justify-center py-1">
                            <span class="absolute inset-x-0 h-px bg-slate-200 dark:bg-slate-700"></span>
                            <span class="relative px-4 bg-white dark:bg-slate-800 text-xs text-slate-400 dark:text-slate-500">ou bien</span>
                        </div>

                        <!-- Bouton secondaire : Creer quand meme -->
                        <a 
                            href="{{ route('site-web.show', $entreprise->slug_web ?? $entreprise->slug) }}?mode=edit"
                            class="w-full py-2.5 px-4 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 font-medium rounded-xl transition-all duration-200 flex items-center justify-center gap-2 text-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Creer mon site quand meme
                        </a>
                        <p class="text-xs text-center text-slate-400 dark:text-slate-500">
                            Votre site ne sera pas visible publiquement tant que l'abonnement n'est pas actif.
                        </p>
                    </div>
                </div>

                <!-- Close button -->
                <button 
                    onclick="document.getElementById('site-web-upsell-overlay').classList.add('hidden')"
                    class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        {{-- Bottom Bar PWA (visible uniquement en PWA mobile) --}}
        <x-nav.pwa-bottom-bar :items="$navItems" :active-tab="$activeTab" context="entreprise" />
        @include('partials.footer')
        @include('partials.cookie-banner')
    </body>
</html>
