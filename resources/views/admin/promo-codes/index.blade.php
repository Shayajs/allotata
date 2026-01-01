@extends('admin.layout')

@section('title', 'Codes promo')
@section('header', '🎁 Codes promotionnels')
@section('subheader', 'Gérez les codes promo pour les abonnements')

@section('content')
<div class="flex justify-end mb-6">
    <a href="{{ route('admin.promo-codes.create') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
        ➕ Nouveau code promo
    </a>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Réduction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Usages</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Période</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($promoCodes as $code)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 {{ !$code->est_actif ? 'opacity-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-lg text-slate-900 dark:text-white">{{ $code->code }}</span>
                                @if($code->premier_abonnement_uniquement)
                                    <span class="px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded">1er abo</span>
                                @endif
                            </div>
                            @if($code->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $code->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-lg font-bold {{ $code->type === 'pourcentage' ? 'text-green-600' : 'text-blue-600' }}">
                                {{ $code->formatted_value }}
                            </span>
                            @if($code->duree_mois)
                                <p class="text-xs text-slate-500 dark:text-slate-400">sur {{ $code->duree_mois }} mois</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900 dark:text-white">
                                {{ $code->usages_actuels }} / {{ $code->usages_max ?? '∞' }}
                            </div>
                            @if($code->usages_restants !== null && $code->usages_restants <= 5 && $code->usages_restants > 0)
                                <span class="text-xs text-orange-600">{{ $code->usages_restants }} restants</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                            @if($code->date_debut || $code->date_fin)
                                {{ $code->date_debut?->format('d/m/Y') ?? '...' }} - {{ $code->date_fin?->format('d/m/Y') ?? '...' }}
                            @else
                                Permanent
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($code->isValid())
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Valide</span>
                            @elseif(!$code->est_actif)
                                <span class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded">Inactif</span>
                            @elseif($code->usages_max && $code->usages_actuels >= $code->usages_max)
                                <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">Épuisé</span>
                            @elseif($code->date_fin && $code->date_fin->isPast())
                                <span class="px-2 py-1 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Expiré</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">Non valide</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.promo-codes.edit', $code) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route('admin.promo-codes.destroy', $code) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                            Aucun code promo. <a href="{{ route('admin.promo-codes.create') }}" class="text-green-600 hover:underline">Créer le premier</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
        {{ $promoCodes->links() }}
    </div>
</div>
@endsection
