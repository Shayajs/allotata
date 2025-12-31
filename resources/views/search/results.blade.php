<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Résultats de recherche - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('home') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </a>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                                Connexion
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Zone de recherche -->
            <div class="mb-8">
                <form action="{{ route('search') }}" method="GET" class="relative" id="search-form-results">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="q" 
                            id="search-input-results"
                            value="{{ $query }}"
                            placeholder="Rechercher une entreprise, un service, une ville..." 
                            autocomplete="off"
                            class="w-full px-6 py-4 pl-14 pr-32 text-lg bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
                        >
                        <svg class="absolute left-5 top-1/2 transform -translate-y-1/2 w-6 h-6 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <button 
                            type="submit"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all"
                        >
                            Rechercher
                        </button>
                    </div>
                    <!-- Résultats en temps réel -->
                    <div id="autocomplete-results-results" class="hidden absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-2xl shadow-2xl z-50 max-h-96 overflow-y-auto">
                        <div id="autocomplete-list-results" class="p-2"></div>
                    </div>
                </form>
            </div>

            <!-- Résultats -->
            <div>
                @if(!empty($query))
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        Résultats pour "{{ $query }}"
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        {{ $count }} résultat(s) trouvé(s)
                    </p>
                @endif

                @if($count > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($results as $entreprise)
                            <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="block p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-green-500 dark:hover:border-green-500 hover:shadow-lg transition-all">
                                <div class="flex items-start gap-4 mb-3">
                                    @if($entreprise->logo)
                                        <img 
                                            src="{{ asset('storage/' . $entreprise->logo) }}" 
                                            alt="Logo {{ $entreprise->nom }}"
                                            class="w-16 h-16 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 flex-shrink-0"
                                        >
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h3 class="text-xl font-bold text-slate-900 dark:text-white truncate">
                                                {{ $entreprise->nom }}
                                            </h3>
                                            @if(!$entreprise->est_verifiee)
                                                <span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-200 dark:border-yellow-800 flex-shrink-0">
                                                    ⏳ En cours
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                                            @if($entreprise->type_activite)
                                                <p class="text-sm text-green-600 dark:text-green-400 font-medium">
                                                    {{ $entreprise->type_activite }}
                                                </p>
                                            @endif
                                            @php
                                                $noteMoyenne = $entreprise->avis()->avg('note') ?? 0;
                                                $nombreAvis = $entreprise->avis()->count();
                                            @endphp
                                            @if($nombreAvis > 0)
                                                <div class="flex items-center gap-1">
                                                    <span class="text-yellow-400">★</span>
                                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($noteMoyenne, 1) }}</span>
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">({{ $nombreAvis }})</span>
                                                </div>
                                            @endif
                                            @if($entreprise->afficher_nom_gerant && $entreprise->user)
                                                <span class="text-xs text-slate-500 dark:text-slate-500">
                                                    • Gérée par {{ $entreprise->user->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                @if($entreprise->description)
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2">
                                        {{ Str::limit($entreprise->description, 100) }}
                                    </p>
                                @endif

                                @if($entreprise->mots_cles)
                                    <div class="flex flex-wrap gap-1 mb-3">
                                        @foreach(array_slice(explode(', ', $entreprise->mots_cles), 0, 3) as $motCle)
                                            <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                {{ trim($motCle) }}
                                            </span>
                                        @endforeach
                                        @if(count(explode(', ', $entreprise->mots_cles)) > 3)
                                            <span class="px-2 py-0.5 text-xs text-slate-500 dark:text-slate-400">
                                                +{{ count(explode(', ', $entreprise->mots_cles)) - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-500">
                                    @if($entreprise->ville)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span>{{ $entreprise->ville }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @elseif(!empty($query))
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucun résultat</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Essayez avec d'autres mots-clés ou retournez à l'accueil.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                                Retour à l'accueil
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <script>
            // Recherche en temps réel
            (function() {
                const searchInput = document.getElementById('search-input-results');
                const resultsContainer = document.getElementById('autocomplete-results-results');
                const resultsList = document.getElementById('autocomplete-list-results');
                let searchTimeout;
                let currentRequest = null;

                if (!searchInput) return;

                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();

                    // Annuler la requête précédente si elle existe
                    if (currentRequest) {
                        currentRequest.abort();
                    }

                    // Masquer les résultats si la requête est trop courte
                    if (query.length < 2) {
                        resultsContainer.classList.add('hidden');
                        return;
                    }

                    // Délai pour éviter trop de requêtes
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const xhr = new XMLHttpRequest();
                        currentRequest = xhr;
                        
                        xhr.open('GET', `{{ route('search.autocomplete') }}?q=${encodeURIComponent(query)}`);
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                const data = JSON.parse(xhr.responseText);
                                if (data.length === 0) {
                                    resultsList.innerHTML = '<div class="p-4 text-center text-slate-500 dark:text-slate-400">Aucun résultat trouvé</div>';
                                } else {
                                    resultsList.innerHTML = data.map(entreprise => `
                                        <a href="/p/${entreprise.slug}" class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition">
                                            ${entreprise.logo ? `<img src="${entreprise.logo}" alt="${entreprise.nom}" class="w-12 h-12 rounded-lg object-cover border border-slate-200 dark:border-slate-700">` : '<div class="w-12 h-12 rounded-lg bg-slate-200 dark:bg-slate-700"></div>'}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <h3 class="font-semibold text-slate-900 dark:text-white truncate">${entreprise.nom}</h3>
                                                    ${!entreprise.est_verifiee ? '<span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded flex-shrink-0">⏳</span>' : ''}
                                                </div>
                                                <p class="text-sm text-green-600 dark:text-green-400">${entreprise.type_activite}</p>
                                                ${entreprise.ville ? `<p class="text-xs text-slate-500 dark:text-slate-400">${entreprise.ville}</p>` : ''}
                                                ${entreprise.services.length > 0 ? `<p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Services: ${entreprise.services.join(', ')}</p>` : ''}
                                            </div>
                                        </a>
                                    `).join('');
                                }
                                resultsContainer.classList.remove('hidden');
                            }
                        };
                        xhr.onerror = function() {
                            console.error('Erreur de recherche');
                        };
                        xhr.send();
                    }, 300);
                });

                // Masquer les résultats quand on clique ailleurs
                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                        resultsContainer.classList.add('hidden');
                    }
                });

                // Soumettre le formulaire si on appuie sur Entrée
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('search-form-results').submit();
                    }
                });
            })();
        </script>
    </body>
</html>

