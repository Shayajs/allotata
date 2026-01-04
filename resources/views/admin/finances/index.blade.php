@extends('admin.layout')

@section('title', 'Gestion des Finances')
@section('header', 'Finances Globales')
@section('subheader', 'Suivi des revenus et dépenses de toutes les entreprises')

@section('content')
<div class="space-y-6">
    <!-- Totaux Globaux -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <p class="text-sm font-medium text-slate-500 uppercase">Total Recettes</p>
            <h3 class="text-3xl font-bold text-green-600">{{ number_format($totalIncome, 2, ',', ' ') }} €</h3>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <p class="text-sm font-medium text-slate-500 uppercase">Total Dépenses</p>
            <h3 class="text-3xl font-bold text-red-600">{{ number_format($totalExpense, 2, ',', ' ') }} €</h3>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <p class="text-sm font-medium text-slate-500 uppercase">Balance Nette</p>
            @php $balance = $totalIncome - $totalExpense; @endphp
            <h3 class="text-3xl font-bold {{ $balance >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                {{ number_format($balance, 2, ',', ' ') }} €
            </h3>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-wrap gap-4 items-center">
        <form action="{{ route('admin.finances.index') }}" method="GET" class="flex flex-wrap gap-4 items-center w-full">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Mois</label>
                <select name="month" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-600 rounded-lg text-sm">
                    <option value="">Tous les mois</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                            {{ Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Année</label>
                <select name="year" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-600 rounded-lg text-sm">
                    <option value="">Toutes les années</option>
                    @foreach(range(now()->year - 2, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end h-full pt-5">
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-all">
                    Filtrer
                </button>
                <a href="{{ route('admin.finances.index') }}" class="ml-2 px-6 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100 dark:bg-slate-700">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Entreprise</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type / Catégorie</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($finances as $record)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 text-sm">{{ $record->date_record->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.entreprises.show', $record->entreprise) }}" class="text-sm font-bold text-green-600 hover:underline">
                                {{ $record->entreprise->nom }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $record->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $record->type === 'income' ? 'Recette' : 'Dépense' }}
                            </span>
                            <span class="ml-2 text-xs text-slate-500">{{ $record->category }}</span>
                        </td>
                        <td class="px-6 py-4 font-bold {{ $record->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($record->amount, 2, ',', ' ') }} €
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.entreprises.show', $record->entreprise) }}" class="p-2 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 transition-all text-xs">
                                Voir Entreprise
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
            {{ $finances->links() }}
        </div>
    </div>
</div>
@endsection
