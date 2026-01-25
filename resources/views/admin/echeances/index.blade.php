@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Paiements et échéances
        </h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            Listing de tous les paiements (Premium + options entreprise). Gestion des états et réductions (gestes commerciaux).
        </p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-500">
            Réductions : <a href="{{ route('admin.custom-prices.index') }}" class="text-green-600 dark:text-green-400 hover:underline">Prix personnalisés</a> (user/entreprise),
            <a href="{{ route('admin.promo-codes.index') }}" class="text-green-600 dark:text-green-400 hover:underline">Codes promo</a>,
            ou <strong>Réduction</strong> ponctuelle sur une échéance (geste commercial).
        </p>
        <div class="mt-3">
            <a href="{{ route('admin.payment-audit-log.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Journal d'audit paiements (verbose)
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-800 dark:text-red-400">{{ session('error') }}</p>
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

    {{-- Stats par statut --}}
    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('admin.echeances.index', ['statut' => 'a_payer']) }}" class="px-3 py-1.5 rounded-lg text-sm {{ request('statut') === 'a_payer' ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            À payer ({{ $stats['a_payer'] }})
        </a>
        <a href="{{ route('admin.echeances.index', ['statut' => 'en_attente']) }}" class="px-3 py-1.5 rounded-lg text-sm {{ request('statut') === 'en_attente' ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            En cours ({{ $stats['en_attente'] }})
        </a>
        <a href="{{ route('admin.echeances.index', ['statut' => 'paye']) }}" class="px-3 py-1.5 rounded-lg text-sm {{ request('statut') === 'paye' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Payé ({{ $stats['paye'] }})
        </a>
        <a href="{{ route('admin.echeances.index', ['statut' => 'echec']) }}" class="px-3 py-1.5 rounded-lg text-sm {{ request('statut') === 'echec' ? 'bg-red-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Échoué ({{ $stats['echec'] }})
        </a>
        <a href="{{ route('admin.echeances.index', ['statut' => 'annule']) }}" class="px-3 py-1.5 rounded-lg text-sm {{ request('statut') === 'annule' ? 'bg-slate-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Annulé ({{ $stats['annule'] }})
        </a>
        <a href="{{ route('admin.echeances.index', ['statut' => 'arrete']) }}" class="px-3 py-1.5 rounded-lg text-sm {{ request('statut') === 'arrete' ? 'bg-slate-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Arrêté ({{ $stats['arrete'] }})
        </a>
        <a href="{{ route('admin.echeances.index') }}" class="px-3 py-1.5 rounded-lg text-sm {{ !request('statut') ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
            Tous
        </a>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.echeances.index') }}" class="mb-6 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @foreach(request()->only(['statut']) as $k => $v)
                @if($v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Recherche (nom, email)</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Membre..." class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Tous</option>
                    <option value="default" {{ request('type') === 'default' ? 'selected' : '' }}>Premium</option>
                    <option value="site_web" {{ request('type') === 'site_web' ? 'selected' : '' }}>Site Web</option>
                    <option value="multi_personnes" {{ request('type') === 'multi_personnes' ? 'selected' : '' }}>Multi-Personnes</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Période début</label>
                <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Période fin</label>
                <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">Filtrer</button>
                <a href="{{ route('admin.echeances.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition">Réinitialiser</a>
            </div>
        </div>
    </form>

    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Membre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Période</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Montant dû</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Réduc.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Payé le</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($echeances as $e)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4" data-label="Membre">
                                @if($e->user)
                                    <a href="{{ route('admin.users.show', $e->user) }}" class="text-sm font-medium text-green-600 dark:text-green-400 hover:underline">{{ $e->user->name }}</a>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $e->user->email }}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Type">
                                <span class="text-sm text-slate-900 dark:text-white">{{ $e->libelle() }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Période">
                                <span class="text-sm text-slate-600 dark:text-slate-400">{{ $e->periode_debut->format('d/m/Y') }} → {{ $e->periode_fin->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Montant dû">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ number_format($e->montant_du, 2, ',', ' ') }} €</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Réduc.">
                                @if((float)($e->reduction_promo ?? 0) > 0 || (float)($e->reduction_manuel ?? 0) > 0)
                                    <span class="text-sm text-green-600 dark:text-green-400">−{{ number_format((float)($e->reduction_promo ?? 0) + (float)($e->reduction_manuel ?? 0), 2, ',', ' ') }} €</span>
                                    @if($e->reduction_manuel_notes)
                                        <span class="text-xs text-slate-500" title="{{ $e->reduction_manuel_notes }}">ℹ️</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                                @php
                                    $statutClass = match($e->statut) {
                                        'a_payer' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                        'en_attente' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'paye' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'echec' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'annule', 'arrete' => 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-300',
                                        default => 'bg-slate-100 text-slate-700 dark:bg-slate-600 dark:text-slate-300',
                                    };
                                @endphp
                                <span class="text-xs px-2 py-1 rounded-full {{ $statutClass }}">{{ $e->statut }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Payé le">
                                @if($e->paye_at)
                                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ $e->paye_at->format('d/m/Y H:i') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm" data-label="Actions">
                                @if(!$e->estPayee() && !$e->estArrete())
                                    <button type="button" onclick="document.getElementById('reduction-form-{{ $e->id }}').classList.toggle('hidden')" class="text-blue-600 dark:text-blue-400 hover:underline mr-2">Réduction</button>
                                    <form action="{{ route('admin.echeances.arrete', $e) }}" method="POST" class="inline" onsubmit="return confirm('Marquer cette échéance comme arrêtée ?');">
                                        @csrf
                                        <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline mr-2">Arrêter</button>
                                    </form>
                                    <form action="{{ route('admin.echeances.annule', $e) }}" method="POST" class="inline" onsubmit="return confirm('Annuler cette échéance ?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Annuler</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @if(!$e->estPayee() && !$e->estArrete())
                            <tr id="reduction-form-{{ $e->id }}" class="hidden bg-slate-50 dark:bg-slate-800/80">
                                <td colspan="8" class="px-6 py-4">
                                    <form action="{{ route('admin.echeances.reduction', $e) }}" method="POST" class="flex flex-wrap gap-4 items-end">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Montant réduction (€)</label>
                                            <input type="number" name="reduction_manuel" step="0.01" min="0" value="{{ old('reduction_manuel', $e->reduction_manuel) }}" class="w-32 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        </div>
                                        <div class="flex-1 min-w-[200px]">
                                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Note (geste commercial, etc.)</label>
                                            <input type="text" name="reduction_manuel_notes" value="{{ old('reduction_manuel_notes', $e->reduction_manuel_notes) }}" placeholder="Ex. Geste commercial" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" maxlength="500">
                                        </div>
                                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">Enregistrer</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                Aucune échéance trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $echeances->links() }}
    </div>
</div>
@endsection
