<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Options - {{ $entreprise->nom }} - Admin</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        @include('admin.partials.nav')

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Options - {{ $entreprise->nom }}</h1>
                    <p class="text-slate-600 dark:text-slate-400">Gérez les abonnements et options de cette entreprise</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                        ← Retour à l'entreprise
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-400">{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Onglets -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="border-b border-slate-200 dark:border-slate-700">
                    <nav class="flex overflow-x-auto" aria-label="Tabs">
                        <button 
                            onclick="showTab('abonnements')"
                            class="tab-button px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 border-green-500 text-green-600 dark:text-green-400"
                            data-tab="abonnements"
                        >
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            Abonnements
                        </button>
                        <button 
                            onclick="showTab('membres')"
                            class="tab-button px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600"
                            data-tab="membres"
                        >
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Membres & Administrateurs
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Onglet Abonnements -->
                    <div id="tab-abonnements" class="tab-content">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">💳 Abonnements et options</h2>

                        @php
                            $aSiteWebActif = $entreprise->aSiteWebActif();
                            $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
                        @endphp

                        <div class="space-y-6">
                            <!-- Site Web Vitrine -->
                            <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6 {{ $aSiteWebActif ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-3xl">🌐</span>
                                            <div>
                                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Site Web Vitrine</h3>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">2€/mois</p>
                                            </div>
                                            @if($aSiteWebActif)
                                                <span class="px-3 py-1 text-sm bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                    Actif
                                                </span>
                                            @else
                                                <span class="px-3 py-1 text-sm bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 rounded-full">
                                                    Inactif
                                                </span>
                                            @endif
                                        </div>

                                        @if($abonnementSiteWeb)
                                            <div class="mt-4 p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                                                        <p class="font-semibold text-slate-900 dark:text-white">
                                                            {{ $abonnementSiteWeb->est_manuel ? 'Abonnement manuel (admin)' : 'Abonnement Stripe' }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Statut</p>
                                                        <p class="font-semibold {{ $abonnementSiteWeb->estActif() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                            {{ $abonnementSiteWeb->estActif() ? 'Actif' : 'Inactif' }}
                                                        </p>
                                                    </div>
                                                    @if($abonnementSiteWeb->est_manuel && $abonnementSiteWeb->actif_jusqu)
                                                        <div>
                                                            <p class="text-slate-600 dark:text-slate-400 mb-1">Actif jusqu'au</p>
                                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                                {{ $abonnementSiteWeb->actif_jusqu->format('d/m/Y') }}
                                                            </p>
                                                        </div>
                                                        @if($abonnementSiteWeb->notes_manuel)
                                                            <div>
                                                                <p class="text-slate-600 dark:text-slate-400 mb-1">Notes</p>
                                                                <p class="font-semibold text-slate-900 dark:text-white">
                                                                    {{ $abonnementSiteWeb->notes_manuel }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                                @if($abonnementSiteWeb->est_manuel)
                                                    <form action="{{ route('admin.entreprises.options.desactiver', [$entreprise, 'site_web']) }}" method="POST" onsubmit="return confirm('Désactiver cette option ?');" class="mt-4">
                                                        @csrf
                                                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition text-sm">
                                                            Désactiver l'option
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif

                                        @if(!$aSiteWebActif)
                                            <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3">Activer manuellement</h4>
                                                <form action="{{ route('admin.entreprises.options.activer', $entreprise) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="type" value="site_web">
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Actif jusqu'au *
                                                            </label>
                                                            <input 
                                                                type="date" 
                                                                name="date_fin" 
                                                                value="{{ old('date_fin', now()->addMonth()->format('Y-m-d')) }}"
                                                                min="{{ now()->addDay()->format('Y-m-d') }}"
                                                                required
                                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Notes (optionnel)
                                                            </label>
                                                            <textarea 
                                                                name="notes" 
                                                                rows="3"
                                                                placeholder="Ex: Paiement direct, ristourne, etc."
                                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >{{ old('notes') }}</textarea>
                                                        </div>
                                                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                            Activer l'option Site Web Vitrine
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Gestion Multi-Personnes -->
                            <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6 {{ $aGestionMultiPersonnes ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-3xl">👥</span>
                                            <div>
                                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Gestion Multi-Personnes</h3>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">20€/mois</p>
                                            </div>
                                            @if($aGestionMultiPersonnes)
                                                <span class="px-3 py-1 text-sm bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                    Actif
                                                </span>
                                            @else
                                                <span class="px-3 py-1 text-sm bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 rounded-full">
                                                    Inactif
                                                </span>
                                            @endif
                                        </div>

                                        @if($abonnementMultiPersonnes)
                                            <div class="mt-4 p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Type</p>
                                                        <p class="font-semibold text-slate-900 dark:text-white">
                                                            {{ $abonnementMultiPersonnes->est_manuel ? 'Abonnement manuel (admin)' : 'Abonnement Stripe' }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-slate-600 dark:text-slate-400 mb-1">Statut</p>
                                                        <p class="font-semibold {{ $abonnementMultiPersonnes->estActif() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                            {{ $abonnementMultiPersonnes->estActif() ? 'Actif' : 'Inactif' }}
                                                        </p>
                                                    </div>
                                                    @if($abonnementMultiPersonnes->est_manuel && $abonnementMultiPersonnes->actif_jusqu)
                                                        <div>
                                                            <p class="text-slate-600 dark:text-slate-400 mb-1">Actif jusqu'au</p>
                                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                                {{ $abonnementMultiPersonnes->actif_jusqu->format('d/m/Y') }}
                                                            </p>
                                                        </div>
                                                        @if($abonnementMultiPersonnes->notes_manuel)
                                                            <div>
                                                                <p class="text-slate-600 dark:text-slate-400 mb-1">Notes</p>
                                                                <p class="font-semibold text-slate-900 dark:text-white">
                                                                    {{ $abonnementMultiPersonnes->notes_manuel }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                                @if($abonnementMultiPersonnes->est_manuel)
                                                    <form action="{{ route('admin.entreprises.options.desactiver', [$entreprise, 'multi_personnes']) }}" method="POST" onsubmit="return confirm('Désactiver cette option ?');" class="mt-4">
                                                        @csrf
                                                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition text-sm">
                                                            Désactiver l'option
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif

                                        @if(!$aGestionMultiPersonnes)
                                            <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                                <h4 class="font-semibold text-slate-900 dark:text-white mb-3">Activer manuellement</h4>
                                                <form action="{{ route('admin.entreprises.options.activer', $entreprise) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="type" value="multi_personnes">
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Actif jusqu'au *
                                                            </label>
                                                            <input 
                                                                type="date" 
                                                                name="date_fin" 
                                                                value="{{ old('date_fin', now()->addMonth()->format('Y-m-d')) }}"
                                                                min="{{ now()->addDay()->format('Y-m-d') }}"
                                                                required
                                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Notes (optionnel)
                                                            </label>
                                                            <textarea 
                                                                name="notes" 
                                                                rows="3"
                                                                placeholder="Ex: Paiement direct, ristourne, etc."
                                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >{{ old('notes') }}</textarea>
                                                        </div>
                                                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                            Activer l'option Gestion Multi-Personnes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Membres -->
                    <div id="tab-membres" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">👥 Membres & Administrateurs</h2>

                        @if($entreprise->aGestionMultiPersonnes())
                            <div class="mb-6">
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                    <p class="text-sm text-green-800 dark:text-green-400">
                                        ✓ L'abonnement Gestion Multi-Personnes est actif. Vous pouvez voir et gérer les membres ci-dessous.
                                    </p>
                                </div>
                            </div>

                            <!-- Formulaire d'ajout de membre -->
                            <div class="mb-6 p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">➕ Ajouter un membre</h3>
                                <form action="{{ route('admin.entreprises.membres.store', $entreprise) }}" method="POST">
                                    @csrf
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Email de l'utilisateur *
                                            </label>
                                            <input 
                                                type="email" 
                                                name="email" 
                                                value="{{ old('email') }}"
                                                required
                                                placeholder="email@exemple.com"
                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Si l'utilisateur n'existe pas, une invitation sera envoyée par email pour créer un compte.
                                            </p>
                                            @error('email')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Rôle *
                                            </label>
                                            <select 
                                                name="role" 
                                                required
                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                                <option value="administrateur" {{ old('role') === 'administrateur' ? 'selected' : '' }}>Administrateur</option>
                                                <option value="membre" {{ old('role') === 'membre' ? 'selected' : '' }}>Membre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                            Ajouter le membre
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="space-y-4">
                                <!-- Propriétaire -->
                                <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-700/50">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $entreprise->user->name }}</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->user->email }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                                                Propriétaire
                                            </span>
                                            <a href="{{ route('admin.users.show', $entreprise->user) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                                Voir →
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Membres -->
                                @foreach($membres as $membre)
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <p class="font-semibold text-slate-900 dark:text-white">{{ $membre->user->name }}</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $membre->user->email }}</p>
                                                @if($membre->invite_at)
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                        Invité le {{ $membre->invite_at->format('d/m/Y à H:i') }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <form action="{{ route('admin.entreprises.membres.update', [$entreprise, $membre]) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <select 
                                                        name="role" 
                                                        onchange="this.form.submit()"
                                                        class="px-3 py-1 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                        <option value="administrateur" {{ $membre->role === 'administrateur' ? 'selected' : '' }}>Administrateur</option>
                                                        <option value="membre" {{ $membre->role === 'membre' ? 'selected' : '' }}>Membre</option>
                                                    </select>
                                                </form>
                                                <span class="px-3 py-1 text-xs {{ $membre->role === 'administrateur' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400' : 'bg-slate-100 dark:bg-slate-600 text-slate-600 dark:text-slate-400' }} rounded-full">
                                                    {{ $membre->role === 'administrateur' ? 'Administrateur' : 'Membre' }}
                                                </span>
                                                @if(!$membre->est_actif)
                                                    <span class="px-3 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full">
                                                        Inactif
                                                    </span>
                                                @endif
                                                <a href="{{ route('admin.users.show', $membre->user) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                                    Voir →
                                                </a>
                                                <form action="{{ route('admin.entreprises.membres.destroy', [$entreprise, $membre]) }}" method="POST" onsubmit="return confirm('Retirer ce membre de l\'entreprise ?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                                        Retirer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($membres->isEmpty())
                                    <div class="text-center py-8 border border-slate-200 dark:border-slate-700 rounded-lg">
                                        <p class="text-slate-600 dark:text-slate-400">Aucun membre ajouté pour le moment.</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
                                <div class="flex items-center gap-3 mb-4">
                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-400">Abonnement requis</h3>
                                </div>
                                <p class="text-sm text-yellow-700 dark:text-yellow-500 mb-4">
                                    L'abonnement "Gestion Multi-Personnes" doit être actif pour que les utilisateurs puissent gérer les membres. 
                                    En tant qu'admin, vous pouvez toujours ajouter des membres ci-dessous.
                                </p>
                                <a href="#tab-abonnements" onclick="showTab('abonnements'); return false;" class="inline-block px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition text-sm">
                                    Activer l'abonnement
                                </a>
                            </div>

                            <!-- Formulaire d'ajout de membre (admin peut toujours ajouter) -->
                            <div class="mb-6 p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">➕ Ajouter un membre (Admin)</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    En tant qu'administrateur, vous pouvez ajouter des membres même si l'abonnement n'est pas actif.
                                </p>
                                <form action="{{ route('admin.entreprises.membres.store', $entreprise) }}" method="POST">
                                    @csrf
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Email de l'utilisateur *
                                            </label>
                                            <input 
                                                type="email" 
                                                name="email" 
                                                value="{{ old('email') }}"
                                                required
                                                placeholder="email@exemple.com"
                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Si l'utilisateur n'existe pas, une invitation sera envoyée par email pour créer un compte.
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Rôle *
                                            </label>
                                            <select 
                                                name="role" 
                                                required
                                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                                <option value="administrateur" {{ old('role') === 'administrateur' ? 'selected' : '' }}>Administrateur</option>
                                                <option value="membre" {{ old('role') === 'membre' ? 'selected' : '' }}>Membre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                            Ajouter le membre
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Liste des membres existants (même si abonnement inactif) -->
                            @if($membres->count() > 0)
                                <div class="space-y-4">
                                    <!-- Propriétaire -->
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-700/50">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-semibold text-slate-900 dark:text-white">{{ $entreprise->user->name }}</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->user->email }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                                                    Propriétaire
                                                </span>
                                                <a href="{{ route('admin.users.show', $entreprise->user) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                                    Voir →
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Membres -->
                                    @foreach($membres as $membre)
                                        <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $membre->user->name }}</p>
                                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $membre->user->email }}</p>
                                                    @if($membre->invite_at)
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Invité le {{ $membre->invite_at->format('d/m/Y à H:i') }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <form action="{{ route('admin.entreprises.membres.update', [$entreprise, $membre]) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <select 
                                                            name="role" 
                                                            onchange="this.form.submit()"
                                                            class="px-3 py-1 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                        >
                                                            <option value="administrateur" {{ $membre->role === 'administrateur' ? 'selected' : '' }}>Administrateur</option>
                                                            <option value="membre" {{ $membre->role === 'membre' ? 'selected' : '' }}>Membre</option>
                                                        </select>
                                                    </form>
                                                    <span class="px-3 py-1 text-xs {{ $membre->role === 'administrateur' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400' : 'bg-slate-100 dark:bg-slate-600 text-slate-600 dark:text-slate-400' }} rounded-full">
                                                        {{ $membre->role === 'administrateur' ? 'Administrateur' : 'Membre' }}
                                                    </span>
                                                    @if(!$membre->est_actif)
                                                        <span class="px-3 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full">
                                                            Inactif
                                                        </span>
                                                    @endif
                                                    <a href="{{ route('admin.users.show', $membre->user) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                                        Voir →
                                                    </a>
                                                    <form action="{{ route('admin.entreprises.membres.destroy', [$entreprise, $membre]) }}" method="POST" onsubmit="return confirm('Retirer ce membre de l\'entreprise ?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                                            Retirer
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 border border-slate-200 dark:border-slate-700 rounded-lg">
                                    <p class="text-slate-600 dark:text-slate-400">Aucun membre ajouté pour le moment.</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <script>
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
                const tabContent = document.getElementById('tab-' + tabName);
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                }

                // Activer le bouton sélectionné
                const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
                if (activeButton) {
                    activeButton.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
                    activeButton.classList.add('border-green-500', 'text-green-600', 'dark:text-green-400');
                }
            }
        </script>
    </body>
</html>
