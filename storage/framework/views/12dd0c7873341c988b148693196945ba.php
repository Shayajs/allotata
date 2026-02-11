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
?>

<section class="py-16 md:py-24 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="rounded-2xl p-8 md:p-12 <?php echo e($alignClass); ?>"
             <?php if($style === 'gradient'): ?>
                 style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));"
             <?php elseif($style === 'outlined'): ?>
                 style="border: 2px solid var(--site-primary); background: transparent;"
             <?php else: ?>
                 style="background: var(--site-primary);"
             <?php endif; ?>
        >
            <h2 class="text-3xl md:text-4xl font-bold mb-4 <?php echo e($style === 'outlined' ? '' : 'text-white'); ?>"
                style="font-family: var(--site-font-heading); <?php echo e($style === 'outlined' ? 'color: var(--site-text);' : ''); ?>"
                <?php if($editMode): ?> data-editable="title" <?php endif; ?>>
                <?php echo e($title); ?>

            </h2>
            
            <p class="text-xl mb-8 <?php echo e($style === 'outlined' ? 'text-slate-600 dark:text-slate-400' : 'text-white/90'); ?>"
               style="font-family: var(--site-font-body);"
               <?php if($editMode): ?> data-editable="subtitle" <?php endif; ?>>
                <?php echo e($subtitle); ?>

            </p>
            
            <a href="<?php echo e($buttonLink); ?>" 
               class="inline-block px-8 py-4 text-lg font-semibold transition hover:opacity-90 hover:scale-105"
               <?php if($style === 'outlined'): ?>
                   style="background: var(--site-primary); color: white; border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);"
               <?php else: ?>
                   style="background: white; color: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);"
               <?php endif; ?>
            >
                <?php echo e($buttonText); ?>

            </a>
        </div>
    </div>
</section>
<?php /**PATH /var/www/html/resources/views/components/site-web/blocks/cta.blade.php ENDPATH**/ ?>