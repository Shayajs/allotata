<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $ticket->numero_ticket }} - Allo Tata</title>
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
                    <a href="{{ route('tickets.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                        Mes tickets
                    </a>
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                        Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">🎫 Ticket #{{ $ticket->numero_ticket }}</h1>
                <p class="text-slate-600 dark:text-slate-400">{{ $ticket->sujet }}</p>
            </div>
            <a href="{{ route('tickets.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                ← Retour
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Colonne principale : Messages -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Description initiale -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($ticket->user->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $ticket->user->name ?? 'Utilisateur' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $ticket->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Description</h2>
                    <div class="prose dark:prose-invert max-w-none">
                        <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $ticket->description }}</p>
                    </div>
                </div>

                <!-- Messages -->
                @if($messages->count() > 0)
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Messages</h2>
                        @foreach($messages as $message)
                            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                                        {{ strtoupper(substr($message->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ $message->user->name ?? 'Utilisateur' }}
                                            @if($message->user->is_admin)
                                                <span class="ml-2 px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">Admin</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $message->created_at->format('d/m/Y à H:i') }}</p>
                                    </div>
                                </div>
                                <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $message->message }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Formulaire de réponse -->
                @if($ticket->statut !== 'ferme')
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Ajouter un message</h3>
                        <form action="{{ route('tickets.add-message', $ticket) }}" method="POST">
                            @csrf
                            <textarea 
                                name="message" 
                                rows="5"
                                required
                                placeholder="Votre message..."
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white mb-4"
                            ></textarea>
                            @error('message')
                                <p class="mb-4 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <button 
                                type="submit" 
                                class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                            >
                                Envoyer
                            </button>
                        </form>
                    </div>
                @else
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-700 p-6 text-center">
                        <p class="text-slate-600 dark:text-slate-400">Ce ticket est fermé. Vous ne pouvez plus y répondre.</p>
                    </div>
                @endif
            </div>

            <!-- Colonne latérale : Informations -->
            <div class="space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Statut</p>
                            <span class="inline-block px-3 py-1 text-sm font-medium rounded-full
                                @if($ticket->statut == 'ouvert') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                @elseif($ticket->statut == 'en_cours') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                @elseif($ticket->statut == 'resolu') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300
                                @endif
                            ">
                                {{ ucfirst(str_replace('_', ' ', $ticket->statut)) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Priorité</p>
                            <span class="inline-block px-3 py-1 text-sm font-medium rounded-full
                                @if($ticket->priorite == 'urgente') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                @elseif($ticket->priorite == 'haute') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                @elseif($ticket->priorite == 'basse') bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300
                                @else bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                @endif
                            ">
                                {{ ucfirst($ticket->priorite) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Catégorie</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white capitalize">{{ $ticket->categorie }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Créé le</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $ticket->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        @if($ticket->resolu_at)
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Résolu le</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $ticket->resolu_at->format('d/m/Y à H:i') }}</p>
                            </div>
                        @endif
                        @if($ticket->assigneA)
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Assigné à</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $ticket->assigneA->name }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
