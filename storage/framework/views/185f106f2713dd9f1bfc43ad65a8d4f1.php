<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Stock et Produits</h2>

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

    <?php if($errors->any()): ?>
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <p class="text-red-800 dark:text-red-400"><?php echo e($error); ?></p>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <!-- Configuration de l'ordre d'affichage -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Ordre d'affichage des produits</h3>
        <form action="<?php echo e(route('entreprise.dashboard.update-mode-ordre', $entreprise->slug)); ?>" method="POST" class="flex items-center gap-4">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <input type="hidden" name="type" value="produits">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mode de tri :</label>
            <select name="mode_ordre" onchange="this.form.submit()" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="manuel" <?php echo e(($entreprise->mode_ordre_produits ?? 'manuel') === 'manuel' ? 'selected' : ''); ?>>Manuel (ordre personnalisé)</option>
                <option value="ventes" <?php echo e(($entreprise->mode_ordre_produits ?? 'manuel') === 'ventes' ? 'selected' : ''); ?>>Par nombre de ventes</option>
                <option value="statistiques" <?php echo e(($entreprise->mode_ordre_produits ?? 'manuel') === 'statistiques' ? 'selected' : ''); ?>>Par statistiques (clics)</option>
            </select>
            <?php if(($entreprise->mode_ordre_produits ?? 'manuel') === 'manuel'): ?>
                <button 
                    type="button"
                    onclick="enableReorderProduits()"
                    class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-medium rounded-lg transition"
                >
                    Réorganiser manuellement
                </button>
            <?php endif; ?>
        </form>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
            Les 9 premiers produits s'affichent directement, les autres dans un menu déroulant sur la page publique.
        </p>
    </div>

    <!-- Section Produits -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </span>
                Produits
            </h3>
            <button 
                onclick="openProduitModal()"
                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
            >
                + Ajouter un produit
            </button>
        </div>

        <?php if($produits && $produits->count() > 0): ?>
            <?php
                $produitsCount = $produits->count();
                $showExpandButton = $produitsCount > 10;
                $initialProduits = $produits->take(10);
                $remainingProduits = $produits->skip(10);
            ?>
            
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" id="produits-list-initial">
                <?php $__currentLoopData = $initialProduits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $produit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow <?php echo e($produit->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75'); ?>">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?php echo e($produit->nom); ?></h4>
                                <?php if($produit->images->count() > 0): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">📷 <?php echo e($produit->images->count()); ?> image(s)</span>
                                <?php endif; ?>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo e($produit->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'); ?>">
                                <?php echo e($produit->est_actif ? 'Actif' : 'Inactif'); ?>

                            </span>
                        </div>
                        
                        <?php
                            $imageCouverture = $produit->imageCouverture;
                            $premiereImage = $produit->images->first();
                            $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                            $promotion = $produit->promotionActive()->first();
                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                        ?>
                        
                        <?php if($imageAffichee): ?>
                            <div class="mb-3 rounded-lg overflow-hidden">
                                <img src="<?php echo e(asset('media/' . $imageAffichee->image_path)); ?>" alt="<?php echo e($produit->nom); ?>" class="w-full h-32 object-cover">
                            </div>
                        <?php endif; ?>
                        
                        <?php if($produit->description): ?>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2"><?php echo e($produit->description); ?></p>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-4 text-sm mb-3">
                            <?php if($promotion): ?>
                                <div class="flex items-center gap-2">
                                    <span class="line-through text-slate-400 text-xs"><?php echo e(number_format($produit->prix, 2, ',', ' ')); ?> €</span>
                                    <span class="font-bold text-red-600 dark:text-red-400"><?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €</span>
                                    <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded">PROMO</span>
                                </div>
                            <?php else: ?>
                                <span class="font-bold text-green-600 dark:text-green-400"><?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €</span>
                            <?php endif; ?>
                        </div>

                        <!-- Info Stock -->
                        <?php if($produit->gestion_stock === 'disponible_immediatement' && $produit->stock): ?>
                            <div class="mb-3 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-600 dark:text-slate-400">Stock:</span>
                                    <span class="font-bold <?php echo e($produit->stock->quantite_disponible <= $produit->stock->quantite_minimum ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'); ?>">
                                        <?php echo e($produit->stock->quantite_disponible); ?>

                                        <?php if($produit->stock->alerte_stock): ?>
                                            <svg class="w-3 h-3 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if($produit->stock->quantite_minimum > 0): ?>
                                    <div class="flex items-center justify-between text-xs mt-1">
                                        <span class="text-slate-500 dark:text-slate-400">Seuil:</span>
                                        <span class="text-slate-600 dark:text-slate-400"><?php echo e($produit->stock->quantite_minimum); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif($produit->gestion_stock === 'en_attente_commandes'): ?>
                            <div class="mb-3 p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <span class="text-xs text-orange-700 dark:text-orange-400">📦 En attente de commandes</span>
                            </div>
                        <?php endif; ?>

                        <div class="flex gap-2">
                            <button 
                                onclick="editProduitFromButton(this)"
                                data-produit-id="<?php echo e($produit->id); ?>"
                                data-produit-nom="<?php echo e(addslashes($produit->nom)); ?>"
                                data-produit-description="<?php echo e(addslashes($produit->description ?? '')); ?>"
                                data-produit-prix="<?php echo e($produit->prix); ?>"
                                data-produit-gestion-stock="<?php echo e($produit->gestion_stock); ?>"
                                data-produit-quantite-disponible="<?php echo e($produit->stock ? ($produit->stock->quantite_disponible ?? 0) : 0); ?>"
                                data-produit-quantite-minimum="<?php echo e($produit->stock ? ($produit->stock->quantite_minimum ?? 0) : 0); ?>"
                                data-produit-actif="<?php echo e($produit->est_actif ? 'true' : 'false'); ?>"
                                data-produit-livraison-disponible="<?php echo e($produit->livraison_disponible !== null ? ($produit->livraison_disponible ? 'true' : 'false') : 'null'); ?>"
                                data-produit-vente-sur-place-disponible="<?php echo e($produit->vente_sur_place_disponible !== null ? ($produit->vente_sur_place_disponible ? 'true' : 'false') : 'null'); ?>"
                                data-produit-images="<?php echo e(base64_encode(json_encode($produit->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values()))); ?>"
                                class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                            >
                                Modifier
                            </button>
                            <form action="<?php echo e(route('stock.produit.delete', [$entreprise->slug, $produit->id])); ?>" method="POST" onsubmit="return confirm('Supprimer ce produit ?');" class="flex-1">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="w-full px-3 py-2 text-sm font-medium bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            
            <?php if($showExpandButton): ?>
                <div id="produits-list-expanded" class="hidden grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-4">
                    <?php $__currentLoopData = $remainingProduits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $produit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow <?php echo e($produit->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75'); ?>">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?php echo e($produit->nom); ?></h4>
                                    <?php if($produit->images->count() > 0): ?>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">📷 <?php echo e($produit->images->count()); ?> image(s)</span>
                                    <?php endif; ?>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo e($produit->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'); ?>">
                                    <?php echo e($produit->est_actif ? 'Actif' : 'Inactif'); ?>

                                </span>
                            </div>
                            
                            <?php
                                $imageCouverture = $produit->imageCouverture;
                                $premiereImage = $produit->images->first();
                                $imageAffichee = $imageCouverture ? $imageCouverture : $premiereImage;
                                $promotion = $produit->promotionActive()->first();
                                $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                            ?>
                            
                            <?php if($imageAffichee): ?>
                                <div class="mb-3 rounded-lg overflow-hidden">
                                    <img src="<?php echo e(asset('media/' . $imageAffichee->image_path)); ?>" alt="<?php echo e($produit->nom); ?>" class="w-full h-32 object-cover">
                                </div>
                            <?php endif; ?>
                            
                            <?php if($produit->description): ?>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2"><?php echo e($produit->description); ?></p>
                            <?php endif; ?>
                            
                            <div class="flex items-center gap-4 text-sm mb-3">
                                <?php if($promotion): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="line-through text-slate-400 text-xs"><?php echo e(number_format($produit->prix, 2, ',', ' ')); ?> €</span>
                                        <span class="font-bold text-red-600 dark:text-red-400"><?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €</span>
                                        <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded">PROMO</span>
                                    </div>
                                <?php else: ?>
                                    <span class="font-bold text-green-600 dark:text-green-400"><?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €</span>
                                <?php endif; ?>
                            </div>

                            <!-- Info Stock -->
                            <?php if($produit->gestion_stock === 'disponible_immediatement' && $produit->stock): ?>
                                <div class="mb-3 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-600 dark:text-slate-400">Stock:</span>
                                        <span class="font-bold <?php echo e($produit->stock->quantite_disponible <= $produit->stock->quantite_minimum ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'); ?>">
                                            <?php echo e($produit->stock->quantite_disponible); ?>

                                            <?php if($produit->stock->alerte_stock): ?>
                                                <svg class="w-3 h-3 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php if($produit->stock->quantite_minimum > 0): ?>
                                        <div class="flex items-center justify-between text-xs mt-1">
                                            <span class="text-slate-500 dark:text-slate-400">Seuil:</span>
                                            <span class="text-slate-600 dark:text-slate-400"><?php echo e($produit->stock->quantite_minimum); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif($produit->gestion_stock === 'en_attente_commandes'): ?>
                                <div class="mb-3 p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                    <span class="text-xs text-orange-700 dark:text-orange-400">📦 En attente de commandes</span>
                                </div>
                            <?php endif; ?>

                            <div class="flex gap-2">
                                <button 
                                    onclick="editProduitFromButton(this)"
                                    data-produit-id="<?php echo e($produit->id); ?>"
                                    data-produit-nom="<?php echo e(addslashes($produit->nom)); ?>"
                                    data-produit-description="<?php echo e(addslashes($produit->description ?? '')); ?>"
                                    data-produit-prix="<?php echo e($produit->prix); ?>"
                                    data-produit-gestion-stock="<?php echo e($produit->gestion_stock); ?>"
                                    data-produit-quantite-disponible="<?php echo e($produit->stock ? ($produit->stock->quantite_disponible ?? 0) : 0); ?>"
                                    data-produit-quantite-minimum="<?php echo e($produit->stock ? ($produit->stock->quantite_minimum ?? 0) : 0); ?>"
                                    data-produit-actif="<?php echo e($produit->est_actif ? 'true' : 'false'); ?>"
                                    data-produit-livraison-disponible="<?php echo e($produit->livraison_disponible !== null ? ($produit->livraison_disponible ? 'true' : 'false') : 'null'); ?>"
                                    data-produit-vente-sur-place-disponible="<?php echo e($produit->vente_sur_place_disponible !== null ? ($produit->vente_sur_place_disponible ? 'true' : 'false') : 'null'); ?>"
                                    data-produit-images="<?php echo e(base64_encode(json_encode($produit->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values()))); ?>"
                                    class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                                >
                                    Modifier
                                </button>
                                <form action="<?php echo e(route('stock.produit.delete', [$entreprise->slug, $produit->id])); ?>" method="POST" onsubmit="return confirm('Supprimer ce produit ?');" class="flex-1">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="w-full px-3 py-2 text-sm font-medium bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                
                <div class="mt-6 text-center">
                    <button 
                        id="produits-expand-button"
                        onclick="toggleProduitsExpand()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition-all"
                    >
                        <span id="produits-expand-text">Voir plus (<?php echo e($remainingProduits->count()); ?> autres)</span>
                        <svg id="produits-expand-icon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                
                <script>
                    function toggleProduitsExpand() {
                        const expandedList = document.getElementById('produits-list-expanded');
                        const expandButton = document.getElementById('produits-expand-button');
                        const expandText = document.getElementById('produits-expand-text');
                        const expandIcon = document.getElementById('produits-expand-icon');
                        
                        if (expandedList.classList.contains('hidden')) {
                            expandedList.classList.remove('hidden');
                            expandedList.style.opacity = '0';
                            setTimeout(() => {
                                expandedList.style.transition = 'opacity 0.3s ease-in-out';
                                expandedList.style.opacity = '1';
                            }, 10);
                            expandText.textContent = 'Voir moins';
                            expandIcon.classList.add('rotate-180');
                        } else {
                            expandedList.style.transition = 'opacity 0.3s ease-in-out';
                            expandedList.style.opacity = '0';
                            setTimeout(() => {
                                expandedList.classList.add('hidden');
                            }, 300);
                            expandText.textContent = 'Voir plus (<?php echo e($remainingProduits->count()); ?> autres)';
                            expandIcon.classList.remove('rotate-180');
                        }
                    }

                    window.enableReorderProduits = function() {
                        alert('Fonctionnalité de réordonnancement à venir. Pour l\'instant, vous pouvez modifier l\'ordre manuellement en éditant chaque produit.');
                        // TODO: Implémenter le drag & drop avec Sortable.js ou similaire
                    };
                </script>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-lg font-medium mb-2">Aucun produit enregistré</p>
                <p class="text-sm">Commencez par ajouter votre premier produit.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Le modal est inclus dans index.blade.php pour éviter les problèmes de z-index -->
<?php /**PATH /var/www/html/resources/views/entreprise/dashboard/tabs/stock.blade.php ENDPATH**/ ?>