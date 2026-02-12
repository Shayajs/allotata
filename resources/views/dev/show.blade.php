@extends('dev.layout')

@section('title', $title)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        {{-- Arborescence --}}
        <aside class="lg:w-56 flex-shrink-0 order-2 lg:order-1">
            <div class="lg:sticky lg:top-20 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 p-4">
                <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Sections</h2>
                <nav class="space-y-1 max-h-[50vh] overflow-y-auto">
                    @foreach($sections as $s)
                        @php $active = ($s['slug'] ?? '') === $sectionSlug; @endphp
                        <details class="group" {{ $active ? 'open' : '' }}>
                            <summary class="flex items-center gap-2 py-1.5 px-2 rounded-lg cursor-pointer list-none text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition [&::-webkit-details-marker]:hidden">
                                <span class="text-lg" style="color: {{ $s['color'] }}">{{ $s['emoji'] }}</span>
                                <span class="font-medium truncate">{{ $s['title'] }}</span>
                                <svg class="w-4 h-4 ml-auto transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </summary>
                            <ul class="ml-6 mt-1 space-y-0.5 pl-2 border-l-2 border-slate-200 dark:border-slate-600">
                                @foreach($s['files'] as $f)
                                    @php $current = $f['path'] === $path; @endphp
                                    <li>
                                        <a href="{{ route('dev.show', ['path' => $f['path']]) }}" class="block py-1 px-2 text-sm rounded truncate {{ $current ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 font-medium' : 'text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-slate-100 dark:hover:bg-slate-700/50' }}">
                                            {{ Str::limit(pathinfo($f['name'], PATHINFO_FILENAME), 28) }}
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

        <main class="flex-1 min-w-0 order-1 lg:order-2 flex flex-col lg:flex-row gap-8">
            <article class="flex-1 min-w-0">
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 sm:p-8">
                    <div class="flex items-center gap-2 mb-6">
                        <span class="text-2xl" style="color: {{ $sectionConfig['color'] ?? '#64748b' }}">{{ $sectionConfig['emoji'] ?? '📄' }}</span>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $title }}</h1>
                    </div>
                    <div class="dev-doc-content">
                        {!! $html !!}
                    </div>
                </div>
            </article>

            @if(count($toc) > 0)
                <aside class="lg:w-52 flex-shrink-0 order-3">
                    <div class="lg:sticky lg:top-20 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 p-4">
                        <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Sommaire</h2>
                        <nav class="space-y-1 max-h-[60vh] overflow-y-auto">
                            @foreach($toc as $item)
                                <a href="#{{ $item['id'] }}" class="block py-1 text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 truncate" style="padding-left: {{ ($item['level'] - 1) * 0.5 }}rem;">
                                    {{ Str::limit($item['title'], 36) }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            @endif
        </main>
    </div>
</div>
@endsection
