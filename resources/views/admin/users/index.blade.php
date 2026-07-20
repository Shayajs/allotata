@extends('admin.layout')

@section('title', 'Gestion des utilisateurs')
@section('header', 'Utilisateurs')
@section('subheader', 'Gérez tous les utilisateurs de la plateforme')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Liste des utilisateurs</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Gérez tous les utilisateurs de la plateforme</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.users.deleted') }}" class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition border border-red-200 dark:border-red-900/30 rounded-lg">
            📦 Comptes supprimés
        </a>
        <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition border border-slate-200 dark:border-slate-700 rounded-lg">
            ← Retour au Dashboard
        </a>
    </div>
</div>

<!-- Barre de recherche et filtres -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <form method="GET" action="{{ route('admin.users.index') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Filtrer par rôle
                </label>
                <select 
                    name="role" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                    <option value="">Tous les rôles</option>
                    <option value="client" {{ request('role') === 'client' ? 'selected' : '' }}>Client</option>
                    <option value="gerant" {{ request('role') === 'gerant' ? 'selected' : '' }}>Gérant</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Filtrer par statut
                </label>
                <select 
                    name="statut" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                    <option value="">Tous les statuts</option>
                    <option value="normal" {{ request('statut') === 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="limite" {{ request('statut') === 'limite' ? 'selected' : '' }}>Limité</option>
                    <option value="interdit" {{ request('statut') === 'interdit' ? 'selected' : '' }}>Interdit</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Email vérifié
                </label>
                <select 
                    name="email_verified" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                    <option value="">Tous</option>
                    <option value="1" {{ request('email_verified') === '1' ? 'selected' : '' }}>Vérifiés</option>
                    <option value="0" {{ request('email_verified') === '0' ? 'selected' : '' }}>Non vérifiés</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="ui-btn-simple w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    🔍 Rechercher
                </button>
            </div>
        </div>
        @if(request()->hasAny(['search', 'role', 'statut', 'email_verified']))
            <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                Réinitialiser les filtres
            </a>
        @endif
    </form>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto table-responsive-to-cards">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Email vérifié</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Rôles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Entreprises</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Réservations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Inscrit le</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4 whitespace-nowrap" data-user-id="{{ $user->id }}" data-label="Nom">
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$user" size="sm" />
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $user->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                            <x-presence-badge :user="$user" size="md" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Email">
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Email vérifié">
                            @if($user->hasVerifiedEmail())
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Vérifié
                                </span>
                            @else
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Non vérifié
                                    </span>
                                    <form action="{{ route('admin.email-logs.verify-user', $user) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('Vérifier manuellement cet email ?')"
                                                class="px-2 py-1 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded transition-colors"
                                                title="Vérifier manuellement l'email">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Rôles">
                            <div class="flex flex-col gap-2">
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
                                @php
                                    $statut = $user->statut_compte ?? 'normal';
                                    $statutConfig = [
                                        'normal' => ['label' => 'Normal', 'color' => 'green'],
                                        'limite' => ['label' => 'Limité', 'color' => 'yellow'],
                                        'interdit' => ['label' => 'Interdit', 'color' => 'red'],
                                        'supprime' => ['label' => 'Supprimé', 'color' => 'gray'],
                                    ];
                                    $config = $statutConfig[$statut] ?? $statutConfig['normal'];
                                @endphp
                                @if($statut !== 'normal')
                                    <span class="px-2 py-1 text-xs font-bold rounded
                                        @if($config['color'] === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                        @elseif($config['color'] === 'red') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400
                                        @else bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                                        @endif">
                                        {{ $config['label'] }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Entreprises">
                            {{ $user->entreprises_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Réservations">
                            {{ $user->reservations_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Inscrit le">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Actions">
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                @if(auth()->id() !== $user->id)
                                    @php
                                        $viewAccessUrl = $accountAccess->impersonationUrl('dashboard', $user, \App\Services\AccountAccessService::MODE_VIEW);
                                        $supportAccessUrl = $accountAccess->impersonationUrl('dashboard', $user, \App\Services\AccountAccessService::MODE_SUPPORT);
                                        $billingAccessUrl = $accountAccess->impersonationUrl('dashboard', $user, \App\Services\AccountAccessService::MODE_BILLING);
                                        $editAccessUrl = $accountAccess->impersonationUrl('dashboard', $user, \App\Services\AccountAccessService::MODE_EDIT);
                                    @endphp
                                    <a href="{{ $viewAccessUrl }}"
                                       class="ui-btn-simple text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2.5 py-1.5 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
                                       title="Observer le compte de {{ $user->name }} (lecture seule)">
                                        Observer
                                    </a>
                                    <a href="{{ $supportAccessUrl }}"
                                       class="ui-btn-simple text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-2.5 py-1.5 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors"
                                       title="Support — tickets et messagerie">
                                        Support
                                    </a>
                                    <a href="{{ $billingAccessUrl }}"
                                       class="ui-btn-simple text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2.5 py-1.5 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors"
                                       title="Facturation — abonnements et paiements">
                                        Facturation
                                    </a>
                                    <a href="{{ $editAccessUrl }}"
                                       class="ui-btn-simple text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2.5 py-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                                       title="Contrôler le compte de {{ $user->name }} (édition)">
                                        Contrôler
                                    </a>
                                    <div class="flex flex-col gap-1 w-full max-w-[240px] mt-1">
                                        <div class="flex items-center gap-1">
                                            <input type="text" readonly value="{{ $viewAccessUrl }}"
                                                   class="text-[10px] flex-1 px-1.5 py-1 rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                                   id="view-url-{{ $user->id }}">
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText(document.getElementById('view-url-{{ $user->id }}').value)"
                                                    class="text-[10px] px-1.5 py-1 rounded bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600"
                                                    title="Copier lien VIEW">
                                                VIEW
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="text" readonly value="{{ $supportAccessUrl }}"
                                                   class="text-[10px] flex-1 px-1.5 py-1 rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                                   id="support-url-{{ $user->id }}">
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText(document.getElementById('support-url-{{ $user->id }}').value)"
                                                    class="text-[10px] px-1.5 py-1 rounded bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600"
                                                    title="Copier lien SUPPORT">
                                                SUP
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="text" readonly value="{{ $billingAccessUrl }}"
                                                   class="text-[10px] flex-1 px-1.5 py-1 rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                                   id="billing-url-{{ $user->id }}">
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText(document.getElementById('billing-url-{{ $user->id }}').value)"
                                                    class="text-[10px] px-1.5 py-1 rounded bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600"
                                                    title="Copier lien BILLING">
                                                BILL
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="text" readonly value="{{ $editAccessUrl }}"
                                                   class="text-[10px] flex-1 px-1.5 py-1 rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                                   id="edit-url-{{ $user->id }}">
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText(document.getElementById('edit-url-{{ $user->id }}').value)"
                                                    class="text-[10px] px-1.5 py-1 rounded bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600"
                                                    title="Copier lien EDIT">
                                                EDIT
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                <a href="{{ route('admin.users.show', $user) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 font-semibold">
                                    Voir
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                            Aucun utilisateur trouvé
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

