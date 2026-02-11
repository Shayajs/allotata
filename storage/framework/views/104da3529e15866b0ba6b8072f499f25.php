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
    
    $style = $content['style'] ?? 'line';
    $spacing = $settings['spacing'] ?? 'medium';
    
    $spacingClass = match($spacing) {
        'small' => 'py-6',
        'medium' => 'py-12',
        'large' => 'py-20',
        default => 'py-12'
    };
?>

<div class="<?php echo e($spacingClass); ?>">
    <div class="max-w-6xl mx-auto px-4">
        <?php switch($style):
            case ('line'): ?>
                <hr class="border-t border-slate-200 dark:border-slate-700">
                <?php break; ?>
                
            <?php case ('dashed'): ?>
                <hr class="border-t-2 border-dashed border-slate-200 dark:border-slate-700">
                <?php break; ?>
                
            <?php case ('dots'): ?>
                <div class="flex justify-center gap-3">
                    <span class="w-2 h-2 rounded-full" style="background: var(--site-primary);"></span>
                    <span class="w-2 h-2 rounded-full" style="background: var(--site-secondary);"></span>
                    <span class="w-2 h-2 rounded-full" style="background: var(--site-primary);"></span>
                </div>
                <?php break; ?>
                
            <?php case ('gradient'): ?>
                <div class="h-1 rounded-full" style="background: linear-gradient(90deg, transparent, var(--site-primary), var(--site-secondary), transparent);"></div>
                <?php break; ?>
                
            <?php case ('space'): ?>
                
                <?php break; ?>
                
            <?php default: ?>
                <hr class="border-t border-slate-200 dark:border-slate-700">
        <?php endswitch; ?>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/components/site-web/blocks/divider.blade.php ENDPATH**/ ?>