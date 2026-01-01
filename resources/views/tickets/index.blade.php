<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes tickets de support - Allo Tata</title>
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
                        Dashboard
                    </a>
                    <a href="{{ route('tickets.create') }}" class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white rounded-lg transition">
                        + Nouveau ticket
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">🎫 Mes tickets de support</h1>
                <p class="text-slate-600 dark:text-slate-400">Suivez l'état de vos demandes de support</p>
            </div>
            <a href="{{ route('tickets.create') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                + Créer un ticket
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Filtres -->
        <div class="mb-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <select 
                        name="statut" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="ouvert" {{ request('statut') == 'ouvert' ? 'selected' : '' }}>Ouvert</option>
                        <option value="en_cours" {{ request('statut') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                        <option value="resolu" {{ request('statut') == 'resolu' ? 'selected' : '' }}>Résolu</option>
                        <option value="ferme" {{ request('statut') == 'ferme' ? 'selected' : '' }}>Fermé</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <select 
                        name="categorie" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">Toutes les catégories</option>
                        <option value="technique" {{ request('categorie') == 'technique' ? 'selected' : '' }}>Technique</option>
                        <option value="facturation" {{ request('categorie') == 'facturation' ? 'selected' : '' }}>Facturation</option>
                        <option value="compte" {{ request('categorie') == 'compte' ? 'selected' : '' }}>Compte</option>
                        <option value="autre" {{ request('categorie') == 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Liste des tickets -->
        @if($tickets->count() > 0)
            <div class="space-y-4">
                @foreach($tickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="block bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-all">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="font-mono text-sm text-slate-500 dark:text-slate-400">#{{ $ticket->numero_ticket }}</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($ticket->statut == 'ouvert') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                        @elseif($ticket->statut == 'en_cours') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                        @elseif($ticket->statut == 'resolu') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                        @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300
                                        @endif
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $ticket->statut)) }}
                                    </span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($ticket->priorite == 'urgente') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                        @elseif($ticket->priorite == 'haute') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                        @elseif($ticket->priorite == 'basse') bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300
                                        @else bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                        @endif
                                    ">
                                        {{ ucfirst($ticket->priorite) }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">{{ $ticket->sujet }}</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-3">{{ $ticket->description }}</p>
                                <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                                    <span>📅 {{ $ticket->created_at->format('d/m/Y à H:i') }}</span>
                                    <span class="capitalize">📁 {{ $ticket->categorie }}</span>
                                    @if($ticket->assigneA)
                                        <span>👤 Assigné à {{ $ticket->assigneA->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $tickets->links() }}
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucun ticket</h3>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Vous n'avez pas encore créé de ticket de support.
                </p>
                <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Créer mon premier ticket
                </a>
            </div>
        @endif
    </div>
</body>
</html>
