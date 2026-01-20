@props(['user', 'size' => 'md'])

@php
    $presence = $user->presence;
    $status = $presence ? $presence->status : 'offline';
    
    $sizeClasses = [
        'sm' => 'w-2 h-2',
        'md' => 'w-3 h-3',
        'lg' => 'w-4 h-4',
    ];
    
    $statusClasses = [
        'online' => 'bg-green-500 ring-green-500',
        'idle' => 'bg-yellow-500 ring-yellow-500',
        'offline' => 'bg-gray-400 ring-gray-400',
    ];
    
    $statusLabels = [
        'online' => 'En ligne',
        'idle' => 'Inactif',
        'offline' => 'Hors ligne',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $statusClass = $statusClasses[$status] ?? $statusClasses['offline'];
    $statusLabel = $statusLabels[$status] ?? 'Hors ligne';
@endphp

<span 
    class="presence-badge presence-badge-{{ $status }} inline-flex items-center {{ $sizeClass }} rounded-full ring-2 ring-white dark:ring-slate-800 {{ $statusClass }}"
    data-user-id="{{ $user->id }}"
    data-status="{{ $status }}"
    title="{{ $statusLabel }}"
    aria-label="{{ $statusLabel }}"
>
    <span class="sr-only">{{ $statusLabel }}</span>
</span>
