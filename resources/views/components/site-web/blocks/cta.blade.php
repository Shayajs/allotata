@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Prêt à commencer ?';
    $subtitle = $content['subtitle'] ?? 'Contactez-nous dès aujourd\'hui';
    $buttonText = $content['buttonText'] ?? 'Nous contacter';
    $buttonLink = $content['buttonLink'] ?? '#contact';
    
    $style = $settings['style'] ?? 'gradient';
    $alignment = $settings['alignment'] ?? 'center';
    
    $alignClass = match($alignment) {
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-center'
    };
@endphp

<section class="py-16 md:py-24 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="rounded-2xl p-8 md:p-12 {{ $alignClass }}"
             @if($style === 'gradient')
                 style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));"
             @elseif($style === 'outlined')
                 style="border: 2px solid var(--site-primary); background: transparent;"
             @else
                 style="background: var(--site-primary);"
             @endif
        >
            <h2 class="text-3xl md:text-4xl font-bold mb-4 {{ $style === 'outlined' ? '' : 'text-white' }}"
                style="font-family: var(--site-font-heading); {{ $style === 'outlined' ? 'color: var(--site-text);' : '' }}"
                @if($editMode) data-editable="title" @endif>
                {{ $title }}
            </h2>
            
            <p class="text-xl mb-8 {{ $style === 'outlined' ? 'text-slate-600 dark:text-slate-400' : 'text-white/90' }}"
               style="font-family: var(--site-font-body);"
               @if($editMode) data-editable="subtitle" @endif>
                {{ $subtitle }}
            </p>
            
            <a href="{{ $buttonLink }}" 
               class="inline-block px-8 py-4 text-lg font-semibold transition hover:opacity-90 hover:scale-105"
               @if($style === 'outlined')
                   style="background: var(--site-primary); color: white; border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);"
               @else
                   style="background: white; color: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);"
               @endif
            >
                {{ $buttonText }}
            </a>
        </div>
    </div>
</section>
