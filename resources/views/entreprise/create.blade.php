<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Créer une entreprise - Allo Tata</title>
        @include('partials.favicon')
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
                    @php
                        use App\Helpers\SiteHelper;
                        $logoUrl = SiteHelper::getLogo('transparent');
                        $siteName = SiteHelper::getSiteName();
                    @endphp
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto hidden sm:block">
                        @endif
                        <span class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">{{ $siteName }}</span>
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
            {{-- Wrapper du formulaire (sera animé en sortie) --}}
            <div id="create-form-wrapper" class="transition-all duration-500 ease-in-out">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Créer votre entreprise
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Remplissez les informations ci-dessous pour créer votre entreprise sur Allo Tata.
                </p>
            </div>

            {{-- Erreurs AJAX (conteneur dynamique) --}}
            <div id="ajax-errors" class="hidden mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"></div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <form id="create-form" action="{{ route('entreprise.store') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
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
                            <x-file-upload 
                                name="logo" 
                                id="logo"
                                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" 
                                label="Logo / Image de l'entreprise"
                                maxSize="2 Mo"
                            />
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
                    <x-entreprise.localisation-form />
                </div>

                <!-- Section Informations légales -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                        Informations légales (optionnel)
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="siren" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Numéro <x-tooltip term="SIREN" position="top">Identifiant unique à 9 chiffres attribué par l'INSEE lors de la création de votre entreprise. Vous le trouverez sur votre extrait Kbis ou votre avis de situation.</x-tooltip> (9 chiffres)
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
                                <x-tooltip term="Statut juridique" position="top">La forme légale de votre entreprise (auto-entrepreneur, SARL, SAS, etc.). Elle définit vos obligations fiscales et sociales.</x-tooltip>
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
                        class="ui-btn-simple flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
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
            </div>{{-- /create-form-wrapper --}}

            {{-- ============================================================ --}}
            {{-- Panneau upsell abonnement (caché, apparaît après création)   --}}
            {{-- ============================================================ --}}
            @php
                $defaultPrice = \App\Models\Tarif::displayForUser(Auth::user(), 'default');
            @endphp
            <div id="upsell-panel" class="hidden opacity-0 translate-y-4 transition-all duration-500 ease-in-out">
                {{-- Succès --}}
                <div class="mb-8 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 mb-5">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                        Entreprise cr&eacute;&eacute;e avec succ&egrave;s !
                    </h1>
                    <p id="upsell-entreprise-name" class="text-lg text-slate-600 dark:text-slate-400"></p>
                </div>

                {{-- Carte abonnement --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-5">
                        <h2 class="text-xl font-bold text-white">Activez votre abonnement Premium</h2>
                        <p class="text-green-100 text-sm mt-1">Pour que vos entreprises soient visibles publiquement</p>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-baseline justify-center gap-2 mb-6">
                            @if($defaultPrice['amount'] > 0)
                                <span class="text-5xl font-bold text-green-600 dark:text-green-400">{{ $defaultPrice['formatted'] }}</span>
                            @else
                                <span class="text-5xl font-bold text-green-600 dark:text-green-400">&ndash;</span>
                            @endif
                            <span class="text-xl text-slate-500 dark:text-slate-400">/mois</span>
                        </div>

                        <ul class="space-y-3 mb-8 max-w-md mx-auto">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span class="text-slate-700 dark:text-slate-300">Vos entreprises visibles sur la plateforme</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span class="text-slate-700 dark:text-slate-300">R&eacute;servations en ligne pour vos clients</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span class="text-slate-700 dark:text-slate-300">Gestion compl&egrave;te de votre activit&eacute;</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span class="text-slate-700 dark:text-slate-300">Sans engagement &ndash; annulation &agrave; tout moment</span>
                            </li>
                        </ul>

                        {{-- Bouton essai gratuit --}}
                        @if(Auth::user()->peutDemarrerEssai('premium') && !Auth::user()->aAbonnementActif())
                        <form action="{{ route('essai-gratuit.utilisateur') }}" method="POST" class="mb-4">
                            @csrf
                            <input type="hidden" name="source" value="upsell_creation">
                            <button type="submit" class="ui-btn-simple w-full min-h-[52px] px-6 py-4 bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white text-lg font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.01] touch-manipulation">
                                Essayer gratuitement pendant 7 jours
                            </button>
                        </form>
                        <p class="text-center text-xs text-slate-500 dark:text-slate-400 mb-4">Sans engagement • Sans carte bancaire</p>
                        <div class="relative flex items-center justify-center py-2 mb-4">
                            <span class="absolute inset-x-0 h-px bg-slate-200 dark:bg-slate-700"></span>
                            <span class="relative px-4 bg-white dark:bg-slate-800 text-xs text-slate-400 dark:text-slate-500">ou</span>
                        </div>
                        @elseif(!Auth::user()->aAbonnementActif())
                        <p class="text-center text-sm text-slate-600 dark:text-slate-400 mb-4">
                            Vous avez déjà utilisé votre essai gratuit. Un nouvel essai n'est plus possible.
                        </p>
                        @endif

                        {{-- Bouton souscrire --}}
                        <form action="{{ route('subscription.checkout') }}" method="POST" class="mb-4 js-play-billing-form" data-play-product="{{ config('play.products.premium.id') }}">
                            @csrf
                            <button type="submit" class="ui-btn-simple w-full min-h-[52px] px-6 py-4 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white text-lg font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.01] touch-manipulation">
                                @if($defaultPrice['amount'] > 0)
                                    Souscrire maintenant ({{ $defaultPrice['formatted'] }}/mois)
                                @else
                                    Souscrire maintenant
                                @endif
                            </button>
                        </form>

                        {{-- Bouton "Plus tard" --}}
                        <a id="upsell-skip-btn" href="{{ route('dashboard') }}" class="block w-full text-center min-h-[44px] px-6 py-3 border-2 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 font-semibold rounded-xl hover:border-slate-400 dark:hover:border-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition-all touch-manipulation">
                            Plus tard, configurer mon entreprise
                        </a>
                    </div>
                </div>

                {{-- Avertissement --}}
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-center">
                    <p class="text-sm text-amber-800 dark:text-amber-400">
                        Sans abonnement actif, vos entreprises ne seront pas visibles publiquement.
                        Vous pouvez souscrire &agrave; tout moment depuis
                        <a href="{{ route('settings.index', ['tab' => 'subscription']) }}" class="underline font-medium hover:text-amber-900 dark:hover:text-amber-300">la section abonnement</a>.
                    </p>
                </div>
            </div>{{-- /upsell-panel --}}
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

            // ============================================================
            // AJAX submit + transition animée vers upsell abonnement
            // ============================================================
            (function() {
                const form = document.getElementById('create-form');
                const formWrapper = document.getElementById('create-form-wrapper');
                const upsellPanel = document.getElementById('upsell-panel');
                const ajaxErrors = document.getElementById('ajax-errors');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                if (!form || !formWrapper || !upsellPanel) return;

                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    // Trouver le bouton submit
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn ? submitBtn.textContent.trim() : '';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<svg class="animate-spin inline-block w-5 h-5 mr-2 -mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Cr\u00e9ation en cours\u2026';
                    }

                    // Nettoyer les erreurs précédentes
                    clearErrors();

                    try {
                        const formData = new FormData(form);
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: formData,
                        });

                        const data = await res.json().catch(() => null);

                        if (res.status === 422 && data && data.errors) {
                            // Erreurs de validation : les afficher
                            showValidationErrors(data.errors);
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = originalText;
                            }
                            return;
                        }

                        if (!res.ok || !data || !data.success) {
                            const msg = data?.message || data?.error || 'Une erreur est survenue. Veuillez r\u00e9essayer.';
                            showGlobalError(msg);
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = originalText;
                            }
                            return;
                        }

                        // Succès !
                        if (!data.show_subscription) {
                            // Déjà abonné → redirect direct
                            window.location.href = data.redirect || '{{ route("dashboard") }}';
                            return;
                        }

                        // Mettre à jour le panneau upsell avec les données
                        const nameEl = document.getElementById('upsell-entreprise-name');
                        if (nameEl && data.entreprise_nom) {
                            nameEl.textContent = '\u00ab\u00a0' + data.entreprise_nom + '\u00a0\u00bb est pr\u00eate\u00a0!';
                        }

                        // Mettre à jour le lien "Plus tard" vers le dashboard de l'entreprise
                        const skipBtn = document.getElementById('upsell-skip-btn');
                        if (skipBtn && data.redirect) {
                            skipBtn.href = data.redirect;
                        }

                        // Animation : sortie du formulaire
                        formWrapper.classList.add('opacity-0', '-translate-y-4');

                        setTimeout(function() {
                            formWrapper.classList.add('hidden');

                            // Animation : entrée du panneau upsell
                            upsellPanel.classList.remove('hidden');
                            // Force reflow pour que la transition se déclenche
                            void upsellPanel.offsetHeight;
                            upsellPanel.classList.remove('opacity-0', 'translate-y-4');
                            upsellPanel.classList.add('opacity-100', 'translate-y-0');

                            // Scroll vers le haut
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }, 500);

                    } catch (err) {
                        showGlobalError('Erreur r\u00e9seau. V\u00e9rifiez votre connexion et r\u00e9essayez.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    }
                });

                /**
                 * Afficher les erreurs de validation sous chaque champ + en bloc global
                 */
                function showValidationErrors(errors) {
                    // Supprimer les anciennes erreurs dynamiques
                    form.querySelectorAll('.js-field-error').forEach(el => el.remove());

                    const messages = [];

                    for (const [field, fieldErrors] of Object.entries(errors)) {
                        const errorTexts = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors];
                        messages.push(...errorTexts);

                        // Trouver le champ par name
                        const input = form.querySelector('[name="' + field + '"]');
                        if (input) {
                            // Ajouter le style erreur
                            input.classList.add('border-red-500', 'dark:border-red-500');

                            // Ajouter le message sous le champ
                            const errorP = document.createElement('p');
                            errorP.className = 'js-field-error mt-1 text-sm text-red-600 dark:text-red-400';
                            errorP.textContent = errorTexts[0];
                            input.closest('div')?.appendChild(errorP);
                        }
                    }

                    // Afficher le bloc global
                    if (messages.length > 0) {
                        showGlobalError(messages.join('<br>'));
                        // Scroller vers le haut pour voir les erreurs
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }

                /**
                 * Afficher une erreur globale
                 */
                function showGlobalError(html) {
                    ajaxErrors.innerHTML = '<p class="text-red-800 dark:text-red-400 font-medium">' + html + '</p>';
                    ajaxErrors.classList.remove('hidden');
                }

                /**
                 * Supprimer toutes les erreurs
                 */
                function clearErrors() {
                    ajaxErrors.innerHTML = '';
                    ajaxErrors.classList.add('hidden');
                    form.querySelectorAll('.js-field-error').forEach(el => el.remove());
                    form.querySelectorAll('.border-red-500').forEach(el => {
                        el.classList.remove('border-red-500', 'dark:border-red-500');
                    });
                }
            })();
        </script>
    </body>
</html>

