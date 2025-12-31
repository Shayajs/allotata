<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - {{ $slug }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Détecter la préférence système et appliquer le thème au chargement
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <!-- Image de fond en en-tête -->
    @if($entreprise->image_fond)
        <div class="relative h-64 md:h-96 w-full overflow-hidden">
            <img 
                src="{{ asset('storage/' . $entreprise->image_fond) }}" 
                alt="Image de fond {{ $entreprise->nom }}"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-6">
                <div class="max-w-4xl mx-auto">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-white hover:text-green-300 transition mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span class="font-medium">Retour à l'accueil</span>
                    </a>
                    <div class="flex items-center gap-4">
                        @if($entreprise->logo)
                            <img 
                                src="{{ asset('storage/' . $entreprise->logo) }}" 
                                alt="Logo {{ $entreprise->nom }}"
                                class="w-20 h-20 rounded-lg object-cover border-2 border-white/20 shadow-lg"
                            >
                        @endif
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <h1 class="text-4xl font-bold text-white">
                                    {{ $entreprise->nom }}
                                </h1>
                                @if(!$entreprise->est_verifiee)
                                    <span class="px-3 py-1 text-xs font-medium bg-yellow-500/80 text-white rounded-full">
                                        ⏳ En cours de création
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs font-medium bg-green-500/80 text-white rounded-full">
                                        ✓ Vérifiée
                                    </span>
                                @endif
                            </div>
                            <p class="text-lg text-white/90">
                                {{ $entreprise->type_activite }}
                                @if($entreprise->ville)
                                    • {{ $entreprise->ville }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="max-w-4xl mx-auto py-12 px-6">
            <!-- Navigation -->
            <nav class="mb-6">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="font-medium">Retour à l'accueil</span>
                </a>
            </nav>
            
            <header class="border-b border-slate-200 dark:border-slate-700 pb-6 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($entreprise->logo)
                        <img 
                            src="{{ asset('storage/' . $entreprise->logo) }}" 
                            alt="Logo {{ $entreprise->nom }}"
                            class="w-20 h-20 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700"
                        >
                    @endif
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-4xl font-bold tracking-tight bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                                {{ $entreprise->nom }}
                            </h1>
                            @if(!$entreprise->est_verifiee)
                                <span class="px-3 py-1 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-200 dark:border-yellow-800">
                                    ⏳ En cours de création
                                </span>
                            @else
                                <span class="px-3 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full border border-green-200 dark:border-green-800">
                                    ✓ Vérifiée
                                </span>
                            @endif
                        </div>
                        <p class="text-lg text-slate-600 dark:text-slate-400">
                            {{ $entreprise->type_activite }}
                            @if($entreprise->ville)
                                • {{ $entreprise->ville }}
                            @endif
                        </p>
                    </div>
                </div>
                <button 
                    id="theme-toggle"
                    class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                    aria-label="Basculer le thème"
                >
                    <svg class="w-6 h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg class="w-6 h-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>
            </div>
        </header>

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-red-800 dark:text-red-300">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-green-800 dark:text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(!$entreprise->est_verifiee)
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-yellow-800 dark:text-yellow-300">Cette entreprise est en cours de création</p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                            Les informations peuvent être incomplètes. L'entreprise sera vérifiée et validée prochainement.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($isOwner) && $isOwner && !$entreprise->aAbonnementActif())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-red-800 dark:text-red-300">⚠️ Votre entreprise n'est pas visible en ligne</p>
                        <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                            Vous consultez votre propre entreprise, mais elle n'est pas visible pour les autres utilisateurs car vous n'avez pas d'abonnement actif. 
                            <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="underline font-semibold">Souscrivez à un abonnement</a> pour rendre votre entreprise visible dans les recherches.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <main class="grid gap-6 md:grid-cols-2">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <h2 class="font-semibold text-xl mb-4 text-slate-900 dark:text-slate-100">Informations</h2>
                
                @if($entreprise->description)
                    <div class="mb-4">
                        <p class="text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $entreprise->description }}</p>
                    </div>
                @endif

                <!-- Galerie de réalisations -->
                @if($entreprise->realisationPhotos->count() > 0)
                    <div class="mt-8">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                            📸 Dernières réalisations
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($entreprise->realisationPhotos as $photo)
                                <div class="group relative overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer" onclick="openModal({{ $loop->index }})">
                                    <img 
                                        src="{{ asset('storage/' . $photo->photo_path) }}" 
                                        alt="{{ $photo->titre ?? 'Réalisation' }}"
                                        class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110"
                                    >
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                        @if($photo->titre)
                                            <div class="absolute bottom-0 left-0 right-0 p-3">
                                                <p class="text-white text-sm font-semibold">{{ $photo->titre }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modal pour afficher les photos en grand -->
                    <div id="photo-modal" class="hidden fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4">
                        <button onclick="closeModal()" class="absolute top-4 right-4 text-white hover:text-green-400 transition z-10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <button onclick="prevPhoto()" class="absolute left-4 text-white hover:text-green-400 transition z-10">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button onclick="nextPhoto()" class="absolute right-4 text-white hover:text-green-400 transition z-10">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <div class="max-w-4xl w-full">
                            <img id="modal-photo" src="" alt="" class="w-full h-auto rounded-lg">
                            <div id="modal-info" class="mt-4 text-center text-white">
                                <h3 id="modal-titre" class="text-xl font-bold mb-2"></h3>
                                <p id="modal-description" class="text-slate-300"></p>
                            </div>
                        </div>
                    </div>

                    <script>
                        let currentPhotoIndex = 0;
                        const photos = [
                            @foreach($entreprise->realisationPhotos as $photo)
                            {
                                path: '{{ asset('storage/' . $photo->photo_path) }}',
                                titre: @json($photo->titre ?? ''),
                                description: @json($photo->description ?? ''),
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

                <div class="space-y-3">
                    @if($entreprise->telephone)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span>{{ $entreprise->telephone }}</span>
                        </div>
                    @endif

                    @if($entreprise->email)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>{{ $entreprise->email }}</span>
                        </div>
                    @endif

                    @if($entreprise->afficher_nom_gerant && $entreprise->user)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Gérée par {{ $entreprise->user->name }}</span>
                        </div>
                    @endif

                    @if($entreprise->rayon_deplacement > 0)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Déplacement jusqu'à {{ $entreprise->rayon_deplacement }} km</span>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Service fixe</span>
                        </div>
                    @endif
                </div>

                @if($entreprise->mots_cles)
                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Mots-clés :</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(explode(', ', $entreprise->mots_cles) as $motCle)
                                <span class="px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                    {{ trim($motCle) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="p-6 bg-gradient-to-br from-green-50 to-orange-50 dark:from-green-900/20 dark:to-orange-900/20 rounded-xl border border-green-200 dark:border-green-800 space-y-3">
                <!-- Horaires d'ouverture -->
                @if($horaires->count() > 0)
                    <div class="mb-6 pb-6 border-b border-green-200 dark:border-green-800">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Horaires d'ouverture
                        </h3>
                        <div class="space-y-2">
                            @php
                                $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                $horairesParJour = [];
                                foreach ($horaires as $horaire) {
                                    if (!$horaire->est_exceptionnel) {
                                        $horairesParJour[$horaire->jour_semaine] = $horaire;
                                    }
                                }
                            @endphp
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $horaire = $horairesParJour[$i] ?? null;
                                    $estFerme = !$horaire || !$horaire->heure_ouverture || !$horaire->heure_fermeture;
                                @endphp
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-700 dark:text-slate-300 {{ $i === now()->dayOfWeek ? 'text-green-600 dark:text-green-400' : '' }}">
                                        {{ $jours[$i] }}
                                        @if($i === now()->dayOfWeek)
                                            <span class="text-xs">(Aujourd'hui)</span>
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

                @if($entreprise->rdv_uniquement_messagerie)
                    @auth
                        <a href="{{ route('messagerie.show', $entreprise->slug) }}" class="block w-full bg-gradient-to-r from-green-600 to-orange-500 hover:from-green-700 hover:to-orange-600 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                            📅 Prendre rendez-vous (via messagerie)
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="block w-full bg-gradient-to-r from-green-600 to-orange-500 hover:from-green-700 hover:to-orange-600 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                            📅 Prendre rendez-vous (via messagerie)
                        </a>
                    @endauth
                @else
                    <a href="{{ route('public.agenda', $entreprise->slug) }}" class="block w-full bg-gradient-to-r from-green-600 to-orange-500 hover:from-green-700 hover:to-orange-600 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                        Prendre rendez-vous
                    </a>
                @endif
                @auth
                    <a href="{{ route('messagerie.show', $entreprise->slug) }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                        💬 Contacter
                    </a>
                @else
                    <a href="{{ route('login') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                        💬 Se connecter pour contacter
                    </a>
                @endauth
            </div>
        </main>

        <!-- Section Avis et Notes -->
        <section class="mt-12">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                        Avis et Notes
                    </h2>
                    @if($entreprise->nombre_avis > 0)
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= round($entreprise->note_moyenne))
                                        <span class="text-2xl text-yellow-400">★</span>
                                    @else
                                        <span class="text-2xl text-slate-300 dark:text-slate-600">☆</span>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ number_format($entreprise->note_moyenne, 1) }} / 5
                            </span>
                            <span class="text-slate-600 dark:text-slate-400">
                                ({{ $entreprise->nombre_avis }} avis)
                            </span>
                        </div>
                    @else
                        <p class="text-slate-600 dark:text-slate-400">Aucun avis pour le moment</p>
                    @endif
                </div>
                @auth
                    @if($peutLaisserAvis && !$userAvis)
                        <a href="{{ route('avis.create', $entreprise->slug) }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                            Laisser un avis
                        </a>
                    @elseif($userAvis)
                        <span class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 font-semibold rounded-lg">
                            Vous avez déjà noté
                        </span>
                    @else
                        <div class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold rounded-lg border border-slate-300 dark:border-slate-700" title="Vous devez avoir une réservation validée et payée pour noter">
                            Réservation requise
                        </div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                        Se connecter pour noter
                    </a>
                @endauth
            </div>

            <!-- Liste des avis -->
            @if($avis->count() > 0)
                <div class="space-y-4">
                    @foreach($avis as $unAvis)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                                        {{ strtoupper(substr($unAvis->user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $unAvis->user->name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $unAvis->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $unAvis->note)
                                            <span class="text-yellow-400">★</span>
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600">☆</span>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            @if($unAvis->commentaire)
                                <p class="text-slate-700 dark:text-slate-300 mt-3">{{ $unAvis->commentaire }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $avis->links() }}
                </div>
            @else
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <p class="text-slate-600 dark:text-slate-400">Aucun avis pour le moment. Soyez le premier à noter cette entreprise !</p>
                </div>
            @endif
        </section>
    @endif
    </div>

    <script>
        // Gérer le toggle du thème
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            
            // Sauvegarder la préférence
            if (html.classList.contains('dark')) {
                localStorage.theme = 'dark';
            } else {
                localStorage.theme = 'light';
            }
        });
    </script>
</body>
</html>