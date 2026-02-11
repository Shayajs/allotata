@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $style = $content['style'] ?? $settings['style'] ?? 'line'; // line, dashed, dots, space
    $spacing = $settings['spacing'] ?? 'medium';
    
    $spacingClass = match($spacing) {
        'small' => 'py-4',
        'medium' => 'py-8',
        'large' => 'py-12',
        default => 'py-8'
    };
@endphp

<section class="{{ $spacingClass }} px-4">
    <div class="max-w-4xl mx-auto">
        @if($style === 'line')
            <hr class="border-t-2 border-slate-300 dark:border-slate-600">
        @elseif($style === 'dashed')
            <hr class="border-t-2 border-dashed border-slate-300 dark:border-slate-600">
        @elseif($style === 'dots')
            <div class="flex items-center justify-center gap-2">
                <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500"></span>
            </div>
        @else
            {{-- Space --}}
            <div></div>
        @endif
    </div>
</section>
