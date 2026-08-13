<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Dashboard - Allo Tata</title>
        @include('partials.favicon')
        
        <!-- PWA Configuration -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#0f172a">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Allo Tata">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/emploi-du-temps.js'])
        @include('partials.theme-script')
        
        <script>
            window.VAPID_PUBLIC_KEY = '{{ config("webpush.vapid.public_key") }}';
            window.REVERB_APP_ID = '{{ env("REVERB_APP_ID", "reverb-app") }}';
            window.REVERB_APP_KEY = '{{ env("REVERB_APP_KEY", "reverb-key") }}';
            window.REVERB_HOST = '{{ env("REVERB_HOST", "127.0.0.2") }}';
            window.REVERB_PORT = '{{ env("REVERB_PORT", "8080") }}';
            window.REVERB_SCHEME = '{{ env("REVERB_SCHEME", "http") }}';
            window.currentUserId = {{ auth()->id() ?? 'null' }};
        </script>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex flex-col">
        @include('partials.super-user-banner')
        @include('partials.push-notifications-banner')
        <!-- Navigation -->
        <nav class="pwa-desktop-header bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 ">
            <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <!-- Menu Burger pour mobile web -->
                        @include('components.mobile-nav', ['navType' => 'dashboard'])
                        
                        <a href="{{ route('home') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                        <span class="hidden md:inline-flex items-center pl-4 ml-1 border-l border-orange-400 dark:border-orange-500/70">
                            <span class="text-sm font-semibold tracking-wide text-slate-600 dark:text-slate-300">
                                Tableau de Bord
                            </span>
                        </span>
                        @if($user->is_admin && config('app.debug'))
                            <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-300 dark:border-yellow-700">
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                DEBUG MODE
                            </span>
                        @endif
                    </div>
                    <!-- Liens desktop (masqués sur mobile) -->
                    <div class="hidden lg:flex items-center gap-4">
                        @if($user->is_admin)
                            <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                Administration
                            </a>
                        @endif
                        <a href="{{ route('checkout.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            Espace Paiement
                        </a>
                        <a href="{{ route('settings.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Paramètres
                        </a>
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            {{ $user->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        @php
            $activeTab = request('tab', 'accueil');
        @endphp

        <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
            <!-- Messages de succès -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Messages d'erreur -->
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- En-tête -->
            <div id="dashboard-welcome" class="mb-8 {{ $activeTab === 'messagerie' ? 'hidden' : '' }}">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Bienvenue, {{ $user->name }} !
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Gérez votre compte et vos entreprises depuis ce tableau de bord.
                </p>
            </div>

            {{-- Header PWA (visible uniquement en PWA mobile) --}}
            <x-nav.pwa-header :title="'Dashboard'" />

            <!-- Layout avec Sidebar -->
            <div class="flex gap-6 pwa-flex-layout">
                {{-- Sidebar (composant centralisé) --}}
                <x-nav.sidebar :items="$navItems" :active-tab="$activeTab" context="dashboard" />

                <!-- Main Content Area -->
                <main class="flex-1 min-w-0">
                    {{-- Barre onglets mobile (composant centralisé) --}}
                    <x-nav.mobile-tabs :items="$navItems" :active-tab="$activeTab" />
                    <div id="dashboard-main-card" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 {{ $activeTab === 'messagerie' ? 'p-0 overflow-hidden' : 'p-4 sm:p-6' }}">
                        <!-- Onglet Accueil -->
                        <div id="tab-accueil" class="tab-content {{ $activeTab !== 'accueil' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.accueil')
                        </div>

                        <!-- Onglet Apprendre -->
                        <div id="tab-apprendre" class="tab-content {{ $activeTab !== 'apprendre' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.apprendre')
                        </div>

                        <!-- Onglet Entreprises -->
                        <div id="tab-entreprises" class="tab-content {{ $activeTab !== 'entreprises' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.entreprises')
                        </div>
                        
                        <!-- Onglet Installer -->
                        <div id="tab-installer" class="tab-content {{ $activeTab !== 'installer' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.installer')
                        </div>

                        <!-- Onglet Abonnements -->
                        <div id="tab-abonnements" class="tab-content {{ $activeTab !== 'abonnements' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.abonnements')
                        </div>

                        <!-- Onglet Réservations -->
                        @if($user->est_client)
                            <div id="tab-reservations" class="tab-content {{ $activeTab !== 'reservations' ? 'hidden' : '' }}">
                                @include('dashboard.tabs.reservations')
                            </div>
                        @endif

                        <!-- Onglet Emploi du temps -->
                        @if($user->est_gerant || $entreprises->count() > 0)
                            <div id="tab-emploi-du-temps" class="tab-content {{ $activeTab !== 'emploi-du-temps' ? 'hidden' : '' }}">
                                @include('dashboard.tabs.emploi-du-temps')
                            </div>
                        @endif

                        <!-- Onglet Factures -->
                        <div id="tab-factures" class="tab-content {{ $activeTab !== 'factures' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.factures')
                        </div>

                        <!-- Onglet Messagerie -->
                        <div id="tab-messagerie" class="tab-content {{ $activeTab !== 'messagerie' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.messagerie')
                        </div>

                        <!-- Onglet Notifications -->
                        <div id="tab-notifications" class="tab-content {{ $activeTab !== 'notifications' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.notifications')
                        </div>

                        <!-- Onglet Sécurité -->
                        <div id="tab-securite" class="tab-content {{ $activeTab !== 'securite' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.securite')
                        </div>

                        <!-- Onglet Support -->
                        <div id="tab-support" class="tab-content {{ $activeTab !== 'support' ? 'hidden' : '' }}">
                            @include('dashboard.tabs.support')
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            // Gestion des onglets
            function showTab(tabName) {
                // Masquer tous les contenus
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Afficher le contenu sélectionné
                const tabContent = document.getElementById('tab-' + tabName);
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                }

                // --- Sidebar & mobile tabs ---
                document.querySelectorAll('.sidebar-tab').forEach(btn => {
                    btn.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
                    btn.classList.add('text-slate-600', 'dark:text-slate-400');
                });
                document.querySelectorAll(`.sidebar-tab[data-tab="${tabName}"]`).forEach(btn => {
                    btn.classList.remove('text-slate-600', 'dark:text-slate-400');
                    btn.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
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
                const welcome = document.getElementById('dashboard-welcome');
                if (welcome) {
                    welcome.classList.toggle('hidden', tabName === 'messagerie');
                }

                // Initialiser l'emploi du temps si on affiche cet onglet
                if (tabName === 'emploi-du-temps' && typeof window.initDashboardEmploiDuTemps === 'function') {
                    setTimeout(() => window.initDashboardEmploiDuTemps(), 100);
                }
            }
        </script>

        <!-- Modale de modification de réservation -->
        <div id="modify-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Modifier la réservation</h3>
                    <button onclick="closeModifyModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="modify-form" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    
                    <div id="modify-date-heure-wrapper">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date *</label>
                                <input 
                                    type="date" 
                                    name="date_reservation" 
                                    id="modify-date"
                                    min="{{ date('Y-m-d') }}"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Heure *</label>
                                <input 
                                    type="time" 
                                    name="heure_reservation" 
                                    id="modify-heure"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                            </div>
                        </div>
                    </div>
                    <div id="modify-date-butoire-wrapper" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date butoire *</label>
                        <input 
                            type="date" 
                            name="date_butoire" 
                            id="modify-date-butoire"
                            min="{{ date('Y-m-d') }}"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Lieu (optionnel)</label>
                        <input 
                            type="text" 
                            name="lieu" 
                            id="modify-lieu"
                            placeholder="Adresse du rendez-vous"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                        <textarea 
                            name="notes" 
                            id="modify-notes"
                            rows="3"
                            placeholder="Informations complémentaires..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                        ></textarea>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button 
                            type="button"
                            onclick="closeModifyModal()"
                            class="flex-1 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition"
                        >
                            Annuler
                        </button>
                        <button 
                            type="submit"
                            class="flex-1 px-4 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                        >
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openModifyModal(reservationId, date, heure, lieu, notes, isDateButoire) {
                document.getElementById('modify-form').action = `/dashboard/reservation/${reservationId}/modify`;
                document.getElementById('modify-date').value = date || '';
                document.getElementById('modify-heure').value = heure || '09:00';
                document.getElementById('modify-lieu').value = lieu || '';
                document.getElementById('modify-notes').value = notes || '';
                var dateHeureWrapper = document.getElementById('modify-date-heure-wrapper');
                var dateButoireWrapper = document.getElementById('modify-date-butoire-wrapper');
                var dateInput = document.getElementById('modify-date');
                var heureInput = document.getElementById('modify-heure');
                var dateButoireInput = document.getElementById('modify-date-butoire');
                if (isDateButoire) {
                    dateHeureWrapper.classList.add('hidden');
                    dateButoireWrapper.classList.remove('hidden');
                    dateInput.removeAttribute('required');
                    heureInput.removeAttribute('required');
                    dateInput.disabled = true;
                    heureInput.disabled = true;
                    dateButoireInput.disabled = false;
                    dateButoireInput.setAttribute('required', 'required');
                    dateButoireInput.value = date || '';
                } else {
                    dateHeureWrapper.classList.remove('hidden');
                    dateButoireWrapper.classList.add('hidden');
                    dateInput.setAttribute('required', 'required');
                    heureInput.setAttribute('required', 'required');
                    dateInput.disabled = false;
                    heureInput.disabled = false;
                    dateButoireInput.disabled = true;
                    dateButoireInput.removeAttribute('required');
                    dateButoireInput.value = '';
                }
                document.getElementById('modify-modal').classList.remove('hidden');
            }

            function closeModifyModal() {
                document.getElementById('modify-modal').classList.add('hidden');
            }

            // Fermer la modale en cliquant en dehors
            document.getElementById('modify-modal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModifyModal();
                }
            });
        </script>
    @stack('scripts')
    {{-- Bottom Bar PWA (visible uniquement en PWA mobile) --}}
    <x-nav.pwa-bottom-bar :items="$navItems" :active-tab="$activeTab" context="dashboard" />
    @include('partials.footer')
    @include('partials.pwa-install-banner')
    @include('partials.cookie-banner')
    </body>
</html>
