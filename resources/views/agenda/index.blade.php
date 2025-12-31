<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Gestion de l'agenda - {{ $entreprise->nom }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- FullCalendar CSS -->
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
        <!-- FullCalendar JS -->
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/fr.global.min.js'></script>
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
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Gestion de l'agenda - {{ $entreprise->nom }}
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Configurez vos horaires d'ouverture et vos types de services.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Calendrier interactif avec détails -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-8">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">📅 Calendrier des réservations</h2>
                <div id="calendar-gerant" class="mb-4"></div>
            </div>

            <!-- Section Horaires d'ouverture -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-8">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Horaires d'ouverture</h2>
                
                <form action="{{ route('agenda.horaires.store', $entreprise->slug) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        @php
                            $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                        @endphp
                        @for($i = 0; $i < 7; $i++)
                            @php
                                $horaire = $horaires->firstWhere('jour_semaine', $i);
                            @endphp
                            <div class="flex items-center gap-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <div class="w-32">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        {{ $jours[$i] }}
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input 
                                            type="checkbox" 
                                            name="horaires[{{ $i }}][ferme]" 
                                            value="1"
                                            class="horaire-ferme-checkbox"
                                            data-index="{{ $i }}"
                                            {{ !$horaire || ($horaire->heure_ouverture === null) ? 'checked' : '' }}
                                        >
                                        <span class="text-sm text-slate-600 dark:text-slate-400">Fermé</span>
                                    </label>
                                </div>
                                <input type="hidden" name="horaires[{{ $i }}][jour_semaine]" value="{{ $i }}">
                                <div class="flex-1 grid grid-cols-2 gap-4 horaire-inputs" data-index="{{ $i }}">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ouverture</label>
                                        <input 
                                            type="time" 
                                            name="horaires[{{ $i }}][heure_ouverture]" 
                                            value="{{ $horaire && $horaire->heure_ouverture ? \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') : '' }}"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fermeture</label>
                                        <input 
                                            type="time" 
                                            name="horaires[{{ $i }}][heure_fermeture]" 
                                            value="{{ $horaire && $horaire->heure_fermeture ? \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') : '' }}"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            Enregistrer les horaires
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section Jours exceptionnels -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Jours exceptionnels</h2>
                    <button 
                        onclick="document.getElementById('modal-jour-exceptionnel').classList.remove('hidden')"
                        class="px-4 py-2 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 text-white font-semibold rounded-lg transition-all"
                    >
                        + Ajouter un jour exceptionnel
                    </button>
                </div>
                
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    Les jours exceptionnels sont prioritaires sur les horaires réguliers. Utilisez-les pour des fermetures exceptionnelles ou des horaires spéciaux.
                </p>

                @php
                    $joursExceptionnels = $entreprise->horairesOuverture()
                        ->where('est_exceptionnel', true)
                        ->where('date_exception', '>=', now()->format('Y-m-d'))
                        ->orderBy('date_exception')
                        ->get();
                @endphp

                @if($joursExceptionnels->count() > 0)
                    <div class="space-y-3">
                        @foreach($joursExceptionnels as $horaire)
                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($horaire->date_exception)->format('d/m/Y') }}
                                    </p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        @if($horaire->heure_ouverture && $horaire->heure_fermeture)
                                            {{ \Carbon\Carbon::parse($horaire->heure_ouverture)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($horaire->heure_fermeture)->format('H:i') }}
                                        @else
                                            <span class="text-red-600 dark:text-red-400">Fermé</span>
                                        @endif
                                    </p>
                                </div>
                                <form action="{{ route('agenda.jour-exceptionnel.delete', [$entreprise->slug, $horaire->id]) }}" method="POST" onsubmit="return confirm('Supprimer ce jour exceptionnel ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 text-sm bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-600 dark:text-slate-400 text-center py-4">Aucun jour exceptionnel configuré.</p>
                @endif
            </div>

            <!-- Section Types de services -->
            <div id="section-services" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Types de services</h2>
                    <button 
                        onclick="openServiceModal()"
                        class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                    >
                        + Ajouter un service
                    </button>
                </div>

                @if($typesServices->count() > 0)
                    <div class="space-y-4">
                        @foreach($typesServices as $service)
                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $service->nom }}</h3>
                                        @if($service->description)
                                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $service->description }}</p>
                                        @endif
                                        <div class="flex items-center gap-4 mt-2 text-sm text-slate-600 dark:text-slate-400">
                                            <span>Durée : {{ $service->duree_minutes }} min</span>
                                            <span>Prix : {{ number_format($service->prix, 2, ',', ' ') }} €</span>
                                            <span class="px-2 py-1 rounded {{ $service->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                                {{ $service->est_actif ? 'Actif' : 'Inactif' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button 
                                            onclick="editService({{ $service->id }}, '{{ $service->nom }}', '{{ $service->description }}', {{ $service->duree_minutes }}, {{ $service->prix }}, {{ $service->est_actif ? 'true' : 'false' }})"
                                            class="px-4 py-2 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                                        >
                                            Modifier
                                        </button>
                                        <form action="{{ route('agenda.service.delete', [$entreprise->slug, $service->id]) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-4 py-2 text-sm bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-slate-600 dark:text-slate-400">Aucun type de service pour le moment.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Modal pour ajouter/modifier un service -->
        <div id="modal-service" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4" id="modal-title">Ajouter un service</h3>
                <form action="{{ route('agenda.service.store', $entreprise->slug) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type_service_id" id="type_service_id">
                    
                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <ul class="text-sm text-red-800 dark:text-red-400 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nom du service *</label>
                            <input 
                                type="text" 
                                name="nom" 
                                id="service_nom"
                                required
                                value="{{ old('nom') }}"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                            @error('nom')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description</label>
                            <textarea 
                                name="description" 
                                id="service_description"
                                rows="3"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Durée (minutes) *</label>
                                <input 
                                    type="number" 
                                    name="duree_minutes" 
                                    id="service_duree"
                                    required
                                    min="1"
                                    value="{{ old('duree_minutes') }}"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                @error('duree_minutes')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Prix (€) *</label>
                                <input 
                                    type="number" 
                                    name="prix" 
                                    id="service_prix"
                                    required
                                    min="0"
                                    step="0.01"
                                    value="{{ old('prix') }}"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                @error('prix')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    name="est_actif" 
                                    id="service_actif"
                                    value="1"
                                    checked
                                    class="rounded border-slate-300 dark:border-slate-600"
                                >
                                <span class="text-sm text-slate-700 dark:text-slate-300">Service actif</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex gap-4 mt-6">
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            Enregistrer
                        </button>
                        <button 
                            type="button"
                            onclick="document.getElementById('modal-service').classList.add('hidden')"
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal pour ajouter un jour exceptionnel -->
        <div id="modal-jour-exceptionnel" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Ajouter un jour exceptionnel</h3>
                <form action="{{ route('agenda.jour-exceptionnel.store', $entreprise->slug) }}" method="POST">
                    @csrf
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date *</label>
                            <input 
                                type="date" 
                                name="date_exception" 
                                required
                                min="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                        <div>
                            <label class="flex items-center gap-2 mb-4">
                                <input 
                                    type="checkbox" 
                                    name="est_ferme" 
                                    id="est_ferme"
                                    value="1"
                                    class="rounded border-slate-300 dark:border-slate-600"
                                    onchange="toggleHorairesExceptionnel()"
                                >
                                <span class="text-sm text-slate-700 dark:text-slate-300">Fermé ce jour</span>
                            </label>
                        </div>
                        <div id="horaires-exceptionnel">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ouverture</label>
                                    <input 
                                        type="time" 
                                        name="heure_ouverture" 
                                        id="heure_ouverture_exceptionnel"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fermeture</label>
                                    <input 
                                        type="time" 
                                        name="heure_fermeture" 
                                        id="heure_fermeture_exceptionnel"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4 mt-6">
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 text-white font-semibold rounded-lg transition-all">
                            Enregistrer
                        </button>
                        <button 
                            type="button"
                            onclick="document.getElementById('modal-jour-exceptionnel').classList.add('hidden')"
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Gérer l'affichage/masquage des champs horaires
            document.querySelectorAll('.horaire-ferme-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const index = this.dataset.index;
                    const inputs = document.querySelector(`.horaire-inputs[data-index="${index}"]`);
                    if (this.checked) {
                        inputs.querySelectorAll('input[type="time"]').forEach(input => {
                            input.disabled = true;
                            input.value = '';
                        });
                    } else {
                        inputs.querySelectorAll('input[type="time"]').forEach(input => {
                            input.disabled = false;
                        });
                    }
                });
                checkbox.dispatchEvent(new Event('change'));
            });

            function openServiceModal() {
                // Réinitialiser le formulaire
                document.getElementById('modal-title').textContent = 'Ajouter un service';
                document.getElementById('type_service_id').value = '';
                document.getElementById('service_nom').value = '';
                document.getElementById('service_description').value = '';
                document.getElementById('service_duree').value = '';
                document.getElementById('service_prix').value = '';
                document.getElementById('service_actif').checked = true;
                
                // Afficher le modal
                document.getElementById('modal-service').classList.remove('hidden');
            }

            function editService(id, nom, description, duree, prix, actif) {
                document.getElementById('modal-title').textContent = 'Modifier le service';
                document.getElementById('type_service_id').value = id;
                document.getElementById('service_nom').value = nom;
                document.getElementById('service_description').value = description || '';
                document.getElementById('service_duree').value = duree;
                document.getElementById('service_prix').value = prix;
                document.getElementById('service_actif').checked = actif;
                document.getElementById('modal-service').classList.remove('hidden');
            }

            // Réinitialiser le formulaire à la fermeture
            document.getElementById('modal-service').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    openServiceModal(); // Réinitialiser
                }
            });

            function toggleHorairesExceptionnel() {
                const estFerme = document.getElementById('est_ferme').checked;
                const horairesDiv = document.getElementById('horaires-exceptionnel');
                const inputs = horairesDiv.querySelectorAll('input[type="time"]');
                
                if (estFerme) {
                    inputs.forEach(input => {
                        input.disabled = true;
                        input.value = '';
                    });
                    horairesDiv.style.opacity = '0.5';
                } else {
                    inputs.forEach(input => {
                        input.disabled = false;
                    });
                    horairesDiv.style.opacity = '1';
                }
            }

            // Réinitialiser le modal jour exceptionnel
            document.getElementById('modal-jour-exceptionnel').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    document.querySelector('#modal-jour-exceptionnel form').reset();
                    toggleHorairesExceptionnel();
                }
            });

            // Scroller vers la section services si on vient de la route service
            @if(isset($showServices) && $showServices)
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        const sectionServices = document.getElementById('section-services');
                        if (sectionServices) {
                            sectionServices.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 100);
                });
            @endif

            // Ouvrir automatiquement le modal s'il y a des erreurs
            @if($errors->any() && old('nom'))
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        openServiceModal();
                        // Restaurer les valeurs
                        @if(old('type_service_id'))
                            document.getElementById('type_service_id').value = '{{ old('type_service_id') }}';
                            document.getElementById('modal-title').textContent = 'Modifier le service';
                        @endif
                    }, 300);
                });
            @endif

            // Calendrier interactif pour le gérant avec tous les détails
            document.addEventListener('DOMContentLoaded', function() {
                const calendarEl = document.getElementById('calendar-gerant');
                const reservationsUrl = '{{ route("agenda.reservations", $entreprise->slug) }}';
                
                // Récupérer les horaires d'ouverture
                const horaires = @json($horaires);
                const joursFermes = [];
                const joursOuverts = {};
                const joursExceptionnels = {};
                
                horaires.forEach(horaire => {
                    if (horaire.est_exceptionnel && horaire.date_exception) {
                        joursExceptionnels[horaire.date_exception] = {
                            ouverture: horaire.heure_ouverture,
                            fermeture: horaire.heure_fermeture,
                            est_ferme: !horaire.heure_ouverture || !horaire.heure_fermeture
                        };
                    } else if (!horaire.est_exceptionnel) {
                        if (!horaire.heure_ouverture || !horaire.heure_fermeture) {
                            joursFermes.push(horaire.jour_semaine);
                        } else {
                            joursOuverts[horaire.jour_semaine] = {
                                ouverture: horaire.heure_ouverture,
                                fermeture: horaire.heure_fermeture
                            };
                        }
                    }
                });

                // Trouver l'heure d'ouverture minimale
                let slotMinTime = '08:00:00';
                const heuresOuverture = Object.values(joursOuverts).map(h => h.ouverture).filter(Boolean);
                if (heuresOuverture.length > 0) {
                    const heures = heuresOuverture.map(h => {
                        const [hours, minutes] = h.split(':');
                        return parseInt(hours) * 60 + parseInt(minutes);
                    });
                    const minMinutes = Math.min(...heures);
                    const hours = Math.floor(minMinutes / 60);
                    const mins = minMinutes % 60;
                    slotMinTime = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:00`;
                }
                
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    locale: 'fr',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: 'Aujourd\'hui',
                        month: 'Mois',
                        week: 'Semaine',
                        day: 'Jour'
                    },
                    firstDay: 1,
                    slotMinTime: slotMinTime,
                    slotMaxTime: '22:00:00',
                    slotDuration: '00:30:00',
                    allDaySlot: false,
                    height: 'auto',
                    events: reservationsUrl,
                    eventClick: function(info) {
                        const props = info.event.extendedProps;
                        const statutLabels = {
                            'en_attente': 'En attente',
                            'confirmee': 'Confirmée',
                            'annulee': 'Annulée',
                            'terminee': 'Terminée'
                        };
                        const statut = statutLabels[props.statut] || props.statut;
                        const estPaye = props.est_paye ? 'Oui ✓' : 'Non ✗';
                        
                        let message = `📋 Réservation\n\n`;
                        message += `Service: ${props.type_service}\n`;
                        message += `Client: ${props.client}\n`;
                        message += `Email: ${props.client_email}\n`;
                        if (props.telephone) {
                            message += `Téléphone: ${props.telephone}\n`;
                        }
                        message += `Statut: ${statut}\n`;
                        message += `Prix: ${props.prix} €\n`;
                        message += `Durée: ${props.duree} min\n`;
                        message += `Payé: ${estPaye}\n`;
                        if (props.lieu) {
                            message += `Lieu: ${props.lieu}\n`;
                        }
                        if (props.notes) {
                            message += `\nNotes: ${props.notes}`;
                        }
                        
                        alert(message);
                    },
                });

                calendar.render();
            });
        </script>
    </body>
</html>

