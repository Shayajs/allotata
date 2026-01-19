@extends('admin.layout')

@section('title', 'Comptes supprimés')
@section('header', 'Comptes supprimés')
@section('subheader', 'Comptes archivés (non supprimés définitivement)')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Comptes supprimés</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Comptes archivés mais conservés dans la base de données</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
            ← Retour aux utilisateurs
        </a>
        <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
            ← Retour au Dashboard
        </a>
    </div>
</div>

<!-- Barre de recherche -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <form method="GET" action="{{ route('admin.users.deleted') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Rechercher
                </label>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Nom, email..."
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    🔍 Rechercher
                </button>
            </div>
        </div>
        @if(request()->has('search'))
            <a href="{{ route('admin.users.deleted') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                Réinitialiser les filtres
            </a>
        @endif
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Rôles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Entreprises</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Réservations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Inscrit le</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$user" size="sm" />
                                <div class="text-sm font-medium text-red-300 dark:text-red-400">{{ $user->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-red-300 dark:text-red-400">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-2">
                                @if($user->est_client)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Client</span>
                                @endif
                                @if($user->est_gerant)
                                    <span class="px-2 py-1 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Gérant</span>
                                @endif
                                @if($user->is_admin)
                                    <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">Admin</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-400 rounded">
                                Supprimé
                            </span>
                            <form action="{{ route('admin.users.status.update', $user) }}" method="POST" class="inline mt-2" onchange="this.submit()">
                                @csrf
                                @method('POST')
                                <select name="statut_compte" class="text-xs px-2 py-1 border border-slate-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="normal" {{ ($user->statut_compte ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="limite" {{ ($user->statut_compte ?? 'normal') === 'limite' ? 'selected' : '' }}>Limité</option>
                                    <option value="interdit" {{ ($user->statut_compte ?? 'normal') === 'interdit' ? 'selected' : '' }}>Interdit</option>
                                    <option value="supprime" {{ ($user->statut_compte ?? 'normal') === 'supprime' ? 'selected' : '' }}>Supprimé</option>
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-300 dark:text-red-400">
                            {{ $user->entreprises_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-300 dark:text-red-400">
                            {{ $user->reservations_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-300 dark:text-red-400">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 font-semibold">
                                    Voir
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                            Aucun compte supprimé trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
        {{ $users->links() }}
    </div>
</div>
@endsection
