@extends('admin.layout')

@section('title', "Données de la table: {$tableName}")
@section('header', "Données de la table: {$tableName}")
@section('subheader', "Affichage des données avec pagination")

@section('content')
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Table: <code class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">{{ $tableName }}</code>
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                Total: {{ number_format($total) }} ligne(s) | Page {{ $page }} sur {{ $totalPages }}
            </p>
        </div>
        <a href="{{ route('admin.database.index') }}" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition-colors">
            ← Retour
        </a>
    </div>

    @if(count($data) > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    @foreach($columns as $column)
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                        {{ $column->Field }}
                        <span class="text-xs text-slate-400 dark:text-slate-500">({{ $column->Type }})</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($data as $row)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    @foreach($columns as $column)
                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                        @if(is_string($row->{$column->Field}) && strlen($row->{$column->Field}) > 100)
                            <span title="{{ $row->{$column->Field} }}">
                                {{ Str::limit($row->{$column->Field}, 100) }}...
                            </span>
                        @else
                            {{ $row->{$column->Field} ?? 'NULL' }}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($totalPages > 1)
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-slate-600 dark:text-slate-400">
            Affichage de {{ (($page - 1) * $perPage) + 1 }} à {{ min($page * $perPage, $total) }} sur {{ number_format($total) }} résultats
        </div>
        <div class="flex gap-2">
            @if($page > 1)
            <a href="?table={{ urlencode($tableName) }}&page={{ $page - 1 }}" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition-colors">
                ← Précédent
            </a>
            @endif
            
            @if($page < $totalPages)
            <a href="?table={{ urlencode($tableName) }}&page={{ $page + 1 }}" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition-colors">
                Suivant →
            </a>
            @endif
        </div>
    </div>
    @endif
    @else
    <div class="text-center py-12 text-slate-500 dark:text-slate-400">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
        </svg>
        <p class="text-lg font-medium">Cette table est vide</p>
        <p class="text-sm mt-2">Aucune donnée à afficher</p>
    </div>
    @endif
</div>
@endsection
