@extends('admin.layout')

@section('title', 'Audit #' . $audit->id)
@section('header', 'Audit #' . $audit->id)
@section('subheader', 'Détail complet de l\'audit du ' . $audit->created_at->format('d/m/Y à H:i'))

@section('content')

    {{-- En-tête avec note globale --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        {{-- Jauge circulaire --}}
        <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col items-center justify-center">
            @if($audit->status === 'running')
                <div class="animate-pulse flex flex-col items-center">
                    <div class="w-32 h-32 rounded-full border-8 border-blue-200 dark:border-blue-900 flex items-center justify-center">
                        <svg class="w-10 h-10 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                    <p class="mt-4 text-sm text-blue-600 dark:text-blue-400 font-medium">Audit en cours...</p>
                </div>
            @elseif($audit->note_globale !== null)
                @php
                    $color = $audit->note_globale >= 80 ? 'green' : ($audit->note_globale >= 50 ? 'yellow' : 'red');
                    $dashOffset = 440 - (440 * $audit->note_globale / 100);
                @endphp
                <div class="relative w-36 h-36">
                    <svg class="w-36 h-36 transform -rotate-90" viewBox="0 0 160 160">
                        <circle cx="80" cy="80" r="70" stroke="currentColor" stroke-width="12" fill="none" class="text-slate-200 dark:text-slate-700"/>
                        <circle cx="80" cy="80" r="70" stroke="currentColor" stroke-width="12" fill="none"
                            class="text-{{ $color }}-500"
                            stroke-dasharray="440"
                            stroke-dashoffset="{{ $dashOffset }}"
                            stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ $audit->note_globale }}</span>
                        <span class="text-sm text-slate-500 dark:text-slate-400">/100</span>
                    </div>
                </div>
                <p class="mt-3 text-sm font-medium text-{{ $color }}-600 dark:text-{{ $color }}-400">
                    @if($audit->note_globale >= 80) Excellent @elseif($audit->note_globale >= 50) À améliorer @else Critique @endif
                </p>
            @else
                <div class="w-32 h-32 rounded-full border-8 border-red-200 dark:border-red-900 flex items-center justify-center">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <p class="mt-3 text-sm font-medium text-red-600 dark:text-red-400">Échoué</p>
            @endif
        </div>

        {{-- Infos générales --}}
        <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Statut</p>
                    <p>{!! $audit->status_badge !!}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Durée</p>
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ $audit->duration_formatted }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Lancé par</p>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $audit->user?->name ?? 'Système' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Date</p>
                    <p class="text-sm text-slate-900 dark:text-white">{{ $audit->started_at?->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            @if($previousAudit)
                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Comparaison avec l'audit précédent</p>
                    @php
                        $diff = ($audit->note_globale ?? 0) - ($previousAudit->note_globale ?? 0);
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-600 dark:text-slate-300">
                            Audit #{{ $previousAudit->id }} ({{ $previousAudit->created_at->format('d/m') }}) : {{ $previousAudit->note_globale }}/100
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium
                            {{ $diff > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($diff < 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400') }}">
                            @if($diff > 0) +{{ $diff }} @elseif($diff < 0) {{ $diff }} @else = @endif
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Voyants par catégorie --}}
    @if($audit->resume)
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-8">
            @foreach($audit->resume as $key => $cat)
                @php
                    $catColor = match($cat['status']) {
                        'ok' => 'green',
                        'warning' => 'yellow',
                        'critical', 'error' => 'red',
                        default => 'slate',
                    };
                @endphp
                <a href="#section-{{ $key }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-center hover:shadow-md transition-shadow">
                    <div class="w-8 h-8 mx-auto rounded-full bg-{{ $catColor }}-100 dark:bg-{{ $catColor }}-900/30 flex items-center justify-center mb-2">
                        <span class="w-3 h-3 rounded-full bg-{{ $catColor }}-500"></span>
                    </div>
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">{{ $cat['label'] }}</p>
                    <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $cat['score'] }}</p>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Détails par catégorie --}}
    @if($audit->resultats)
        <div class="space-y-4">
            @foreach($audit->resultats as $key => $result)
                @php
                    $sectionColor = match($result['status'] ?? 'ok') {
                        'ok' => 'green',
                        'warning' => 'yellow',
                        'critical', 'error' => 'red',
                        default => 'slate',
                    };
                    $prevScore = $previousAudit && isset($previousAudit->resultats[$key]) ? $previousAudit->resultats[$key]['score'] : null;
                    $scoreDiff = $prevScore !== null ? ($result['score'] - $prevScore) : null;
                @endphp
                <div id="section-{{ $key }}" x-data="{ open: {{ $result['status'] !== 'ok' ? 'true' : 'false' }} }" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    {{-- Header cliquable --}}
                    <button @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <span class="w-3 h-3 rounded-full bg-{{ $sectionColor }}-500 flex-shrink-0"></span>
                            <span class="text-base font-semibold text-slate-900 dark:text-white">{{ $result['label'] }}</span>
                            <span class="text-sm font-medium text-{{ $sectionColor }}-600 dark:text-{{ $sectionColor }}-400">{{ $result['score'] }}/100</span>
                            @if($scoreDiff !== null && $scoreDiff !== 0)
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $scoreDiff > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $scoreDiff > 0 ? '+' : '' }}{{ $scoreDiff }}
                                </span>
                            @endif
                        </div>
                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    {{-- Contenu déplié --}}
                    <div x-show="open" x-collapse>
                        <div class="px-6 pb-6 border-t border-slate-100 dark:border-slate-700">
                            {{-- Items --}}
                            @if(!empty($result['items']))
                                <div class="mt-4 space-y-2">
                                    @foreach($result['items'] as $item)
                                        @php
                                            $itemColor = match($item['severity'] ?? 'info') {
                                                'ok' => 'green',
                                                'warning' => 'yellow',
                                                'critical' => 'red',
                                                default => 'slate',
                                            };
                                        @endphp
                                        <div class="flex items-center justify-between py-2 px-3 rounded-lg {{ $item['severity'] === 'critical' ? 'bg-red-50 dark:bg-red-900/10' : ($item['severity'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/10' : '') }}">
                                            <div class="flex items-center gap-3">
                                                @if($item['severity'] !== 'info')
                                                    <span class="w-2 h-2 rounded-full bg-{{ $itemColor }}-500 flex-shrink-0"></span>
                                                @else
                                                    <span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600 flex-shrink-0"></span>
                                                @endif
                                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $item['label'] }}</span>
                                            </div>
                                            <span class="text-sm font-medium text-slate-900 dark:text-white ml-4 text-right">{{ $item['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Recommandations --}}
                            @if(!empty($result['recommendations']))
                                <div class="mt-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30">
                                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-2">Recommandations</p>
                                    <ul class="space-y-1.5">
                                        @foreach($result['recommendations'] as $rec)
                                            <li class="flex items-start gap-2 text-sm text-amber-800 dark:text-amber-300">
                                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ $rec }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Navigation --}}
    <div class="mt-8 flex justify-between items-center">
        <a href="{{ route('admin.audits.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Tous les audits
        </a>
        @if($audit->status === 'running')
            <p class="text-sm text-blue-600 dark:text-blue-400 animate-pulse">Actualiser la page pour voir les résultats...</p>
        @endif
    </div>

@endsection
