@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $text = $content['text'] ?? 'Titre';
    $level = $content['level'] ?? 2;
    $alignment = $settings['alignment'] ?? 'left';
    
    $alignClass = match($alignment) {
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left'
    };
    
    $sizeClass = match($level) {
        1 => 'text-4xl md:text-5xl',
        2 => 'text-3xl md:text-4xl',
        3 => 'text-2xl md:text-3xl',
        4 => 'text-xl md:text-2xl',
        5 => 'text-lg md:text-xl',
        6 => 'text-base md:text-lg',
        default => 'text-2xl md:text-3xl'
    };
    
    $colorClass = $settings['color'] ?? 'text-slate-900 dark:text-white';
@endphp

<section class="py-6 md:py-8 px-4">
    <div class="max-w-4xl mx-auto">
        @if($level === 1)
            <h1 class="{{ $sizeClass }} {{ $alignClass }} {{ $colorClass }} font-bold mb-4"
                @if($editMode) data-editable="text" @endif>
                {{ $text }}
            </h1>
        @elseif($level === 2)
            <h2 class="{{ $sizeClass }} {{ $alignClass }} {{ $colorClass }} font-bold mb-4"
                @if($editMode) data-editable="text" @endif>
                {{ $text }}
            </h2>
        @elseif($level === 3)
            <h3 class="{{ $sizeClass }} {{ $alignClass }} {{ $colorClass }} font-bold mb-3"
                @if($editMode) data-editable="text" @endif>
                {{ $text }}
            </h3>
        @elseif($level === 4)
            <h4 class="{{ $sizeClass }} {{ $alignClass }} {{ $colorClass }} font-semibold mb-2"
                @if($editMode) data-editable="text" @endif>
                {{ $text }}
            </h4>
        @elseif($level === 5)
            <h5 class="{{ $sizeClass }} {{ $alignClass }} {{ $colorClass }} font-semibold mb-2"
                @if($editMode) data-editable="text" @endif>
                {{ $text }}
            </h5>
        @else
            <h6 class="{{ $sizeClass }} {{ $alignClass }} {{ $colorClass }} font-semibold mb-2"
                @if($editMode) data-editable="text" @endif>
                {{ $text }}
            </h6>
        @endif
    </div>
</section>
