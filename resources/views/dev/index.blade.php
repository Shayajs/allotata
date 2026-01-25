@extends('dev.layout')

@section('title', 'Documentation développeur')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        {{-- Arborescence --}}
        <aside class="lg:w-64 flex-shrink-0 order-2 lg:order-1">
            <div class="lg:sticky lg:top-20 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 p-4">
                <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Arborescence</h2>
                <nav class="space-y-1 max-h-[60vh] overflow-y-auto">
                    @foreach($sections as $s)
                        <details class="group" {{ $loop->first ? 'open' : '' }}>
                            <summary class="flex items-center gap-2 py-1.5 px-2 rounded-lg cursor-pointer list-none text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition [&::-webkit-details-marker]:hidden">
                                <span class="text-lg" style="color: {{ $s['color'] }}">{{ $s['emoji'] }}</span>
                                <span class="font-medium truncate">{{ $s['title'] }}</span>
                                <svg class="w-4 h-4 ml-auto transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </summary>
                            <ul class="ml-6 mt-1 space-y-0.5 pl-2 border-l-2 border-slate-200 dark:border-slate-600">
                                @foreach($s['files'] as $f)
                                    <li>
                                        <a href="{{ route('dev.show', ['path' => $f['path']]) }}" class="block py-1 px-2 text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 truncate rounded hover:bg-slate-100 dark:hover:bg-slate-700/50">
                                            {{ Str::limit(pathinfo($f['name'], PATHINFO_FILENAME), 32) }}
                                            @if($f['admin_only'] ?? false)
                                                <span class="text-amber-500" title="Admin">🔒</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </nav>
            </div>
        </aside>

        <main class="flex-1 min-w-0 order-1 lg:order-2">
            <div class="text-center mb-10">
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-2">
                    Documentation développeur
                </h1>
                <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Guides techniques, APIs, déploiement et intégrations. Utilisez l’arborescence pour naviguer.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                @foreach($sections as $s)
                    <a href="{{ count($s['files']) ? route('dev.show', ['path' => $s['files'][0]['path']]) : '#' }}" class="block rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 hover:border-green-400/50 dark:hover:border-green-500/50 hover:shadow-lg transition group {{ !count($s['files']) ? 'pointer-events-none opacity-60' : '' }}">
                        <div class="flex items-start gap-4">
                            <span class="text-3xl flex-shrink-0" style="color: {{ $s['color'] }}">{{ $s['emoji'] }}</span>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition">
                                    {{ $s['title'] }}
                                </h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                                    {{ $s['description'] }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-500">
                                    {{ count($s['files']) }} fichier(s)
                                </p>
                            </div>
                            @if(count($s['files']))
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </main>
    </div>
</div>
@endsection
