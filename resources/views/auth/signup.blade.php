<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
        <title>Inscription - Allo Tata</title>
        @include('partials.favicon')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex items-center justify-center py-6 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-6 sm:space-y-8">
            <!-- Logo + Titre -->
            <div>
                <a href="{{ route('home') }}" class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    @php
                        use App\Helpers\SiteHelper;
                        $logoUrl = SiteHelper::getLogo('transparent');
                        $siteName = SiteHelper::getSiteName();
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-12 w-auto sm:h-10">
                    @endif
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        {{ $siteName }}
                    </h1>
                </a>
                <h2 class="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Créer un compte
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                    Ou
                    <a href="{{ route('login') }}" class="font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                        connectez-vous à votre compte existant
                    </a>
                </p>
            </div>

            <!-- Stepper horizontal -->
            <div class="flex items-center justify-between px-2" id="stepper">
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 step-circle active" data-step="1">1</div>
                    <span class="text-xs mt-1 text-slate-500 dark:text-slate-400 hidden sm:block">Infos</span>
                </div>
                <div class="flex-1 h-0.5 bg-slate-200 dark:bg-slate-700 step-bar" data-bar="1"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 step-circle" data-step="2">2</div>
                    <span class="text-xs mt-1 text-slate-500 dark:text-slate-400 hidden sm:block">Notifications</span>
                </div>
                <div class="flex-1 h-0.5 bg-slate-200 dark:bg-slate-700 step-bar" data-bar="2"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 step-circle" data-step="3">3</div>
                    <span class="text-xs mt-1 text-slate-500 dark:text-slate-400 hidden sm:block">CGU</span>
                </div>
                <div class="flex-1 h-0.5 bg-slate-200 dark:bg-slate-700 step-bar" data-bar="3"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 step-circle" data-step="4">4</div>
                    <span class="text-xs mt-1 text-slate-500 dark:text-slate-400 hidden sm:block">Email</span>
                </div>
            </div>

            <!-- Erreurs serveur globales -->
            @if ($errors->any())
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <ul class="text-sm text-red-600 dark:text-red-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="signup-form" action="{{ route('register') }}" method="POST" novalidate>
                @csrf

                @if(isset($invitation) && $invitation)
                    <input type="hidden" name="invitation_token" value="{{ $invitation->token }}">
                @endif

                <!-- ═══════════════════════════════════════════════════ -->
                <!--  ÉTAPE 1 : Informations personnelles              -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div id="step-1" class="wizard-step">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prénom *</label>
                                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                                    class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800"
                                    placeholder="Votre prénom">
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="name"></p>
                            </div>
                            <div>
                                <label for="surname" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom de famille *</label>
                                <input id="surname" name="surname" type="text" required value="{{ old('surname') }}"
                                    class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800"
                                    placeholder="Votre nom de famille">
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="surname"></p>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse email *</label>
                            <input id="email" name="email" type="email" required
                                value="{{ old('email', isset($invitation) && $invitation ? $invitation->email : '') }}"
                                {{ isset($invitation) && $invitation ? 'readonly' : '' }}
                                class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 {{ isset($invitation) && $invitation ? 'bg-slate-100 dark:bg-slate-700' : '' }}"
                                placeholder="votre@email.com">
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="email"></p>
                        </div>

                        <div>
                            <label for="date_naissance" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date de naissance *</label>
                            <input id="date_naissance" name="date_naissance" type="date" required value="{{ old('date_naissance') }}"
                                class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800">
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="date_naissance"></p>
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone *</label>
                            <input id="telephone" name="telephone" type="tel" required value="{{ old('telephone') }}"
                                class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800"
                                placeholder="06 12 34 56 78">
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="telephone"></p>
                        </div>

                        <!-- Adresse avec autocomplétion -->
                        <div class="relative">
                            <label for="address-search" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse *</label>
                            <div class="relative">
                                <input id="address-search" type="text" autocomplete="off"
                                    value="{{ old('adresse') ? old('adresse') . ', ' . old('code_postal') . ' ' . old('ville') : '' }}"
                                    class="appearance-none relative block w-full px-3 py-3 pl-10 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800"
                                    placeholder="Commencez à taper votre adresse...">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            </div>
                            <!-- Résultats autocomplétion -->
                            <div id="address-results" class="hidden absolute z-50 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="adresse"></p>

                            <!-- Champs cachés remplis par l'autocomplétion -->
                            <input type="hidden" id="adresse" name="adresse" value="{{ old('adresse') }}">
                            <input type="hidden" id="ville" name="ville" value="{{ old('ville') }}">
                            <input type="hidden" id="code_postal" name="code_postal" value="{{ old('code_postal') }}">
                            <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                            <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                        </div>

                        <!-- Adresse sélectionnée (résumé) -->
                        <div id="address-selected" class="hidden p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-green-800 dark:text-green-300" id="address-selected-label"></p>
                                        <p class="text-xs text-green-600 dark:text-green-400" id="address-selected-detail"></p>
                                    </div>
                                </div>
                                <button type="button" id="address-change-btn" class="text-xs text-green-700 dark:text-green-400 hover:underline font-medium">
                                    Modifier
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mot de passe *</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required
                                    class="appearance-none relative block w-full px-3 py-3 pr-12 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800"
                                    placeholder="Minimum 8 caractères">
                                <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Afficher le mot de passe">
                                    <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                                    <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="password"></p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirmer le mot de passe *</label>
                            <div class="relative">
                                <input id="password_confirmation" name="password_confirmation" type="password" required
                                    class="appearance-none relative block w-full px-3 py-3 pr-12 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800"
                                    placeholder="Confirmez votre mot de passe">
                                <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Afficher le mot de passe">
                                    <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                                    <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" data-error="password_confirmation"></p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="button" onclick="goToStep(2)"
                            class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                            Suivant
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!--  ÉTAPE 2 : Notifications                          -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div id="step-2" class="wizard-step hidden">
                    <div class="space-y-4">
                        <div class="text-center mb-4">
                            <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Restez informé</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Activez les notifications pour ne rien manquer de vos réservations et messages.</p>
                        </div>

                        <!-- Bouton d'activation des notifications push -->
                        <div id="push-activation-area">
                            <div id="push-unsupported" class="hidden p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-700 dark:text-amber-300">
                                Votre navigateur ne supporte pas les notifications push. Vous pourrez toujours recevoir vos notifications par email.
                            </div>
                            <div id="push-denied" class="hidden p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-700 dark:text-amber-300">
                                Les notifications ont été bloquées dans votre navigateur. Vous pouvez les réactiver dans les paramètres de votre navigateur, ou dans vos paramètres de compte plus tard.
                            </div>
                            <div id="push-prompt" class="hidden">
                                <button type="button" id="btn-activate-push"
                                    class="w-full flex items-center justify-center gap-2 py-3 px-4 border-2 border-green-500 text-green-700 dark:text-green-400 font-medium rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 focus:outline-none focus:ring-2 focus:ring-green-500 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    Activer les notifications push
                                </button>
                            </div>
                            <div id="push-granted" class="hidden p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-300 flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Notifications push activées avec succès !
                            </div>
                        </div>

                        <!-- Préférences de notifications (toggles) -->
                        <div class="space-y-3 mt-4">
                            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Types de notifications</h4>

                            @php
                                $notifCategories = [
                                    ['name' => 'notifications_reservations', 'label' => 'Réservations', 'desc' => 'Confirmations, rappels et modifications de vos réservations'],
                                    ['name' => 'notifications_paiements', 'label' => 'Paiements', 'desc' => 'Confirmations de paiement et factures'],
                                    ['name' => 'notifications_messages', 'label' => 'Messages', 'desc' => 'Nouveaux messages de vos contacts'],
                                    ['name' => 'notifications_rappels', 'label' => 'Rappels de RDV', 'desc' => 'Rappels avant vos rendez-vous'],
                                    ['name' => 'notifications_promotions', 'label' => 'Promotions & Offres', 'desc' => 'Offres spéciales et promotions'],
                                    ['name' => 'notifications_mises_a_jour', 'label' => 'Mises à jour', 'desc' => 'Nouvelles fonctionnalités et améliorations'],
                                ];
                            @endphp

                            @foreach($notifCategories as $cat)
                                <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $cat['label'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $cat['desc'] }}</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-3 flex-shrink-0">
                                        <input type="checkbox" name="{{ $cat['name'] }}" value="1" checked class="sr-only peer notif-toggle">
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-500 peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" onclick="goToStep(1)"
                            class="flex-1 flex justify-center py-3 px-4 border border-slate-300 dark:border-slate-600 text-base font-medium rounded-lg text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                            Précédent
                        </button>
                        <button type="button" onclick="goToStep(3)"
                            class="flex-1 flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                            Suivant
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!--  ÉTAPE 3 : CGU / CGV / Confidentialité            -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div id="step-3" class="wizard-step hidden">
                    <div class="space-y-4">
                        <div class="text-center mb-4">
                            <div class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Conditions d'utilisation</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Veuillez accepter nos conditions pour continuer.</p>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <input type="checkbox" id="cgu_accepted" name="cgu_accepted" value="1" required
                                    class="mt-1 w-5 h-5 text-green-600 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded focus:ring-green-500 cursor-pointer flex-shrink-0">
                                <label for="cgu_accepted" class="text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                    J'accepte les <a href="{{ route('legal.cgu') }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline font-medium">Conditions Générales d'Utilisation</a> *
                                </label>
                            </div>
                            <p class="text-sm text-red-600 dark:text-red-400 hidden -mt-2 ml-1" data-error="cgu_accepted"></p>

                            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <input type="checkbox" id="cgv_accepted" name="cgv_accepted" value="1" required
                                    class="mt-1 w-5 h-5 text-green-600 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded focus:ring-green-500 cursor-pointer flex-shrink-0">
                                <label for="cgv_accepted" class="text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                    J'accepte les <a href="{{ route('legal.cgv') }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline font-medium">Conditions Générales de Vente</a> *
                                </label>
                            </div>
                            <p class="text-sm text-red-600 dark:text-red-400 hidden -mt-2 ml-1" data-error="cgv_accepted"></p>

                            <div class="flex items-start gap-3 p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <input type="checkbox" id="confidentialite_accepted" name="confidentialite_accepted" value="1" required
                                    class="mt-1 w-5 h-5 text-green-600 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded focus:ring-green-500 cursor-pointer flex-shrink-0">
                                <label for="confidentialite_accepted" class="text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                    J'accepte la <a href="{{ route('legal.confidentialite') }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline font-medium">Politique de confidentialité</a> *
                                </label>
                            </div>
                            <p class="text-sm text-red-600 dark:text-red-400 hidden -mt-2 ml-1" data-error="confidentialite_accepted"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" onclick="goToStep(2)"
                            class="flex-1 flex justify-center py-3 px-4 border border-slate-300 dark:border-slate-600 text-base font-medium rounded-lg text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                            Précédent
                        </button>
                        <button type="submit" id="btn-submit"
                            class="flex-1 flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                            Créer mon compte
                        </button>
                    </div>
                </div>
            </form>

            <!-- ═══════════════════════════════════════════════════ -->
            <!--  ÉTAPE 4 : Vérification email (post-soumission)   -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div id="step-4" class="wizard-step hidden">
                <div class="text-center space-y-4">
                    <div class="mx-auto w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Vérifiez votre boîte mail</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Un email de vérification a été envoyé à <strong id="email-display" class="text-slate-900 dark:text-white"></strong>.
                        <br>Cliquez sur le lien dans l'email pour activer votre compte.
                    </p>

                    <!-- Bouton vers la boîte mail -->
                    <div id="email-provider-btn" class="mt-4"></div>

                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-4">
                        Vous n'avez pas reçu l'email ? Vérifiez vos spams ou 
                        <a href="{{ route('verification.required') }}" class="text-green-600 dark:text-green-400 hover:underline">renvoyez-le</a>.
                    </p>
                </div>
            </div>
        </div>

        <style>
            .wizard-step { transition: opacity 0.3s ease, transform 0.3s ease; }
            .wizard-step.hidden { display: none; }
            .step-circle { background-color: rgb(226 232 240); color: rgb(100 116 139); }
            .step-circle.active { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2); }
            .step-circle.completed { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; }
            .step-bar.active { background: linear-gradient(90deg, #22c55e, #16a34a); }
            .dark .step-circle { background-color: rgb(51 65 85); color: rgb(148 163 184); }
        </style>

        <script>
            let currentStep = 1;
            const totalSteps = 4;

            // ── Stepper ──
            function updateStepper(step) {
                document.querySelectorAll('.step-circle').forEach(el => {
                    const s = parseInt(el.dataset.step);
                    el.classList.remove('active', 'completed');
                    if (s === step) el.classList.add('active');
                    else if (s < step) el.classList.add('completed');
                });
                document.querySelectorAll('.step-bar').forEach(el => {
                    const b = parseInt(el.dataset.bar);
                    el.classList.toggle('active', b < step);
                });
            }

            function showStep(step) {
                document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('hidden'));
                const target = document.getElementById('step-' + step);
                if (target) target.classList.remove('hidden');
                currentStep = step;
                updateStepper(step);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // ── Validation ──
            function showError(field, message) {
                const el = document.querySelector(`[data-error="${field}"]`);
                if (el) { el.textContent = message; el.classList.remove('hidden'); }
                const input = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                if (input) input.classList.add('border-red-500', 'dark:border-red-500');
            }

            function clearErrors() {
                document.querySelectorAll('[data-error]').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('.border-red-500').forEach(el => {
                    el.classList.remove('border-red-500', 'dark:border-red-500');
                });
            }

            function validateStep1() {
                clearErrors();
                let valid = true;
                const fields = [
                    { id: 'name', msg: 'Le prénom est requis.' },
                    { id: 'surname', msg: 'Le nom de famille est requis.' },
                    { id: 'email', msg: 'L\'adresse email est requise.' },
                    { id: 'date_naissance', msg: 'La date de naissance est requise.' },
                    { id: 'telephone', msg: 'Le numéro de téléphone est requis.' },
                    { id: 'password', msg: 'Le mot de passe est requis.' },
                ];

                fields.forEach(f => {
                    const input = document.getElementById(f.id);
                    if (!input || !input.value.trim()) {
                        showError(f.id, f.msg);
                        valid = false;
                    }
                });

                // Vérifier l'adresse (champs cachés remplis par l'autocomplétion)
                const adresse = document.getElementById('adresse');
                if (!adresse || !adresse.value.trim()) {
                    showError('adresse', 'Veuillez sélectionner une adresse dans la liste de suggestions.');
                    valid = false;
                }

                // Email format
                const email = document.getElementById('email');
                if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                    showError('email', 'L\'adresse email n\'est pas valide.');
                    valid = false;
                }

                // Date de naissance dans le passé
                const dob = document.getElementById('date_naissance');
                if (dob && dob.value && new Date(dob.value) >= new Date()) {
                    showError('date_naissance', 'La date de naissance doit être dans le passé.');
                    valid = false;
                }

                // Mot de passe >= 8 caractères
                const pwd = document.getElementById('password');
                if (pwd && pwd.value && pwd.value.length < 8) {
                    showError('password', 'Le mot de passe doit contenir au moins 8 caractères.');
                    valid = false;
                }

                // Confirmation mot de passe
                const pwdConfirm = document.getElementById('password_confirmation');
                if (pwd && pwdConfirm && pwd.value !== pwdConfirm.value) {
                    showError('password_confirmation', 'Les mots de passe ne correspondent pas.');
                    valid = false;
                }

                return valid;
            }

            function validateStep3() {
                clearErrors();
                let valid = true;
                const checks = [
                    { id: 'cgu_accepted', msg: 'Vous devez accepter les CGU pour continuer.' },
                    { id: 'cgv_accepted', msg: 'Vous devez accepter les CGV pour continuer.' },
                    { id: 'confidentialite_accepted', msg: 'Vous devez accepter la politique de confidentialité.' },
                ];

                checks.forEach(c => {
                    const input = document.getElementById(c.id);
                    if (!input || !input.checked) {
                        showError(c.id, c.msg);
                        valid = false;
                    }
                });

                return valid;
            }

            // ── Navigation ──
            function goToStep(step) {
                // Validation avant d'avancer
                if (step > currentStep) {
                    if (currentStep === 1 && !validateStep1()) return;
                    if (currentStep === 3 && !validateStep3()) return;
                }
                showStep(step);

                // Initialiser les push notifications à l'étape 2
                if (step === 2) initPushStep();
            }

            // ── Push notifications (étape 2) ──
            function initPushStep() {
                const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

                document.getElementById('push-unsupported').classList.toggle('hidden', supported);
                document.getElementById('push-prompt').classList.add('hidden');
                document.getElementById('push-denied').classList.add('hidden');
                document.getElementById('push-granted').classList.add('hidden');

                if (!supported) return;

                const perm = Notification.permission;
                if (perm === 'granted') {
                    document.getElementById('push-granted').classList.remove('hidden');
                    registerPushSubscription();
                } else if (perm === 'denied') {
                    document.getElementById('push-denied').classList.remove('hidden');
                } else {
                    document.getElementById('push-prompt').classList.remove('hidden');
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const activateBtn = document.getElementById('btn-activate-push');
                if (activateBtn) {
                    activateBtn.addEventListener('click', async function() {
                        const permission = await Notification.requestPermission();
                        if (permission === 'granted') {
                            document.getElementById('push-prompt').classList.add('hidden');
                            document.getElementById('push-granted').classList.remove('hidden');
                            registerPushSubscription();
                        } else if (permission === 'denied') {
                            document.getElementById('push-prompt').classList.add('hidden');
                            document.getElementById('push-denied').classList.remove('hidden');
                        }
                    });
                }
            });

            async function registerPushSubscription() {
                try {
                    const registration = await navigator.serviceWorker.ready;
                    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
                    if (!vapidKey) return;

                    let subscription = await registration.pushManager.getSubscription();
                    if (!subscription) {
                        const padding = '='.repeat((4 - vapidKey.length % 4) % 4);
                        const base64 = (vapidKey + padding).replace(/-/g, '+').replace(/_/g, '/');
                        const rawData = window.atob(base64);
                        const outputArray = new Uint8Array(rawData.length);
                        for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);

                        subscription = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: outputArray,
                        });
                    }

                    // Stocker en session côté serveur (guest)
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    await fetch('/push-subscription/guest', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            endpoint: subscription.endpoint,
                            keys: {
                                p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                                auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
                            },
                            content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                        }),
                    });
                } catch (e) {
                    console.error('Erreur push subscription:', e);
                }
            }

            // ── Soumission du formulaire ──
            document.getElementById('signup-form').addEventListener('submit', function(e) {
                if (!validateStep3()) {
                    e.preventDefault();
                    return;
                }

                // On laisse le formulaire se soumettre normalement
                // L'étape 4 sera affichée après la redirection serveur vers verification.required
            });

            // ── Étape 4 : détection du fournisseur email ──
            function showStep4WithEmail(email) {
                showStep(4);
                document.getElementById('email-display').textContent = email;

                const domain = email.split('@')[1]?.toLowerCase() || '';
                const providers = {
                    'gmail.com': { name: 'Gmail', url: 'https://mail.google.com', color: 'bg-red-500 hover:bg-red-600' },
                    'outlook.com': { name: 'Outlook', url: 'https://outlook.live.com', color: 'bg-blue-500 hover:bg-blue-600' },
                    'hotmail.com': { name: 'Outlook', url: 'https://outlook.live.com', color: 'bg-blue-500 hover:bg-blue-600' },
                    'hotmail.fr': { name: 'Outlook', url: 'https://outlook.live.com', color: 'bg-blue-500 hover:bg-blue-600' },
                    'live.com': { name: 'Outlook', url: 'https://outlook.live.com', color: 'bg-blue-500 hover:bg-blue-600' },
                    'live.fr': { name: 'Outlook', url: 'https://outlook.live.com', color: 'bg-blue-500 hover:bg-blue-600' },
                    'yahoo.com': { name: 'Yahoo Mail', url: 'https://mail.yahoo.com', color: 'bg-purple-500 hover:bg-purple-600' },
                    'yahoo.fr': { name: 'Yahoo Mail', url: 'https://mail.yahoo.com', color: 'bg-purple-500 hover:bg-purple-600' },
                    'orange.fr': { name: 'Orange', url: 'https://messagerie.orange.fr', color: 'bg-orange-500 hover:bg-orange-600' },
                    'wanadoo.fr': { name: 'Orange', url: 'https://messagerie.orange.fr', color: 'bg-orange-500 hover:bg-orange-600' },
                    'sfr.fr': { name: 'SFR Mail', url: 'https://webmail.sfr.fr', color: 'bg-red-600 hover:bg-red-700' },
                    'free.fr': { name: 'Free', url: 'https://webmail.free.fr', color: 'bg-slate-700 hover:bg-slate-800' },
                    'laposte.net': { name: 'La Poste', url: 'https://www.laposte.net/accueil', color: 'bg-yellow-500 hover:bg-yellow-600' },
                };

                const provider = providers[domain];
                const container = document.getElementById('email-provider-btn');

                if (provider) {
                    container.innerHTML = `<a href="${provider.url}" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 ${provider.color} text-white font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        Ouvrir ${provider.name}
                    </a>`;
                } else {
                    container.innerHTML = `<p class="text-sm text-slate-500 dark:text-slate-400">Ouvrez votre boîte mail pour vérifier votre compte.</p>`;
                }
            }

            // ── Toggle mot de passe ──
            function togglePasswordVisibility(inputId, btn) {
                const input = document.getElementById(inputId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.querySelector('.eye-off').classList.toggle('hidden', isPassword);
                btn.querySelector('.eye-on').classList.toggle('hidden', !isPassword);
            }

            // ── Service Worker registration ──
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW registration failed:', err));
            }

            // ── Autocomplétion d'adresse ──
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof AddressAutocomplete === 'undefined') return;

                const addressAC = new AddressAutocomplete({
                    minLength: 3,
                    debounceMs: 300,
                    onSelect: function(addressData) {
                        // Remplir les champs cachés
                        const street = addressData.housenumber 
                            ? addressData.housenumber + ' ' + (addressData.street || '') 
                            : (addressData.street || addressData.label || '');
                        
                        document.getElementById('adresse').value = street.trim();
                        document.getElementById('ville').value = addressData.city || '';
                        document.getElementById('code_postal').value = addressData.postcode || '';
                        document.getElementById('latitude').value = addressData.latitude || '';
                        document.getElementById('longitude').value = addressData.longitude || '';

                        // Afficher le résumé
                        document.getElementById('address-search').classList.add('hidden');
                        document.getElementById('address-search').parentElement.querySelector('.pointer-events-none')?.closest('.relative')?.classList.add('hidden');
                        
                        const selectedDiv = document.getElementById('address-selected');
                        selectedDiv.classList.remove('hidden');
                        document.getElementById('address-selected-label').textContent = addressData.label || street;
                        document.getElementById('address-selected-detail').textContent = 
                            (addressData.postcode || '') + ' ' + (addressData.city || '') + 
                            (addressData.context ? ' — ' + addressData.context : '');

                        // Masquer le champ de recherche, montrer le résumé
                        document.getElementById('address-search').closest('.relative').querySelector('#address-search').style.display = 'none';
                        document.getElementById('address-search').closest('.relative').querySelector('.pointer-events-none')?.parentElement && 
                            (document.getElementById('address-search').style.display = 'none');

                        clearErrors();
                    }
                });

                addressAC.init('address-search', 'address-results', 'address');

                // Bouton "Modifier" pour changer l'adresse
                document.getElementById('address-change-btn')?.addEventListener('click', function() {
                    document.getElementById('address-selected').classList.add('hidden');
                    const searchInput = document.getElementById('address-search');
                    searchInput.style.display = '';
                    searchInput.classList.remove('hidden');
                    searchInput.value = '';
                    searchInput.focus();
                    // Vider les champs cachés
                    document.getElementById('adresse').value = '';
                    document.getElementById('ville').value = '';
                    document.getElementById('code_postal').value = '';
                    document.getElementById('latitude').value = '';
                    document.getElementById('longitude').value = '';
                });

                // Si on a déjà une adresse (retour avec old()), afficher le résumé
                const existingAdresse = document.getElementById('adresse').value;
                if (existingAdresse) {
                    const ville = document.getElementById('ville').value;
                    const cp = document.getElementById('code_postal').value;
                    document.getElementById('address-selected').classList.remove('hidden');
                    document.getElementById('address-selected-label').textContent = existingAdresse;
                    document.getElementById('address-selected-detail').textContent = cp + ' ' + ville;
                    document.getElementById('address-search').style.display = 'none';
                }
            });
        </script>
    </body>
</html>
