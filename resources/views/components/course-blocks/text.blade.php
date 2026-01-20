@props(['block', 'lesson', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $alignClass = match($settings['alignment'] ?? 'left') {
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left'
    };
    
    $maxWidthClass = match($settings['maxWidth'] ?? 'prose') {
        'narrow' => 'max-w-xl',
        'prose' => 'max-w-prose',
        'wide' => 'max-w-4xl',
        'full' => 'max-w-none',
        default => 'max-w-prose'
    };
    
    $html = $content['html'] ?? '<p>Votre texte ici...</p>';
@endphp

<section class="py-8 md:py-12 px-4">
    <div class="{{ $maxWidthClass }} mx-auto {{ $alignClass }}">
        <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-a:text-green-600 dark:prose-a:text-green-400 prose-a:no-underline hover:prose-a:underline"
             @if($editMode) data-editable="html" @endif>
            {!! $html !!}
        </div>
    </div>
</section>
