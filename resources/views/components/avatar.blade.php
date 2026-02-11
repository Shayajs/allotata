@props([
    'user' => null,
    'size' => 'md',
    'class' => '',
])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-base',
        'lg' => 'w-12 h-12 text-lg',
        'xl' => 'w-16 h-16 text-xl',
        '2xl' => 'w-20 h-20 text-2xl',
    ];
    $sizeClass = isset($sizes[$size]) ? $sizes[$size] : $sizes['md'];
    
    $name = ($user && $user->name) ? $user->name : 'U';
    $initial = strtoupper(substr($name, 0, 1));
    $photo = ($user && $user->photo_profil) ? $user->photo_profil : null;
@endphp

@if($photo)
    <img 
        src="{{ asset('media/' . $photo) }}" 
        alt="{{ $name }}"
        {{ $attributes->merge(['class' => "{$sizeClass} rounded-full object-cover border-2 border-slate-200 dark:border-slate-600 {$class}"]) }}
    >
@else
    <div {{ $attributes->merge(['class' => "{$sizeClass} rounded-full bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white font-bold {$class}"]) }}>
        {{ $initial }}
    </div>
@endif
