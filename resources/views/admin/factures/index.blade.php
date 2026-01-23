@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Gestion des factures
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Consultez et gérez toutes les factures du système.
            </p>
        </div>
        <div class="flex gap-3">
            <form action="{{ route('admin.factures.generate-subscription') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Générer factures abonnements
                </button>
            </form>
            <a href="{{ route('admin.factures.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Créer une facture
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <ul class="text-sm text-red-800 dark:text-red-400">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filtres -->
    <form method="GET" action="{{ route('admin.factures.index') }}" class="mb-6 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Numéro, entreprise, client..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type</label>
                <select name="type_facture" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Tous</option>
                    <option value="reservation" {{ request('type_facture') === 'reservation' ? 'selected' : '' }}>Réservation</option>
                    <option value="abonnement_manuel" {{ request('type_facture') === 'abonnement_manuel' ? 'selected' : '' }}>Abonnement manuel</option>
                    <option value="abonnement_entreprise" {{ request('type_facture') === 'abonnement_entreprise' ? 'selected' : '' }}>Abonnement entreprise</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Statut</label>
                <select name="statut" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Tous</option>
                    <option value="emise" {{ request('statut') === 'emise' ? 'selected' : '' }}>Émise</option>
                    <option value="payee" {{ request('statut') === 'payee' ? 'selected' : '' }}>Payée</option>
                    <option value="annulee" {{ request('statut') === 'annulee' ? 'selected' : '' }}>Annulée</option>
                    <option value="brouillon" {{ request('statut') === 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Filtrer</button>
                <a href="{{ route('admin.factures.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition">Réinitialiser</a>
            </div>
        </div>
    </form>

    <!-- Liste des factures -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Numéro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Entreprise</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Montant TTC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($factures as $facture)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Numéro">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $facture->numero_facture }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Type">
                                <span class="text-xs px-2 py-1 rounded-full
                                    @if($facture->type_facture === 'reservation') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif($facture->type_facture === 'abonnement_manuel') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                    @else bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                    @endif">
                                    @if($facture->type_facture === 'reservation')
                                        Réservation
                                    @elseif($facture->type_facture === 'abonnement_manuel')
                                        Abonnement manuel
                                    @else
                                        Abonnement entreprise
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Entreprise">
                                <span class="text-sm text-slate-900 dark:text-white">
                                    {{ $facture->entreprise ? $facture->entreprise->nom : 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Client">
                                <span class="text-sm text-slate-900 dark:text-white">
                                    {{ $facture->user ? $facture->user->name : 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Date">
                                <span class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ $facture->date_facture->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Montant TTC">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ number_format($facture->montant_ttc, 2, ',', ' ') }} €
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                                <span class="text-xs px-2 py-1 rounded-full
                                    @if($facture->statut === 'payee') bg-green-100 text-green-800 dark:bg-green-900/30 text-green-400
                                    @elseif($facture->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($facture->statut === 'brouillon') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @endif">
                                    {{ ucfirst($facture->statut) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Actions">
                                <a href="{{ route('admin.factures.show', $facture->id) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                    Voir
                                </a>
                            </td>
                        </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                            Aucune facture trouvée.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $factures->links() }}
    </div>
</div>
@endsection
