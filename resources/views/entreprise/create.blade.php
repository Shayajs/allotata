<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Créer une entreprise - Allo Tata</title>
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
                    <a href="{{ route('home') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
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

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Créer votre entreprise
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Remplissez les informations ci-dessous pour créer votre entreprise sur Allo Tata.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <form action="{{ route('entreprise.store') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
                @csrf

                <!-- Section Identité -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                        Identité de l'entreprise
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="nom" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Nom de l'entreprise <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="nom" 
                                name="nom" 
                                value="{{ old('nom') }}"
                                required
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="Ex: Coiffure Africaine Sarah"
                            >
                            @error('nom')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type_activite" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Type d'activité <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="type_activite" 
                                name="type_activite" 
                                required
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="">Sélectionnez un type d'activité</option>
                                <optgroup label="Beauté & Bien-être">
                                    <option value="Coiffeuse" {{ old('type_activite') == 'Coiffeuse' ? 'selected' : '' }}>Coiffure / Tressage</option>
                                    <option value="Esthéticienne" {{ old('type_activite') == 'Esthéticienne' ? 'selected' : '' }}>Soins esthétiques</option>
                                    <option value="Massage" {{ old('type_activite') == 'Massage' ? 'selected' : '' }}>Massage / Relaxation</option>
                                    <option value="Onglerie" {{ old('type_activite') == 'Onglerie' ? 'selected' : '' }}>Onglerie / Manucure</option>
                                    <option value="Maquillage" {{ old('type_activite') == 'Maquillage' ? 'selected' : '' }}>Maquillage professionnel</option>
                                    <option value="Barbier" {{ old('type_activite') == 'Barbier' ? 'selected' : '' }}>Barbier</option>
                                </optgroup>
                                <optgroup label="Restauration & Alimentation">
                                    <option value="Restauration" {{ old('type_activite') == 'Restauration' ? 'selected' : '' }}>Restauration</option>
                                    <option value="Cuisinière" {{ old('type_activite') == 'Cuisinière' ? 'selected' : '' }}>Traiteur / Cuisine à domicile</option>
                                    <option value="Pâtisserie" {{ old('type_activite') == 'Pâtisserie' ? 'selected' : '' }}>Pâtisserie / Boulangerie</option>
                                    <option value="Catering" {{ old('type_activite') == 'Catering' ? 'selected' : '' }}>Catering / Événements</option>
                                </optgroup>
                                <optgroup label="Photo & Vidéo">
                                    <option value="Photographie" {{ old('type_activite') == 'Photographie' ? 'selected' : '' }}>Photographie</option>
                                    <option value="Vidéographie" {{ old('type_activite') == 'Vidéographie' ? 'selected' : '' }}>Vidéographie</option>
                                    <option value="Photographe_Mariage" {{ old('type_activite') == 'Photographe_Mariage' ? 'selected' : '' }}>Photographe de mariage</option>
                                    <option value="Studio_Photo" {{ old('type_activite') == 'Studio_Photo' ? 'selected' : '' }}>Studio photo</option>
                                </optgroup>
                                <optgroup label="Éducation & Formation">
                                    <option value="Cours_Particuliers" {{ old('type_activite') == 'Cours_Particuliers' ? 'selected' : '' }}>Cours particuliers</option>
                                    <option value="Formation" {{ old('type_activite') == 'Formation' ? 'selected' : '' }}>Formation professionnelle</option>
                                    <option value="Soutien_Scolaire" {{ old('type_activite') == 'Soutien_Scolaire' ? 'selected' : '' }}>Soutien scolaire</option>
                                    <option value="Langues" {{ old('type_activite') == 'Langues' ? 'selected' : '' }}>Cours de langues</option>
                                </optgroup>
                                <optgroup label="Services à domicile">
                                    <option value="Ménage" {{ old('type_activite') == 'Ménage' ? 'selected' : '' }}>Ménage / Aide à domicile</option>
                                    <option value="Repassage" {{ old('type_activite') == 'Repassage' ? 'selected' : '' }}>Repassage</option>
                                    <option value="Garde_Enfants" {{ old('type_activite') == 'Garde_Enfants' ? 'selected' : '' }}>Garde d'enfants / Baby-sitting</option>
                                    <option value="Assistant_Virtuel" {{ old('type_activite') == 'Assistant_Virtuel' ? 'selected' : '' }}>Assistant(e) virtuel(le)</option>
                                </optgroup>
                                <optgroup label="Bricolage & Rénovation">
                                    <option value="Peinture" {{ old('type_activite') == 'Peinture' ? 'selected' : '' }}>Peinture / Rénovation</option>
                                    <option value="Plomberie" {{ old('type_activite') == 'Plomberie' ? 'selected' : '' }}>Plomberie</option>
                                    <option value="Électricité" {{ old('type_activite') == 'Électricité' ? 'selected' : '' }}>Électricité</option>
                                    <option value="Menuiserie" {{ old('type_activite') == 'Menuiserie' ? 'selected' : '' }}>Menuiserie</option>
                                </optgroup>
                                <optgroup label="Événements">
                                    <option value="Organisation_Événements" {{ old('type_activite') == 'Organisation_Événements' ? 'selected' : '' }}>Organisation d'événements</option>
                                    <option value="Animation" {{ old('type_activite') == 'Animation' ? 'selected' : '' }}>Animation / DJ</option>
                                    <option value="Décoration" {{ old('type_activite') == 'Décoration' ? 'selected' : '' }}>Décoration événementielle</option>
                                </optgroup>
                                <optgroup label="Santé & Sport">
                                    <option value="Coach_Sportif" {{ old('type_activite') == 'Coach_Sportif' ? 'selected' : '' }}>Coach sportif / Fitness</option>
                                    <option value="Yoga" {{ old('type_activite') == 'Yoga' ? 'selected' : '' }}>Yoga / Pilates</option>
                                    <option value="Nutritionniste" {{ old('type_activite') == 'Nutritionniste' ? 'selected' : '' }}>Nutritionniste / Diététicien</option>
                                </optgroup>
                                <optgroup label="Mode & Création">
                                    <option value="Couture" {{ old('type_activite') == 'Couture' ? 'selected' : '' }}>Couture / Retouches</option>
                                    <option value="Styliste" {{ old('type_activite') == 'Styliste' ? 'selected' : '' }}>Styliste</option>
                                    <option value="Accessoires" {{ old('type_activite') == 'Accessoires' ? 'selected' : '' }}>Création d'accessoires</option>
                                </optgroup>
                                <optgroup label="Autres">
                                    <option value="Autre" {{ old('type_activite') == 'Autre' ? 'selected' : '' }}>Autre</option>
                                </optgroup>
                            </select>
                            @error('type_activite')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Description
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="4"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="Décrivez vos services, votre spécialité..."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mots_cles" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Mots-clés <span class="text-orange-500">*</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-normal">(séparés par des virgules)</span>
                            </label>
                            <input 
                                type="text" 
                                id="mots_cles" 
                                name="mots_cles" 
                                value="{{ old('mots_cles') }}"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="Ex: tressage africain, coiffure, cheveux crépus, tresses, nattes, braids"
                            >
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Ajoutez des mots-clés pertinents pour améliorer la visibilité de votre entreprise dans les recherches.
                            </p>
                            @error('mots_cles')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="logo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Logo / Image de l'entreprise
                            </label>
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <input 
                                        type="file" 
                                        id="logo" 
                                        name="logo" 
                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400 hover:file:bg-green-100 dark:hover:file:bg-green-900/30"
                                    >
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Formats acceptés : JPEG, PNG, JPG, GIF, WEBP (max 2MB)
                                    </p>
                                </div>
                                <div id="logo-preview" class="hidden w-24 h-24 rounded-lg overflow-hidden border-2 border-slate-300 dark:border-slate-600">
                                    <img id="logo-preview-img" src="" alt="Aperçu du logo" class="w-full h-full object-cover">
                                </div>
                            </div>
                            @error('logo')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section Contact -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                        Informations de contact
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                required
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="contact@entreprise.com"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Téléphone
                            </label>
                            <input 
                                type="tel" 
                                id="telephone" 
                                name="telephone" 
                                value="{{ old('telephone') }}"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="06 12 34 56 78"
                            >
                            @error('telephone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section Localisation -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                        Localisation
                    </h2>
                    
                    <!-- Recherche d'adresse avec autocomplete -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            🔍 Rechercher une adresse
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="address-search"
                                placeholder="Commencez à taper votre adresse..."
                                autocomplete="off"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                            <div id="address-results" class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg shadow-xl z-50 max-h-64 overflow-y-auto"></div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Recherchez votre adresse pour remplir automatiquement les champs ci-dessous
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Adresse (rue et numéro)
                            </label>
                            <input 
                                type="text" 
                                name="adresse_rue" 
                                id="adresse_rue"
                                value="{{ old('adresse_rue') }}"
                                placeholder="123 rue de la Paix"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Code postal
                            </label>
                            <input 
                                type="text" 
                                name="code_postal" 
                                id="code_postal"
                                value="{{ old('code_postal') }}"
                                placeholder="75001"
                                maxlength="5"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Ville <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="ville" 
                                id="ville"
                                value="{{ old('ville') }}"
                                required
                                placeholder="Paris"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                            @error('ville')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Rayon de déplacement (km)
                            </label>
                            <input 
                                type="number" 
                                name="rayon_deplacement" 
                                id="rayon_deplacement"
                                value="{{ old('rayon_deplacement', 0) }}"
                                min="0"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="0 = fixe, >0 = mobile"
                            >
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Mettez 0 si vous êtes fixe, ou le nombre de km si vous vous déplacez
                            </p>
                            @error('rayon_deplacement')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Toggle affichage adresse complète -->
                    <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition mb-4">
                        <input 
                            type="checkbox" 
                            name="afficher_adresse_complete" 
                            value="1"
                            {{ old('afficher_adresse_complete') ? 'checked' : '' }}
                            class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                        >
                        <div>
                            <span class="text-sm font-medium text-slate-900 dark:text-white">
                                📍 Afficher l'adresse complète publiquement
                            </span>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Si activé, votre adresse complète (rue, numéro) sera visible. Sinon, seule la ville sera affichée.
                            </p>
                        </div>
                    </label>

                    <!-- Champs cachés pour les coordonnées GPS -->
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const addressAutocomplete = new AddressAutocomplete({
                            onSelect: function(data) {
                                // Remplir les champs
                                document.getElementById('adresse_rue').value = (data.housenumber || '') + ' ' + (data.street || data.name || '');
                                document.getElementById('code_postal').value = data.postcode || '';
                                document.getElementById('ville').value = data.city || '';
                                document.getElementById('latitude').value = data.latitude || '';
                                document.getElementById('longitude').value = data.longitude || '';
                                
                                // Vider le champ de recherche
                                document.getElementById('address-search').value = data.label || '';
                            }
                        });

                        addressAutocomplete.init('address-search', 'address-results', 'address');
                    });
                </script>

                <!-- Section Informations légales -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                        Informations légales (optionnel)
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="siren" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Numéro SIREN (9 chiffres)
                            </label>
                            <input 
                                type="text" 
                                id="siren" 
                                name="siren" 
                                value="{{ old('siren') }}"
                                maxlength="9"
                                pattern="[0-9]{9}"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="123456789"
                            >
                            @error('siren')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status_juridique" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Statut juridique
                            </label>
                            <select 
                                id="status_juridique" 
                                name="status_juridique" 
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="en_cours" {{ old('status_juridique', 'en_cours') == 'en_cours' ? 'selected' : '' }}>En cours de création</option>
                                <option value="auto_entrepreneur" {{ old('status_juridique') == 'auto_entrepreneur' ? 'selected' : '' }}>Auto-entrepreneur</option>
                                <option value="sarl" {{ old('status_juridique') == 'sarl' ? 'selected' : '' }}>SARL</option>
                                <option value="eurl" {{ old('status_juridique') == 'eurl' ? 'selected' : '' }}>EURL</option>
                                <option value="sas" {{ old('status_juridique') == 'sas' ? 'selected' : '' }}>SAS</option>
                            </select>
                            @error('status_juridique')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                    >
                        Créer mon entreprise
                    </button>
                    <a 
                        href="{{ route('dashboard') }}" 
                        class="px-6 py-3 text-center border-2 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg hover:border-slate-400 dark:hover:border-slate-500 transition-all"
                    >
                        Annuler
                    </a>
                </div>
            </form>
        </div>

        <script>
            // Aperçu du logo en temps réel
            document.getElementById('logo').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('logo-preview');
                        const previewImg = document.getElementById('logo-preview-img');
                        previewImg.src = e.target.result;
                        preview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('logo-preview').classList.add('hidden');
                }
            });
        </script>
    </body>
</html>

