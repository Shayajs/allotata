<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $entreprise->nom }} - Allo Tata</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <!-- Image de fond en en-tête -->
    @if($entreprise->image_fond)
        <div class="relative h-48 sm:h-64 md:h-80 lg:h-96 w-full overflow-hidden">
            <img 
                src="{{ asset('media/' . $entreprise->image_fond) }}" 
                alt="Image de fond {{ $entreprise->nom }}"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-6">
                <div class="max-w-6xl mx-auto">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-white/90 hover:text-green-300 transition mb-3 sm:mb-4 text-sm sm:text-base">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span class="font-medium">Retour à l'accueil</span>
                    </a>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        @if($entreprise->logo)
                            <img 
                                src="{{ asset('media/' . $entreprise->logo) }}" 
                                alt="Logo {{ $entreprise->nom }}"
                                class="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 rounded-lg object-cover border-2 border-white/20 shadow-lg flex-shrink-0"
                            >
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-1 sm:mb-2">
                                <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-white truncate">
                                    {{ $entreprise->nom }}
                                </h1>
                                @if(!$entreprise->est_verifiee)
                                    <span class="px-2 py-0.5 sm:px-3 sm:py-1 text-[10px] sm:text-xs font-medium bg-yellow-500/80 text-white rounded-full whitespace-nowrap">
                                        ⏳ En cours
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 sm:px-3 sm:py-1 text-[10px] sm:text-xs font-medium bg-green-500/80 text-white rounded-full whitespace-nowrap">
                                        ✓ Vérifiée
                                    </span>
                                    @if($entreprise->aGestionMultiPersonnes())
                                        <span class="px-2 py-0.5 sm:px-3 sm:py-1 text-[10px] sm:text-xs font-medium bg-gradient-to-r from-purple-500/90 to-pink-500/90 text-white rounded-full whitespace-nowrap shadow-lg">
                                            ✨ Entreprise Platine
                                        </span>
                                    @endif
                                @endif
                            </div>
                            <p class="text-sm sm:text-base md:text-lg text-white/90 truncate">
                                {{ $entreprise->type_activite }}
                                @if($entreprise->estVirtuelle())
                                    • Prestations en ligne
                                @elseif($entreprise->ville)
                                    • {{ $entreprise->ville }}
                                @endif
                            </p>
                            @auth
                                @if(auth()->user()->id === $entreprise->user_id)
                                    <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" 
                                       class="inline-flex items-center gap-1 mt-2 text-sm text-green-300 hover:text-green-200 font-medium transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Gérer mon entreprise →
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Contenu principal -->
    <div class="max-w-6xl mx-auto py-6 sm:py-8 md:py-12 px-4 sm:px-6">
        <!-- Navigation (uniquement si pas d'image de fond) -->
        @if(!$entreprise->image_fond)
            <nav class="mb-4 sm:mb-6">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition text-sm sm:text-base">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="font-medium">Retour à l'accueil</span>
                </a>
            </nav>
        @endif
            
        <!-- Header (uniquement si pas d'image de fond) -->
        @if(!$entreprise->image_fond)
            <header class="border-b border-slate-200 dark:border-slate-700 pb-4 sm:pb-6 mb-6 sm:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        @if($entreprise->logo)
                            <img 
                                src="{{ asset('media/' . $entreprise->logo) }}" 
                                alt="Logo {{ $entreprise->nom }}"
                                class="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 flex-shrink-0"
                            >
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-1 sm:mb-2">
                                <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold tracking-tight bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                                    {{ $entreprise->nom }}
                                </h1>
                                @if(!$entreprise->est_verifiee)
                                    <span class="px-2 py-0.5 sm:px-3 sm:py-1 text-[10px] sm:text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-200 dark:border-yellow-800 whitespace-nowrap">
                                        ⏳ En cours
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 sm:px-3 sm:py-1 text-[10px] sm:text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full border border-green-200 dark:border-green-800 whitespace-nowrap">
                                        ✓ Vérifiée
                                    </span>
                                    @if($entreprise->aGestionMultiPersonnes())
                                        <span class="px-2 py-0.5 sm:px-3 sm:py-1 text-[10px] sm:text-xs font-medium bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/40 dark:to-pink-900/40 text-purple-800 dark:text-purple-300 rounded-full border border-purple-200 dark:border-purple-800 whitespace-nowrap shadow-sm">
                                            ✨ Entreprise Platine
                                        </span>
                                    @endif
                                @endif
                            </div>
                            <p class="text-sm sm:text-base md:text-lg text-slate-600 dark:text-slate-400">
                                {{ $entreprise->type_activite }}
                                @if($entreprise->estVirtuelle())
                                    • Prestations en ligne
                                @elseif($entreprise->ville)
                                    • {{ $entreprise->ville }}
                                @endif
                            </p>
                            @auth
                                @if(auth()->user()->id === $entreprise->user_id)
                                    <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" 
                                       class="inline-flex items-center gap-1 mt-2 text-sm text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 font-medium transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Gérer mon entreprise →
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>
                    <button 
                        class="theme-toggle-btn self-end sm:self-auto p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                        aria-label="Basculer le thème"
                    >
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                </div>
            </header>
        @else
            <!-- Bouton thème flottant si image de fond -->
            <div class="flex justify-end mb-4 sm:mb-6">
                <button 
                    class="theme-toggle-btn p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                    aria-label="Basculer le thème"
                >
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>
            </div>
        @endif

        <!-- Messages d'alerte -->
        @if(session('error'))
            <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-start gap-2 sm:gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm sm:text-base text-red-800 dark:text-red-300">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-start gap-2 sm:gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm sm:text-base text-green-800 dark:text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(!$entreprise->est_verifiee)
            <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start gap-2 sm:gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-sm sm:text-base text-yellow-800 dark:text-yellow-300">Cette entreprise est en cours de création</p>
                        <p class="text-xs sm:text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                            Les informations peuvent être incomplètes. L'entreprise sera vérifiée et validée prochainement.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($isOwner) && $isOwner && !$entreprise->aAbonnementActif())
            <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-start gap-2 sm:gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-sm sm:text-base text-red-800 dark:text-red-300 flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Votre entreprise n'est pas visible en ligne
                        </p>
                        <p class="text-xs sm:text-sm text-red-700 dark:text-red-400 mt-1">
                            Vous consultez votre propre entreprise, mais elle n'est pas visible pour les autres utilisateurs car vous n'avez pas d'abonnement actif. 
                            <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="underline font-semibold">Souscrivez à un abonnement</a> pour rendre votre entreprise visible dans les recherches.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Contenu principal en 2 colonnes -->
        <main class="grid gap-4 sm:gap-6 lg:grid-cols-3">
            <!-- Colonne gauche : Informations -->
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                <div class="p-4 sm:p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <h2 class="font-semibold text-lg sm:text-xl mb-3 sm:mb-4 text-slate-900 dark:text-slate-100">Informations</h2>
                    
                    @php
                        $embedUrl = \App\Helpers\VideoHelper::getEmbedUrl($entreprise->video_url);
                    @endphp
                    
                    @if($embedUrl && $entreprise->afficher_video)
                        <div class="mb-6">
                            <div class="relative w-full" style="padding-bottom: 56.25%;">
                                <iframe 
                                    src="{{ $embedUrl }}" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen
                                    class="absolute top-0 left-0 w-full h-full rounded-lg"
                                ></iframe>
                            </div>
                        </div>
                    @endif
                    
                    @if($entreprise->description)
                        <div class="mb-4">
                            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $entreprise->description }}</p>
                        </div>
                    @endif

                    <div class="space-y-2 sm:space-y-3">
                        @if($entreprise->telephone)
                            <div class="flex items-center gap-2 text-sm sm:text-base text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <a href="tel:{{ $entreprise->telephone }}" class="hover:text-green-600 dark:hover:text-green-400 transition">{{ $entreprise->telephone }}</a>
                            </div>
                        @endif

                        @if($entreprise->email)
                            <div class="flex items-center gap-2 text-sm sm:text-base text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <a href="mailto:{{ $entreprise->email }}" class="hover:text-green-600 dark:hover:text-green-400 transition truncate">{{ $entreprise->email }}</a>
                            </div>
                        @endif



                        @if($entreprise->afficher_nom_gerant && $entreprise->user)
                            <div class="flex items-center gap-2 text-sm sm:text-base text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Gérée par {{ $entreprise->user->name }}</span>
                            </div>
                        @endif

                        @if($entreprise->estVirtuelle())
                            <div class="flex items-center gap-2 text-sm sm:text-base text-violet-700 dark:text-violet-300">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span>Prestations 100 % en ligne — pas de lieu d'accueil physique</span>
                            </div>
                        @elseif($entreprise->rayon_deplacement > 0)
                            <div class="flex items-center gap-2 text-sm sm:text-base text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Déplacement jusqu'à {{ $entreprise->rayon_deplacement }} km</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-sm sm:text-base text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Sur place{{ $entreprise->ville ? ' — '.$entreprise->ville : '' }}</span>
                            </div>
                        @endif
                    </div>

                    @if($entreprise->mots_cles)
                        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <p class="text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Mots-clés :</p>
                            <div class="flex flex-wrap gap-1.5 sm:gap-2">
                                @foreach(explode(', ', $entreprise->mots_cles) as $motCle)
                                    <a href="{{ route('search', ['q' => trim($motCle)]) }}" class="px-2 sm:px-3 py-0.5 sm:py-1 text-[10px] sm:text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full hover:bg-green-200 dark:hover:bg-green-800/50 transition-colors cursor-pointer">
                                        {{ trim($motCle) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Boutons de navigation mobile (Services et Produits) -->
                <div class="lg:hidden flex gap-3 mt-4 mb-4">
                    @if($services->count() > 0)
                        <button 
                            onclick="const el = document.getElementById('services-section'); if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }"
                            class="flex-1 inline-flex items-center justify-center gap-2 bg-white dark:bg-slate-800 border-2 border-green-500 text-green-600 dark:text-green-400 font-bold py-3 px-4 rounded-lg transition hover:bg-green-50 dark:hover:bg-green-900/20 text-sm sm:text-base"
                        >
                            Services
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </button>
                    @endif
                    @php
                        $produitsDisponibles = $produits ?? collect([]);
                    @endphp
                    @if($produitsDisponibles->count() > 0)
                        <button 
                            onclick="const el = document.getElementById('produits-section'); if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }"
                            class="flex-1 inline-flex items-center justify-center gap-2 bg-white dark:bg-slate-800 border-2 border-blue-500 text-blue-600 dark:text-blue-400 font-bold py-3 px-4 rounded-lg transition hover:bg-blue-50 dark:hover:bg-blue-900/20 text-sm sm:text-base"
                        >
                            Produits
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </button>
                    @endif
                </div>

                <!-- Galerie de réalisations -->
                @if($entreprise->realisationPhotos->count() > 0)
                    <div class="p-4 sm:p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-3 sm:mb-4 flex items-center gap-2">
                            📸 Dernières réalisations
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-4">
                            @foreach($entreprise->realisationPhotos as $photo)
                                <div class="group relative overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer aspect-square" onclick="openModal({{ $loop->index }})">
                                    <img 
                                        src="{{ asset('media/' . $photo->photo_path) }}" 
                                        alt="{{ $photo->titre ? $photo->titre : 'Réalisation' }}"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                        @if($photo->titre)
                                            <div class="absolute bottom-0 left-0 right-0 p-2 sm:p-3">
                                                <p class="text-white text-xs sm:text-sm font-semibold truncate">{{ $photo->titre }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modal pour afficher les photos en grand -->
                    <div id="photo-modal" class="hidden fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-2 sm:p-4">
                        <button onclick="closeModal()" class="absolute top-2 right-2 sm:top-4 sm:right-4 text-white hover:text-green-400 transition z-10">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <button onclick="prevPhoto()" class="absolute left-2 sm:left-4 text-white hover:text-green-400 transition z-10">
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button onclick="nextPhoto()" class="absolute right-2 sm:right-4 text-white hover:text-green-400 transition z-10">
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <div class="max-w-4xl w-full px-8 sm:px-12">
                            <img id="modal-photo" src="" alt="" class="w-full h-auto rounded-lg max-h-[70vh] sm:max-h-[80vh] object-contain">
                            <div id="modal-info" class="mt-2 sm:mt-4 text-center text-white">
                                <h3 id="modal-titre" class="text-base sm:text-xl font-bold mb-1 sm:mb-2"></h3>
                                <p id="modal-description" class="text-xs sm:text-base text-slate-300"></p>
                            </div>
                        </div>
                    </div>

                    <script>
                        let currentPhotoIndex = 0;
                        const photos = [
                            @foreach($entreprise->realisationPhotos as $photo)
                            {
                                path: '{{ asset('media/' . $photo->photo_path) }}',
                                titre: @json($photo->titre ? $photo->titre : ''),
                                description: @json($photo->description ? $photo->description : ''),
                            },
                            @endforeach
                        ];

                        function openModal(index) {
                            currentPhotoIndex = index;
                            updateModal();
                            document.getElementById('photo-modal').classList.remove('hidden');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeModal() {
                            document.getElementById('photo-modal').classList.add('hidden');
                            document.body.style.overflow = '';
                        }

                        function prevPhoto() {
                            currentPhotoIndex = (currentPhotoIndex - 1 + photos.length) % photos.length;
                            updateModal();
                        }

                        function nextPhoto() {
                            currentPhotoIndex = (currentPhotoIndex + 1) % photos.length;
                            updateModal();
                        }

                        function updateModal() {
                            const photo = photos[currentPhotoIndex];
                            document.getElementById('modal-photo').src = photo.path;
                            document.getElementById('modal-titre').textContent = photo.titre || '';
                            document.getElementById('modal-description').textContent = photo.description || '';
                        }

                        // Navigation au clavier
                        document.addEventListener('keydown', function(e) {
                            const modal = document.getElementById('photo-modal');
                            if (!modal.classList.contains('hidden')) {
                                if (e.key === 'Escape') closeModal();
                                if (e.key === 'ArrowLeft') prevPhoto();
                                if (e.key === 'ArrowRight') nextPhoto();
                            }
                        });
                    </script>
                @endif
            </div>

            <!-- Colonne droite : Actions et horaires -->
            <div class="space-y-4 sm:space-y-6">
                <div class="p-4 sm:p-6 bg-gradient-to-br from-green-50 to-orange-50 dark:from-green-900/20 dark:to-orange-900/20 rounded-xl border border-green-200 dark:border-green-800 space-y-3">
                    <!-- Horaires d'ouverture -->
                    @if($horaires->count() > 0)
                        <div class="mb-4 sm:mb-6 pb-4 sm:pb-6 border-b border-green-200 dark:border-green-800">
                            <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Horaires d'ouverture
                            </h3>
                            <div class="space-y-1.5 sm:space-y-2">
                                @php
                                    $jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
                                    $joursComplets = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                    $horairesParJour = [];
                                    foreach ($horaires as $horaire) {
                                        if (!$horaire->est_exceptionnel) {
                                            $horairesParJour[$horaire->jour_semaine] = $horaire;
                                        }
                                    }
                                @endphp
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $horaire = isset($horairesParJour[$i]) ? $horairesParJour[$i] : null;
                                        $estFerme = !$horaire || !$horaire->heure_ouverture || !$horaire->heure_fermeture;
                                    @endphp
                                    <div class="flex items-center justify-between text-xs sm:text-sm">
                                        <span class="font-medium text-slate-700 dark:text-slate-300 {{ $i === now()->dayOfWeek ? 'text-green-600 dark:text-green-400' : '' }}">
                                            <span class="hidden sm:inline">{{ $joursComplets[$i] }}</span>
                                            <span class="sm:hidden">{{ $jours[$i] }}</span>
                                            @if($i === now()->dayOfWeek)
                                                <span class="text-[10px] sm:text-xs">(Auj.)</span>
                                            @endif
                                        </span>
                                        <span class="text-slate-600 dark:text-slate-400">
                                            @if($estFerme)
                                                <span class="text-red-600 dark:text-red-400">Fermé</span>
                                            @else
                                                {{ \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') }} - 
                                                {{ \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') }}
                                            @endif
                                        </span>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endif

                    <!-- Bouton Prendre rendez-vous -->
                    <div class="space-y-3">
                        @if($entreprise->prendRdvSurDemande())
                            <a href="{{ route('public.agenda', $entreprise->slug) }}" class="block w-full bg-gradient-to-r from-green-600 to-orange-500 hover:from-green-700 hover:to-orange-600 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition text-center text-sm sm:text-base">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Demander un rendez-vous
                            </a>
                        @else
                            <a href="{{ route('public.agenda', $entreprise->slug) }}" class="block w-full bg-gradient-to-r from-green-600 to-orange-500 hover:from-green-700 hover:to-orange-600 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition text-center text-sm sm:text-base">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Prendre rendez-vous
                            </a>
                        @endif
                        @auth
                            <a href="{{ route('messagerie.show', $entreprise->slug) }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition text-center text-sm sm:text-base">
                                💬 Contacter
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="block w-full bg-slate-400 hover:bg-slate-500 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition text-center text-sm sm:text-base">
                                🔒 Connectez-vous pour contacter
                            </a>
                        @endauth
                    </div>
                </div>

                @if(($entreprise->site_web_externe) || ($entreprise->aSiteWebActif() && $entreprise->slug_web))
                    <div class="p-4 sm:p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 space-y-3">
                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            Sites Web
                        </h3>
                        
                        @if($entreprise->aSiteWebActif() && $entreprise->slug_web)
                            <a href="{{ route('site-web.show', $entreprise->slug_web) }}" target="_blank" class="block w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded-lg transition text-center shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                Site A.T. de {{ $entreprise->nom }}
                            </a>
                        @endif

                        @if($entreprise->site_web_externe)
                            <a href="{{ $entreprise->site_web_externe }}" target="_blank" rel="noopener noreferrer" class="block w-full bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-bold py-3 px-4 rounded-lg transition text-center border border-slate-200 dark:border-slate-600">
                                Accéder à {{ parse_url($entreprise->site_web_externe, PHP_URL_HOST) ?? $entreprise->site_web_externe }}
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Carte de localisation (entreprises physiques uniquement) --}}
                @if($entreprise->estVirtuelle())
                    <div class="p-4 sm:p-6 bg-violet-50 dark:bg-violet-900/20 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800">
                        <h3 class="text-base sm:text-lg font-semibold text-violet-900 dark:text-violet-100 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Activité en ligne
                        </h3>
                        <p class="text-sm text-violet-800 dark:text-violet-200">
                            Cette entreprise propose ses services à distance (visio, livraison numérique, conseil en ligne, etc.). Contactez-la via la messagerie pour convenir des modalités.
                        </p>
                    </div>
                @elseif($entreprise->latitude && $entreprise->longitude)
                    <div class="p-4 sm:p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Localisation
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                            {{ $entreprise->formatted_address }}
                        </p>
                        @include('components.map-standalone', [
                            'entreprises' => collect([$entreprise]),
                            'center' => ['lat' => (float) $entreprise->latitude, 'lng' => (float) $entreprise->longitude],
                            'zoom' => 14,
                            'height' => '250px',
                            'single' => true,
                            'enableClustering' => false,
                        ])
                    </div>
                @endif

            </div>
        </main>

        <!-- Section Services -->
        @if($services->count() > 0)
            <section id="services-section" class="mt-8 sm:mt-12">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4 sm:mb-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                        Services proposés
                    </h2>
                    <div class="flex items-center gap-2">
                        @if($entreprise->prix_negociables)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 text-xs sm:text-sm font-medium rounded-full border border-orange-200 dark:border-orange-800">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Prix négociables
                            </span>
                        @endif
                        <a 
                            href="{{ route('public.services', $entreprise->slug) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-700 hover:to-pink-600 text-white text-xs sm:text-sm font-semibold rounded-lg transition"
                        >
                            Voir tous les services
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="grid gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($services as $service)
                        <div 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all cursor-pointer hover:border-green-300 dark:hover:border-green-700 group"
                            onclick="openServiceDetailModal({{ $loop->index }})"
                            data-service-id="{{ $service->id }}"
                            data-service-nom="{{ $service->nom }}"
                            data-tracking-service="true"
                        >
                            <!-- Image de couverture ou première image -->
                            @php
                                $imageCouverture = $service->imageCouverture;
                                $premiereImage = $service->images->first();
                                $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            @endphp
                            
                            @if($imageAffichee)
                                <div class="relative h-36 sm:h-48 w-full overflow-hidden">
                                    <img 
                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                        alt="{{ $service->nom }}"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                    @if($service->images->count() > 1)
                                        <div class="absolute top-2 right-2 bg-black/60 text-white px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold">
                                            📷 {{ $service->images->count() }}
                                        </div>
                                    @endif
                                    @if($entreprise->prix_negociables)
                                        <div class="absolute top-2 left-2 bg-orange-500 text-white px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Négociable
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="relative h-36 sm:h-48 w-full bg-gradient-to-br from-green-100 to-orange-100 dark:from-green-900/20 dark:to-orange-900/20 flex items-center justify-center">
                                    <svg class="w-12 h-12 sm:w-16 sm:h-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    @if($entreprise->prix_negociables)
                                        <div class="absolute top-2 left-2 bg-orange-500 text-white px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Négociable
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="p-4 sm:p-6">
                                <div class="flex items-center justify-between mb-1 sm:mb-2">
                                    <h3 class="text-base sm:text-xl font-bold text-slate-900 dark:text-white truncate group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                        {{ $service->nom }}
                                    </h3>
                                    @if($service->options->count() > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[10px] sm:text-xs font-semibold rounded-full border border-blue-200 dark:border-blue-800 shadow-sm" title="Des options sont disponibles pour ce service">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                            </svg>
                                            Options
                                        </span>
                                    @endif
                                </div>
                                
                                @if($service->description)
                                    <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm mb-3 sm:mb-4 line-clamp-2">
                                        {{ $service->description }}
                                    </p>
                                @endif
                                
                                <div class="flex items-center justify-between pt-3 sm:pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400">
                                                {{ number_format($service->prix, 2) }} €
                                            </span>
                                        </div>
                                        <span class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $service->duree_formatee }}
                                        </span>
                                    </div>
                                    <div class="text-green-600 dark:text-green-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($entreprise->prestation_libre_active && $entreprise->tarif_horaire)
                    <div class="mt-6 border-2 border-dashed border-green-300 dark:border-green-700 rounded-xl bg-green-50/50 dark:bg-green-900/10 p-4 sm:p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white">Prestation sur demande</h3>
                                    @if($entreprise->prestation_libre_description)
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->prestation_libre_description }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($entreprise->tarif_horaire, 0, ',', ' ') }} €/h</span>
                                <a href="{{ route('messagerie.show', $entreprise->slug) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition shadow-md">
                                    Contacter
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Modal détaillé pour un service -->
                <div id="service-detail-modal" class="hidden fixed inset-0 bg-black/80 z-50 overflow-y-auto" onclick="closeServiceDetailModal(event)">
                    <div class="min-h-screen py-4 sm:py-8 px-2 sm:px-4 flex items-start justify-center">
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-3xl my-4 overflow-hidden" onclick="event.stopPropagation()">
                            <!-- Header avec fermeture -->
                            <div class="relative">
                                <button onclick="closeServiceDetailModal()" class="absolute top-3 right-3 sm:top-4 sm:right-4 z-20 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                                
                                <!-- Galerie d'images -->
                                <div id="service-detail-gallery" class="relative h-56 sm:h-72 md:h-80 bg-slate-200 dark:bg-slate-700">
                                    <!-- Image principale -->
                                    <img id="service-detail-image" src="" alt="" class="w-full h-full object-cover">
                                    
                                    <!-- Placeholder quand pas d'image -->
                                    <div id="service-detail-no-image" class="hidden absolute inset-0 bg-gradient-to-br from-green-100 to-orange-100 dark:from-green-900/20 dark:to-orange-900/20 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Navigation galerie -->
                                    <button onclick="prevServiceDetailImage(event)" class="absolute left-2 top-1/2 -translate-y-1/2 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition hidden" id="service-detail-prev">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                    </button>
                                    <button onclick="nextServiceDetailImage(event)" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition hidden" id="service-detail-next">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Indicateur de position -->
                                    <div id="service-detail-indicator" class="absolute bottom-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-black/60 text-white text-xs sm:text-sm rounded-full hidden"></div>
                                </div>
                                
                                <!-- Miniatures -->
                                <div id="service-detail-thumbnails" class="flex gap-1.5 sm:gap-2 p-2 sm:p-3 bg-slate-100 dark:bg-slate-900 overflow-x-auto hidden"></div>
                            </div>
                            
                            <!-- Contenu -->
                            <div class="p-4 sm:p-6">
                                <h3 id="service-detail-nom" class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-2"></h3>
                                
                                <div class="flex flex-wrap items-center gap-3 mb-4">
                                    <div class="flex items-center gap-1.5">
                                        <span id="service-detail-prix" class="text-2xl sm:text-3xl font-bold text-green-600 dark:text-green-400"></span>
                                        <span id="service-detail-negociable-badge" class="hidden px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 text-xs font-medium rounded-full">
                                            Négociable
                                        </span>
                                    </div>
                                    <span class="text-slate-500 dark:text-slate-400 text-sm">•</span>
                                    <span id="service-detail-duree" class="text-slate-600 dark:text-slate-400 text-sm sm:text-base"></span>
                                </div>
                                
                                <div id="service-detail-description" class="text-slate-600 dark:text-slate-400 text-sm sm:text-base mb-6 whitespace-pre-line"></div>
                                <div id="service-detail-options" class="hidden mb-6"></div>
                                
                                <!-- Zone de négociation -->
                                <div id="service-detail-negociation" class="hidden mb-6 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                    <h4 class="font-semibold text-orange-800 dark:text-orange-300 mb-2 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        Proposer un prix
                                    </h4>
                                    <p class="text-sm text-orange-700 dark:text-orange-400 mb-3">
                                        Cette entreprise accepte les négociations. Vous pouvez proposer un prix via la messagerie.
                                    </p>
                                    @auth
                                        <a href="{{ route('messagerie.show', $entreprise->slug) }}" id="service-detail-negocier-btn" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                            Négocier ce service
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition text-sm">
                                            Connectez-vous pour négocier
                                        </a>
                                    @endauth
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                                    @if($entreprise->prendRdvSurDemande())
                                        <a href="{{ route('public.agenda', $entreprise->slug) }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-lg transition text-sm sm:text-base">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Demander un rendez-vous
                                        </a>
                                    @else
                                        <a href="{{ route('public.agenda', $entreprise->slug) }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-lg transition text-sm sm:text-base">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Réserver ce service
                                        </a>
                                    @endif
                                    @auth
                                        <a href="#" id="service-contacter-link" class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition text-sm sm:text-base">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                            Contacter
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition text-sm sm:text-base">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                            Contacter
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    let currentServiceDetailIndex = 0;
                    let currentServiceDetailImageIndex = 0;
                    const servicesDetailData = [
                        @foreach($services as $service)
                        {
                            id: {{ $service->id }},
                            nom: "{{ addslashes($service->nom) }}",
                            description: "{{ addslashes($service->description ?? '') }}",
                            prix: "{{ number_format($service->prix, 2, ',', ' ') }}",
                            duree: {{ $service->duree_minutes }},
                            images: [
                                @foreach($service->images as $image)
                                "{{ asset('media/' . $image->image_path) }}",
                                @endforeach
                            ],
                            options: {!! json_encode($service->options->map(function($opt) {
                                return [
                                    'nom' => $opt->nom,
                                    'obligatoire' => $opt->obligatoire,
                                    'choices' => $opt->choices->map(function($c) {
                                        return [
                                            'nom' => $c->nom,
                                            'prix' => $c->prix_supplementaire,
                                            'temps' => $c->temps_supplementaire
                                        ];
                                    })
                                ];
                            })) !!},
                        },
                        @endforeach
                    ];
                    const prixNegociables = {{ $entreprise->prix_negociables ? 'true' : 'false' }};

                    function openServiceDetailModal(serviceIndex) {
                        currentServiceDetailIndex = serviceIndex;
                        currentServiceDetailImageIndex = 0;
                        updateServiceDetailModal();
                        document.getElementById('service-detail-modal').classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    }

                    function closeServiceDetailModal(event) {
                        if (event && event.target !== event.currentTarget) return;
                        document.getElementById('service-detail-modal').classList.add('hidden');
                        document.body.style.overflow = '';
                    }

                    function prevServiceDetailImage(event) {
                        event.stopPropagation();
                        const service = servicesDetailData[currentServiceDetailIndex];
                        if (service.images.length > 1) {
                            currentServiceDetailImageIndex = (currentServiceDetailImageIndex - 1 + service.images.length) % service.images.length;
                            updateServiceDetailGallery();
                        }
                    }

                    function nextServiceDetailImage(event) {
                        event.stopPropagation();
                        const service = servicesDetailData[currentServiceDetailIndex];
                        if (service.images.length > 1) {
                            currentServiceDetailImageIndex = (currentServiceDetailImageIndex + 1) % service.images.length;
                            updateServiceDetailGallery();
                        }
                    }

                    function selectServiceDetailImage(index) {
                        currentServiceDetailImageIndex = index;
                        updateServiceDetailGallery();
                    }

                    function updateServiceDetailModal() {
                        const service = servicesDetailData[currentServiceDetailIndex];
                        
                        // Infos de base
                        document.getElementById('service-detail-nom').textContent = service.nom;
                        document.getElementById('service-detail-prix').textContent = service.prix + ' €';
                        document.getElementById('service-detail-duree').innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Durée : ' + service.duree + ' minutes';
                        document.getElementById('service-detail-description').textContent = service.description || 'Aucune description disponible.';
                        
                        // Mettre à jour les liens
                        const contacterLink = document.getElementById('service-contacter-link');
                        const reserverLink = document.getElementById('service-reserver-link');
                        const baseUrl = "{{ route('messagerie.demander-service', [$entreprise->slug, 'serviceId' => 0]) }}";
                        if (contacterLink) {
                            contacterLink.href = baseUrl.replace('/0', '/' + service.id);
                        }
                        if (reserverLink) {
                            reserverLink.href = baseUrl.replace('/0', '/' + service.id);
                        }
                        
                        // Badge négociable
                        const negociableBadge = document.getElementById('service-detail-negociable-badge');
                        const negociationZone = document.getElementById('service-detail-negociation');
                        if (prixNegociables) {
                            negociableBadge.classList.remove('hidden');
                            negociationZone.classList.remove('hidden');
                        } else {
                            negociableBadge.classList.add('hidden');
                            negociationZone.classList.add('hidden');
                        }
                        
                        // Galerie
                        updateServiceDetailGallery();

                        // Afficher les options
                        const optionsContainer = document.getElementById('service-detail-options');
                        if (optionsContainer) {
                            if (service.options && service.options.length > 0) {
                                optionsContainer.classList.remove('hidden');
                                optionsContainer.innerHTML = `
                                    <div class="mt-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600">
                                        <h4 class="font-bold text-slate-900 dark:text-white mb-3 text-sm uppercase tracking-wider flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                            </svg>
                                            Options disponibles
                                        </h4>
                                        <div class="space-y-4">
                                            ${service.options.map(opt => `
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 flex items-center justify-between">
                                                        ${opt.nom}
                                                        ${opt.obligatoire ? '<span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">OBLIGATOIRE</span>' : ''}
                                                    </p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                        ${opt.choices.map(c => {
                                                            let details = [];
                                                            if (c.prix > 0) details.push(`+${c.prix}€`);
                                                            if (c.temps > 0) details.push(`+${c.temps}min`);
                                                            const detailsStr = details.length > 0 ? `<span class="text-green-600 dark:text-green-400 font-bold ml-1">(${details.join(', ')})</span>` : '';
                                                            return `
                                                                <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center bg-white dark:bg-slate-800 p-2 rounded-lg border border-slate-100 dark:border-slate-700">
                                                                    <span class="w-1.5 h-1.5 bg-slate-300 dark:bg-slate-600 rounded-full mr-2"></span>
                                                                    ${c.nom} ${detailsStr}
                                                                </div>
                                                            `;
                                                        }).join('')}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-4 italic text-center">Vous sélectionnerez vos options lors de la réservation.</p>
                                    </div>
                                `;
                            } else {
                                optionsContainer.classList.add('hidden');
                                optionsContainer.innerHTML = '';
                            }
                        }
                    }

                    function updateServiceDetailGallery() {
                        const service = servicesDetailData[currentServiceDetailIndex];
                        const imageEl = document.getElementById('service-detail-image');
                        const noImageEl = document.getElementById('service-detail-no-image');
                        const prevBtn = document.getElementById('service-detail-prev');
                        const nextBtn = document.getElementById('service-detail-next');
                        const indicator = document.getElementById('service-detail-indicator');
                        const thumbnails = document.getElementById('service-detail-thumbnails');
                        
                        if (service.images && service.images.length > 0) {
                            // Afficher l'image, masquer le placeholder
                            imageEl.src = service.images[currentServiceDetailImageIndex];
                            imageEl.classList.remove('hidden');
                            noImageEl.classList.add('hidden');
                            
                            if (service.images.length > 1) {
                                prevBtn.classList.remove('hidden');
                                nextBtn.classList.remove('hidden');
                                indicator.classList.remove('hidden');
                                indicator.textContent = (currentServiceDetailImageIndex + 1) + ' / ' + service.images.length;
                                
                                // Miniatures
                                thumbnails.classList.remove('hidden');
                                thumbnails.innerHTML = service.images.map((img, i) => `
                                    <img 
                                        src="${img}" 
                                        alt="Miniature ${i + 1}"
                                        onclick="selectServiceDetailImage(${i})"
                                        class="w-14 h-14 sm:w-16 sm:h-16 object-cover rounded cursor-pointer flex-shrink-0 border-2 transition ${i === currentServiceDetailImageIndex ? 'border-green-500' : 'border-transparent hover:border-slate-400'}"
                                    >
                                `).join('');
                            } else {
                                prevBtn.classList.add('hidden');
                                nextBtn.classList.add('hidden');
                                indicator.classList.add('hidden');
                                thumbnails.classList.add('hidden');
                            }
                        } else {
                            // Masquer l'image, afficher le placeholder
                            imageEl.classList.add('hidden');
                            noImageEl.classList.remove('hidden');
                            prevBtn.classList.add('hidden');
                            nextBtn.classList.add('hidden');
                            indicator.classList.add('hidden');
                            thumbnails.classList.add('hidden');
                        }
                    }

                    // Navigation au clavier pour le modal service detail
                    document.addEventListener('keydown', function(e) {
                        const modal = document.getElementById('service-detail-modal');
                        if (!modal.classList.contains('hidden')) {
                            if (e.key === 'Escape') closeServiceDetailModal();
                            if (e.key === 'ArrowLeft') {
                                const service = servicesDetailData[currentServiceDetailIndex];
                                if (service.images.length > 1) {
                                    currentServiceDetailImageIndex = (currentServiceDetailImageIndex - 1 + service.images.length) % service.images.length;
                                    updateServiceDetailGallery();
                                }
                            }
                            if (e.key === 'ArrowRight') {
                                const service = servicesDetailData[currentServiceDetailIndex];
                                if (service.images.length > 1) {
                                    currentServiceDetailImageIndex = (currentServiceDetailImageIndex + 1) % service.images.length;
                                    updateServiceDetailGallery();
                                }
                            }
                        }
                    });
                </script>
            </section>
        @endif

        <!-- Section Produits -->
        @php
            $produitsDisponibles = $produits ?? collect([]);
        @endphp
        @if($produitsDisponibles->count() > 0)
            <section id="produits-section" class="mt-8 sm:mt-12">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4 sm:mb-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                        Produits
                    </h2>
                    <a href="{{ route('public.produits', $entreprise->slug) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white text-xs sm:text-sm font-semibold rounded-lg transition">
                        Voir tous les produits
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
                <div class="grid gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($produitsDisponibles->take(6) as $produit)
                        @php
                            $imageCouverture = $produit->imageCouverture;
                            $premiereImage = $produit->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            $promotion = $produit->promotionActive()->first();
                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                        @endphp
                        <a 
                            href="{{ route('public.store', $entreprise->slug) }}" 
                            class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all hover:border-green-300 dark:hover:border-green-700 group"
                            data-produit-id="{{ $produit->id }}"
                            data-produit-nom="{{ $produit->nom }}"
                            data-tracking-produit="true"
                        >
                            @if($imageAffichee)
                                <div class="relative h-36 sm:h-48 w-full overflow-hidden">
                                    <img 
                                        src="{{ asset('media/' . $imageAffichee->image_path) }}" 
                                        alt="{{ $produit->nom }}"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                    @if($promotion)
                                        <div class="absolute top-2 right-2 bg-red-500 text-white px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold">
                                            PROMO
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="relative h-36 sm:h-48 w-full bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/20 dark:to-cyan-900/20 flex items-center justify-center">
                                    <svg class="w-12 h-12 sm:w-16 sm:h-16 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    @if($promotion)
                                        <div class="absolute top-2 right-2 bg-red-500 text-white px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold">
                                            PROMO
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="p-4 sm:p-6">
                                <h3 class="text-base sm:text-xl font-bold text-slate-900 dark:text-white mb-1 sm:mb-2 truncate group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                    {{ $produit->nom }}
                                </h3>
                                
                                @if($produit->description)
                                    <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm mb-3 sm:mb-4 line-clamp-2">
                                        {{ $produit->description }}
                                    </p>
                                @endif
                                
                                <div class="flex items-center justify-between pt-3 sm:pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <div class="flex flex-col">
                                        @if($promotion)
                                            <div class="flex items-center gap-2">
                                                <span class="text-base sm:text-lg line-through text-slate-400">{{ number_format($produit->prix, 2, ',', ' ') }} €</span>
                                                <span class="text-lg sm:text-2xl font-bold text-red-600 dark:text-red-400">
                                                    {{ number_format($prixActuel, 2, ',', ' ') }} €
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400">
                                                {{ number_format($prixActuel, 2, ',', ' ') }} €
                                            </span>
                                        @endif
                                        @if($produit->gestion_stock === 'en_attente_commandes')
                                            <span class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">📦 En attente de commandes</span>
                                        @elseif($produit->stock)
                                            <span class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">En stock: {{ $produit->stock->quantite_disponible }}</span>
                                        @endif
                                    </div>
                                    <div class="text-green-600 dark:text-green-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Section Avis et Notes -->
        <section class="mt-8 sm:mt-12">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-4 sm:mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-1 sm:mb-2">
                        Avis et Notes
                    </h2>
                    @if($entreprise->nombre_avis > 0)
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            <div class="flex items-center gap-0.5 sm:gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= round($entreprise->note_moyenne))
                                        <span class="text-lg sm:text-2xl text-yellow-400">★</span>
                                    @else
                                        <span class="text-lg sm:text-2xl text-slate-300 dark:text-slate-600">☆</span>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-sm sm:text-lg font-semibold text-slate-900 dark:text-white">
                                {{ number_format($entreprise->note_moyenne, 1) }} / 5
                            </span>
                            <span class="text-xs sm:text-base text-slate-600 dark:text-slate-400">
                                ({{ $entreprise->nombre_avis }} avis)
                            </span>
                        </div>
                    @else
                        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400">Aucun avis pour le moment</p>
                    @endif
                </div>
                @auth
                    @if($peutLaisserAvis && !$userAvis)
                        <a href="{{ route('avis.create', $entreprise->slug) }}" class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm sm:text-base">
                            Laisser un avis
                        </a>
                    @elseif($userAvis)
                        <a href="{{ route('avis.create', $entreprise->slug) }}" class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-400 hover:from-orange-600 hover:to-orange-500 text-white font-semibold rounded-lg transition text-sm sm:text-base">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Modifier mon avis
                        </a>
                    @else
                        <div class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold rounded-lg border border-slate-300 dark:border-slate-700 text-xs sm:text-sm" title="Vous devez avoir une réservation validée et payée pour noter">
                            Réservation requise
                        </div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-3 sm:px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm sm:text-base">
                        Se connecter pour noter
                    </a>
                @endauth
            </div>

            <!-- Liste des avis -->
            @if($avis->count() > 0)
                <div class="space-y-3 sm:space-y-4">
                    @foreach($avis as $unAvis)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <div class="flex items-start justify-between gap-2 mb-2 sm:mb-3">
                                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                                    @if($unAvis->user)
                                        <div class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center overflow-hidden">
                                            @if($unAvis->user->photo_profil)
                                                <img 
                                                    src="{{ asset('media/' . $unAvis->user->photo_profil) }}" 
                                                    alt="{{ $unAvis->user->name }}"
                                                    class="h-full w-full object-cover"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                >
                                                <span class="text-white font-bold text-xs sm:text-sm hidden">
                                                    {{ strtoupper(substr($unAvis->user->name ?? '?', 0, 2)) }}
                                                </span>
                                            @else
                                                <span class="text-white font-bold text-xs sm:text-sm">
                                                    {{ strtoupper(substr($unAvis->user->name ?? '?', 0, 2)) }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center">
                                            <span class="text-slate-500 dark:text-slate-400 font-bold text-xs sm:text-sm">?</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm sm:text-base text-slate-900 dark:text-white truncate">
                                            {{ $unAvis->user->name ?? 'Utilisateur supprimé' }}
                                        </p>
                                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400">{{ $unAvis->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-0.5 flex-shrink-0">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $unAvis->note)
                                            <span class="text-sm sm:text-base text-yellow-400">★</span>
                                        @else
                                            <span class="text-sm sm:text-base text-slate-300 dark:text-slate-600">☆</span>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            @if($unAvis->commentaire)
                                <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 mt-2 sm:mt-3">{{ $unAvis->commentaire }}</p>
                            @endif
                            
                            @if($unAvis->photos && $unAvis->photos->count() > 0)
                                <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <p class="text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 mb-2 flex items-center gap-1.5 sm:gap-2">
                                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="truncate">Photos de {{ $unAvis->user->name ?? 'utilisateur supprimé' }}</span>
                                    </p>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-1.5 sm:gap-2">
                                        @foreach($unAvis->photos as $photo)
                                            <div class="relative overflow-hidden rounded-lg cursor-pointer group aspect-square" onclick="openAvisPhoto('{{ asset('media/' . $photo->photo_path) }}')">
                                                <img 
                                                    src="{{ asset('media/' . $photo->photo_path) }}" 
                                                    alt="Photo avis"
                                                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                                >
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 sm:mt-6">
                    {{ $avis->links() }}
                </div>
            @else
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sm:p-8 text-center">
                    <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-slate-400 mb-3 sm:mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400">Aucun avis pour le moment. Soyez le premier à noter cette entreprise !</p>
                </div>
            @endif
        </section>
    </div>

    <!-- Modal pour les photos des avis -->
    <div id="avis-photo-modal" class="hidden fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-2 sm:p-4" onclick="closeAvisPhotoModal(event)">
        <button onclick="closeAvisPhotoModal()" class="absolute top-2 right-2 sm:top-4 sm:right-4 text-white hover:text-green-400 transition z-10">
            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <div class="max-w-4xl w-full px-4">
            <img id="avis-modal-photo" src="" alt="Photo avis" class="w-full h-auto rounded-lg max-h-[80vh] object-contain">
        </div>
    </div>

    <script>
        // Fonctions pour le modal des photos d'avis
        function openAvisPhoto(src) {
            document.getElementById('avis-modal-photo').src = src;
            document.getElementById('avis-photo-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAvisPhotoModal(event) {
            if (event && event.target !== event.currentTarget && !event.target.closest('button')) {
                return;
            }
            document.getElementById('avis-photo-modal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Navigation au clavier pour le modal des photos d'avis
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('avis-photo-modal');
            if (!modal.classList.contains('hidden') && e.key === 'Escape') {
                closeAvisPhotoModal();
            }
        });
    </script>

    <!-- Bouton fixe "Prendre rendez-vous" en bas sur mobile -->
    <div class="fixed bottom-0 left-0 right-0 z-50 lg:hidden bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 shadow-lg">
            <div class="max-w-6xl mx-auto px-4 py-3">
                @if($entreprise->prendRdvSurDemande())
                    <a href="{{ route('public.agenda', $entreprise->slug) }}" class="block w-full bg-gradient-to-r from-green-600 to-orange-500 hover:from-green-700 hover:to-orange-600 text-white font-bold py-3 px-4 rounded-lg transition text-center text-base">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Demander un rendez-vous
                    </a>
                @else
                    <a href="{{ route('public.agenda', $entreprise->slug) }}" class="block w-full bg-gradient-to-r from-green-600 to-orange-500 hover:from-green-700 hover:to-orange-600 text-white font-bold py-3 px-4 rounded-lg transition text-center text-base">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Prendre rendez-vous
                    </a>
                @endif
            </div>
        </div>
    <!-- Script de tracking des visites -->
    @vite('resources/js/tracking-visite.js')

    <!-- Padding en bas pour éviter que le contenu soit masqué par le bouton fixe -->
    <div class="h-20 lg:hidden"></div>

    @include('partials.footer')
    @include('partials.cookie-banner')
</body>
</html>
