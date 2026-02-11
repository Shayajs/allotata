<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Commandes Produits</h2>

    <?php if(session('success')): ?>
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-green-800 dark:text-green-300 font-medium"><?php echo e(session('success')); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-red-800 dark:text-red-300 font-medium"><?php echo e(session('error')); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <form method="GET" action="<?php echo e(route('entreprise.dashboard', $entreprise->slug)); ?>" class="space-y-4">
            <input type="hidden" name="tab" value="commandes">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Statut
                    </label>
                    <select 
                        name="statut" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?php echo e(request('statut') === 'en_attente' ? 'selected' : ''); ?>>En attente</option>
                        <option value="confirmee" <?php echo e(request('statut') === 'confirmee' ? 'selected' : ''); ?>>Confirmée</option>
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
                        onchange="this.form.submit()"
                    >
                        <option value="">Tous</option>
                        <option value="1" <?php echo e(request('est_paye') === '1' ? 'selected' : ''); ?>>Payé</option>
                        <option value="0" <?php echo e(request('est_paye') === '0' ? 'selected' : ''); ?>>Non payé</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Produit
                    </label>
                    <select 
                        name="produit_id" 
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">Tous les produits</option>
                        <?php $__currentLoopData = $produits ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $produit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($produit->id); ?>" <?php echo e(request('produit_id') == $produit->id ? 'selected' : ''); ?>>
                                <?php echo e($produit->nom); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <?php if(request()->hasAny(['statut', 'est_paye', 'produit_id'])): ?>
                        <a href="<?php echo e(route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'commandes'])); ?>" class="w-full px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition-all text-center">
                            Réinitialiser
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des commandes -->
    <?php if($commandes && $commandes->count() > 0): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Produit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Quantité</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prix total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Mode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Paiement</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        <?php $__currentLoopData = $commandes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $commande): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <?php if($commande->produit && $commande->produit->images->first()): ?>
                                            <img 
                                                src="<?php echo e(asset('media/' . $commande->produit->images->first()->image_path)); ?>" 
                                                alt="<?php echo e($commande->produit->nom); ?>"
                                                class="w-12 h-12 rounded-lg object-cover"
                                            >
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                <?php echo e($commande->produit->nom ?? 'Produit supprimé'); ?>

                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 dark:text-white">
                                        <?php echo e($commande->nom_client ?? ($commande->user->name ?? 'Client inconnu')); ?>

                                    </div>
                                    <?php if($commande->user): ?>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            <?php echo e($commande->user->email); ?>

                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-slate-900 dark:text-white"><?php echo e($commande->quantite); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                                        <?php echo e(number_format($commande->prix_total, 2, ',', ' ')); ?> €
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs px-2 py-1 rounded-full 
                                        <?php if($commande->mode_livraison === 'livraison'): ?> bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                        <?php elseif($commande->mode_livraison === 'vente_sur_place'): ?> bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                        <?php else: ?> bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300
                                        <?php endif; ?>">
                                        <?php if($commande->mode_livraison === 'livraison'): ?>
                                            Livraison
                                        <?php elseif($commande->mode_livraison === 'vente_sur_place'): ?>
                                            Sur place
                                        <?php else: ?>
                                            À discuter
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 dark:text-white">
                                        <?php echo e($commande->date_commande->format('d/m/Y')); ?>

                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        <?php echo e($commande->date_commande->format('H:i')); ?>

                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs px-2 py-1 rounded-full font-medium
                                        <?php if($commande->statut === 'en_attente'): ?> bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                        <?php elseif($commande->statut === 'confirmee'): ?> bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                        <?php else: ?> bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                        <?php endif; ?>">
                                        <?php if($commande->statut === 'en_attente'): ?>
                                            En attente
                                        <?php elseif($commande->statut === 'confirmee'): ?>
                                            Confirmée
                                        <?php else: ?>
                                            Annulée
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($commande->est_paye): ?>
                                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 font-medium">
                                            ✓ Payé
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium">
                                            Non payé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a 
                                        href="<?php echo e(route('entreprise.commandes.show', ['slug' => $entreprise->slug, 'id' => $commande->id])); ?>"
                                        class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300"
                                    >
                                        Voir détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <svg class="w-16 h-16 text-slate-400 dark:text-slate-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <p class="text-slate-500 dark:text-slate-400 text-lg">Aucune commande pour le moment.</p>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/entreprise/dashboard/tabs/commandes.blade.php ENDPATH**/ ?>