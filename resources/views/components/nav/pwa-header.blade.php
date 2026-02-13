{{-- Header contextuel PWA – Visible uniquement en PWA mobile via CSS --}}
@props(['title' => '', 'showBack' => false, 'backUrl' => null])

<header class="pwa-header items-center h-14 px-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
    <div class="flex items-center justify-between h-full max-w-lg mx-auto">
        {{-- Bouton retour --}}
        <div class="w-10">
            @if($showBack)
                <a href="{{ $backUrl ?? 'javascript:history.back()' }}" class="flex items-center justify-center w-9 h-9 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
            @endif
        </div>

        {{-- Titre --}}
        <h1 class="text-base font-semibold text-slate-900 dark:text-white truncate text-center flex-1">{{ $title }}</h1>

        {{-- Actions (slot pour le futur) --}}
        <div class="w-10 flex justify-end">
            {{ $slot ?? '' }}
        </div>
    </div>
</header>
