<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Paramètres - Allo Tata</title>
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
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
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

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

            <!-- Onglets -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="border-b border-slate-200 dark:border-slate-700">
                    <nav class="flex overflow-x-auto" aria-label="Tabs">
                        <button 
                            onclick="showTab('account')"
                            class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-green-500 text-green-600 dark:text-green-400 whitespace-nowrap"
                            data-tab="account"
                        >
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Mon compte
                        </button>
                        @if($user->est_gerant && $entreprises->count() > 0)
                            <button 
                                onclick="showTab('entreprise')"
                                class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 whitespace-nowrap"
                                data-tab="entreprise"
                            >
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Mes entreprises
                            </button>
                        @endif
                        <button 
                            onclick="showTab('notifications')"
                            class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 whitespace-nowrap"
                            data-tab="notifications"
                        >
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            Notifications
                        </button>
                        <button 
                            onclick="showTab('security')"
                            class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 whitespace-nowrap"
                            data-tab="security"
                        >
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Sécurité
                        </button>
                        @if($user->est_gerant)
                            <button 
                                onclick="showTab('subscription')"
                                class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 whitespace-nowrap"
                                data-tab="subscription"
                            >
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                Abonnement
                            </button>
                        @endif
                        <button 
                            onclick="showTab('preferences')"
                            class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 whitespace-nowrap"
                            data-tab="preferences"
                        >
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Préférences
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Onglet Compte -->
                    <div id="tab-account" class="tab-content">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Informations du compte</h2>
                        
                        <form action="{{ route('settings.account.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            
                            <!-- Photo de profil -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Photo de profil
                                </label>
                                <div class="flex items-center gap-4">
                                    @if($user->photo_profil)
                                        <img 
                                            src="{{ asset('storage/' . $user->photo_profil) }}" 
                                            alt="Photo de profil"
                                            class="w-20 h-20 rounded-full object-cover border-2 border-slate-200 dark:border-slate-700"
                                        >
                                    @else
                                        <div class="w-20 h-20 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-2xl border-2 border-slate-200 dark:border-slate-700">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <input 
                                            type="file" 
                                            name="photo_profil" 
                                            accept="image/*"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Formats acceptés : JPEG, PNG, GIF, WebP (max 2MB)
                                        </p>
                                    </div>
                                </div>
                                @error('photo_profil')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Nom complet *
                                    </label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        value="{{ old('name', $user->name) }}"
                                        required
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

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

                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Changer le mot de passe</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Mot de passe actuel
                                        </label>
                                        <input 
                                            type="password" 
                                            name="current_password" 
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        @error('current_password')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Nouveau mot de passe
                                            </label>
                                            <input 
                                                type="password" 
                                                name="new_password" 
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            @error('new_password')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Confirmer le mot de passe
                                            </label>
                                            <input 
                                                type="password" 
                                                name="new_password_confirmation" 
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>
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
                                                    src="{{ $entreprise->logo ? asset('storage/' . $entreprise->logo) : '' }}" 
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
                                                        src="{{ $entreprise->image_fond ? asset('storage/' . $entreprise->image_fond) : '' }}" 
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
                                                        <option value="Coiffeuse" {{ $entreprise->type_activite == 'Coiffeuse' ? 'selected' : '' }}>Coiffeuse / Tressage</option>
                                                        <option value="Cuisinière" {{ $entreprise->type_activite == 'Cuisinière' ? 'selected' : '' }}>Cuisinière / Restauration</option>
                                                        <option value="Esthéticienne" {{ $entreprise->type_activite == 'Esthéticienne' ? 'selected' : '' }}>Esthéticienne</option>
                                                        <option value="Autre" {{ $entreprise->type_activite == 'Autre' ? 'selected' : '' }}>Autre</option>
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
                                                                src="{{ asset('storage/' . $photo->photo_path) }}" 
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

                                            <!-- RDV uniquement via messagerie -->
                                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                                <label class="flex items-start gap-3 cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        name="rdv_uniquement_messagerie" 
                                                        value="1"
                                                        {{ old('rdv_uniquement_messagerie', $entreprise->rdv_uniquement_messagerie) ? 'checked' : '' }}
                                                        class="mt-1 w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                                    >
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            💬 Rendez-vous uniquement via messagerie
                                                        </span>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Si activé, les clients devront passer par la messagerie pour prendre rendez-vous. L'agenda public sera désactivé.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Onglet Notifications -->
                    <div id="tab-notifications" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Préférences de notifications</h2>
                        
                        <div class="space-y-4">
                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-slate-900 dark:text-white">Notifications par email</h3>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Recevez des emails pour les nouvelles réservations</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-slate-900 dark:text-white">Notifications de paiement</h3>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Soyez informé lorsque vous recevez un paiement</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Sécurité -->
                    <div id="tab-security" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Sécurité</h2>
                        
                        <div class="space-y-6">
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
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">💳 Gestion de l'abonnement</h2>
                            
                            @php
                                $hasActiveSubscription = $user->aAbonnementActif();
                            @endphp

                            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                                <div class="text-center mb-6">
                                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                                        Abonnement Premium
                                    </h3>
                                    <div class="flex items-baseline justify-center gap-2 mb-4">
                                        <span class="text-5xl font-bold text-green-600 dark:text-green-400">15€</span>
                                        <span class="text-xl text-slate-600 dark:text-slate-400">/mois</span>
                                    </div>
                                    <p class="text-slate-600 dark:text-slate-400">
                                        Accès complet à toutes les fonctionnalités • Sans engagement • Annulation à tout moment
                                    </p>
                                </div>

                                @if($hasActiveSubscription)
                                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
                                        <div class="flex items-center gap-3 mb-4">
                                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <h3 class="text-xl font-bold text-green-800 dark:text-green-400">
                                                Abonnement actif
                                            </h3>
                                        </div>
                                        
                                        @if($subscription && $subscription->valid())
                                            <div class="space-y-4">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                                                        <p class="font-semibold text-slate-900 dark:text-white">Abonnement Stripe</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Statut</p>
                                                        @if($subscription->onGracePeriod())
                                                            <p class="font-semibold text-yellow-600 dark:text-yellow-400">Annulé - Actif jusqu'au {{ $subscription->ends_at->format('d/m/Y') }}</p>
                                                        @else
                                                            <p class="font-semibold text-green-600 dark:text-green-400">Actif</p>
                                                        @endif
                                                    </div>
                                                    @if($stripeSubscription)
                                                        <div>
                                                            <p class="text-slate-600 dark:text-slate-400 mb-1">Prochain paiement</p>
                                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                                {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('d/m/Y') }}
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p class="text-slate-600 dark:text-slate-400 mb-1">Période actuelle</p>
                                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                                Du {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start)->format('d/m/Y') }}
                                                                au {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('d/m/Y') }}
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <div class="flex gap-3 mt-4">
                                                    @if($subscription->onGracePeriod())
                                                        <form action="{{ route('subscription.resume') }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                                                Reprendre l'abonnement
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('subscription.cancel') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?');">
                                                            @csrf
                                                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                                                Annuler l'abonnement
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu)
                                            <div class="space-y-4">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                                                        <p class="font-semibold text-slate-900 dark:text-white">Abonnement manuel (géré par l'administrateur)</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Actif jusqu'au</p>
                                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_actif_jusqu->format('d/m/Y') }}</p>
                                                    </div>
                                                    @if($user->abonnement_manuel_notes)
                                                        <div class="md:col-span-2">
                                                            <p class="text-slate-600 dark:text-slate-400 mb-1">Note</p>
                                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $user->abonnement_manuel_notes }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                                    <p class="text-sm text-blue-800 dark:text-blue-400">
                                                        ℹ️ Vous avez un abonnement manuel actif. Vous ne pouvez pas souscrire à un abonnement Stripe tant que l'abonnement manuel est actif.
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Historique des factures (uniquement pour Stripe) -->
                                    @if($subscription && $subscription->valid() && $invoices->count() > 0)
                                        <div class="mt-6">
                                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">📄 Historique des factures</h3>
                                            <div class="space-y-3">
                                                @foreach($invoices->take(10) as $invoice)
                                                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-600">
                                                        <div>
                                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                                Facture du {{ \Carbon\Carbon::createFromTimestamp($invoice->created)->format('d/m/Y') }}
                                                            </p>
                                                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                                                {{ number_format($invoice->amount_paid / 100, 2, ',', ' ') }} €
                                                                @if($invoice->status === 'paid')
                                                                    <span class="ml-2 text-green-600 dark:text-green-400">✓ Payée</span>
                                                                @elseif($invoice->status === 'open')
                                                                    <span class="ml-2 text-yellow-600 dark:text-yellow-400">En attente</span>
                                                                @else
                                                                    <span class="ml-2 text-red-600 dark:text-red-400">Impayée</span>
                                                                @endif
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('subscription.invoice.download', $invoice->id) }}" 
                                                               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                                                📥 Télécharger
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
                                        <div class="mb-4">
                                            <p class="text-yellow-800 dark:text-yellow-400 font-semibold mb-2">
                                                ⚠️ Vous n'avez pas d'abonnement actif
                                            </p>
                                            <p class="text-sm text-yellow-700 dark:text-yellow-500">
                                                Sans abonnement actif, vos entreprises ne seront pas visibles en ligne. Souscrivez maintenant pour accéder à toutes les fonctionnalités.
                                            </p>
                                        </div>
                                        @php
                                            $priceId = config('services.stripe.price_id');
                                        @endphp
                                        @if(empty($priceId))
                                            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                                <p class="text-red-800 dark:text-red-400 text-sm">
                                                    ⚠️ <strong>Configuration incomplète :</strong> Le STRIPE_PRICE_ID n'est pas configuré. Veuillez contacter l'administrateur.
                                                </p>
                                            </div>
                                        @else
                                            <form action="{{ route('subscription.checkout') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                    Souscrire à l'abonnement (15€/mois)
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

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
                                        id="theme-toggle-settings"
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Gestion des onglets
            function showTab(tabName) {
                // Masquer tous les contenus
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Réinitialiser tous les boutons
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.classList.remove('border-green-500', 'text-green-600', 'dark:text-green-400');
                    button.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
                });

                // Afficher le contenu sélectionné
                document.getElementById('tab-' + tabName).classList.remove('hidden');

                // Activer le bouton sélectionné
                const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
                if (activeButton) {
                    activeButton.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
                    activeButton.classList.add('border-green-500', 'text-green-600', 'dark:text-green-400');
                }
            }

            // Gérer le toggle du thème
            document.getElementById('theme-toggle-settings')?.addEventListener('click', function() {
                const html = document.documentElement;
                html.classList.toggle('dark');
                
                if (html.classList.contains('dark')) {
                    localStorage.theme = 'dark';
                } else {
                    localStorage.theme = 'light';
                }
            });

            // Afficher l'onglet par défaut ou depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'account';
            showTab(tab);

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
    </body>
</html>

