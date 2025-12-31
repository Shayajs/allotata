<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Administration Temporaire - Allo Tata</title>
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
        <nav class="bg-gradient-to-r from-red-600 to-orange-600 border-b border-red-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-bold text-white">
                            ⚠️ ADMIN TEMPORAIRE
                        </span>
                        <span class="px-3 py-1 text-xs font-bold bg-yellow-300 text-red-800 rounded-full">
                            PAGE DE DÉVELOPPEMENT
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('home') }}" class="px-4 py-2 text-sm font-medium text-white hover:text-yellow-200 transition">
                            Accueil
                        </a>
                        <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium bg-white/20 hover:bg-white/30 text-white rounded-lg transition">
                            Admin Normal
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Alerte de sécurité -->
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-500 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-bold text-red-800 dark:text-red-300 text-lg">⚠️ PAGE TEMPORAIRE - DÉVELOPPEMENT UNIQUEMENT</p>
                        <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                            Cette page permet de créer des comptes administrateurs sans authentification. 
                            <strong>À SUPPRIMER EN PRODUCTION !</strong>
                        </p>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    @if(session('error'))
                        <p class="text-red-800 dark:text-red-400">{{ session('error') }}</p>
                    @endif
                    @if($errors->any())
                        <ul class="list-disc list-inside text-red-800 dark:text-red-400">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <!-- En-tête -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Administration Temporaire
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Créez des comptes administrateurs et gérez les utilisateurs.
                </p>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total utilisateurs</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_users'] }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Administrateurs</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['total_admins'] }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Entreprises</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_entreprises'] }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Réservations</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_reservations'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Formulaire de création d'admin -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                        Créer un nouveau compte admin
                    </h2>
                    <form action="{{ route('temp-admin.create-admin') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nom complet *
                                </label>
                                <input 
                                    type="text" 
                                    name="name" 
                                    required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    placeholder="Jean Dupont"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Email *
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    placeholder="jean@example.com"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Mot de passe *
                                </label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    required
                                    minlength="8"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    placeholder="Minimum 8 caractères"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Confirmer le mot de passe *
                                </label>
                                <input 
                                    type="password" 
                                    name="password_confirmation" 
                                    required
                                    minlength="8"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    placeholder="Répétez le mot de passe"
                                >
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2">
                                    <input 
                                        type="checkbox" 
                                        name="est_client" 
                                        value="1"
                                        checked
                                        class="w-4 h-4 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                    >
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Client</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input 
                                        type="checkbox" 
                                        name="est_gerant" 
                                        value="1"
                                        class="w-4 h-4 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                    >
                                    <span class="text-sm text-slate-700 dark:text-slate-300">Gérant</span>
                                </label>
                            </div>
                            <button 
                                type="submit" 
                                class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                            >
                                Créer le compte admin
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Liste des administrateurs -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                        Administrateurs actuels ({{ $admins->count() }})
                    </h2>
                    @if($admins->count() > 0)
                        <div class="space-y-3">
                            @foreach($admins as $admin)
                                <div class="p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $admin->name }}</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $admin->email }}</p>
                                            <div class="flex gap-2 mt-1">
                                                @if($admin->est_client)
                                                    <span class="px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded">Client</span>
                                                @endif
                                                @if($admin->est_gerant)
                                                    <span class="px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Gérant</span>
                                                @endif
                                                <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Admin</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <form action="{{ route('temp-admin.login-as', $admin) }}" method="POST">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition"
                                                    title="Se connecter en tant que cet utilisateur"
                                                >
                                                    Se connecter
                                                </button>
                                            </form>
                                            @if($admins->count() > 1)
                                                <form action="{{ route('temp-admin.demote', $admin) }}" method="POST" onsubmit="return confirm('Retirer les droits admin à {{ $admin->name }} ?');">
                                                    @csrf
                                                    <button 
                                                        type="submit" 
                                                        class="px-3 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition"
                                                    >
                                                        Retirer admin
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-600 dark:text-slate-400 text-center py-4">
                            Aucun administrateur pour le moment.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                    Tous les utilisateurs ({{ $users->total() }})
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Rôles</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($users as $user)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-600 dark:text-slate-400">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex gap-2">
                                            @if($user->est_client)
                                                <span class="px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded">Client</span>
                                            @endif
                                            @if($user->est_gerant)
                                                <span class="px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Gérant</span>
                                            @endif
                                            @if($user->is_admin)
                                                <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Admin</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            @if(!$user->is_admin)
                                                <form action="{{ route('temp-admin.promote', $user) }}" method="POST">
                                                    @csrf
                                                    <button 
                                                        type="submit" 
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                    >
                                                        Promouvoir admin
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('temp-admin.login-as', $user) }}" method="POST">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    Se connecter
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </body>
</html>

