@props(['term' => '', 'position' => 'top'])

<span class="tooltip-wrapper relative inline-flex items-center gap-1 cursor-help group">
    <span class="border-b border-dotted border-slate-400 dark:border-slate-500">{{ $term }}</span>
    <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <span class="tooltip-content tooltip-{{ $position }} invisible opacity-0 group-hover:visible group-hover:opacity-100 absolute z-50 px-3 py-2 text-xs font-normal text-white bg-slate-800 dark:bg-slate-600 rounded-lg shadow-lg whitespace-normal max-w-xs transition-all duration-200 pointer-events-none">
        {{ $slot }}
        <span class="tooltip-arrow"></span>
    </span>
</span>
