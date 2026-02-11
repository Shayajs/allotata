
<div class="flex flex-col">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Vue d'ensemble</h2>

    <!-- Statut des rôles -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 order-1">
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">Statut Client</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php if($user->est_client): ?>
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Vous pouvez effectuer des achats
                        <?php else: ?>
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Client désactivé
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">Statut Gérant</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php if($user->est_gerant): ?>
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Vous gérez <?php echo e($entreprises->count()); ?> entreprise(s)
                        <?php else: ?>
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Aucune entreprise pour le moment
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques globales (pour les gérants) -->
    <?php if($user->est_gerant && $stats): ?>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 mb-6 order-3 md:order-2">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Statistiques globales
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total réservations</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo e($stats['total_reservations']); ?></p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu total</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo e(number_format($stats['revenu_total'], 2, ',', ' ')); ?> €</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Revenu payé</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo e(number_format($stats['revenu_paye'], 2, ',', ' ')); ?> €</p>
                </div>
                <div class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-orange-200 dark:border-orange-800">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Ce mois</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400"><?php echo e(number_format($stats['revenu_ce_mois'], 2, ',', ' ')); ?> €</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo e($stats['reservations_ce_mois']); ?> réservation(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">Confirmées</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo e($stats['reservations_confirmees']); ?></p>
                </div>
                <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">En attente</p>
                    <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo e($stats['reservations_en_attente']); ?></p>
                </div>
                <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">Terminées</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400"><?php echo e($stats['reservations_terminees']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Réservations en attente (pour les gérants) -->
    <?php if($user->est_gerant && $reservationsEnAttente->count() > 0): ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-500 dark:border-yellow-600 rounded-xl p-6 mb-6 order-4 md:order-3">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                        <svg class="w-6 h-6 flex-shrink-0 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Réservations en attente
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">
                        <?php echo e($reservationsEnAttente->count()); ?> réservation(s) nécessitent votre validation
                    </p>
                </div>
                <button onclick="showTab('reservations')" class="px-4 py-2 text-sm bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition">
                    Voir tout →
                </button>
            </div>
            <div class="space-y-2">
                <?php $__currentLoopData = $reservationsEnAttente->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-3 bg-white dark:bg-slate-800 rounded-lg border border-yellow-200 dark:border-yellow-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <?php if (isset($component)) { $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.avatar','data' => ['user' => $reservation->user,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reservation->user),'size' => 'sm']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b)): ?>
<?php $attributes = $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b; ?>
<?php unset($__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b)): ?>
<?php $component = $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b; ?>
<?php unset($__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b); ?>
<?php endif; ?>
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white text-sm"><?php echo e($reservation->nom_client_complet ?? 'Client non inscrit'); ?></p>
                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                    <?php echo e($reservation->entreprise->nom); ?> - <?php echo e($reservation->date_reservation->format('d/m à H:i')); ?>

                                </p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                            <?php echo e(number_format($reservation->prix, 2, ',', ' ')); ?> €
                        </span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Accès rapide aux entreprises -->
    <?php if($entreprises->count() > 0): ?>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 mb-6 md:mb-0 order-2 md:order-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Mes entreprises
                </h3>
                <button onclick="showTab('entreprises')" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                    Voir tout →
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php $__currentLoopData = $entreprises->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entreprise): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('entreprise.dashboard', $entreprise->slug)); ?>" class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-500 dark:hover:border-green-500 transition-all group">
                        <div class="flex items-center gap-3">
                            <?php if($entreprise->logo): ?>
                                <img src="<?php echo e(asset('media/' . $entreprise->logo)); ?>" alt="<?php echo e($entreprise->nom); ?>" class="w-12 h-12 rounded-lg object-cover">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-lg bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                                    <?php echo e(strtoupper(substr($entreprise->nom, 0, 1))); ?>

                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 dark:text-white truncate group-hover:text-green-600 dark:group-hover:text-green-400 transition"><?php echo e($entreprise->nom); ?></p>
                                <p class="text-xs text-slate-600 dark:text-slate-400"><?php echo e($entreprise->type_activite); ?></p>
                            </div>
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-green-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-12 bg-slate-50 dark:bg-slate-700/50 rounded-xl mb-6 md:mb-0 order-2 md:order-4">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucune entreprise</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Créez votre première entreprise pour proposer vos services.
            </p>
            <div class="mt-6">
                <a href="<?php echo e(route('entreprise.create')); ?>" class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                    + Créer mon entreprise
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/dashboard/tabs/accueil.blade.php ENDPATH**/ ?>