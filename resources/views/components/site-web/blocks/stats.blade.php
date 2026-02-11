@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $items = $content['items'] ?? [
        ['value' => '100+', 'label' => 'Clients satisfaits'],
        ['value' => '5+', 'label' => 'Années d\'expérience'],
        ['value' => '1000+', 'label' => 'Projets réalisés'],
    ];
    
    $animated = $settings['animated'] ?? true;
    $layout = $settings['layout'] ?? 'horizontal';
    
    $statsId = 'stats-' . ($block['id'] ?? uniqid());
@endphp

<section class="py-16 md:py-24 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-wrap justify-center gap-8 md:gap-16" id="{{ $statsId }}">
            @foreach($items as $index => $stat)
                <div class="text-center min-w-[150px]">
                    <div class="text-4xl md:text-5xl lg:text-6xl font-bold mb-2 stat-value"
                         style="color: var(--site-primary); font-family: var(--site-font-heading);"
                         @if($animated) data-value="{{ $stat['value'] }}" @endif>
                        @if($animated)
                            <span class="counter">0</span>{{ preg_replace('/[0-9]+/', '', $stat['value']) }}
                        @else
                            {{ $stat['value'] }}
                        @endif
                    </div>
                    <div class="text-slate-600 dark:text-slate-400 text-lg" style="font-family: var(--site-font-body);">
                        {{ $stat['label'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if($animated)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statsSection = document.getElementById('{{ $statsId }}');
    if (!statsSection) return;
    
    const counters = statsSection.querySelectorAll('.counter');
    let animated = false;
    
    function animateCounters() {
        if (animated) return;
        animated = true;
        
        counters.forEach(counter => {
            const parent = counter.closest('.stat-value');
            const target = parent.dataset.value;
            const numericMatch = target.match(/([0-9]+)/);
            
            if (!numericMatch) {
                counter.textContent = target;
                return;
            }
            
            const targetNumber = parseInt(numericMatch[1]);
            const duration = 2000;
            const start = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const current = Math.floor(easeOut * targetNumber);
                
                counter.textContent = current;
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    counter.textContent = targetNumber;
                }
            }
            
            requestAnimationFrame(update);
        });
    }
    
    // Observer pour démarrer l'animation quand visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    observer.observe(statsSection);
});
</script>
@endif
