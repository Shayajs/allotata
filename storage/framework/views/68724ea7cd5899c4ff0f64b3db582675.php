<?php $__env->startSection('title', 'Gestion des entreprises'); ?>
<?php $__env->startSection('header', 'Entreprises'); ?>
<?php $__env->startSection('subheader', 'Gérez et vérifiez les entreprises'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestion des Entreprises</h1>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <form method="GET" action="<?php echo e(route('admin.entreprises.index')); ?>" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Rechercher
                    </label>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo e(request('search')); ?>"
                        placeholder="Nom, type, ville, SIREN..."
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
                        <option value="verifiee" <?php echo e(request('statut') === 'verifiee' ? 'selected' : ''); ?>>Vérifiées</option>
                        <option value="en_attente" <?php echo e(request('statut') === 'en_attente' ? 'selected' : ''); ?>>En attente</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        SIREN vérifié
                    </label>
                    <select 
                        name="siren_verifie" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="">Tous</option>
                        <option value="1" <?php echo e(request('siren_verifie') === '1' ? 'selected' : ''); ?>>Vérifié</option>
                        <option value="0" <?php echo e(request('siren_verifie') === '0' ? 'selected' : ''); ?>>Non vérifié</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        🔍 Rechercher
                    </button>
                </div>
            </div>
            <?php if(request()->hasAny(['search', 'statut', 'siren_verifie'])): ?>
                <a href="<?php echo e(route('admin.entreprises.index')); ?>" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Gérant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Ville</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Réservations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    <?php $__empty_1 = true; $__currentLoopData = $entreprises; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entreprise): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Nom">
                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo e($entreprise->nom); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Type">
                                <div class="text-sm text-slate-600 dark:text-slate-400"><?php echo e($entreprise->type_activite); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Gérant">
                                <div class="text-sm text-slate-600 dark:text-slate-400"><?php echo e($entreprise->user->name); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Ville">
                                <div class="text-sm text-slate-600 dark:text-slate-400"><?php echo e($entreprise->ville ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Réservations">
                                <?php echo e($entreprise->reservations_count); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                                <?php if($entreprise->est_verifiee): ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Vérifiée</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded">En attente</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Actions">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo e(route('admin.entreprises.show', $entreprise)); ?>" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        Voir
                                    </a>
                                    <?php if(!$entreprise->est_verifiee): ?>
                                        <form action="<?php echo e(route('admin.entreprises.verify', $entreprise)); ?>" method="POST" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                Vérifier
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?php echo e(route('admin.entreprises.unverify', $entreprise)); ?>" method="POST" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300">
                                                Désactiver
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                                Aucune entreprise trouvée
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
            <?php echo e($entreprises->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/entreprises/index.blade.php ENDPATH**/ ?>