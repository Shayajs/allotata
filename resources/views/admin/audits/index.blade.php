@extends('admin.layout')

@section('title', 'Audits du site')
@section('header', 'Audits du site')
@section('subheader', 'Historique complet des audits automatiques du site')

@section('content')

    {{-- Bouton lancer un audit --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            @if($audits->total() > 0)
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $audits->total() }} audit(s) enregistré(s)</p>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.audits.start') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Lancer un audit
            </button>
        </form>
    </div>

    @if($audits->isEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p class="text-slate-600 dark:text-slate-400 text-lg">Aucun audit n'a encore été réalisé.</p>
            <p class="text-slate-500 dark:text-slate-500 text-sm mt-2">Lancez votre premier audit pour obtenir un diagnostic complet du site.</p>
        </div>
    @else
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto table-responsive-to-cards">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Lancé par</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Note</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Durée</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Catégories</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($audits as $audit)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 text-slate-900 dark:text-white font-medium" data-label="#">{{ $audit->id }}</td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300" data-label="Date">
                                    {{ $audit->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300" data-label="Lancé par">
                                    {{ $audit->user?->name ?? 'Système' }}
                                </td>
                                <td class="px-6 py-4 text-center" data-label="Note">
                                    @if($audit->note_globale !== null)
                                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full text-sm font-bold
                                            {{ $audit->note_globale >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                            {{ $audit->note_globale >= 50 && $audit->note_globale < 80 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                            {{ $audit->note_globale < 50 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                        ">
                                            {{ $audit->note_globale }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center" data-label="Statut">{!! $audit->status_badge !!}</td>
                                <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-300" data-label="Durée">{{ $audit->duration_formatted }}</td>
                                <td class="px-6 py-4 text-center" data-label="Catégories">
                                    @if($audit->resume)
                                        @php
                                            $ok = collect($audit->resume)->where('status', 'ok')->count();
                                            $warn = collect($audit->resume)->where('status', 'warning')->count();
                                            $crit = collect($audit->resume)->whereIn('status', ['critical', 'error'])->count();
                                        @endphp
                                        <div class="flex items-center justify-center gap-1.5">
                                            @if($ok > 0)<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ $ok }}</span>@endif
                                            @if($warn > 0)<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">{{ $warn }}</span>@endif
                                            @if($crit > 0)<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ $crit }}</span>@endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right" data-label="">
                                    <a href="{{ route('admin.audits.show', $audit) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium text-sm">
                                        Détail &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $audits->links() }}
        </div>
    @endif

@endsection
