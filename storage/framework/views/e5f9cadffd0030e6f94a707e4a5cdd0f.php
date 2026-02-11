<?php $__env->startSection('title', 'Gestion des utilisateurs'); ?>
<?php $__env->startSection('header', 'Utilisateurs'); ?>
<?php $__env->startSection('subheader', 'Gérez tous les utilisateurs de la plateforme'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Liste des utilisateurs</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Gérez tous les utilisateurs de la plateforme</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo e(route('admin.users.deleted')); ?>" class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition border border-red-200 dark:border-red-900/30 rounded-lg">
            📦 Comptes supprimés
        </a>
        <a href="<?php echo e(route('admin.index')); ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition border border-slate-200 dark:border-slate-700 rounded-lg">
            ← Retour au Dashboard
        </a>
    </div>
</div>

<!-- Barre de recherche et filtres -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
    <form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Rechercher
                </label>
                <input 
                    type="text" 
                    name="search" 
                    value="<?php echo e(request('search')); ?>"
                    placeholder="Nom, email..."
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Filtrer par rôle
                </label>
                <select 
                    name="role" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                    <option value="">Tous les rôles</option>
                    <option value="client" <?php echo e(request('role') === 'client' ? 'selected' : ''); ?>>Client</option>
                    <option value="gerant" <?php echo e(request('role') === 'gerant' ? 'selected' : ''); ?>>Gérant</option>
                    <option value="admin" <?php echo e(request('role') === 'admin' ? 'selected' : ''); ?>>Admin</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Filtrer par statut
                </label>
                <select 
                    name="statut" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                    <option value="">Tous les statuts</option>
                    <option value="normal" <?php echo e(request('statut') === 'normal' ? 'selected' : ''); ?>>Normal</option>
                    <option value="limite" <?php echo e(request('statut') === 'limite' ? 'selected' : ''); ?>>Limité</option>
                    <option value="interdit" <?php echo e(request('statut') === 'interdit' ? 'selected' : ''); ?>>Interdit</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Email vérifié
                </label>
                <select 
                    name="email_verified" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                    <option value="">Tous</option>
                    <option value="1" <?php echo e(request('email_verified') === '1' ? 'selected' : ''); ?>>Vérifiés</option>
                    <option value="0" <?php echo e(request('email_verified') === '0' ? 'selected' : ''); ?>>Non vérifiés</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    🔍 Rechercher
                </button>
            </div>
        </div>
        <?php if(request()->hasAny(['search', 'role', 'statut', 'email_verified'])): ?>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Email vérifié</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Rôles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Entreprises</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Réservations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Inscrit le</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="px-6 py-4 whitespace-nowrap" data-user-id="<?php echo e($user->id); ?>" data-label="Nom">
                            <div class="flex items-center gap-3">
                                <?php if (isset($component)) { $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.avatar','data' => ['user' => $user,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => 'sm']); ?>
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
                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo e($user->name); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Statut">
                            <?php if (isset($component)) { $__componentOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.presence-badge','data' => ['user' => $user,'size' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('presence-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => 'md']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9)): ?>
<?php $attributes = $__attributesOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9; ?>
<?php unset($__attributesOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9)): ?>
<?php $component = $__componentOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9; ?>
<?php unset($__componentOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9); ?>
<?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Email">
                            <div class="text-sm text-slate-600 dark:text-slate-400"><?php echo e($user->email); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Email vérifié">
                            <?php if($user->hasVerifiedEmail()): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Vérifié
                                </span>
                            <?php else: ?>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Non vérifié
                                    </span>
                                    <form action="<?php echo e(route('admin.email-logs.verify-user', $user)); ?>" method="POST" class="inline-block">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" 
                                                onclick="return confirm('Vérifier manuellement l\'email de <?php echo e($user->email); ?> ?')"
                                                class="px-2 py-1 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded transition-colors"
                                                title="Vérifier manuellement l'email">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Rôles">
                            <div class="flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <?php if($user->est_client): ?>
                                        <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Client</span>
                                    <?php endif; ?>
                                    <?php if($user->est_gerant): ?>
                                        <span class="px-2 py-1 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded">Gérant</span>
                                    <?php endif; ?>
                                    <?php if($user->is_admin): ?>
                                        <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded">Admin</span>
                                    <?php endif; ?>
                                </div>
                                <?php
                                    $statut = $user->statut_compte ?? 'normal';
                                    $statutConfig = [
                                        'normal' => ['label' => 'Normal', 'color' => 'green'],
                                        'limite' => ['label' => 'Limité', 'color' => 'yellow'],
                                        'interdit' => ['label' => 'Interdit', 'color' => 'red'],
                                        'supprime' => ['label' => 'Supprimé', 'color' => 'gray'],
                                    ];
                                    $config = $statutConfig[$statut] ?? $statutConfig['normal'];
                                ?>
                                <?php if($statut !== 'normal'): ?>
                                    <span class="px-2 py-1 text-xs font-bold rounded
                                        <?php if($config['color'] === 'yellow'): ?> bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                        <?php elseif($config['color'] === 'red'): ?> bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400
                                        <?php else: ?> bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-400
                                        <?php endif; ?>">
                                        <?php echo e($config['label']); ?>

                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Entreprises">
                            <?php echo e($user->entreprises_count); ?>

                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Réservations">
                            <?php echo e($user->reservations_count); ?>

                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400" data-label="Inscrit le">
                            <?php echo e($user->created_at->format('d/m/Y')); ?>

                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Actions">
                            <div class="flex items-center justify-end gap-3">
                                <?php if(auth()->id() !== $user->id): ?>
                                    <form action="<?php echo e(route('admin.users.impersonate', $user)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2.5 py-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors flex items-center gap-1" title="Se connecter en tant que <?php echo e($user->name); ?>">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Connecter
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 font-semibold">
                                    Voir
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">
                            Aucun utilisateur trouvé
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
        <?php echo e($users->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/users/index.blade.php ENDPATH**/ ?>