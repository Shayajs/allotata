<!-- Bandeau accès admin au compte utilisateur -->
@if($accountAccess->isActive())
@php
    $accessMode = $accountAccess->mode();
    $modeStyles = match ($accessMode) {
        \App\Services\AccountAccessService::MODE_VIEW => [
            'banner' => 'from-slate-900 via-blue-950 to-slate-900',
            'ping' => 'bg-blue-400',
            'dot' => 'bg-blue-500',
            'label' => 'text-blue-300',
        ],
        \App\Services\AccountAccessService::MODE_SUPPORT => [
            'banner' => 'from-slate-900 via-amber-950 to-slate-900',
            'ping' => 'bg-amber-400',
            'dot' => 'bg-amber-500',
            'label' => 'text-amber-300',
        ],
        \App\Services\AccountAccessService::MODE_BILLING => [
            'banner' => 'from-slate-900 via-emerald-950 to-slate-900',
            'ping' => 'bg-emerald-400',
            'dot' => 'bg-emerald-500',
            'label' => 'text-emerald-300',
        ],
        default => [
            'banner' => 'from-slate-900 via-red-950 to-slate-900',
            'ping' => 'bg-red-400',
            'dot' => 'bg-red-500',
            'label' => 'text-red-300',
        ],
    };
    $modeLabel = $accountAccess->modeLabel();
    $switchModes = [
        \App\Services\AccountAccessService::MODE_VIEW => ['label' => 'VIEW', 'class' => 'text-blue-300 hover:text-white'],
        \App\Services\AccountAccessService::MODE_SUPPORT => ['label' => 'SUPPORT', 'class' => 'text-amber-300 hover:text-white'],
        \App\Services\AccountAccessService::MODE_BILLING => ['label' => 'BILLING', 'class' => 'text-emerald-300 hover:text-white'],
        \App\Services\AccountAccessService::MODE_EDIT => ['label' => 'EDIT', 'class' => 'text-red-300 hover:text-white'],
    ];
@endphp
<div class="relative z-50 bg-gradient-to-r {{ $modeStyles['banner'] }} text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-9 gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $modeStyles['ping'] }} opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 {{ $modeStyles['dot'] }}"></span>
                </span>
                <span class="text-xs font-semibold tracking-widest uppercase {{ $modeStyles['label'] }}">{{ $modeLabel }}</span>
                <span class="hidden sm:inline text-xs text-slate-400">—</span>
                <span class="hidden sm:inline text-xs text-slate-300 truncate">
                    Compte de <strong class="text-white">{{ auth()->user()->name }}</strong>
                </span>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @foreach($switchModes as $switchMode => $switchMeta)
                    @if($switchMode !== $accessMode && ($switchUrl = $accountAccess->switchModeOnCurrentUrl(request(), $switchMode)))
                        <a href="{{ $switchUrl }}" class="hidden sm:inline text-xs font-semibold {{ $switchMeta['class'] }} transition-colors">
                            {{ $switchMeta['label'] }}
                        </a>
                    @endif
                @endforeach
                <a href="{{ $accountAccess->exitUrl('admin.users.index') }}" class="group flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition-colors duration-200">
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Quitter
                </a>
            </div>
        </div>
    </div>
</div>
@endif
