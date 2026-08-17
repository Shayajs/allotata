@php
    $title = $title ?? 'Allo Tata';
    $showBack = $showBack ?? false;
    $backUrl = $backUrl ?? route('dashboard');
    $unread = auth()->check() ? auth()->user()->nombre_notifications_non_lues : 0;
@endphp
<header class="android-top-bar">
    <div class="flex items-center gap-2 min-w-0">
        @if($showBack)
            <a href="{{ $backUrl }}" class="flex items-center justify-center w-10 h-10 rounded-full text-slate-200 hover:bg-white/10" aria-label="Retour">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
        @endif
        <h1 class="text-base font-semibold truncate">{{ $title }}</h1>
    </div>
    @auth
        <a href="{{ route('notifications.index') }}" class="relative flex items-center justify-center w-10 h-10 rounded-full text-slate-200 hover:bg-white/10" aria-label="Notifications">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            @if($unread > 0)
                <span class="absolute top-1.5 right-1.5 min-w-[16px] h-4 px-1 text-[10px] font-bold bg-orange-500 text-white rounded-full flex items-center justify-center">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
        </a>
    @endauth
</header>
