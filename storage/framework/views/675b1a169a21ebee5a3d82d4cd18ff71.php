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
    
    $heightClass = match($settings['height'] ?? 'large') {
        'small' => 'min-h-[300px]',
        'medium' => 'min-h-[400px]',
        'large' => 'min-h-[500px]',
        'full' => 'min-h-screen',
        default => 'min-h-[500px]'
    };
    
    $alignClass = match($settings['alignment'] ?? 'center') {
        'left' => 'text-left items-start',
        'center' => 'text-center items-center',
        'right' => 'text-right items-end',
        default => 'text-center items-center'
    };
    
    // Utiliser l'image de fond de l'entreprise par défaut pour que ça reste dynamique
    // Si l'utilisateur veut une image spécifique, on pourrait ajouter une option "Override" plus tard
    $bgImage = $entreprise->image_fond;
    
    $hasOverlay = $content['overlay'] ?? true;
    
    // Utiliser les valeurs de l'entreprise par défaut
    $title = $content['title'] ?? $entreprise->nom;
    $subtitle = $content['subtitle'] ?? $entreprise->phrase_accroche ?? $entreprise->type_activite;
    $buttonText = $content['buttonText'] ?? 'Nous contacter';
    $buttonLink = $content['buttonLink'] ?? '#contact';
?>

<section class="<?php echo e($heightClass); ?> relative flex flex-col justify-center <?php echo e($alignClass); ?> p-8 md:p-16 overflow-hidden"
    <?php if($bgImage): ?>
        style="background-image: url('<?php echo e(str_starts_with($bgImage, 'http') ? $bgImage : asset('storage/' . $bgImage)); ?>'); background-size: cover; background-position: center;"
    <?php else: ?>
        style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));"
    <?php endif; ?>
>
    <?php if($hasOverlay && $bgImage): ?>
        <div class="absolute inset-0 bg-black/<?php echo e($settings['overlayOpacity'] ?? 50); ?>"></div>
    <?php endif; ?>
    
    <div class="relative z-10 max-w-4xl mx-auto">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight"
            style="font-family: var(--site-font-heading);"
            <?php if($editMode): ?> data-editable="title" <?php endif; ?>>
            <?php echo e($title); ?>

        </h1>
        
        <?php if($subtitle): ?>
            <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-2xl <?php echo e($settings['alignment'] === 'center' ? 'mx-auto' : ''); ?>"
               style="font-family: var(--site-font-body);"
               <?php if($editMode): ?> data-editable="subtitle" <?php endif; ?>>
                <?php echo e($subtitle); ?>

            </p>
        <?php endif; ?>
        
        <?php if($buttonText): ?>
            <a href="<?php echo e($buttonLink); ?>" 
               class="inline-block px-8 py-4 text-lg font-semibold text-white transition hover:opacity-90 hover:scale-105"
               style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                <?php echo e($buttonText); ?>

            </a>
        <?php endif; ?>
    </div>
    
    
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/60 animate-bounce">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
</section>
<?php /**PATH /var/www/html/resources/views/components/site-web/blocks/hero.blade.php ENDPATH**/ ?>