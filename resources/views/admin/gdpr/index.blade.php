@extends('admin.layout')

@section('title', 'RGPD')
@section('header', 'RGPD — Protection des données')
@section('subheader', 'Gestion des demandes d\'export et de suppression')

@section('content')
<div class="space-y-6">

    <!-- Statistiques -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_deletions'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Suppressions en attente</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['pending_exports'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Exports en cours</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed_total'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Demandes traitées</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="text-2xl font-bold text-slate-700 dark:text-slate-300">{{ $stats['total'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Total</div>
        </div>
    </div>

    <!-- Configuration délai + Actions admin -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configuration du délai de grâce -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Délai de grâce</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Nombre de jours entre la demande de suppression et son exécution effective. L'utilisateur peut annuler pendant ce délai.
            </p>
            <form action="{{ route('admin.gdpr.update-delay') }}" method="POST" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Délai (jours)</label>
                    <input type="number" name="delay_days" value="{{ $delayDays }}" min="0" max="365" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-green-500 focus:border-green-500">
                </div>
                <button type="submit" class="ui-btn-simple px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                    Enregistrer
                </button>
            </form>
        </div>

        <!-- Créer une demande (admin) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Action manuelle</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Générer un export ou créer une demande de suppression pour un utilisateur (demande institutionnelle, etc.).
            </p>
            <div x-data="{ action: 'export', userId: '', userName: '', searchResults: [], searching: false }" class="space-y-3">
                <!-- Recherche utilisateur -->
                <div class="relative">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Utilisateur</label>
                    <input
                        type="text"
                        placeholder="Rechercher par nom ou email..."
                        x-on:input.debounce.300ms="
                            if ($event.target.value.length >= 2) {
                                searching = true;
                                fetch('{{ route('admin.gdpr.search-users') }}?q=' + encodeURIComponent($event.target.value))
                                    .then(r => r.json())
                                    .then(data => { searchResults = data; searching = false; });
                            } else { searchResults = []; }
                        "
                        x-bind:value="userName"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-green-500 focus:border-green-500"
                    >
                    <div x-show="searchResults.length > 0" x-cloak class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="user in searchResults" :key="user.id">
                            <button
                                type="button"
                                x-on:click="userId = user.id; userName = user.name + ' (' + user.email + ')'; searchResults = [];"
                                class="w-full px-4 py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-600 text-sm"
                            >
                                <span x-text="user.name" class="font-medium text-slate-900 dark:text-white"></span>
                                <span x-text="user.email" class="text-slate-500 dark:text-slate-400 ml-2"></span>
                                <span x-show="user.est_gerant" class="ml-1 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 px-1 rounded">Gérant</span>
                                <span x-show="user.est_client" class="ml-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-1 rounded">Client</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Type d'action -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Action</label>
                    <select x-model="action" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-green-500 focus:border-green-500">
                        <option value="export">Générer un export</option>
                        <option value="deletion">Demander la suppression</option>
                    </select>
                </div>

                <!-- Raison -->
                <div x-show="action === 'deletion'" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Raison (obligatoire)</label>
                    <textarea id="admin-reason" rows="2" maxlength="1000" placeholder="Ex: Demande CNIL, demande client par email..." class="ui-textarea w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-green-500 focus:border-green-500"></textarea>
                </div>

                <!-- Boutons d'action -->
                <div class="flex gap-2">
                    <template x-if="action === 'export'">
                        <form method="POST" action="{{ route('admin.gdpr.generate-export') }}" class="inline">
                            @csrf
                            <input type="hidden" name="user_id" x-bind:value="userId">
                            <input type="hidden" name="reason" value="">
                            <button type="submit" x-bind:disabled="!userId" class="ui-btn-simple px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg transition">
                                Générer l'export
                            </button>
                        </form>
                    </template>
                    <template x-if="action === 'deletion'">
                        <form method="POST" action="{{ route('admin.gdpr.request-deletion') }}" class="inline" x-on:submit="$event.target.querySelector('[name=reason]').value = document.getElementById('admin-reason').value;">
                            @csrf
                            <input type="hidden" name="user_id" x-bind:value="userId">
                            <input type="hidden" name="reason" value="">
                            <button type="submit" x-bind:disabled="!userId" onclick="return confirm('Confirmer la demande de suppression pour cet utilisateur ?')" class="ui-btn-simple px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg transition">
                                Demander la suppression
                            </button>
                        </form>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Type</label>
                <select name="type" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-green-500">
                    <option value="">Tous</option>
                    <option value="export" {{ request('type') === 'export' ? 'selected' : '' }}>Export</option>
                    <option value="deletion" {{ request('type') === 'deletion' ? 'selected' : '' }}>Suppression</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Statut</label>
                <select name="status" class="rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-green-500">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>En cours</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminée</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échouée</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom ou email..." class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-green-500">
            </div>
            <button type="submit" class="ui-btn-simple px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-lg text-sm transition">
                Filtrer
            </button>
            @if(request()->hasAny(['type', 'status', 'search']))
                <a href="{{ route('admin.gdpr.index') }}" class="px-4 py-2.5 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 text-sm">
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <!-- Tableau des demandes -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3">Utilisateur</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3">Initié par</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Exécution prévue</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($requests as $req)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3" data-label="Utilisateur">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <div class="font-medium text-slate-900 dark:text-white">{{ $req->user?->name ?? 'Supprimé' }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $req->user?->email ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3" data-label="Type">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $req->isExport() ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' }}">
                                    {{ $req->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3" data-label="Statut">
                                @php
                                    $sc = [
                                        'pending' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
                                        'processing' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
                                        'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
                                        'cancelled' => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400',
                                        'failed' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $sc[$req->status] ?? '' }}">
                                    {{ $req->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400" data-label="Initié par">
                                {{ $req->requestedBy ? $req->requestedBy->name . ' (admin)' : 'Self-service' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400" data-label="Date">
                                {{ $req->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400" data-label="Exécution">
                                @if($req->isDeletion() && $req->isPending() && $req->scheduled_at)
                                    <span class="text-orange-600 dark:text-orange-400 font-medium">
                                        {{ $req->scheduled_at->format('d/m/Y') }}
                                        <span class="text-xs">(J-{{ $req->daysUntilExecution() }})</span>
                                    </span>
                                @elseif($req->processed_at)
                                    {{ $req->processed_at->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3" data-label="Actions">
                                <div class="flex items-center gap-1">
                                    @if($req->isExport() && $req->isCompleted() && $req->export_path)
                                        <a href="{{ route('admin.gdpr.download-export', $req) }}" class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded text-xs font-medium hover:bg-green-200 dark:hover:bg-green-900/50 transition">
                                            Télécharger
                                        </a>
                                    @endif

                                    @if($req->canBeCancelled())
                                        <form action="{{ route('admin.gdpr.cancel', $req) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Annuler cette demande ?')" class="ui-btn-simple px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded text-xs font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                                                Annuler
                                            </button>
                                        </form>
                                    @endif

                                    @if($req->isDeletion() && $req->isPending())
                                        <form action="{{ route('admin.gdpr.execute-now', $req) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('ATTENTION : Cela va anonymiser immédiatement cet utilisateur. Êtes-vous sûr ?')" class="ui-btn-simple px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded text-xs font-medium hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                                Exécuter maintenant
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                Aucune demande RGPD pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
                {{ $requests->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
