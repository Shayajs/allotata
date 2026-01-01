<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $entreprise->nom }} - Admin</title>
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
        @include('admin.partials.nav')

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ $entreprise->nom }}</h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $entreprise->type_activite }}</p>
                    @if($entreprise->est_verifiee)
                        <span class="inline-block mt-2 px-3 py-1 text-sm bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full border border-green-200 dark:border-green-800">
                            ✓ Entreprise vérifiée
                        </span>
                    @elseif($entreprise->aDesRefus())
                        <span class="inline-block mt-2 px-3 py-1 text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full border border-red-200 dark:border-red-800">
                            ✗ Refusée
                        </span>
                    @else
                        <span class="inline-block mt-2 px-3 py-1 text-sm bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-200 dark:border-yellow-800">
                            ⏳ En attente de vérification
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.entreprises.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                        ← Retour
                    </a>
                </div>
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

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-400">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Panneau de vérification -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">🔍 Vérification de l'entreprise</h2>
                
                <div class="space-y-4">
                    <!-- Vérification du nom -->
                    <div class="p-4 border rounded-lg {{ $entreprise->nom_valide === true ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20' : ($entreprise->nom_valide === false ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50') }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-slate-900 dark:text-white">Nom de l'entreprise</h3>
                                @if($entreprise->nom_valide === true)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">✓ Validé</span>
                                @elseif($entreprise->nom_valide === false)
                                    <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">✗ Refusé</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">⏳ En attente</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if($entreprise->nom_valide !== true)
                                    <form action="{{ route('admin.entreprises.validate-nom', $entreprise) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                            ✓ Valider
                                        </button>
                                    </form>
                                    <button 
                                        onclick="document.getElementById('modal-refus-nom').classList.remove('hidden')"
                                        class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition"
                                    >
                                        ✗ Refuser
                                    </button>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-2"><strong>Nom :</strong> {{ $entreprise->nom }}</p>
                        @if($entreprise->nom_valide === false && $entreprise->nom_refus_raison)
                            <p class="text-sm text-red-600 dark:text-red-400 mt-2"><strong>Raison du refus :</strong> {{ $entreprise->nom_refus_raison }}</p>
                        @endif
                    </div>

                    <!-- Vérification du SIREN -->
                    @if($entreprise->siren)
                        <div class="p-4 border rounded-lg {{ $entreprise->siren_valide === true ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20' : ($entreprise->siren_valide === false ? 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50') }}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-white">SIREN</h3>
                                    @if($entreprise->siren_valide === true)
                                        <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">✓ Validé</span>
                                    @elseif($entreprise->siren_valide === false)
                                        <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">✗ Refusé</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">⏳ En attente</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($entreprise->siren_valide !== true)
                                        <form action="{{ route('admin.entreprises.validate-siren', $entreprise) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                                ✓ Vérifier
                                            </button>
                                        </form>
                                    @endif
                                    @if($entreprise->siren_valide !== false)
                                        <button 
                                            onclick="document.getElementById('modal-refus-siren').classList.remove('hidden')"
                                            class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition"
                                        >
                                            ✗ Refuser
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2"><strong>SIREN :</strong> {{ $entreprise->siren }}</p>
                            @if($entreprise->siren_valide === false && $entreprise->siren_refus_raison)
                                <p class="text-sm text-red-600 dark:text-red-400 mt-2"><strong>Raison du refus :</strong> {{ $entreprise->siren_refus_raison }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- Actions globales -->
                    <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                        <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Actions globales</h3>
                        <div class="flex flex-wrap gap-3">
                            @if($entreprise->tousElementsValides() && !$entreprise->est_verifiee)
                                <form action="{{ route('admin.entreprises.validate', $entreprise) }}" method="POST" onsubmit="return confirm('Valider cette entreprise ? Tous les éléments sont validés.');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                        ✓ Valider l'entreprise
                                    </button>
                                </form>
                            @endif
                            
                            @if(!$entreprise->tousElementsValides() || $entreprise->aDesRefus())
                                <button 
                                    onclick="document.getElementById('modal-refus-global').classList.remove('hidden')"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition"
                                >
                                    ✗ Refuser l'entreprise
                                </button>
                            @endif

                            <form action="{{ route('admin.entreprises.renvoyer', $entreprise) }}" method="POST" onsubmit="return confirm('Renvoyer cette entreprise pour correction ? Tous les statuts de vérification seront réinitialisés.');">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                    🔄 Renvoyer pour correction
                                </button>
                            </form>
                        </div>
                        @if($entreprise->raison_refus_globale)
                            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                <p class="text-sm text-red-800 dark:text-red-400"><strong>Raison du refus global :</strong> {{ $entreprise->raison_refus_globale }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Informations</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Email</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entreprise->email }}</dd>
                            </div>
                            @if($entreprise->telephone)
                                <div>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Téléphone</dt>
                                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entreprise->telephone }}</dd>
                                </div>
                            @endif
                            @if($entreprise->ville)
                                <div>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Ville</dt>
                                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entreprise->ville }}</dd>
                                </div>
                            @endif
                            @if($entreprise->description)
                                <div>
                                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Description</dt>
                                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entreprise->description }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Statut juridique</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $entreprise->status_juridique }}</dd>
                            </div>
                        </dl>
                    </div>

                    @if($entreprise->reservations->count() > 0)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Réservations ({{ $entreprise->reservations->count() }})</h2>
                            <div class="space-y-3">
                                @foreach($entreprise->reservations->take(10) as $reservation)
                                    <div class="p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-slate-900 dark:text-white">{{ $reservation->user->name }}</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    {{ $reservation->date_reservation->format('d/m/Y à H:i') }} - {{ number_format($reservation->prix, 2, ',', ' ') }} €
                                                </p>
                                            </div>
                                            <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                                Voir →
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Gérant</h2>
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $entreprise->user->name }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $entreprise->user->email }}</p>
                            <a href="{{ route('admin.users.show', $entreprise->user) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400">
                                Voir le profil →
                            </a>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mt-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Statut</h2>
                        <div class="space-y-2">
                            @if($entreprise->est_verifiee)
                                <span class="inline-block px-3 py-1 text-sm bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Vérifiée</span>
                            @else
                                <span class="inline-block px-3 py-1 text-sm bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">En attente de vérification</span>
                            @endif
                        </div>
                    </div>

                    @if($entreprise->siren_verifie)
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl shadow-sm border border-green-200 dark:border-green-800 p-6 mt-6">
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">Facturation</h2>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                Cette entreprise peut générer des factures automatiquement pour les réservations payées.
                            </p>
                            <a href="{{ route('factures.entreprise', $entreprise->slug) }}" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400 font-medium">
                                Voir les factures →
                            </a>
                        </div>
                    @endif

                    <!-- Options d'entreprise -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800 p-6 mt-6">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">⚡ Options d'entreprise</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                            Gérez les abonnements et options de cette entreprise.
                        </p>
                        <a href="{{ route('admin.entreprises.options', $entreprise) }}" class="inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                            Gérer les options
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal refus nom -->
        <div id="modal-refus-nom" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Refuser le nom</h3>
                <form action="{{ route('admin.entreprises.reject-nom', $entreprise) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Raison du refus *
                        </label>
                        <textarea 
                            name="raison" 
                            rows="4"
                            required
                            placeholder="Expliquez pourquoi le nom est refusé..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        ></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                            Refuser
                        </button>
                        <button 
                            type="button"
                            onclick="document.getElementById('modal-refus-nom').classList.add('hidden')"
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal refus SIREN -->
        <div id="modal-refus-siren" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Refuser le SIREN</h3>
                <form action="{{ route('admin.entreprises.reject-siren', $entreprise) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Raison du refus *
                        </label>
                        <textarea 
                            name="raison" 
                            rows="4"
                            required
                            placeholder="Expliquez pourquoi le SIREN est refusé..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        ></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                            Refuser
                        </button>
                        <button 
                            type="button"
                            onclick="document.getElementById('modal-refus-siren').classList.add('hidden')"
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal refus global -->
        <div id="modal-refus-global" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Refuser l'entreprise</h3>
                <form action="{{ route('admin.entreprises.reject', $entreprise) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Raison du refus *
                        </label>
                        <textarea 
                            name="raison" 
                            rows="4"
                            required
                            placeholder="Expliquez pourquoi l'entreprise est refusée..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        ></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                            Refuser l'entreprise
                        </button>
                        <button 
                            type="button"
                            onclick="document.getElementById('modal-refus-global').classList.add('hidden')"
                            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>

