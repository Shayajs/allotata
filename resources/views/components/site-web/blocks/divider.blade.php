@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $style = $content['style'] ?? 'line';
    $spacing = $settings['spacing'] ?? 'medium';
    
    $spacingClass = match($spacing) {
        'small' => 'py-6',
        'medium' => 'py-12',
        'large' => 'py-20',
        default => 'py-12'
    };
@endphp

<div class="{{ $spacingClass }}">
    <div class="max-w-6xl mx-auto px-4">
        @switch($style)
            @case('line')
                <hr class="border-t border-slate-200 dark:border-slate-700">
                @break
                
            @case('dashed')
                <hr class="border-t-2 border-dashed border-slate-200 dark:border-slate-700">
                @break
                
            @case('dots')
                <div class="flex justify-center gap-3">
                    <span class="w-2 h-2 rounded-full" style="background: var(--site-primary);"></span>
                    <span class="w-2 h-2 rounded-full" style="background: var(--site-secondary);"></span>
                    <span class="w-2 h-2 rounded-full" style="background: var(--site-primary);"></span>
                </div>
                @break
                
            @case('gradient')
                <div class="h-1 rounded-full" style="background: linear-gradient(90deg, transparent, var(--site-primary), var(--site-secondary), transparent);"></div>
                @break
                
            @case('space')
                {{-- Just empty space --}}
                @break
                
            @default
                <hr class="border-t border-slate-200 dark:border-slate-700">
        @endswitch
    </div>
</div>
