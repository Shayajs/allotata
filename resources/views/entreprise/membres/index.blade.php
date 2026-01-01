<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Gestion des membres - {{ $entreprise->nom }} - Allo Tata</title>
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
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
                        </a>
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            {{ auth()->user()->name }}
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
                    👥 Gestion des membres - {{ $entreprise->nom }}
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Gérez les administrateurs et membres de votre entreprise
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <!-- Formulaire d'ajout de membre -->
                <div class="mb-8 p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">➕ Ajouter un membre</h2>
                    <form action="{{ route('entreprise.membres.store', $entreprise->slug) }}" method="POST">
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
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    L'utilisateur doit déjà avoir un compte sur la plateforme.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Rôle *
                                </label>
                                <select 
                                    name="role" 
                                    required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
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

                <!-- Liste des membres -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Membres de l'entreprise</h2>
                    
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
                                        <form action="{{ route('entreprise.membres.update', [$entreprise->slug, $membre]) }}" method="POST" class="inline">
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
                                        <form action="{{ route('entreprise.membres.destroy', [$entreprise->slug, $membre]) }}" method="POST" onsubmit="return confirm('Retirer ce membre de l\'entreprise ?');" class="inline">
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
                </div>
            </div>
        </div>
    </body>
</html>
