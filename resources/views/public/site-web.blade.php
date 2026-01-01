<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $entreprise->nom }} - Site Web</title>
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
    @if(isset($isOwner) && $isOwner && !empty($entreprise->slug_web))
        <!-- Barre pour le propriétaire en mode vue -->
        <div class="bg-blue-600 text-white py-2 px-4 shadow-md">
            <div class="max-w-6xl mx-auto flex items-center justify-between text-sm">
                <span>👁️ Vous visualisez votre site en mode public</span>
                <a 
                    href="{{ route('site-web.show', ['slug' => $entreprise->slug_web]) }}" 
                    class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded transition font-medium"
                >
                    ✏️ Passer en mode édition
                </a>
            </div>
        </div>
    @endif
    <!-- En-tête avec image de fond ou logo -->
    @if(!empty($entreprise->image_fond))
        <div class="relative h-64 md:h-96 w-full overflow-hidden">
            <img 
                src="{{ route('storage.serve', ['path' => $entreprise->image_fond]) }}" 
                alt="Image de fond {{ $entreprise->nom }}"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8">
                <div class="max-w-6xl mx-auto">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        @if(!empty($entreprise->logo))
                            <img 
                                src="{{ route('storage.serve', ['path' => $entreprise->logo]) }}" 
                                alt="Logo {{ $entreprise->nom }}"
                                class="w-20 h-20 md:w-24 md:h-24 rounded-lg object-cover border-2 border-white/20 shadow-lg flex-shrink-0"
                            >
                        @endif
                        <div class="min-w-0">
                            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-2">
                                {{ $entreprise->nom }}
                            </h1>
                            @if($entreprise->phrase_accroche)
                                <p class="text-lg md:text-xl text-white/90">
                                    {{ $entreprise->phrase_accroche }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    @if(!empty($entreprise->logo))
                        <img 
                            src="{{ route('storage.serve', ['path' => $entreprise->logo]) }}" 
                            alt="Logo {{ $entreprise->nom }}"
                            class="w-20 h-20 md:w-24 md:h-24 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700 flex-shrink-0"
                        >
                    @endif
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent mb-2">
                            {{ $entreprise->nom }}
                        </h1>
                        @if($entreprise->phrase_accroche)
                            <p class="text-lg text-slate-600 dark:text-slate-400">
                                {{ $entreprise->phrase_accroche }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </header>
    @endif
    
    <!-- Contenu principal -->
    <div class="max-w-6xl mx-auto py-8 md:py-12 px-4 sm:px-6">
        <!-- Description -->
        @if($entreprise->description)
            <section class="mb-12">
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                        {{ $entreprise->description }}
                    </p>
                </div>
            </section>
        @endif

        <!-- Photos de réalisations -->
        @if($entreprise->realisationPhotos->count() > 0)
            <section class="mb-12">
                <h2 class="text-2xl md:text-3xl font-bold mb-6">Nos réalisations</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    @foreach($entreprise->realisationPhotos as $photo)
                        @if(!empty($photo->photo_path))
                            <div class="relative group overflow-hidden rounded-lg aspect-square">
                                <img 
                                    src="{{ route('storage.serve', ['path' => $photo->photo_path]) }}" 
                                    alt="{{ $photo->titre ?? 'Photo de réalisation' }}"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                >
                                @if($photo->titre)
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div class="absolute bottom-0 left-0 right-0 p-4">
                                            <p class="text-white font-medium">{{ $photo->titre }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Informations de contact -->
        <section class="bg-white dark:bg-slate-800 rounded-lg p-6 md:p-8 border border-slate-200 dark:border-slate-700">
            <h2 class="text-2xl md:text-3xl font-bold mb-6">Contactez-nous</h2>
            <div class="space-y-4">
                @if($entreprise->email)
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <a href="mailto:{{ $entreprise->email }}" class="text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400">
                            {{ $entreprise->email }}
                        </a>
                    </div>
                @endif
                @if($entreprise->telephone)
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <a href="tel:{{ $entreprise->telephone }}" class="text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400">
                            {{ $entreprise->telephone }}
                        </a>
                    </div>
                @endif
                @if($entreprise->ville)
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="text-slate-700 dark:text-slate-300">{{ $entreprise->ville }}</span>
                    </div>
                @endif
            </div>
        </section>

        <!-- Lien vers le profil complet sur Allo Tata -->
        <div class="mt-8 text-center">
            <a 
                href="{{ route('public.entreprise', ['slug' => $entreprise->slug]) }}" 
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-orange-500 text-white font-semibold rounded-lg hover:from-green-600 hover:to-orange-600 transition"
            >
                Voir le profil complet sur Allo Tata
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- Pied de page -->
    <footer class="bg-slate-800 dark:bg-slate-900 text-slate-400 py-8 mt-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 text-center">
            <p class="text-sm">
                Site web créé avec <a href="{{ route('home') }}" class="text-green-400 hover:text-green-300">Allo Tata</a>
            </p>
        </div>
    </footer>
</body>
</html>
