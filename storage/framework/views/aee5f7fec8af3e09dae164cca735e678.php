<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['block', 'entreprise', 'editMode' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['block', 'entreprise', 'editMode' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
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
?>

<section class="py-16 md:py-24 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-wrap justify-center gap-8 md:gap-16" id="<?php echo e($statsId); ?>">
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="text-center min-w-[150px]">
                    <div class="text-4xl md:text-5xl lg:text-6xl font-bold mb-2 stat-value"
                         style="color: var(--site-primary); font-family: var(--site-font-heading);"
                         <?php if($animated): ?> data-value="<?php echo e($stat['value']); ?>" <?php endif; ?>>
                        <?php if($animated): ?>
                            <span class="counter">0</span><?php echo e(preg_replace('/[0-9]+/', '', $stat['value'])); ?>

                        <?php else: ?>
                            <?php echo e($stat['value']); ?>

                        <?php endif; ?>
                    </div>
                    <div class="text-slate-600 dark:text-slate-400 text-lg" style="font-family: var(--site-font-body);">
                        <?php echo e($stat['label']); ?>

                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>

<?php if($animated): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statsSection = document.getElementById('<?php echo e($statsId); ?>');
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
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/site-web/blocks/stats.blade.php ENDPATH**/ ?>