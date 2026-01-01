<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un ticket de support - Allo Tata</title>
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
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Dashboard
                        </a>
                        <a href="{{ route('tickets.index') }}" class="px-4 py-2 text-sm font-medium bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-400 rounded-lg transition">
                            Mes tickets
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

    <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">🎫 Créer un ticket de support</h1>
            <p class="text-slate-600 dark:text-slate-400">
                Décrivez votre problème et notre équipe vous répondra dans les plus brefs délais.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <form action="{{ route('tickets.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Catégorie *
                        </label>
                        <select 
                            name="categorie" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Sélectionnez une catégorie</option>
                            <option value="technique" {{ old('categorie') == 'technique' ? 'selected' : '' }}>🔧 Technique</option>
                            <option value="facturation" {{ old('categorie') == 'facturation' ? 'selected' : '' }}>💳 Facturation</option>
                            <option value="compte" {{ old('categorie') == 'compte' ? 'selected' : '' }}>👤 Compte</option>
                            <option value="autre" {{ old('categorie') == 'autre' ? 'selected' : '' }}>❓ Autre</option>
                        </select>
                        @error('categorie')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Priorité
                        </label>
                        <select 
                            name="priorite" 
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="normale" {{ old('priorite', 'normale') == 'normale' ? 'selected' : '' }}>🟢 Normale</option>
                            <option value="haute" {{ old('priorite') == 'haute' ? 'selected' : '' }}>🟡 Haute</option>
                            <option value="urgente" {{ old('priorite') == 'urgente' ? 'selected' : '' }}>🔴 Urgente</option>
                            <option value="basse" {{ old('priorite') == 'basse' ? 'selected' : '' }}>⚪ Basse</option>
                        </select>
                        @error('priorite')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Sujet *
                    </label>
                    <input 
                        type="text" 
                        name="sujet" 
                        value="{{ old('sujet') }}"
                        required
                        placeholder="Ex: Problème de connexion"
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                    @error('sujet')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Description du problème *
                    </label>
                    <textarea 
                        name="description" 
                        rows="8"
                        required
                        placeholder="Décrivez en détail votre problème, les étapes pour le reproduire, et tout autre détail pertinent..."
                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >{{ old('description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Plus votre description est détaillée, plus nous pourrons vous aider rapidement.
                    </p>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                    >
                        Créer le ticket
                    </button>
                    <a 
                        href="{{ route('home') }}" 
                        class="px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    >
                        Annuler
                    </a>
                </div>
            </form>
        </div>

        @auth
            <div class="mt-6">
                <a href="{{ route('tickets.index') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Voir mes tickets existants
                </a>
            </div>
        @endauth
    </div>
</body>
</html>
