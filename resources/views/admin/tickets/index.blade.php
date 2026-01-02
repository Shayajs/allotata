@extends('admin.layout')

@section('title', 'Gestion des tickets')
@section('header', 'Tickets Support')
@section('subheader', 'Gérez les demandes de support des utilisateurs')

@section('content')
    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Ouverts</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $tickets->where('statut', 'ouvert')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-lg">📬</span>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">En cours</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $tickets->where('statut', 'en_cours')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-lg">⚙️</span>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Résolus</p>
                    <p class="text-2xl font-bold text-green-600">{{ $tickets->where('statut', 'resolu')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-lg">✅</span>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Non assignés</p>
                    <p class="text-2xl font-bold text-red-600">{{ $tickets->whereNull('assigne_a')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <span class="text-lg">⚠️</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Rechercher</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Numéro, sujet, utilisateur..."
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                    <select name="statut" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Tous</option>
                        <option value="ouvert" {{ request('statut') === 'ouvert' ? 'selected' : '' }}>Ouvert</option>
                        <option value="en_cours" {{ request('statut') === 'en_cours' ? 'selected' : '' }}>En cours</option>
                        <option value="resolu" {{ request('statut') === 'resolu' ? 'selected' : '' }}>Résolu</option>
                        <option value="ferme" {{ request('statut') === 'ferme' ? 'selected' : '' }}>Fermé</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Priorité</label>
                    <select name="priorite" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Toutes</option>
                        <option value="basse" {{ request('priorite') === 'basse' ? 'selected' : '' }}>Basse</option>
                        <option value="normale" {{ request('priorite') === 'normale' ? 'selected' : '' }}>Normale</option>
                        <option value="haute" {{ request('priorite') === 'haute' ? 'selected' : '' }}>Haute</option>
                        <option value="urgente" {{ request('priorite') === 'urgente' ? 'selected' : '' }}>Urgente</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Catégorie</label>
                    <select name="categorie" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Toutes</option>
                        <option value="technique" {{ request('categorie') === 'technique' ? 'selected' : '' }}>Technique</option>
                        <option value="facturation" {{ request('categorie') === 'facturation' ? 'selected' : '' }}>Facturation</option>
                        <option value="compte" {{ request('categorie') === 'compte' ? 'selected' : '' }}>Compte</option>
                        <option value="autre" {{ request('categorie') === 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Assigné à</label>
                    <select name="assigne_a" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Tous</option>
                        <option value="non_assignes" {{ request('assigne_a') === 'non_assignes' ? 'selected' : '' }}>Non assignés</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ request('assigne_a') == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    🔍 Filtrer
                </button>
                @if(request()->hasAny(['search', 'statut', 'priorite', 'categorie', 'assigne_a']))
                    <a href="{{ route('admin.tickets.index') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                        Réinitialiser les filtres
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Liste des tickets -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Catégorie</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Priorité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Assigné à</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Créé le</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $ticket->numero_ticket }}</div>
                                <div class="text-sm text-slate-600 dark:text-slate-400 truncate max-w-xs">{{ $ticket->sujet }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $ticket->user->name }}</div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $ticket->user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $prioriteColors = [
                                        'basse' => 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-400',
                                        'normale' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'haute' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                        'urgente' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded {{ $prioriteColors[$ticket->priorite] ?? $prioriteColors['normale'] }}">
                                    {{ ucfirst($ticket->priorite) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statutColors = [
                                        'ouvert' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                        'en_cours' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'resolu' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'ferme' => 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-400',
                                    ];
                                    $statutLabels = [
                                        'ouvert' => 'Ouvert',
                                        'en_cours' => 'En cours',
                                        'resolu' => 'Résolu',
                                        'ferme' => 'Fermé',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded {{ $statutColors[$ticket->statut] ?? $statutColors['ouvert'] }}">
                                    {{ $statutLabels[$ticket->statut] ?? $ticket->statut }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                @if($ticket->assigneA)
                                    {{ $ticket->assigneA->name }}
                                @else
                                    <span class="text-red-500">Non assigné</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                    Voir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                                Aucun ticket trouvé
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $tickets->links() }}
        </div>
    </div>
@endsection
