@props(['pageKey', 'courseLinks' => []])
@php
    // $courseLinks est un array ['page_key' => ['module_id' => X, 'module_titre' => '...', 'lesson_id' => Y, 'lesson_titre' => '...']]
    $link = $courseLinks[$pageKey] ?? null;
@endphp
@if($link)
    <a 
        href="{{ route('courses.module', $link['module_id']) }}"
        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors border border-blue-200 dark:border-blue-800/50"
        title="Cours disponible : {{ $link['module_titre'] }}"
    >
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <span class="hidden sm:inline">Cours</span>
    </a>
@endif
