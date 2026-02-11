<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Documentation'); ?> - Allo Tata</title>
    <?php echo $__env->make('partials.favicon', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->make('partials.theme-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <?php echo $__env->make('partials.super-user-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <nav class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2 text-xl font-bold">
                    <?php
                        use App\Helpers\SiteHelper;
                        $logoUrl = SiteHelper::getLogo('transparent');
                        $siteName = SiteHelper::getSiteName();
                    ?>
                    <?php if($logoUrl): ?>
                        <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($siteName); ?>" class="h-8 w-auto" style="max-height: 32px;">
                    <?php endif; ?>
                    <span class="bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent"><?php echo e($siteName); ?></span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="<?php echo e(route('dev.index')); ?>" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                        Documentation
                    </a>
                    <a href="<?php echo e(route('home')); ?>" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                        Accueil
                    </a>
                    <?php if(auth()->guard()->check()): ?>
                        <?php if(auth()->user()->is_admin): ?>
                            <a href="<?php echo e(route('admin.index')); ?>" class="text-sm font-medium text-amber-600 dark:text-amber-400 hover:underline">
                                Admin
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php echo $__env->yieldContent('content'); ?>
    <?php echo $__env->make('partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.cookie-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/dev/layout.blade.php ENDPATH**/ ?>