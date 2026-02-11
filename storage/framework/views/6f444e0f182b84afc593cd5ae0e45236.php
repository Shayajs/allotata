<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'user' => null,
    'size' => 'md',
    'class' => '',
]));

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

foreach (array_filter(([
    'user' => null,
    'size' => 'md',
    'class' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $sizes = [
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'md' => 'w-10 h-10 text-base',
        'lg' => 'w-12 h-12 text-lg',
        'xl' => 'w-16 h-16 text-xl',
        '2xl' => 'w-20 h-20 text-2xl',
    ];
    $sizeClass = isset($sizes[$size]) ? $sizes[$size] : $sizes['md'];
    
    $name = ($user && $user->name) ? $user->name : 'U';
    $initial = strtoupper(substr($name, 0, 1));
    $photo = ($user && $user->photo_profil) ? $user->photo_profil : null;
?>

<?php if($photo): ?>
    <img 
        src="<?php echo e(asset('media/' . $photo)); ?>" 
        alt="<?php echo e($name); ?>"
        <?php echo e($attributes->merge(['class' => "{$sizeClass} rounded-full object-cover border-2 border-slate-200 dark:border-slate-600 {$class}"])); ?>

    >
<?php else: ?>
    <div <?php echo e($attributes->merge(['class' => "{$sizeClass} rounded-full bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white font-bold {$class}"])); ?>>
        <?php echo e($initial); ?>

    </div>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/avatar.blade.php ENDPATH**/ ?>