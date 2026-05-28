@extends('admin.layout')

@section('title', 'Messages de contact')
@section('header', 'Messages de Contact')
@section('subheader', 'Gérez les messages envoyés via le formulaire de contact')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Messages de Contact</h1>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Total</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $contacts->total() }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Non lus</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $contacts->where('est_lu', false)->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 flex-shrink-0 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Lus</p>
                    <p class="text-2xl font-bold text-green-600">{{ $contacts->where('est_lu', true)->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 flex-shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <form method="GET" action="{{ route('admin.contacts.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Rechercher</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Nom, email, sujet..."
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                    <select name="est_lu" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">Tous</option>
                        <option value="0" {{ request('est_lu') === '0' ? 'selected' : '' }}>Non lus</option>
                        <option value="1" {{ request('est_lu') === '1' ? 'selected' : '' }}>Lus</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ui-btn-simple w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        🔍 Rechercher
                    </button>
                </div>
            </div>
            @if(request()->hasAny(['search', 'est_lu']))
                <a href="{{ route('admin.contacts.index') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                    Réinitialiser les filtres
                </a>
            @endif
        </form>
    </div>

    <!-- Liste des contacts -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Expéditeur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Sujet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($contacts as $contact)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 {{ !$contact->est_lu ? 'bg-orange-50/50 dark:bg-orange-900/10' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                                @if($contact->est_lu)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Lu</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Non lu</span>
                                @endif
                            </td>
                            <td class="px-6 py-4" data-label="Expéditeur">
                                <div class="text-sm font-medium text-slate-900 dark:text-white {{ !$contact->est_lu ? 'font-bold' : '' }}">{{ $contact->nom }}</div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $contact->email }}</div>
                            </td>
                            <td class="px-6 py-4" data-label="Sujet">
                                <div class="text-sm text-slate-900 dark:text-white truncate max-w-xs {{ !$contact->est_lu ? 'font-semibold' : '' }}">{{ $contact->sujet }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Date">
                                {{ $contact->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Actions">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.contacts.show', $contact) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        Voir
                                    </a>
                                    <form method="POST" action="{{ route('admin.contacts.toggle-read', $contact) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="ui-btn-simple text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $contact->est_lu ? 'Non lu' : 'Lu' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-btn-simple text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                                Aucun message de contact trouvé
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $contacts->links() }}
        </div>
    </div>
@endsection
