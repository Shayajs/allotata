<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['user', 'size' => 'md']));

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

foreach (array_filter((['user', 'size' => 'md']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $presence = $user->presence;
    $status = $presence ? $presence->status : 'offline';
    
    $sizeClasses = [
        'sm' => 'w-2 h-2',
        'md' => 'w-3 h-3',
        'lg' => 'w-4 h-4',
    ];
    
    $statusClasses = [
        'online' => 'bg-green-500 ring-green-500',
        'idle' => 'bg-yellow-500 ring-yellow-500',
        'offline' => 'bg-gray-400 ring-gray-400',
    ];
    
    $statusLabels = [
        'online' => 'En ligne',
        'idle' => 'Inactif',
        'offline' => 'Hors ligne',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $statusClass = $statusClasses[$status] ?? $statusClasses['offline'];
    $statusLabel = $statusLabels[$status] ?? 'Hors ligne';
?>

<span 
    class="presence-badge presence-badge-<?php echo e($status); ?> inline-flex items-center <?php echo e($sizeClass); ?> rounded-full ring-2 ring-white dark:ring-slate-800 <?php echo e($statusClass); ?>"
    data-user-id="<?php echo e($user->id); ?>"
    data-status="<?php echo e($status); ?>"
    title="<?php echo e($statusLabel); ?>"
    aria-label="<?php echo e($statusLabel); ?>"
>
    <span class="sr-only"><?php echo e($statusLabel); ?></span>
</span>
<?php /**PATH /var/www/html/resources/views/components/presence-badge.blade.php ENDPATH**/ ?>