<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Ticket {{ $ticket->numero_ticket }} - Admin</title>
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
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">🎫 {{ $ticket->numero_ticket }}</h1>
                    <p class="text-slate-600 dark:text-slate-400">{{ $ticket->sujet }}</p>
                </div>
                <a href="{{ route('admin.tickets.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Retour à la liste
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Colonne principale : Conversation -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description initiale -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Description</h2>
                        <div class="prose dark:prose-invert max-w-none">
                            <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $ticket->description }}</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 text-sm text-slate-500 dark:text-slate-400">
                            Créé le {{ $ticket->created_at->format('d/m/Y à H:i') }}
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Conversation ({{ $messages->count() }} messages)</h2>
                        
                        <div class="space-y-4 max-h-96 overflow-y-auto mb-6">
                            @forelse($messages as $message)
                                <div class="p-4 rounded-lg {{ $message->user->is_admin ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-slate-50 dark:bg-slate-700' }} {{ $message->est_interne ? 'border-l-4 border-l-yellow-500' : '' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $message->user->name }}</span>
                                            @if($message->user->is_admin)
                                                <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Admin</span>
                                            @endif
                                            @if($message->est_interne)
                                                <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">Note interne</span>
                                            @endif
                                        </div>
                                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $message->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $message->message }}</p>
                                </div>
                            @empty
                                <p class="text-center text-slate-500 dark:text-slate-400 py-8">Aucun message pour le moment</p>
                            @endforelse
                        </div>

                        <!-- Formulaire de réponse -->
                        <form method="POST" action="{{ route('admin.tickets.message', $ticket) }}" class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Votre réponse</label>
                                <textarea 
                                    name="message" 
                                    rows="4" 
                                    required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                                    placeholder="Tapez votre réponse..."
                                ></textarea>
                            </div>
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="est_interne" value="1" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-yellow-600 focus:ring-yellow-500">
                                    <span class="text-sm text-slate-600 dark:text-slate-400">Note interne (invisible pour l'utilisateur)</span>
                                </label>
                                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                    Envoyer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Colonne latérale : Infos et actions -->
                <div class="space-y-6">
                    <!-- Infos utilisateur -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">👤 Utilisateur</h2>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-slate-500 dark:text-slate-400">Nom</span>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $ticket->user->name }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-slate-500 dark:text-slate-400">Email</span>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $ticket->user->email }}</p>
                            </div>
                            <a href="{{ route('admin.users.show', $ticket->user) }}" class="inline-block text-sm text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300">
                                Voir le profil →
                            </a>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">⚙️ Actions</h2>
                        <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                                <select name="statut" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="ouvert" {{ $ticket->statut === 'ouvert' ? 'selected' : '' }}>Ouvert</option>
                                    <option value="en_cours" {{ $ticket->statut === 'en_cours' ? 'selected' : '' }}>En cours</option>
                                    <option value="resolu" {{ $ticket->statut === 'resolu' ? 'selected' : '' }}>Résolu</option>
                                    <option value="ferme" {{ $ticket->statut === 'ferme' ? 'selected' : '' }}>Fermé</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Priorité</label>
                                <select name="priorite" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="basse" {{ $ticket->priorite === 'basse' ? 'selected' : '' }}>Basse</option>
                                    <option value="normale" {{ $ticket->priorite === 'normale' ? 'selected' : '' }}>Normale</option>
                                    <option value="haute" {{ $ticket->priorite === 'haute' ? 'selected' : '' }}>Haute</option>
                                    <option value="urgente" {{ $ticket->priorite === 'urgente' ? 'selected' : '' }}>Urgente</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Assigner à</label>
                                <select name="assigne_a" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="">Non assigné</option>
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}" {{ $ticket->assigne_a == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                Mettre à jour
                            </button>
                        </form>
                    </div>

                    <!-- Infos ticket -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">📋 Informations</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500 dark:text-slate-400">Catégorie</span>
                                @php
                                    $categorieColors = [
                                        'technique' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                        'facturation' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'compte' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'autre' => 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-400',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded {{ $categorieColors[$ticket->categorie] ?? $categorieColors['autre'] }}">
                                    {{ ucfirst($ticket->categorie) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500 dark:text-slate-400">Créé le</span>
                                <span class="text-sm text-slate-900 dark:text-white">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($ticket->resolu_at)
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Résolu le</span>
                                    <span class="text-sm text-slate-900 dark:text-white">{{ $ticket->resolu_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500 dark:text-slate-400">Mis à jour le</span>
                                <span class="text-sm text-slate-900 dark:text-white">{{ $ticket->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
