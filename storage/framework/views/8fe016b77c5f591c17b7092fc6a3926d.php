<div>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Services</h2>

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

    <!-- Configuration de l'ordre d'affichage -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Ordre d'affichage des services</h3>
        <form action="<?php echo e(route('entreprise.dashboard.update-mode-ordre', $entreprise->slug)); ?>" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <input type="hidden" name="type" value="services">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-300 sm:shrink-0">Mode de tri :</label>
            <select name="mode_ordre" onchange="this.form.submit()" class="w-full sm:w-auto px-4 py-2.5 sm:py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="manuel" <?php echo e(($entreprise->mode_ordre_services ?? 'manuel') === 'manuel' ? 'selected' : ''); ?>>Manuel (ordre personnalisé)</option>
                <option value="ventes" <?php echo e(($entreprise->mode_ordre_services ?? 'manuel') === 'ventes' ? 'selected' : ''); ?>>Par nombre de réservations</option>
                <option value="statistiques" <?php echo e(($entreprise->mode_ordre_services ?? 'manuel') === 'statistiques' ? 'selected' : ''); ?>>Par statistiques (clics)</option>
            </select>
            <?php if(($entreprise->mode_ordre_services ?? 'manuel') === 'manuel'): ?>
                <button 
                    type="button"
                    onclick="enableReorderServices()"
                    class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-medium rounded-lg transition"
                >
                    Réorganiser manuellement
                </button>
            <?php endif; ?>
        </form>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
            Les 9 premiers services s'affichent directement, les autres dans un menu déroulant sur la page publique.
        </p>
    </div>

    <!-- Section Types de services -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </span>
                Types de services
            </h3>
            <button 
                onclick="openServiceModal()"
                class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all shadow-md hover:shadow-lg"
            >
                + Ajouter
            </button>
        </div>

        <?php if($typesServices && $typesServices->count() > 0): ?>
            <?php
                $servicesCount = $typesServices->count();
                $showExpandButton = $servicesCount > 10;
                $initialServices = $typesServices->take(10);
                $remainingServices = $typesServices->skip(10);
            ?>
            
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" id="services-list-initial">
                <?php $__currentLoopData = $initialServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow <?php echo e($service->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75'); ?>">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?php echo e($service->nom); ?></h4>
                                <?php if($service->images->count() > 0): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">📷 <?php echo e($service->images->count()); ?> image(s)</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 justify-end">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo e($service->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'); ?>">
                                    <?php echo e($service->est_actif ? 'Actif' : 'Inactif'); ?>

                                </span>
                                <?php if(($service->type_structure ?? '') === 'date_butoire'): ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800">Date butoire</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if($service->description): ?>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2"><?php echo e($service->description); ?></p>
                        <?php endif; ?>
                        <div class="flex items-center gap-4 text-sm mb-4">
                            <?php if(($service->type_structure ?? '') !== 'date_butoire'): ?>
                            <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <?php echo e($service->duree_minutes); ?> min
                            </span>
                            <?php endif; ?>
                            <span class="flex items-center gap-1 font-bold text-green-600 dark:text-green-400">
                                <?php echo e(number_format($service->prix, 0, ',', ' ')); ?> €
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                onclick="editServiceFromButton(this)"
                                data-service-id="<?php echo e($service->id); ?>"
                                data-service-nom="<?php echo e(addslashes($service->nom)); ?>"
                                data-service-description="<?php echo e(addslashes($service->description ?? '')); ?>"
                                data-service-duree="<?php echo e($service->duree_minutes); ?>"
                                data-service-prix="<?php echo e($service->prix); ?>"
                                data-service-type-structure="<?php echo e($service->type_structure ?? 'ponctuel'); ?>"
                                data-service-actif="<?php echo e($service->est_actif ? 'true' : 'false'); ?>"
                                data-service-images="<?php echo e(base64_encode(json_encode($service->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values()))); ?>"
                                data-service-options="<?php echo e(base64_encode(json_encode($service->options->map(fn($opt) => ['id' => $opt->id, 'nom' => $opt->nom, 'type' => $opt->type, 'obligatoire' => $opt->obligatoire, 'choices' => $opt->choices])->values()))); ?>"
                                class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                            >
                                Modifier
                            </button>
                            <form action="<?php echo e(route('agenda.service.delete', [$entreprise->slug, $service->id])); ?>" method="POST" onsubmit="return confirm('Supprimer ce service ?');" class="flex-1">
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
                <div id="services-list-expanded" class="hidden grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-4">
                    <?php $__currentLoopData = $remainingServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="p-5 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-lg transition-shadow <?php echo e($service->est_actif ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-700/50 opacity-75'); ?>">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?php echo e($service->nom); ?></h4>
                                    <?php if($service->images->count() > 0): ?>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">📷 <?php echo e($service->images->count()); ?> image(s)</span>
                                    <?php endif; ?>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo e($service->est_actif ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'); ?>">
                                    <?php echo e($service->est_actif ? 'Actif' : 'Inactif'); ?>

                                </span>
                                <?php if(($service->type_structure ?? '') === 'date_butoire'): ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800">Date butoire</span>
                                <?php endif; ?>
                            </div>
                            <?php if($service->description): ?>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 line-clamp-2"><?php echo e($service->description); ?></p>
                            <?php endif; ?>
                            <div class="flex items-center gap-4 text-sm mb-4">
                                <?php if(($service->type_structure ?? '') !== 'date_butoire'): ?>
                                <span class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <?php echo e($service->duree_minutes); ?> min
                                </span>
                                <?php endif; ?>
                                <span class="flex items-center gap-1 font-bold text-green-600 dark:text-green-400">
                                    <?php echo e(number_format($service->prix, 0, ',', ' ')); ?> €
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <button 
                                    onclick="editServiceFromButton(this)"
                                    data-service-id="<?php echo e($service->id); ?>"
                                    data-service-nom="<?php echo e(addslashes($service->nom)); ?>"
                                    data-service-description="<?php echo e(addslashes($service->description ?? '')); ?>"
                                    data-service-duree="<?php echo e($service->duree_minutes); ?>"
                                    data-service-prix="<?php echo e($service->prix); ?>"
                                    data-service-type-structure="<?php echo e($service->type_structure ?? 'ponctuel'); ?>"
                                    data-service-actif="<?php echo e($service->est_actif ? 'true' : 'false'); ?>"
                                    data-service-images="<?php echo e(base64_encode(json_encode($service->images->map(fn($img) => ['id' => $img->id, 'path' => asset('media/' . $img->image_path), 'est_couverture' => $img->est_couverture])->values()))); ?>"
                                    class="flex-1 px-3 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition"
                                >
                                    Modifier
                                </button>
                                <form action="<?php echo e(route('agenda.service.delete', [$entreprise->slug, $service->id])); ?>" method="POST" onsubmit="return confirm('Supprimer ce service ?');" class="flex-1">
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
                        id="services-expand-button"
                        onclick="toggleServicesExpand()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-xl transition-all"
                    >
                        <span id="services-expand-text">Voir plus (<?php echo e($remainingServices->count()); ?> autres)</span>
                        <svg id="services-expand-icon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                
                <script>
                    function toggleServicesExpand() {
                        const expandedList = document.getElementById('services-list-expanded');
                        const expandButton = document.getElementById('services-expand-button');
                        const expandText = document.getElementById('services-expand-text');
                        const expandIcon = document.getElementById('services-expand-icon');
                        
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
                            expandText.textContent = 'Voir plus (<?php echo e($remainingServices->count()); ?> autres)';
                            expandIcon.classList.remove('rotate-180');
                        }
                    }

                    window.enableReorderServices = function() {
                        alert('Fonctionnalité de réordonnancement à venir. Pour l\'instant, vous pouvez modifier l\'ordre manuellement en éditant chaque service.');
                        // TODO: Implémenter le drag & drop avec Sortable.js ou similaire
                    };
                </script>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="mb-4">Aucun service configuré</p>
                <button 
                    onclick="openServiceModal()"
                    class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all"
                >
                    Créer votre premier service
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/entreprise/dashboard/tabs/services.blade.php ENDPATH**/ ?>