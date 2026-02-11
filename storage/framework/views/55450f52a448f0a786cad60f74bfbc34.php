<?php $__env->startSection('title', 'Gestion des réservations'); ?>
<?php $__env->startSection('header', 'Réservations'); ?>
<?php $__env->startSection('subheader', 'Gérez toutes les réservations'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestion des Réservations</h1>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <form method="GET" action="<?php echo e(route('admin.reservations.index')); ?>" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Rechercher
                    </label>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo e(request('search')); ?>"
                        placeholder="Client, entreprise, service..."
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Statut
                    </label>
                    <select 
                        name="statut" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?php echo e(request('statut') === 'en_attente' ? 'selected' : ''); ?>>En attente</option>
                        <option value="confirmee" <?php echo e(request('statut') === 'confirmee' ? 'selected' : ''); ?>>Confirmée</option>
                        <option value="terminee" <?php echo e(request('statut') === 'terminee' ? 'selected' : ''); ?>>Terminée</option>
                        <option value="annulee" <?php echo e(request('statut') === 'annulee' ? 'selected' : ''); ?>>Annulée</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Paiement
                    </label>
                    <select 
                        name="est_paye" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="">Tous</option>
                        <option value="1" <?php echo e(request('est_paye') === '1' ? 'selected' : ''); ?>>Payé</option>
                        <option value="0" <?php echo e(request('est_paye') === '0' ? 'selected' : ''); ?>>Non payé</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Date début
                    </label>
                    <input 
                        type="date" 
                        name="date_debut" 
                        value="<?php echo e(request('date_debut')); ?>"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        🔍 Rechercher
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Date fin
                    </label>
                    <input 
                        type="date" 
                        name="date_fin" 
                        value="<?php echo e(request('date_fin')); ?>"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
            </div>
            <?php if(request()->hasAny(['search', 'statut', 'est_paye', 'date_debut', 'date_fin'])): ?>
                <a href="<?php echo e(route('admin.reservations.index')); ?>" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                    Réinitialiser les filtres
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto table-responsive-to-cards">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Entreprise</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Paiement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    <?php $__empty_1 = true; $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Client">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                    <?php if (isset($component)) { $__componentOriginal590152d7e93f2a6ba04de164b0aa0e55 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal590152d7e93f2a6ba04de164b0aa0e55 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-name','data' => ['user' => $reservation->user]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-name'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reservation->user)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal590152d7e93f2a6ba04de164b0aa0e55)): ?>
<?php $attributes = $__attributesOriginal590152d7e93f2a6ba04de164b0aa0e55; ?>
<?php unset($__attributesOriginal590152d7e93f2a6ba04de164b0aa0e55); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal590152d7e93f2a6ba04de164b0aa0e55)): ?>
<?php $component = $__componentOriginal590152d7e93f2a6ba04de164b0aa0e55; ?>
<?php unset($__componentOriginal590152d7e93f2a6ba04de164b0aa0e55); ?>
<?php endif; ?>
                                    <?php if(!$reservation->user && $reservation->nom_client): ?>
                                        <?php echo e($reservation->nom_client); ?>

                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">
                                    <?php echo e($reservation->user?->email ?? ($reservation->email_client ?? 'N/A')); ?>

                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Entreprise">
                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo e($reservation->entreprise->nom); ?></div>
                                <div class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($reservation->entreprise->type_activite); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Date">
                                <div class="text-sm text-slate-900 dark:text-white"><?php echo e($reservation->date_reservation->format('d/m/Y')); ?></div>
                                <div class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($reservation->date_reservation->format('H:i')); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Prix">
                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo e(number_format($reservation->prix, 2, ',', ' ')); ?> €</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Paiement">
                                <?php if($reservation->est_paye): ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Payé</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">Non payé</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                                <span class="px-2 py-1 text-xs rounded
                                    <?php if($reservation->statut === 'confirmee'): ?> bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                    <?php elseif($reservation->statut === 'annulee'): ?> bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400
                                    <?php elseif($reservation->statut === 'terminee'): ?> bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400
                                    <?php else: ?> bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                    <?php endif; ?>">
                                    <?php echo e(ucfirst(str_replace('_', ' ', $reservation->statut))); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Actions">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo e(route('admin.reservations.show', $reservation)); ?>" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        Voir
                                    </a>
                                    <?php if(!$reservation->est_paye): ?>
                                        <form action="<?php echo e(route('admin.reservations.mark-paid', $reservation)); ?>" method="POST" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                Marquer payé
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                                Aucune réservation trouvée
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            <?php echo e($reservations->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/reservations/index.blade.php ENDPATH**/ ?>