<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Entreprises autres - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('home') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            ← Retour au dashboard
                        </a>
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            {{ $user->name }}
                        </span>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Entreprises autres
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Les entreprises où vous êtes membre (mais pas propriétaire)
                </p>
            </div>

            <!-- Section Entreprises -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                @if($entreprisesAvecStats->count() > 0)
                    <div class="space-y-6">
                        @foreach($entreprisesAvecStats as $item)
                            @php
                                $entreprise = $item['entreprise'];
                                $membre = $item['membre'];
                                $estAdmin = $item['estAdmin'];
                            @endphp
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-blue-500 dark:hover:border-blue-500 transition-all">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex items-start gap-4 flex-1">
                                                @if($entreprise->logo)
                                                    <img 
                                                        src="{{ asset('media/' . $entreprise->logo) }}" 
                                                        alt="Logo {{ $entreprise->nom }}"
                                                        class="w-16 h-16 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 flex-shrink-0"
                                                    >
                                                @endif
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                                                            {{ $entreprise->nom }}
                                                        </h3>
                                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded capitalize">
                                                            {{ $membre->role }}
                                                        </span>
                                                        @if($estAdmin)
                                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">
                                                                Admin
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                                                        {{ $entreprise->type_activite }}
                                                        @if($entreprise->ville)
                                                            • {{ $entreprise->ville }}
                                                        @endif
                                                    </p>
                                                    @if($entreprise->est_verifiee)
                                                        <span class="inline-block px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">✓ Vérifiée</span>
                                                    @else
                                                        <span class="inline-block px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">⏳ En attente de vérification</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if($estAdmin && isset($item['stats']))
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                                <div class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Réservations</p>
                                                    <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $item['stats']['total_reservations'] }}</p>
                                                </div>
                                                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Revenu total</p>
                                                    <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($item['stats']['revenu_total'], 2, ',', ' ') }} €</p>
                                                </div>
                                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Revenu payé</p>
                                                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($item['stats']['revenu_paye'], 2, ',', ' ') }} €</p>
                                                </div>
                                                <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">Ce mois</p>
                                                    <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $item['stats']['reservations_ce_mois'] }}</p>
                                                </div>
                                            </div>
                                        @elseif(!$estAdmin)
                                            <div class="mt-4 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    Les statistiques ne sont disponibles que pour les administrateurs de l'entreprise.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-center bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white rounded-lg transition-all shadow-sm hover:shadow-md">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Accéder à l'entreprise
                                        </a>
                                        <a href="{{ route('public.entreprise', $entreprise->slug) }}" target="_blank" class="px-4 py-2 text-sm font-medium text-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            Page publique
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune entreprise</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Vous n'êtes membre d'aucune autre entreprise pour le moment.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                                Retour au dashboard
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
