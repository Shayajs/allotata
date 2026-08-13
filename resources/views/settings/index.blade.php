<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Paramètres - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="pwa-desktop-header bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-4">
                        <!-- Menu Burger pour mobile web -->
                        @include('components.mobile-nav', ['navType' => 'dashboard'])
                        
                        <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                    </div>
                    <!-- Liens desktop (masqués sur mobile) -->
                    <div class="hidden lg:flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
                        </a>
                        <a href="{{ route('checkout.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Espace Paiement
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

        <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Paramètres
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Gérez vos préférences et vos informations personnelles.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            @php $activeTab = request('tab', 'account'); @endphp

            {{-- Header PWA (visible uniquement en PWA mobile) --}}
            <x-nav.pwa-header :title="'Paramètres'" :show-back="true" :back-url="route('dashboard')" />

            <!-- Layout avec Sidebar -->
            <div class="flex gap-6 pwa-flex-layout">
                {{-- Sidebar (composant centralisé) --}}
                <x-nav.sidebar :items="$navItems" :active-tab="$activeTab" context="settings" />

                <!-- Main Content Area -->
                <main class="flex-1 min-w-0">
                    {{-- Barre onglets mobile (composant centralisé) --}}
                    <x-nav.mobile-tabs :items="$navItems" :active-tab="$activeTab" />
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                    <!-- Onglet Compte -->
                    <div id="tab-account" class="tab-content">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Informations du compte</h2>
                        
                        <p class="text-sm italic text-slate-500 dark:text-slate-400 mb-6">
                            Toutes les informations enregistrées sont visibles uniquement par vous.
                        </p>
                        
                        <form action="{{ route('settings.account.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            
                            <!-- Photo de profil -->
                            <div>
                                <div class="flex items-start gap-4">
                                    <x-avatar :user="$user" size="2xl" />
                                    <div class="flex-1">
                                        <x-file-upload 
                                            name="photo_profil" 
                                            accept="image/jpeg,image/png,image/gif,image/webp" 
                                            label="Photo de profil"
                                            maxSize="2 Mo"
                                            :currentImage="$user->photo_profil ? asset('media/' . $user->photo_profil) : null"
                                        />
                                    </div>
                                </div>
                                @error('photo_profil')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Prénom *
                                    </label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        value="{{ old('name', $user->first_name) }}"
                                        required
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Nom de famille
                                    </label>
                                    <input 
                                        type="text" 
                                        name="surname" 
                                        value="{{ old('surname', $user->last_name) }}"
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('surname')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Email *
                                    </label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        value="{{ old('email', $user->email) }}"
                                        required
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Informations personnelles (optionnelles) -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations personnelles (optionnelles)</h3>
                                
                                <div class="space-y-6">
                                    <!-- Téléphone -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Téléphone
                                        </label>
                                        <input 
                                            type="tel" 
                                            name="telephone" 
                                            value="{{ old('telephone', $user->telephone) }}"
                                            placeholder="Ex: 06 12 34 56 78"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        @error('telephone')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Bio -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            À propos de moi
                                        </label>
                                        <textarea 
                                            name="bio" 
                                            rows="4"
                                            placeholder="Parlez-nous un peu de vous..."
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >{{ old('bio', $user->bio) }}</textarea>
                                        @error('bio')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Maximum 1000 caractères
                                        </p>
                                    </div>

                                    <!-- Date de naissance -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Date de naissance
                                        </label>
                                        <input 
                                            type="date" 
                                            name="date_naissance" 
                                            value="{{ old('date_naissance', $user->date_naissance ? $user->date_naissance->format('Y-m-d') : '') }}"
                                            max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        @error('date_naissance')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Adresse -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Adresse
                                        </label>
                                        <input 
                                            type="text" 
                                            name="adresse" 
                                            value="{{ old('adresse', $user->adresse) }}"
                                            placeholder="Ex: 123 Rue de la République"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        @error('adresse')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Ville et Code postal -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Ville
                                            </label>
                                            <input 
                                                type="text" 
                                                name="ville" 
                                                value="{{ old('ville', $user->ville) }}"
                                                placeholder="Ex: Paris"
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            @error('ville')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Code postal
                                            </label>
                                            <input 
                                                type="text" 
                                                name="code_postal" 
                                                value="{{ old('code_postal', $user->code_postal) }}"
                                                placeholder="Ex: 75001"
                                                maxlength="10"
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            @error('code_postal')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Genre -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Genre
                                        </label>
                                        <select 
                                            name="genre" 
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                            <option value="non_precise" {{ old('genre', $user->genre) == 'non_precise' ? 'selected' : '' }}>Ne souhaite pas préciser</option>
                                            <option value="homme" {{ old('genre', $user->genre) == 'homme' ? 'selected' : '' }}>Homme</option>
                                            <option value="femme" {{ old('genre', $user->genre) == 'femme' ? 'selected' : '' }}>Femme</option>
                                        </select>
                                        @error('genre')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Langue préférée -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Langue préférée
                                        </label>
                                        <select 
                                            name="langue_preferee" 
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                            <option value="fr" {{ old('langue_preferee', $user->langue_preferee) == 'fr' ? 'selected' : '' }}>Français</option>
                                            <option value="en" {{ old('langue_preferee', $user->langue_preferee) == 'en' ? 'selected' : '' }}>English</option>
                                            <option value="es" {{ old('langue_preferee', $user->langue_preferee) == 'es' ? 'selected' : '' }}>Español</option>
                                            <option value="de" {{ old('langue_preferee', $user->langue_preferee) == 'de' ? 'selected' : '' }}>Deutsch</option>
                                            <option value="pt" {{ old('langue_preferee', $user->langue_preferee) == 'pt' ? 'selected' : '' }}>Português</option>
                                            <option value="ar" {{ old('langue_preferee', $user->langue_preferee) == 'ar' ? 'selected' : '' }}>العربية</option>
                                        </select>
                                        @error('langue_preferee')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Contact d'urgence -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Contact d'urgence</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                    Personne a contacter en cas de probleme lors d'une prestation.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Nom du contact
                                        </label>
                                        <input 
                                            type="text" 
                                            name="urgence_nom" 
                                            value="{{ old('urgence_nom', $user->urgence_nom) }}"
                                            placeholder="Nom et prenom"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        @error('urgence_nom')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Telephone du contact
                                        </label>
                                        <input 
                                            type="tel" 
                                            name="urgence_telephone" 
                                            value="{{ old('urgence_telephone', $user->urgence_telephone) }}"
                                            placeholder="06 12 34 56 78"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        @error('urgence_telephone')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Santé / Allergies -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Sante et allergies</h3>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Allergies ou informations medicales
                                    </label>
                                    <textarea 
                                        name="allergies_notes" 
                                        rows="3"
                                        placeholder="Ex: allergie au latex, produits capillaires specifiques..."
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >{{ old('allergies_notes', $user->allergies_notes) }}</textarea>
                                    @error('allergies_notes')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Notes pour les prestataires -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Notes pour les prestataires</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                    Ces informations seront visibles par vos prestataires lors de vos reservations.
                                </p>
                                <div>
                                    <textarea 
                                        name="notes_prestataires" 
                                        rows="3"
                                        placeholder="Ex: code d'entree 4521, 3eme etage sans ascenseur, sonnez 2 fois..."
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >{{ old('notes_prestataires', $user->notes_prestataires) }}</textarea>
                                    @error('notes_prestataires')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Préférences horaires -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Preferences horaires</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                    Indiquez vos creneaux de disponibilite preferes pour les prestations.
                                </p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @php
                                        $horaires = [
                                            ['name' => 'pref_horaire_matin', 'label' => 'Matin', 'desc' => '8h - 12h'],
                                            ['name' => 'pref_horaire_apres_midi', 'label' => 'Apres-midi', 'desc' => '12h - 18h'],
                                            ['name' => 'pref_horaire_soir', 'label' => 'Soir', 'desc' => '18h - 21h'],
                                            ['name' => 'pref_horaire_weekend', 'label' => 'Weekend', 'desc' => 'Sam & Dim'],
                                        ];
                                    @endphp
                                    @foreach($horaires as $h)
                                        <label class="flex flex-col items-center p-4 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 transition-colors has-[:checked]:border-green-500 has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-900/20">
                                            <input type="checkbox" name="{{ $h['name'] }}" value="1"
                                                {{ old($h['name'], $user->{$h['name']}) ? 'checked' : '' }}
                                                class="sr-only peer">
                                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $h['label'] }}</span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $h['desc'] }}</span>
                                            <div class="mt-2 w-5 h-5 rounded border-2 border-slate-300 dark:border-slate-500 flex items-center justify-center peer-checked:bg-green-500 peer-checked:border-green-500">
                                                <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Préférences prestataire -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Preferences prestataire</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Genre du prestataire prefere
                                        </label>
                                        <select 
                                            name="pref_prestataire_genre" 
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                            <option value="indifferent" {{ old('pref_prestataire_genre', $user->pref_prestataire_genre) == 'indifferent' ? 'selected' : '' }}>Indifferent</option>
                                            <option value="homme" {{ old('pref_prestataire_genre', $user->pref_prestataire_genre) == 'homme' ? 'selected' : '' }}>Homme</option>
                                            <option value="femme" {{ old('pref_prestataire_genre', $user->pref_prestataire_genre) == 'femme' ? 'selected' : '' }}>Femme</option>
                                        </select>
                                        @error('pref_prestataire_genre')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Experience minimum (annees)
                                        </label>
                                        <input 
                                            type="number" 
                                            name="pref_prestataire_experience_min" 
                                            value="{{ old('pref_prestataire_experience_min', $user->pref_prestataire_experience_min) }}"
                                            min="0" max="50"
                                            placeholder="Pas de preference"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        @error('pref_prestataire_experience_min')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Code de parrainage (lecture seule) -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Parrainage</h3>
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Votre code de parrainage
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input 
                                                type="text" 
                                                value="{{ $user->code_parrain ?? 'Non genere' }}"
                                                readonly
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white font-mono tracking-wider"
                                            >
                                            @if($user->code_parrain)
                                                <button type="button" onclick="navigator.clipboard.writeText('{{ $user->code_parrain }}').then(() => this.textContent = 'Copie !')" 
                                                    class="px-4 py-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium whitespace-nowrap">
                                                    Copier
                                                </button>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Partagez ce code avec vos amis pour les inviter sur la plateforme.
                                        </p>
                                    </div>
                                </div>
                                @if($user->filleuls && $user->filleuls->count() > 0)
                                    <div class="mt-4">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            {{ $user->filleuls->count() }} filleul{{ $user->filleuls->count() > 1 ? 's' : '' }} inscrit{{ $user->filleuls->count() > 1 ? 's' : '' }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>

                        <!-- Section Enfants (CRUD séparé) -->
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-6 mt-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Mes enfants</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Ajoutez vos enfants pour personnaliser les prestations.</p>
                                </div>
                                <button type="button" id="btn-add-enfant"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Ajouter
                                </button>
                            </div>

                            <!-- Formulaire ajout enfant (masqué par défaut) -->
                            <div id="form-add-enfant" class="hidden mb-4 p-4 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-lg">
                                <form action="{{ route('settings.enfants.store') }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prenom *</label>
                                            <input type="text" name="prenom" required placeholder="Prenom de l'enfant"
                                                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date de naissance</label>
                                            <input type="date" name="date_naissance" max="{{ date('Y-m-d') }}"
                                                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                                            <input type="text" name="notes" placeholder="Besoins specifiques, allergies..."
                                                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="document.getElementById('form-add-enfant').classList.add('hidden')"
                                            class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                            Annuler
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                                            Ajouter l'enfant
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Liste des enfants -->
                            @if($user->enfants && $user->enfants->count() > 0)
                                <div class="space-y-3">
                                    @foreach($user->enfants as $enfant)
                                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg" id="enfant-row-{{ $enfant->id }}">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $enfant->prenom }}</p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                                            @if($enfant->age_formate)
                                                                {{ $enfant->age_formate }}
                                                                @if($enfant->date_naissance)
                                                                    <span class="mx-1">·</span>
                                                                    {{ $enfant->date_naissance->format('d/m/Y') }}
                                                                @endif
                                                            @else
                                                                Age non renseigne
                                                            @endif
                                                            @if($enfant->notes)
                                                                <span class="mx-1">·</span>
                                                                {{ Str::limit($enfant->notes, 50) }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <form action="{{ route('settings.enfants.destroy', $enfant->id) }}" method="POST" 
                                                onsubmit="return confirm('Supprimer {{ $enfant->prenom }} de la liste ?')" class="flex-shrink-0 ml-3">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="Supprimer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <p class="text-sm">Aucun enfant enregistre.</p>
                                    <p class="text-xs mt-1">Cliquez sur "Ajouter" pour enregistrer un enfant.</p>
                                </div>
                            @endif
                        </div>

                        <script>
                            document.getElementById('btn-add-enfant')?.addEventListener('click', function() {
                                document.getElementById('form-add-enfant').classList.toggle('hidden');
                            });
                        </script>
                    </div>

                    <!-- Onglet Entreprises -->
                    @if($user->est_gerant && $entreprises->count() > 0)
                        <div id="tab-entreprise" class="tab-content hidden">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Mes entreprises</h2>
                            
                            <div class="space-y-6">
                                @foreach($entreprises as $entreprise)
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6">
                                        <div class="flex items-start gap-4 mb-6">
                                            <div id="logo-preview-{{ $entreprise->id }}" class="{{ $entreprise->logo ? '' : 'hidden' }}">
                                                <img 
                                                    id="logo-img-{{ $entreprise->id }}"
                                                    src="{{ $entreprise->logo ? asset('media/' . $entreprise->logo) : '' }}" 
                                                    alt="Logo {{ $entreprise->nom }}"
                                                    class="w-20 h-20 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700"
                                                >
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $entreprise->nom }}</h3>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }}</p>
                                            </div>
                                        </div>

                                        <!-- Upload immédiat du logo et image de fond (en dehors du formulaire) -->
                                        <div class="mb-6 space-y-4 border-b border-slate-200 dark:border-slate-700 pb-6">
                                            <!-- Logo -->
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Logo / Image de l'entreprise
                                                </label>
                                                <div class="flex items-center gap-4">
                                                    <input 
                                                        type="file" 
                                                        id="logo-input-{{ $entreprise->id }}"
                                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                                        class="flex-1 px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                                                    >
                                                    <div id="logo-loading-{{ $entreprise->id }}" class="hidden">
                                                        <svg class="animate-spin h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </div>
                                                    @if($entreprise->logo)
                                                        <button 
                                                            type="button"
                                                            onclick="if(confirm('Supprimer le logo ?')) { document.getElementById('delete-logo-form-{{ $entreprise->id }}').submit(); }"
                                                            class="px-4 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition"
                                                        >
                                                            Supprimer
                                                        </button>
                                                        <form id="delete-logo-form-{{ $entreprise->id }}" action="{{ route('settings.entreprise.logo.delete', $entreprise->slug) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    Formats acceptés : JPEG, PNG, GIF, WebP (max 2MB). L'upload est automatique.
                                                </p>
                                            </div>

                                            <!-- Image de fond -->
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Image de fond (pour le profil public)
                                                </label>
                                                <div id="image-fond-preview-{{ $entreprise->id }}" class="{{ $entreprise->image_fond ? 'mb-3' : 'hidden' }}">
                                                    <img 
                                                        id="image-fond-img-{{ $entreprise->id }}"
                                                        src="{{ $entreprise->image_fond ? asset('media/' . $entreprise->image_fond) : '' }}" 
                                                        alt="Image de fond"
                                                        class="w-full h-48 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                                                    >
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <input 
                                                        type="file" 
                                                        id="image-fond-input-{{ $entreprise->id }}"
                                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                                        class="flex-1 px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                                                    >
                                                    <div id="image-fond-loading-{{ $entreprise->id }}" class="hidden">
                                                        <svg class="animate-spin h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </div>
                                                    @if($entreprise->image_fond)
                                                        <button 
                                                            type="button"
                                                            onclick="if(confirm('Supprimer l\'image de fond ?')) { document.getElementById('delete-image-fond-form-{{ $entreprise->id }}').submit(); }"
                                                            class="px-4 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition"
                                                        >
                                                            Supprimer
                                                        </button>
                                                        <form id="delete-image-fond-form-{{ $entreprise->id }}" action="{{ route('settings.entreprise.image-fond.delete', $entreprise->slug) }}" method="POST" style="display: none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    @endif
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                                    Cette image sera affichée en en-tête de votre page publique. Taille recommandée : 1920x600px (max 5MB). L'upload est automatique.
                                                </p>
                                            </div>
                                        </div>

                                        <form action="{{ route('settings.entreprise.update', $entreprise->slug) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                                            @csrf
                                            
                                            @if($errors->any())
                                                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                                    <div class="flex items-start gap-3">
                                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <div>
                                                            <p class="font-medium text-red-800 dark:text-red-300 mb-2">Erreurs de validation :</p>
                                                            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                                                                @foreach($errors->all() as $error)
                                                                    <li>{{ $error }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Nom de l'entreprise *
                                                    </label>
                                                    <input 
                                                        type="text" 
                                                        name="nom" 
                                                        value="{{ old('nom', $entreprise->nom) }}"
                                                        required
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Type d'activité *
                                                    </label>
                                                    <select 
                                                        name="type_activite" 
                                                        required
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                        <optgroup label="Beauté & Bien-être">
                                                            <option value="Coiffeuse" {{ $entreprise->type_activite == 'Coiffeuse' ? 'selected' : '' }}>Coiffure / Tressage</option>
                                                            <option value="Esthéticienne" {{ $entreprise->type_activite == 'Esthéticienne' ? 'selected' : '' }}>Soins esthétiques</option>
                                                            <option value="Massage" {{ $entreprise->type_activite == 'Massage' ? 'selected' : '' }}>Massage / Relaxation</option>
                                                            <option value="Onglerie" {{ $entreprise->type_activite == 'Onglerie' ? 'selected' : '' }}>Onglerie / Manucure</option>
                                                            <option value="Maquillage" {{ $entreprise->type_activite == 'Maquillage' ? 'selected' : '' }}>Maquillage professionnel</option>
                                                            <option value="Barbier" {{ $entreprise->type_activite == 'Barbier' ? 'selected' : '' }}>Barbier</option>
                                                        </optgroup>
                                                        <optgroup label="Restauration & Alimentation">
                                                            <option value="Restauration" {{ $entreprise->type_activite == 'Restauration' ? 'selected' : '' }}>Restauration</option>
                                                            <option value="Cuisinière" {{ $entreprise->type_activite == 'Cuisinière' ? 'selected' : '' }}>Traiteur / Cuisine à domicile</option>
                                                            <option value="Pâtisserie" {{ $entreprise->type_activite == 'Pâtisserie' ? 'selected' : '' }}>Pâtisserie / Boulangerie</option>
                                                            <option value="Catering" {{ $entreprise->type_activite == 'Catering' ? 'selected' : '' }}>Catering / Événements</option>
                                                        </optgroup>
                                                        <optgroup label="Photo & Vidéo">
                                                            <option value="Photographie" {{ $entreprise->type_activite == 'Photographie' ? 'selected' : '' }}>Photographie</option>
                                                            <option value="Vidéographie" {{ $entreprise->type_activite == 'Vidéographie' ? 'selected' : '' }}>Vidéographie</option>
                                                            <option value="Photographe_Mariage" {{ $entreprise->type_activite == 'Photographe_Mariage' ? 'selected' : '' }}>Photographe de mariage</option>
                                                            <option value="Studio_Photo" {{ $entreprise->type_activite == 'Studio_Photo' ? 'selected' : '' }}>Studio photo</option>
                                                        </optgroup>
                                                        <optgroup label="Éducation & Formation">
                                                            <option value="Cours_Particuliers" {{ $entreprise->type_activite == 'Cours_Particuliers' ? 'selected' : '' }}>Cours particuliers</option>
                                                            <option value="Formation" {{ $entreprise->type_activite == 'Formation' ? 'selected' : '' }}>Formation professionnelle</option>
                                                            <option value="Soutien_Scolaire" {{ $entreprise->type_activite == 'Soutien_Scolaire' ? 'selected' : '' }}>Soutien scolaire</option>
                                                            <option value="Langues" {{ $entreprise->type_activite == 'Langues' ? 'selected' : '' }}>Cours de langues</option>
                                                        </optgroup>
                                                        <optgroup label="Services à domicile">
                                                            <option value="Ménage" {{ $entreprise->type_activite == 'Ménage' ? 'selected' : '' }}>Ménage / Aide à domicile</option>
                                                            <option value="Repassage" {{ $entreprise->type_activite == 'Repassage' ? 'selected' : '' }}>Repassage</option>
                                                            <option value="Garde_Enfants" {{ $entreprise->type_activite == 'Garde_Enfants' ? 'selected' : '' }}>Garde d'enfants / Baby-sitting</option>
                                                            <option value="Assistant_Virtuel" {{ $entreprise->type_activite == 'Assistant_Virtuel' ? 'selected' : '' }}>Assistant(e) virtuel(le)</option>
                                                        </optgroup>
                                                        <optgroup label="Bricolage & Rénovation">
                                                            <option value="Peinture" {{ $entreprise->type_activite == 'Peinture' ? 'selected' : '' }}>Peinture / Rénovation</option>
                                                            <option value="Plomberie" {{ $entreprise->type_activite == 'Plomberie' ? 'selected' : '' }}>Plomberie</option>
                                                            <option value="Électricité" {{ $entreprise->type_activite == 'Électricité' ? 'selected' : '' }}>Électricité</option>
                                                            <option value="Menuiserie" {{ $entreprise->type_activite == 'Menuiserie' ? 'selected' : '' }}>Menuiserie</option>
                                                        </optgroup>
                                                        <optgroup label="Événements">
                                                            <option value="Organisation_Événements" {{ $entreprise->type_activite == 'Organisation_Événements' ? 'selected' : '' }}>Organisation d'événements</option>
                                                            <option value="Animation" {{ $entreprise->type_activite == 'Animation' ? 'selected' : '' }}>Animation / DJ</option>
                                                            <option value="Décoration" {{ $entreprise->type_activite == 'Décoration' ? 'selected' : '' }}>Décoration événementielle</option>
                                                        </optgroup>
                                                        <optgroup label="Santé & Sport">
                                                            <option value="Coach_Sportif" {{ $entreprise->type_activite == 'Coach_Sportif' ? 'selected' : '' }}>Coach sportif / Fitness</option>
                                                            <option value="Yoga" {{ $entreprise->type_activite == 'Yoga' ? 'selected' : '' }}>Yoga / Pilates</option>
                                                            <option value="Nutritionniste" {{ $entreprise->type_activite == 'Nutritionniste' ? 'selected' : '' }}>Nutritionniste / Diététicien</option>
                                                        </optgroup>
                                                        <optgroup label="Mode & Création">
                                                            <option value="Couture" {{ $entreprise->type_activite == 'Couture' ? 'selected' : '' }}>Couture / Retouches</option>
                                                            <option value="Styliste" {{ $entreprise->type_activite == 'Styliste' ? 'selected' : '' }}>Styliste</option>
                                                            <option value="Accessoires" {{ $entreprise->type_activite == 'Accessoires' ? 'selected' : '' }}>Création d'accessoires</option>
                                                        </optgroup>
                                                        <optgroup label="Autres">
                                                            <option value="Autre" {{ $entreprise->type_activite == 'Autre' ? 'selected' : '' }}>Autre</option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Email *
                                                    </label>
                                                    <input 
                                                        type="email" 
                                                        name="email" 
                                                        value="{{ old('email', $entreprise->email) }}"
                                                        required
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Téléphone
                                                    </label>
                                                    <input 
                                                        type="tel" 
                                                        name="telephone" 
                                                        value="{{ old('telephone', $entreprise->telephone) }}"
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Description
                                                </label>
                                                <textarea 
                                                    name="description" 
                                                    rows="4"
                                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                >{{ old('description', $entreprise->description) }}</textarea>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Mots-clés (séparés par des virgules)
                                                </label>
                                                <input 
                                                    type="text" 
                                                    name="mots_cles" 
                                                    value="{{ old('mots_cles', $entreprise->mots_cles) }}"
                                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                >
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Ville
                                                    </label>
                                                    <input 
                                                        type="text" 
                                                        name="ville" 
                                                        value="{{ old('ville', $entreprise->ville) }}"
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Rayon de déplacement (km)
                                                    </label>
                                                    <input 
                                                        type="number" 
                                                        name="rayon_deplacement" 
                                                        value="{{ old('rayon_deplacement', $entreprise->rayon_deplacement) }}"
                                                        min="0"
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>
                                            </div>

                                            <div class="flex justify-end mt-6">
                                                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                    Enregistrer les modifications
                                                </button>
                                            </div>
                                        </form>

                                        <!-- Galerie de réalisations (en dehors du formulaire principal) -->
                                        <div class="mt-6 border-t border-slate-200 dark:border-slate-700 pt-6">
                                            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                                                📸 Photos de réalisations
                                            </h4>
                                            
                                            @if($entreprise->realisationPhotos->count() > 0)
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                                    @foreach($entreprise->realisationPhotos as $photo)
                                                        <div class="relative group">
                                                            <img 
                                                                src="{{ asset('media/' . $photo->photo_path) }}" 
                                                                alt="{{ $photo->titre ?? 'Réalisation' }}"
                                                                class="w-full h-32 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                                                            >
                                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                                                <button 
                                                                    type="button"
                                                                    onclick="if(confirm('Supprimer cette photo ?')) { document.getElementById('delete-photo-form-{{ $photo->id }}').submit(); }"
                                                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition"
                                                                >
                                                                    Supprimer
                                                                </button>
                                                                <form id="delete-photo-form-{{ $photo->id }}" action="{{ route('settings.entreprise.photo.delete', [$entreprise->slug, $photo->id]) }}" method="POST" style="display: none;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                </form>
                                                            </div>
                                                            @if($photo->titre)
                                                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400 truncate">{{ $photo->titre }}</p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-700/50">
                                                <form action="{{ route('settings.entreprise.photo.add', $entreprise->slug) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Ajouter une photo
                                                            </label>
                                                            <input 
                                                                type="file" 
                                                                name="photo" 
                                                                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                                                required
                                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Titre (optionnel)
                                                            </label>
                                                            <input 
                                                                type="text" 
                                                                name="titre" 
                                                                placeholder="Ex: Tressage cheveux crépus"
                                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Description (optionnel)
                                                            </label>
                                                            <textarea 
                                                                name="description" 
                                                                rows="2"
                                                                placeholder="Description de la réalisation..."
                                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            ></textarea>
                                                        </div>
                                                        <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                            Ajouter la photo
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                            <div class="mt-6">
                                                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                                    <input 
                                                        type="checkbox" 
                                                        name="afficher_nom_gerant" 
                                                        value="1"
                                                        {{ old('afficher_nom_gerant', $entreprise->afficher_nom_gerant) ? 'checked' : '' }}
                                                        class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                                    >
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            Afficher mon nom avec l'entreprise
                                                        </span>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Si activé, votre nom sera visible sur la page publique de l'entreprise et dans les conversations.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- Prix négociables -->
                                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                                <label class="flex items-start gap-3 cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        name="prix_negociables" 
                                                        value="1"
                                                        {{ old('prix_negociables', $entreprise->prix_negociables) ? 'checked' : '' }}
                                                        class="mt-1 w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                                    >
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            💰 Prix négociables
                                                        </span>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Si activé, les clients pourront négocier les prix des rendez-vous proposés via la messagerie.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- Sur rendez-vous uniquement -->
                                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg" data-rdv-sur-demande>
                                                <label class="flex items-start gap-3 cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        name="rdv_uniquement_messagerie"
                                                        value="1"
                                                        {{ old('rdv_uniquement_messagerie', $entreprise->rdv_uniquement_messagerie) ? 'checked' : '' }}
                                                        class="mt-1 w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500 js-rdv-sur-demande"
                                                    >
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            Sur rendez-vous uniquement
                                                        </span>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Aucun agenda n’est montré aux clients. Ils voient une page d’information et vous contactent pour convenir d’un créneau.
                                                        </p>
                                                    </div>
                                                </label>
                                                <div class="js-rdv-sur-demande-message mt-3 {{ old('rdv_uniquement_messagerie', $entreprise->rdv_uniquement_messagerie) ? '' : 'hidden' }}">
                                                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Message affiché aux clients (optionnel)</label>
                                                    <textarea
                                                        name="rdv_sur_demande_message"
                                                        rows="3"
                                                        maxlength="2000"
                                                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500"
                                                    >{{ old('rdv_sur_demande_message', $entreprise->rdv_sur_demande_message) }}</textarea>
                                                </div>
                                            </div>

                                            <!-- Options supplémentaires -->
                                            <div class="mt-6 border-t border-slate-200 dark:border-slate-700 pt-6">
                                                <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                                                    ⚡ Options supplémentaires
                                                </h4>
                                                
                                                @php
                                                    $abonnementSiteWeb = $entreprise->abonnementSiteWeb();
                                                    $abonnementMultiPersonnes = $entreprise->abonnementMultiPersonnes();
                                                    $aSiteWebActif = $entreprise->aSiteWebActif();
                                                    $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
                                                @endphp

                                                <!-- Site Web Vitrine -->
                                                <div class="mb-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg {{ $aSiteWebActif ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <h5 class="font-semibold text-slate-900 dark:text-white">🌐 Site Web Vitrine</h5>
                                                                @if($aSiteWebActif)
                                                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                                        Actif
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                                                Créez une page vitrine personnalisée pour votre entreprise accessible via /w/{slug}. 
                                                                Inclut logo, phrase d'accroche, photos et sections configurables.
                                                            </p>
                                                            @if($aSiteWebActif && !empty($entreprise->slug_web))
                                                                <div class="text-sm text-slate-700 dark:text-slate-300 mb-3">
                                                                    <p><strong>URL de votre site :</strong> 
                                                                        <a href="{{ route('site-web.show', ['slug' => $entreprise->slug_web]) }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline">
                                                                            {{ url('/w/' . $entreprise->slug_web) }}
                                                                        </a>
                                                                    </p>
                                                                </div>
                                                                <button onclick="openAbonnementModal('{{ $entreprise->slug }}', '{{ $entreprise->nom }}')" class="inline-block px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                                                    Gérer l'abonnement
                                                                </button>
                                                            @else
                                                                <div class="flex items-center gap-3">
                                                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">5€/mois</span>
                                                                    <button onclick="openAbonnementModal('{{ $entreprise->slug }}', '{{ $entreprise->nom }}')" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                                                        S'abonner
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Gestion Multi-Personnes -->
                                                <div class="mb-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg {{ $aGestionMultiPersonnes ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <h5 class="font-semibold text-slate-900 dark:text-white">👥 Gestion Multi-Personnes</h5>
                                                                @if($aGestionMultiPersonnes)
                                                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                                        Actif
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                                                Gérez plusieurs personnes pour votre entreprise. Ajoutez des administrateurs, 
                                                                accédez à des statistiques avancées et gérez plusieurs établissements.
                                                            </p>
                                                            @if($aGestionMultiPersonnes)
                                                                <div class="flex items-center gap-3">
                                                                    <a href="{{ route('entreprise.membres.index', $entreprise->slug) }}" class="inline-block px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                                                        Gérer les membres
                                                                    </a>
                                                                    <button onclick="openAbonnementModal('{{ $entreprise->slug }}', '{{ $entreprise->nom }}')" class="inline-block px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                                                        Gérer l'abonnement
                                                                    </button>
                                                                </div>
                                                            @else
                                                                <div class="flex items-center gap-3">
                                                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">20€/mois</span>
                                                                    <button onclick="openAbonnementModal('{{ $entreprise->slug }}', '{{ $entreprise->nom }}')" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                                                        S'abonner
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Site Web Externe (Gratuit) -->
                                                <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <h5 class="font-semibold text-slate-900 dark:text-white">🔗 Lier un site web externe</h5>
                                                                <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                                                                    Gratuit
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                                                Si vous avez déjà un site web, vous pouvez le lier à votre entreprise.
                                                            </p>
                                                            <div class="mt-2">
                                                                <input 
                                                                    type="url" 
                                                                    name="site_web_externe" 
                                                                    value="{{ old('site_web_externe', $entreprise->site_web_externe) }}"
                                                                    placeholder="https://votre-site.com"
                                                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                                                                >
                                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                                    L'URL sera visible sur votre profil public.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Onglet Notifications -->
                    <div id="tab-notifications" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Préférences de notifications</h2>
                        
                        <!-- Section Push Notifications -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                Notifications Push
                            </h3>

                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg mb-4">
                                <div id="settings-push-status">
                                    <div id="settings-push-unsupported" class="hidden text-sm text-amber-700 dark:text-amber-300">
                                        Votre navigateur ne supporte pas les notifications push.
                                    </div>
                                    <div id="settings-push-denied" class="hidden text-sm text-amber-700 dark:text-amber-300">
                                        Les notifications push sont bloquées par votre navigateur. Modifiez les permissions dans les paramètres de votre navigateur pour les réactiver.
                                    </div>
                                    <div id="settings-push-inactive" class="hidden">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-slate-900 dark:text-white">Notifications push</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">Recevez des notifications en temps réel dans votre navigateur</p>
                                            </div>
                                            <button type="button" id="btn-settings-activate-push"
                                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                                Activer
                                            </button>
                                        </div>
                                    </div>
                                    <div id="settings-push-active" class="hidden">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <div>
                                                    <p class="font-medium text-slate-900 dark:text-white">Notifications push activées</p>
                                                    <p class="text-sm text-slate-600 dark:text-slate-400">Vous recevez des notifications dans ce navigateur</p>
                                                </div>
                                            </div>
                                            <button type="button" id="btn-settings-deactivate-push"
                                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                Désactiver
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Préférences par catégorie × canal -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Canaux par catégorie</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                Mêmes réglages pour votre usage <strong>client</strong> et <strong>professionnel</strong>.
                                Pour les messages, le <strong>push</strong> est recommandé ; l’email est désactivé par défaut.
                                @if($user->is_admin)
                                    La ligne <strong>Administration plateforme</strong> concerne les alertes tickets, contacts, audits, etc.
                                @endif
                            </p>

                            @php
                                use App\Services\NotificationPreferenceService as NPref;
                                $notifCategories = app(NPref::class)->categoriesForUser($user);
                                $notifChannels = NPref::channels();
                                $notifLabels = NPref::categoryLabels();
                                $notifDescs = NPref::categoryDescriptions();
                                $notifChannelLabels = NPref::channelLabels();
                                $channelPrefs = $notificationChannelPrefs ?? app(NPref::class)->allForUser($user);
                            @endphp

                            <form action="{{ route('settings.notifications.update') }}" method="POST" id="form-notification-prefs">
                                @csrf
                                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                                    <table class="w-full min-w-[520px] text-sm">
                                        <thead class="bg-slate-50 dark:bg-slate-800/80">
                                            <tr>
                                                <th class="text-left p-3 font-medium text-slate-700 dark:text-slate-300">Catégorie</th>
                                                @foreach($notifChannels as $ch)
                                                    <th class="p-3 text-center font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ $notifChannelLabels[$ch] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                            @foreach($notifCategories as $cat)
                                                <tr class="bg-white dark:bg-slate-800">
                                                    <td class="p-3 align-top">
                                                        <p class="font-medium text-slate-900 dark:text-white">{{ $notifLabels[$cat] }}</p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $notifDescs[$cat] }}</p>
                                                    </td>
                                                    @foreach($notifChannels as $ch)
                                                        <td class="p-3 text-center align-middle">
                                                            <label class="inline-flex items-center justify-center cursor-pointer" title="{{ $notifChannelLabels[$ch] }}">
                                                                <input type="checkbox"
                                                                    name="notif[{{ $cat }}][{{ $ch }}]"
                                                                    value="1"
                                                                    {{ ($channelPrefs[$cat][$ch] ?? false) ? 'checked' : '' }}
                                                                    class="w-5 h-5 rounded border-slate-300 text-green-600 focus:ring-green-500 dark:border-slate-600 dark:bg-slate-700">
                                                            </label>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button type="submit" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                        Enregistrer les préférences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Script Push Notifications Settings -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
                            
                            if (!supported) {
                                document.getElementById('settings-push-unsupported').classList.remove('hidden');
                                return;
                            }

                            async function checkPushStatus() {
                                const perm = Notification.permission;
                                if (perm === 'denied') {
                                    document.getElementById('settings-push-denied').classList.remove('hidden');
                                    return;
                                }

                                const reg = await navigator.serviceWorker.ready;
                                const sub = await reg.pushManager.getSubscription();

                                if (sub) {
                                    document.getElementById('settings-push-active').classList.remove('hidden');
                                } else {
                                    document.getElementById('settings-push-inactive').classList.remove('hidden');
                                }
                            }

                            checkPushStatus();

                            // Activer push
                            const activateBtn = document.getElementById('btn-settings-activate-push');
                            if (activateBtn) {
                                activateBtn.addEventListener('click', async function() {
                                    const permission = await Notification.requestPermission();
                                    if (permission !== 'granted') {
                                        document.getElementById('settings-push-inactive').classList.add('hidden');
                                        document.getElementById('settings-push-denied').classList.remove('hidden');
                                        return;
                                    }

                                    try {
                                        const reg = await navigator.serviceWorker.ready;
                                        const vapidKey = '{{ config("webpush.vapid.public_key") }}';
                                        const padding = '='.repeat((4 - vapidKey.length % 4) % 4);
                                        const base64 = (vapidKey + padding).replace(/-/g, '+').replace(/_/g, '/');
                                        const rawData = window.atob(base64);
                                        const outputArray = new Uint8Array(rawData.length);
                                        for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);

                                        const subscription = await reg.pushManager.subscribe({
                                            userVisibleOnly: true,
                                            applicationServerKey: outputArray,
                                        });

                                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content 
                                            || document.querySelector('input[name="_token"]')?.value;

                                        await fetch('/push-subscription', {
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

                                        document.getElementById('settings-push-inactive').classList.add('hidden');
                                        document.getElementById('settings-push-active').classList.remove('hidden');
                                    } catch (e) {
                                        console.error('Erreur activation push:', e);
                                    }
                                });
                            }

                            // Désactiver push
                            const deactivateBtn = document.getElementById('btn-settings-deactivate-push');
                            if (deactivateBtn) {
                                deactivateBtn.addEventListener('click', async function() {
                                    try {
                                        const reg = await navigator.serviceWorker.ready;
                                        const subscription = await reg.pushManager.getSubscription();

                                        if (subscription) {
                                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content 
                                                || document.querySelector('input[name="_token"]')?.value;

                                            await fetch('/push-subscription', {
                                                method: 'DELETE',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': csrfToken,
                                                    'Accept': 'application/json',
                                                },
                                                body: JSON.stringify({ endpoint: subscription.endpoint }),
                                            });

                                            await subscription.unsubscribe();
                                        }

                                        document.getElementById('settings-push-active').classList.add('hidden');
                                        document.getElementById('settings-push-inactive').classList.remove('hidden');
                                    } catch (e) {
                                        console.error('Erreur désactivation push:', e);
                                    }
                                });
                            }
                        });
                    </script>

                    <!-- Onglet Sécurité -->
                    <div id="tab-security" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Sécurité</h2>
                        
                        <div class="space-y-6">
                            <!-- Changer le mot de passe -->
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Changer le mot de passe</h3>
                                
                                <form action="{{ route('settings.password.update') }}" method="POST" class="space-y-4">
                                    @csrf
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Mot de passe actuel *
                                        </label>
                                        <div class="relative">
                                            <input 
                                                id="settings-current-password"
                                                type="password" 
                                                name="current_password" 
                                                required
                                                class="w-full px-4 py-3 pr-12 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            <button type="button" onclick="togglePasswordVisibility('settings-current-password', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Afficher le mot de passe">
                                                <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                                                <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        </div>
                                        @error('current_password')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Nouveau mot de passe *
                                            </label>
                                            <div class="relative">
                                                <input 
                                                    id="settings-new-password"
                                                    type="password" 
                                                    name="new_password" 
                                                    required
                                                    minlength="8"
                                                    class="w-full px-4 py-3 pr-12 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                >
                                                <button type="button" onclick="togglePasswordVisibility('settings-new-password', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Afficher le mot de passe">
                                                    <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                                                    <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </button>
                                            </div>
                                            @error('new_password')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Minimum 8 caractères
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Confirmer le mot de passe *
                                            </label>
                                            <div class="relative">
                                                <input 
                                                    id="settings-new-password-confirm"
                                                    type="password" 
                                                    name="new_password_confirmation" 
                                                    required
                                                    minlength="8"
                                                    class="w-full px-4 py-3 pr-12 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                >
                                                <button type="button" onclick="togglePasswordVisibility('settings-new-password-confirm', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Afficher le mot de passe">
                                                    <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                                                    <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                            Mettre à jour le mot de passe
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Sessions actives -->
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Sessions actives</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    Vous êtes actuellement connecté sur cet appareil.
                                </p>
                                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Session actuelle</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ now()->format('d/m/Y à H:i') }}</p>
                                    </div>
                                    <span class="px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                        Actif
                                    </span>
                                </div>
                            </div>

                            <!-- Zone de danger -->
                            <div class="p-6 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
                                <h3 class="text-lg font-semibold text-red-900 dark:text-red-400 mb-2">Zone de danger</h3>
                                <p class="text-sm text-red-800 dark:text-red-300 mb-4">
                                    Une fois votre compte supprimé, toutes vos données seront définitivement effacées.
                                </p>
                                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                    Supprimer mon compte
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Abonnement -->
                    @if($user->est_gerant)
                        <div id="tab-subscription" class="tab-content hidden">
                            @include('partials.settings.subscription-tab')
                        </div>
                    @endif

                    <!-- Onglet Confidentialité -->
                    <div id="tab-confidentialite" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Confidentialité & RGPD</h2>
                        
                        <div class="space-y-6">
                            <!-- Consentement aux trackers -->
                            <form action="{{ route('settings.confidentialite.update') }}" method="POST" class="space-y-6">
                                @csrf
                                <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                    <div class="flex items-start justify-between gap-4 mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                                                Tracker de visites
                                            </h3>
                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                                En acceptant les trackers, vous aidez les professionnels (Tata) à améliorer et simplifier leurs activités. 
                                                Ces statistiques anonymisées leur permettent de mieux comprendre les besoins de leurs clients et d'optimiser leurs services.
                                            </p>
                                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                                                <p class="text-sm text-blue-800 dark:text-blue-400">
                                                    <strong>Données collectées :</strong> Les trackers enregistrent uniquement des données anonymes (durée de visite, pages consultées, services/produits cliqués). 
                                                    Aucune donnée personnelle identifiable n'est collectée sans votre consentement explicite.
                                                </p>
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-500 mb-4">
                                                En conformité avec le RGPD, vous pouvez à tout moment modifier votre préférence. 
                                                <a href="{{ route('legal.confidentialite') }}" class="text-green-600 dark:text-green-400 hover:underline">En savoir plus sur notre politique de confidentialité</a>.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                                        <div class="flex-1">
                                            <label class="text-base font-medium text-slate-900 dark:text-white cursor-pointer" for="tracking-consent">
                                                Autoriser le tracking des visites pour améliorer les services des Tata
                                            </label>
                                        </div>
                                        <div class="ml-4">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    id="tracking-consent"
                                                    name="tracking_consent" 
                                                    value="1"
                                                    {{ old('tracking_consent', $user->tracking_consent ?? true) ? 'checked' : '' }}
                                                    class="sr-only peer"
                                                >
                                                <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end mt-4">
                                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
                                            Enregistrer
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Droit d'accès : Télécharger mes données -->
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                                    Télécharger mes données
                                </h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    Conformément au RGPD (articles 15 et 20), vous avez le droit d'obtenir une copie de toutes les données personnelles que nous détenons vous concernant. 
                                    L'export sera un fichier ZIP contenant vos données au format JSON, un récapitulatif PDF lisible, ainsi que vos fichiers (photos, etc.).
                                </p>

                                @if(isset($gdprData['pendingExport']) && $gdprData['pendingExport'])
                                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-4">
                                        <p class="text-sm text-yellow-800 dark:text-yellow-400">
                                            Un export est en cours de génération. Veuillez patienter...
                                        </p>
                                    </div>
                                @endif

                                @if(isset($gdprData['lastExport']) && $gdprData['lastExport'] && $gdprData['lastExport']->isDownloadAvailable())
                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-green-800 dark:text-green-400 font-medium">
                                                    Votre dernier export est prêt
                                                </p>
                                                <p class="text-xs text-green-600 dark:text-green-500 mt-1">
                                                    Généré le {{ $gdprData['lastExport']->processed_at->format('d/m/Y à H:i') }} 
                                                    — Expire le {{ $gdprData['lastExport']->expires_at->format('d/m/Y') }}
                                                    @if($gdprData['lastExport']->metadata && isset($gdprData['lastExport']->metadata['file_size']))
                                                        — {{ number_format($gdprData['lastExport']->metadata['file_size'] / 1024, 0) }} Ko
                                                    @endif
                                                </p>
                                            </div>
                                            <a href="{{ route('gdpr.download', $gdprData['lastExport']) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                                                Télécharger
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                <form action="{{ route('gdpr.export') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
                                        Générer un nouvel export de mes données
                                    </button>
                                </form>
                            </div>

                            <!-- Droit à l'effacement : Suppression du compte -->
                            <div class="p-6 border border-red-200 dark:border-red-800 rounded-lg bg-red-50/50 dark:bg-red-900/10">
                                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-2">
                                    Suppression du compte
                                </h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                    Conformément au RGPD (article 17), vous pouvez demander la suppression de votre compte et de vos données personnelles.
                                </p>

                                @if(isset($gdprData['pendingDeletion']) && $gdprData['pendingDeletion'])
                                    {{-- Demande de suppression en cours --}}
                                    <div class="p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg mb-4">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                                                    Demande de suppression en cours
                                                </p>
                                                <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                                                    Votre compte sera définitivement supprimé le <strong>{{ $gdprData['pendingDeletion']->scheduled_at->format('d/m/Y') }}</strong>
                                                    (dans {{ $gdprData['pendingDeletion']->daysUntilExecution() }} jour(s)).
                                                </p>
                                                @if($gdprData['pendingDeletion']->reason)
                                                    <p class="text-xs text-red-600 dark:text-red-500 mt-1">Raison : {{ $gdprData['pendingDeletion']->reason }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <form action="{{ route('gdpr.cancel-deletion', $gdprData['pendingDeletion']) }}" method="POST" class="mt-4">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 bg-white dark:bg-slate-800 text-red-700 dark:text-red-400 border border-red-300 dark:border-red-700 rounded-lg text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                                Annuler la demande de suppression
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    {{-- Formulaire de demande de suppression --}}
                                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg mb-4">
                                        <p class="text-sm text-amber-800 dark:text-amber-400">
                                            <strong>Attention :</strong> Cette action est irréversible une fois le délai de grâce écoulé. Vos données personnelles seront anonymisées. 
                                            Les factures seront conservées conformément à la législation française (10 ans). 
                                            Nous vous recommandons de télécharger vos données avant de procéder.
                                        </p>
                                    </div>

                                    <form action="{{ route('gdpr.request-deletion') }}" method="POST" id="deletion-form" class="space-y-4">
                                        @csrf
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                                Raison (facultatif)
                                            </label>
                                            <textarea name="reason" rows="2" maxlength="1000" placeholder="Pourquoi souhaitez-vous supprimer votre compte ?" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-red-500 focus:border-red-500"></textarea>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                                Confirmez votre mot de passe
                                            </label>
                                            <input type="password" name="password" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-red-500 focus:border-red-500" placeholder="Votre mot de passe actuel">
                                        </div>

                                        <div class="flex items-start gap-2">
                                            <input type="checkbox" name="confirm_deletion" id="confirm-deletion" value="1" required class="mt-1 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                            <label for="confirm-deletion" class="text-sm text-slate-600 dark:text-slate-400">
                                                Je comprends que cette action supprimera définitivement mes données personnelles après le délai de grâce et qu'elle est irréversible.
                                            </label>
                                        </div>

                                        <button type="submit" onclick="return confirm('Êtes-vous absolument sûr(e) de vouloir demander la suppression de votre compte ?')" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
                                            Demander la suppression de mon compte
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <!-- Historique des demandes RGPD -->
                            @if(isset($gdprData['history']) && $gdprData['history']->count() > 0)
                                <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                                        Historique de vos demandes RGPD
                                    </h3>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-left">
                                            <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                                                <tr>
                                                    <th class="py-2 pr-4">Type</th>
                                                    <th class="py-2 pr-4">Statut</th>
                                                    <th class="py-2 pr-4">Date</th>
                                                    <th class="py-2">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                @foreach($gdprData['history'] as $req)
                                                    <tr>
                                                        <td class="py-2 pr-4">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $req->isExport() ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' }}">
                                                                {{ $req->type_label }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2 pr-4">
                                                            @php
                                                                $statusColors = [
                                                                    'pending' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
                                                                    'processing' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
                                                                    'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
                                                                    'cancelled' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
                                                                    'failed' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400',
                                                                ];
                                                            @endphp
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$req->status] ?? '' }}">
                                                                {{ $req->status_label }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2 pr-4 text-slate-600 dark:text-slate-400">
                                                            {{ $req->created_at->format('d/m/Y H:i') }}
                                                        </td>
                                                        <td class="py-2">
                                                            @if($req->isDownloadAvailable())
                                                                <a href="{{ route('gdpr.download', $req) }}" class="text-green-600 dark:text-green-400 hover:underline text-xs font-medium">
                                                                    Télécharger
                                                                </a>
                                                            @elseif($req->isDeletion() && $req->canBeCancelled())
                                                                <form action="{{ route('gdpr.cancel-deletion', $req) }}" method="POST" class="inline">
                                                                    @csrf
                                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-xs font-medium">
                                                                        Annuler
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <span class="text-xs text-slate-400">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Onglet Préférences -->
                    <div id="tab-preferences" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Préférences</h2>
                        
                        <div class="space-y-6">
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Apparence</h3>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Thème sombre</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Activez le mode sombre pour une meilleure expérience</p>
                                    </div>
                                    <button
                                        id="theme-toggle"
                                        class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                                    >
                                        <svg class="w-6 h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        <svg class="w-6 h-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            @if($user->is_admin)
                                <!-- Mode Debug -->
                                <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Mode Debug (Admin)</h3>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-slate-900 dark:text-white">État actuel</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    @if(config('app.debug'))
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Mode Debug ACTIVÉ
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Mode Debug DÉSACTIVÉ
                                                        </span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                            <p class="text-sm text-blue-800 dark:text-blue-400 mb-2">
                                                <strong>ℹ️ Comment activer/désactiver le mode debug :</strong>
                                            </p>
                                            <ol class="list-decimal list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                                <li>Ouvrez le fichier <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">.env</code> à la racine du projet</li>
                                                <li>Modifiez la ligne <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">APP_DEBUG=true</code> (ou <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">false</code>)</li>
                                                <li>Rechargez la page pour voir le changement</li>
                                            </ol>
                                            <p class="text-xs text-blue-600 dark:text-blue-500 mt-3">
                                                ⚠️ <strong>Attention :</strong> Le mode debug doit être désactivé en production pour des raisons de sécurité.
                                            </p>
                                        </div>
                                        <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                            <p class="text-sm font-medium text-slate-900 dark:text-white mb-2">Informations de l'environnement :</p>
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <span class="text-slate-600 dark:text-slate-400">Environnement :</span>
                                                    <span class="ml-2 font-mono text-slate-900 dark:text-white">{{ config('app.env') }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-600 dark:text-slate-400">Debug :</span>
                                                    <span class="ml-2 font-mono text-slate-900 dark:text-white">{{ config('app.debug') ? 'true' : 'false' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $hasNotificationsColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'notifications_erreurs_actives');
                                @endphp
                                @if($hasNotificationsColumn)
                                    <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Notifications d'erreurs (Admin)</h3>
                                        <form action="{{ route('settings.error-notifications.update') }}" method="POST">
                                            @csrf
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-medium text-slate-900 dark:text-white">Notifications d'erreurs en temps réel</p>
                                                    <p class="text-sm text-slate-600 dark:text-slate-400">Recevez des notifications en temps réel lorsque des erreurs se produisent sur l'application</p>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        name="notifications_erreurs_actives" 
                                                        value="1"
                                                        {{ isset($user->notifications_erreurs_actives) && $user->notifications_erreurs_actives ? 'checked' : '' }}
                                                        onchange="this.form.submit()"
                                                        class="sr-only peer"
                                                    >
                                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                                </label>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            @endif

                            {{-- Interblocage entre entreprises --}}
                            @if($user->est_gerant && $user->entreprises()->count() >= 2)
                                <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Interblocage entre entreprises</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                        Quand cette option est activée, les réservations de toutes vos entreprises sont visibles dans l'emploi du temps de chacune. Les créneaux réservés sur une entreprise apparaîtront sur les autres.
                                    </p>
                                    <form action="{{ route('settings.interblocage.update') }}" method="POST">
                                        @csrf
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900 dark:text-white">Synchroniser les créneaux entre mes entreprises</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input 
                                                    type="hidden" 
                                                    name="interbloquer_entreprises" 
                                                    value="0"
                                                >
                                                <input 
                                                    type="checkbox" 
                                                    name="interbloquer_entreprises" 
                                                    value="1"
                                                    {{ $user->interbloquer_entreprises ? 'checked' : '' }}
                                                    onchange="this.form.submit()"
                                                    class="sr-only peer"
                                                >
                                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                                            </label>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
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
                window.history.replaceState({}, '', url);
            }

            // Afficher l'onglet par défaut ou depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'account';
            showTab(tab);

            document.querySelectorAll('[data-rdv-sur-demande]').forEach(function(wrap) {
                const checkbox = wrap.querySelector('.js-rdv-sur-demande');
                const message = wrap.querySelector('.js-rdv-sur-demande-message');
                if (!checkbox || !message) return;
                const apply = () => message.classList.toggle('hidden', !checkbox.checked);
                checkbox.addEventListener('change', apply);
                apply();
            });

            // Upload automatique du logo
            @foreach($entreprises as $entreprise)
                document.getElementById('logo-input-{{ $entreprise->id }}')?.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('logo', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    const loadingEl = document.getElementById('logo-loading-{{ $entreprise->id }}');
                    const previewEl = document.getElementById('logo-preview-{{ $entreprise->id }}');
                    const imgEl = document.getElementById('logo-img-{{ $entreprise->id }}');

                    loadingEl.classList.remove('hidden');

                    fetch('{{ route('settings.entreprise.logo.upload', $entreprise->slug) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Erreur lors de l\'upload');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadingEl.classList.add('hidden');
                        if (data.success) {
                            previewEl.classList.remove('hidden');
                            imgEl.src = data.logo_url + '?t=' + new Date().getTime();
                            // Afficher un message de succès temporaire
                            const inputContainer = e.target.closest('.flex');
                            let existingMsg = inputContainer.parentElement.querySelector('.upload-success-msg');
                            if (existingMsg) existingMsg.remove();
                            const successMsg = document.createElement('div');
                            successMsg.className = 'upload-success-msg mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-400 text-sm';
                            successMsg.textContent = data.message;
                            inputContainer.parentElement.appendChild(successMsg);
                            setTimeout(() => successMsg.remove(), 3000);
                            // Réinitialiser l'input
                            e.target.value = '';
                        } else {
                            throw new Error(data.message || 'Erreur lors de l\'upload du logo');
                        }
                    })
                    .catch(error => {
                        loadingEl.classList.add('hidden');
                        console.error('Error:', error);
                        // Afficher un message d'erreur
                        const inputContainer = e.target.closest('.flex');
                        let existingMsg = inputContainer.parentElement.querySelector('.upload-error-msg');
                        if (existingMsg) existingMsg.remove();
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'upload-error-msg mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-400 text-sm';
                        errorMsg.textContent = error.message || 'Erreur lors de l\'upload du logo';
                        inputContainer.parentElement.appendChild(errorMsg);
                        setTimeout(() => errorMsg.remove(), 5000);
                    });
                });

                // Upload automatique de l'image de fond
                document.getElementById('image-fond-input-{{ $entreprise->id }}')?.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('image_fond', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    const loadingEl = document.getElementById('image-fond-loading-{{ $entreprise->id }}');
                    const previewEl = document.getElementById('image-fond-preview-{{ $entreprise->id }}');
                    const imgEl = document.getElementById('image-fond-img-{{ $entreprise->id }}');

                    loadingEl.classList.remove('hidden');

                    fetch('{{ route('settings.entreprise.image-fond.upload', $entreprise->slug) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Erreur lors de l\'upload');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadingEl.classList.add('hidden');
                        if (data.success) {
                            previewEl.classList.remove('hidden');
                            previewEl.classList.add('mb-3');
                            imgEl.src = data.image_fond_url + '?t=' + new Date().getTime();
                            // Afficher un message de succès temporaire
                            const inputContainer = e.target.closest('.flex');
                            let existingMsg = inputContainer.parentElement.querySelector('.upload-success-msg');
                            if (existingMsg) existingMsg.remove();
                            const successMsg = document.createElement('div');
                            successMsg.className = 'upload-success-msg mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-400 text-sm';
                            successMsg.textContent = data.message;
                            inputContainer.parentElement.appendChild(successMsg);
                            setTimeout(() => successMsg.remove(), 3000);
                            // Réinitialiser l'input
                            e.target.value = '';
                        } else {
                            throw new Error(data.message || 'Erreur lors de l\'upload de l\'image de fond');
                        }
                    })
                    .catch(error => {
                        loadingEl.classList.add('hidden');
                        console.error('Error:', error);
                        // Afficher un message d'erreur
                        const inputContainer = e.target.closest('.flex');
                        let existingMsg = inputContainer.parentElement.querySelector('.upload-error-msg');
                        if (existingMsg) existingMsg.remove();
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'upload-error-msg mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-400 text-sm';
                        errorMsg.textContent = error.message || 'Erreur lors de l\'upload de l\'image de fond';
                        inputContainer.parentElement.appendChild(errorMsg);
                        setTimeout(() => errorMsg.remove(), 5000);
                    });
                });
            @endforeach
        </script>

        <!-- Modale de gestion d'abonnement entreprise -->
        <div id="abonnement-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 overflow-y-auto p-4">
            <div class="min-h-screen flex items-center justify-center py-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
                        <h3 id="abonnement-modal-title" class="text-2xl font-bold text-slate-900 dark:text-white">Gestion des abonnements</h3>
                        <button onclick="closeAbonnementModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                            <svg class="w-6 h-6 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="abonnement-modal-content" class="flex-1 overflow-y-auto p-6">
                        <div class="flex items-center justify-center py-12">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-green-600 dark:text-green-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-slate-600 dark:text-slate-400">Chargement...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function openAbonnementModal(slug, nomEntreprise) {
                const modal = document.getElementById('abonnement-modal');
                const modalTitle = document.getElementById('abonnement-modal-title');
                const modalContent = document.getElementById('abonnement-modal-content');
                
                modalTitle.textContent = `Abonnements - ${nomEntreprise}`;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                
                // Afficher le loader
                modalContent.innerHTML = `
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-green-600 dark:text-green-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-slate-600 dark:text-slate-400">Chargement...</p>
                        </div>
                    </div>
                `;
                
                // Charger le contenu via AJAX
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch(`/m/${slug}/abonnements/modal`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    credentials: 'same-origin',
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 403) {
                            throw new Error('Vous n\'avez pas accès à cette entreprise.');
                        } else if (response.status === 401) {
                            throw new Error('Vous devez être connecté pour accéder à cette fonctionnalité.');
                        } else {
                            throw new Error(`Erreur ${response.status} lors du chargement`);
                        }
                    }
                    return response.text();
                })
                .then(html => {
                    modalContent.innerHTML = html;
                    initModalForms(slug);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modalContent.innerHTML = `
                        <div class="flex items-center justify-center py-12">
                            <div class="text-center">
                                <svg class="w-12 h-12 text-red-600 dark:text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-red-600 dark:text-red-400 mb-2 font-semibold">Erreur</p>
                                <p class="text-slate-600 dark:text-slate-400 mb-4">${error.message || 'Erreur lors du chargement'}</p>
                                <button onclick="closeAbonnementModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    `;
                });
            }
            
            function initModalForms(slug) {
                const modalContent = document.getElementById('abonnement-modal-content');
                const forms = modalContent.querySelectorAll('form');
                
                forms.forEach(form => {
                    // Si c'est un formulaire de checkout Stripe, laisser le comportement par défaut (redirection)
                    if (form.action.includes('checkout')) {
                        return;
                    }
                    
                    // Pour les formulaires d'annulation, gérer via AJAX puis recharger le contenu
                    if (form.action.includes('cancel')) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            if (confirm('Êtes-vous sûr de vouloir annuler cet abonnement ?')) {
                                fetch(form.action, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]')?.value,
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: new FormData(form),
                                })
                                .then(response => {
                                    if (response.redirected) {
                                        // Si redirection, recharger la page complète
                                        window.location.reload();
                                    } else {
                                        // Recharger le contenu de la modale
                                        return fetch(`/m/${slug}/abonnements/modal`, {
                                            method: 'GET',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'text/html',
                                            },
                                        });
                                    }
                                })
                                .then(response => {
                                    if (response && response.ok) {
                                        return response.text();
                                    }
                                })
                                .then(html => {
                                    if (html) {
                                        modalContent.innerHTML = html;
                                        // Réinitialiser les gestionnaires d'événements pour les nouveaux formulaires
                                        initModalForms(slug);
                                    }
                                })
                                .catch(error => {
                                    console.error('Erreur:', error);
                                    alert('Une erreur est survenue. La page va être rechargée.');
                                    window.location.reload();
                                });
                            }
                        });
                    }
                });
            }
            
            function closeAbonnementModal() {
                const modal = document.getElementById('abonnement-modal');
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
            
            // Fermer la modale en cliquant sur le fond
            document.getElementById('abonnement-modal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAbonnementModal();
                }
            });
            
            // Fermer avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('abonnement-modal');
                    if (!modal.classList.contains('hidden')) {
                        closeAbonnementModal();
                    }
                }
            });
        </script>
    </body>
</html>

