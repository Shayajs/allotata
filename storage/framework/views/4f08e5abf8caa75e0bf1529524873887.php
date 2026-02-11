<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Notifications - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->make('partials.theme-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-4">
                        <?php echo $__env->make('components.mobile-nav', ['navType' => 'dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <a href="<?php echo e(route('dashboard')); ?>" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                    </div>
                    <div class="hidden md:flex items-center gap-4">
                        <a href="<?php echo e(route('dashboard')); ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        Notifications
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">
                        <?php if($nombreNonLues > 0): ?>
                            Vous avez <?php echo e($nombreNonLues); ?> notification<?php echo e($nombreNonLues > 1 ? 's' : ''); ?> non lue<?php echo e($nombreNonLues > 1 ? 's' : ''); ?>

                        <?php else: ?>
                            Aucune notification non lue
                        <?php endif; ?>
                    </p>
                </div>
                <?php if($nombreNonLues > 0): ?>
                    <form action="<?php echo e(route('notifications.marquer-toutes-lues')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition text-sm font-medium">
                            Tout marquer comme lu
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if(session('success')): ?>
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400"><?php echo e(session('success')); ?></p>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6">
                <form method="GET" action="<?php echo e(route('notifications.index')); ?>" class="flex flex-col sm:flex-row flex-wrap gap-4">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Type
                        </label>
                        <select 
                            name="type" 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous les types</option>
                            <option value="reservation" <?php echo e(request('type') === 'reservation' ? 'selected' : ''); ?>>Réservations</option>
                            <option value="paiement" <?php echo e(request('type') === 'paiement' ? 'selected' : ''); ?>>Paiements</option>
                            <option value="rappel" <?php echo e(request('type') === 'rappel' ? 'selected' : ''); ?>>Rappels</option>
                            <option value="systeme" <?php echo e(request('type') === 'systeme' ? 'selected' : ''); ?>>Système</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Statut
                        </label>
                        <select 
                            name="statut" 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Toutes</option>
                            <option value="non_lue" <?php echo e(request('statut') === 'non_lue' ? 'selected' : ''); ?>>Non lues</option>
                            <option value="lue" <?php echo e(request('statut') === 'lue' ? 'selected' : ''); ?>>Lues</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            🔍 Filtrer
                        </button>
                        <?php if(request()->hasAny(['type', 'statut'])): ?>
                            <a href="<?php echo e(route('notifications.index')); ?>" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition text-sm">
                                ✕
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des notifications -->
            <?php if($notifications->count() > 0): ?>
                <div class="space-y-3">
                    <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 hover:border-green-500 dark:hover:border-green-500 transition-all <?php echo e(!$notification->est_lue ? 'ring-2 ring-green-500/20' : ''); ?>">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                                <div class="flex items-start gap-4 flex-1">
                                    <div class="flex-shrink-0">
                                        <?php if($notification->type === 'reservation'): ?>
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        <?php elseif($notification->type === 'paiement'): ?>
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                            </div>
                                        <?php elseif($notification->type === 'rappel'): ?>
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white">
                                                <?php echo e($notification->titre); ?>

                                            </h3>
                                            <?php if(!$notification->est_lue): ?>
                                                <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                    Nouveau
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-slate-600 dark:text-slate-400 mb-2 text-sm sm:text-base whitespace-pre-line">
                                            <?php echo e($notification->message); ?>

                                        </p>
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs text-slate-500 dark:text-slate-400">
                                            <span><?php echo e($notification->created_at->format('d/m/Y à H:i')); ?></span>
                                            <?php if($notification->est_lue): ?>
                                                <span class="hidden sm:inline">Lu le <?php echo e($notification->lue_at->format('d/m/Y à H:i')); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <!-- Actions - empilées sur mobile -->
                                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-200 dark:border-slate-700">
                                    <?php if($notification->lien): ?>
                                        <a href="<?php echo e($notification->lien); ?>" class="flex-1 sm:flex-none text-center px-3 py-2 text-sm bg-green-100 dark:bg-green-900/30 hover:bg-green-200 dark:hover:bg-green-900/50 text-green-800 dark:text-green-400 rounded-lg transition">
                                            Voir →
                                        </a>
                                    <?php endif; ?>
                                    <?php if(!$notification->est_lue): ?>
                                        <form action="<?php echo e(route('notifications.marquer-lue', $notification->id)); ?>" method="POST" class="flex-1 sm:flex-none">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="w-full px-3 py-2 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition">
                                                ✓ Lu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="<?php echo e(route('notifications.destroy', $notification->id)); ?>" method="POST" onsubmit="return confirm('Supprimer cette notification ?');" class="flex-1 sm:flex-none">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="w-full px-3 py-2 text-sm bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="mt-6">
                    <?php echo e($notifications->links()); ?>

                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune notification</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Vous n'avez pas encore de notifications.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </body>
</html>

<?php /**PATH /var/www/html/resources/views/notifications/index.blade.php ENDPATH**/ ?>