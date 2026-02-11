<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Conversation - <?php echo e($entreprise->nom); ?></title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <script>
            // Configuration Reverb pour la présence en temps réel
            window.REVERB_APP_ID = '<?php echo e(env("REVERB_APP_ID", "reverb-app")); ?>';
            window.REVERB_APP_KEY = '<?php echo e(env("REVERB_APP_KEY", "reverb-key")); ?>';
            window.REVERB_HOST = '<?php echo e(env("REVERB_HOST", "127.0.0.2")); ?>';
            window.REVERB_PORT = '<?php echo e(env("REVERB_PORT", "8080")); ?>';
            window.REVERB_SCHEME = '<?php echo e(env("REVERB_SCHEME", "http")); ?>';
            window.currentUserId = <?php echo e(auth()->id() ?? 'null'); ?>;
        </script>
        <?php echo $__env->make('partials.theme-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen">
        <!-- Navigation améliorée -->
        <nav class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg border-b border-slate-200/50 dark:border-slate-700/50 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="<?php echo e(route('messagerie.index')); ?>" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent hover:from-green-600 hover:to-orange-600 transition flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Messagerie</span>
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="<?php echo e(route('messagerie.index')); ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            Liste des conversations
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Colonne principale : Messages -->
                <div class="lg:col-span-2 space-y-6">
            <!-- En-tête de conversation amélioré -->
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 p-6 mb-6">
                <div class="flex items-center gap-4">
                    <?php if(isset($isGerant) && $isGerant): ?>
                        <div class="relative" data-user-id="<?php echo e($conversation->user->id); ?>">
                            <?php if (isset($component)) { $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.avatar','data' => ['user' => $conversation->user,'size' => '2xl','class' => 'shadow-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($conversation->user),'size' => '2xl','class' => 'shadow-lg']); ?>
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
                            <div class="absolute bottom-0 right-0">
                                <?php if (isset($component)) { $__componentOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf9a1e3c05c0a3a3f4a27c6d085b462b9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.presence-badge','data' => ['user' => $conversation->user,'size' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('presence-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($conversation->user),'size' => 'md']); ?>
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
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                                    <?php echo e($conversation->user->name); ?>

                                </h1>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <?php echo e($conversation->user->email); ?>

                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <?php echo e($entreprise->nom); ?>

                            </p>
                        </div>
                    <?php else: ?>
                        <div class="relative">
                            <?php if($entreprise->logo): ?>
                                <img 
                                    src="<?php echo e(asset('media/' . $entreprise->logo)); ?>" 
                                    alt="<?php echo e($entreprise->nom); ?>"
                                    class="w-20 h-20 rounded-2xl object-cover border-3 border-slate-200 dark:border-slate-700 shadow-lg"
                                >
                            <?php else: ?>
                                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-2xl border-3 border-slate-200 dark:border-slate-700 shadow-lg">
                                    <?php echo e(strtoupper(substr($entreprise->nom, 0, 1))); ?>

                                </div>
                            <?php endif; ?>
                            <div class="absolute bottom-0 right-0 w-5 h-5 bg-green-500 rounded-full border-3 border-white dark:border-slate-800"></div>
                        </div>
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1">
                                <?php echo e($entreprise->nom); ?>

                            </h1>
                            <p class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <?php echo e($entreprise->type_activite); ?>

                            </p>
                            <?php if($entreprise->afficher_nom_gerant && $entreprise->user): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Gérée par <?php echo e($entreprise->user->name); ?>

                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($conversation->reservation) && $conversation->reservation): ?>
                        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <span class="text-sm font-semibold text-blue-900 dark:text-blue-300">Réservation #<?php echo e($conversation->reservation->id); ?></span>
                            </div>
                            <p class="text-xs text-blue-700 dark:text-blue-400">
                                Cette conversation concerne la réservation du <?php echo e($conversation->reservation->date_reservation->format('d/m/Y à H:i')); ?>

                            </p>
                            <?php if(isset($isGerant) && $isGerant): ?>
                                <a href="<?php echo e(route('reservations.show', [$entreprise->slug, $conversation->reservation->id])); ?>" class="mt-2 inline-block text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    Voir la réservation →
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($conversation->produit_id && $conversation->produit): ?>
                        <?php
                            $produit = $conversation->produit;
                            $promotion = $produit->promotionActive()->first();
                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                        ?>
                        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span class="text-sm font-semibold text-green-900 dark:text-green-300">Commande de produit</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <?php if($produit->imageCouverture || $produit->images->first()): ?>
                                    <img src="<?php echo e(asset('media/' . ($produit->imageCouverture ? $produit->imageCouverture->image_path : $produit->images->first()->image_path))); ?>" alt="<?php echo e($produit->nom); ?>" class="w-16 h-16 rounded-lg object-cover">
                                <?php endif; ?>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-900 dark:text-white"><?php echo e($produit->nom); ?></h4>
                                    <?php if($promotion): ?>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">
                                            <span class="line-through text-slate-400"><?php echo e(number_format($produit->prix, 2, ',', ' ')); ?> €</span>
                                            <span class="font-bold text-red-600 dark:text-red-400 ml-1"><?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €</span>
                                            <span class="ml-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded">PROMO</span>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-sm font-bold text-green-600 dark:text-green-400"><?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($conversation->type_service_id && $conversation->typeService): ?>
                        <?php
                            $service = $conversation->typeService;
                        ?>
                        <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <span class="text-sm font-semibold text-purple-900 dark:text-purple-300">Demande de service</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <?php if($service->imageCouverture || $service->images->first()): ?>
                                    <img src="<?php echo e(asset('media/' . ($service->imageCouverture ? $service->imageCouverture->image_path : $service->images->first()->image_path))); ?>" alt="<?php echo e($service->nom); ?>" class="w-16 h-16 rounded-lg object-cover">
                                <?php endif; ?>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-900 dark:text-white"><?php echo e($service->nom); ?></h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        <?php echo e(number_format($service->prix, 2, ',', ' ')); ?> € • <?php echo e($service->duree_minutes); ?> min
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(session('success')): ?>
                <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 border border-green-200 dark:border-green-800 rounded-xl shadow-sm">
                    <p class="text-green-800 dark:text-green-400 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo e(session('success')); ?>

                    </p>
                </div>
            <?php endif; ?>

            <?php if(session('info')): ?>
                <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border border-blue-200 dark:border-blue-800 rounded-xl shadow-sm">
                    <p class="text-blue-800 dark:text-blue-400 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo e(session('info')); ?>

                    </p>
                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 border border-red-200 dark:border-red-800 rounded-xl shadow-sm">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p class="text-red-800 dark:text-red-400 font-medium flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php echo e($error); ?>

                        </p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <!-- Récapitulatif de proposition de RDV active amélioré -->
            <?php if(isset($propositionActive) && $propositionActive): ?>
                <?php
                    // S'assurer que les relations sont chargées
                    $propositionActive->loadMissing(['auteur', 'entreprise', 'conversation.user']);
                    
                    // Déterminer qui est l'auteur et qui est le destinataire
                    $currentUser = Auth::user();
                    $auteurUserId = $propositionActive->auteur_user_id ?? $propositionActive->user_id ?? null;
                    $currentUserId = $currentUser->id;
                    
                    // L'auteur est celui dont l'ID correspond à auteur_user_id dans la proposition
                    $isCurrentUserAuthor = ($auteurUserId === $currentUserId);
                ?>
                <div class="bg-gradient-to-br from-green-50 via-orange-50 to-green-50 dark:from-green-900/20 dark:via-orange-900/20 dark:to-green-900/20 border-2 border-green-300 dark:border-green-700 rounded-2xl p-6 mb-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center text-white text-2xl shadow-md">
                                📅
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Proposition de rendez-vous</h3>
                                <?php if($isCurrentUserAuthor): ?>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 flex items-center gap-1">
                                        <span>✉️</span>
                                        <span>Vous avez proposé cette modification à <?php echo e($propositionActive->nom_destinataire); ?></span>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 flex items-center gap-1">
                                        <span>📨</span>
                                        <span><?php echo e($propositionActive->nom_auteur); ?> vous propose une modification</span>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if($propositionActive->statut === 'proposee' || $propositionActive->statut === 'negociee'): ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                En attente de réponse
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Date</p>
                            <p class="font-bold text-lg text-slate-900 dark:text-white"><?php echo e($propositionActive->date_rdv->format('d/m/Y')); ?></p>
                        </div>
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Heure</p>
                            <p class="font-bold text-lg text-slate-900 dark:text-white"><?php echo e(\Carbon\Carbon::parse($propositionActive->heure_debut)->format('H:i')); ?> - <?php echo e(\Carbon\Carbon::parse($propositionActive->heure_fin)->format('H:i')); ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">(<?php echo e($propositionActive->duree_minutes); ?> min)</p>
                        </div>
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Prix proposé</p>
                            <p class="font-bold text-lg text-green-600 dark:text-green-400"><?php echo e(number_format($propositionActive->prix_propose, 2, ',', ' ')); ?> €</p>
                        </div>
                        <?php if($propositionActive->prix_final && $propositionActive->prix_final != $propositionActive->prix_propose): ?>
                            <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Prix négocié</p>
                                <p class="font-bold text-lg text-orange-600 dark:text-orange-400"><?php echo e(number_format($propositionActive->prix_final, 2, ',', ' ')); ?> €</p>
                            </div>
                        <?php endif; ?>
                        <?php if($propositionActive->lieu): ?>
                            <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4 md:col-span-2">
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Lieu</p>
                                <p class="font-semibold text-slate-900 dark:text-white"><?php echo e($propositionActive->lieu); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if($propositionActive->notes): ?>
                        <div class="bg-white/50 dark:bg-slate-800/50 rounded-xl p-4 mb-4">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Notes</p>
                            <p class="text-slate-900 dark:text-white"><?php echo e($propositionActive->notes); ?></p>
                        </div>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-3">
                        <?php if($propositionActive->statut === 'proposee' || $propositionActive->statut === 'negociee'): ?>
                            <?php if($isCurrentUserAuthor): ?>
                                <!-- Actions pour l'auteur : message d'attente, mais peut quand même contre-proposer -->
                                <div class="w-full p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl mb-3">
                                    <p class="text-sm text-blue-800 dark:text-blue-300 mb-2">
                                        ⏳ Vous attendez la réponse de <?php echo e($propositionActive->nom_destinataire); ?>

                                    </p>
                                    <p class="text-xs text-blue-700 dark:text-blue-400">
                                        Votre proposition est en attente. Vous pouvez créer une contre-proposition si vous souhaitez modifier votre demande.
                                    </p>
                                </div>
                                <?php if($propositionActive->reservation_id && $propositionActive->reservation && $propositionActive->reservation->statut === 'en_attente'): ?>
                                    <?php
                                        $canModify = ($isGerant || ($entreprise->prix_negociables && !$isGerant));
                                    ?>
                                    <?php if($canModify): ?>
                                        <?php
                                            $heureDebut = \Carbon\Carbon::parse($propositionActive->heure_debut);
                                        ?>
                                        <button 
                                            onclick="openModifyPropositionModal(<?php echo e($propositionActive->reservation->id); ?>, '<?php echo e($propositionActive->date_rdv->format('Y-m-d')); ?>', '<?php echo e($heureDebut->format('H:i')); ?>', <?php echo e($propositionActive->duree_minutes); ?>, <?php echo e(number_format($propositionActive->prix_propose, 2, '.', '')); ?>, <?php echo json_encode($propositionActive->lieu ?? $propositionActive->reservation->lieu ?? ''); ?>, <?php echo json_encode($propositionActive->notes ?? $propositionActive->reservation->notes ?? ''); ?>, <?php echo e($isGerant ? 'true' : 'false'); ?>, <?php echo e($propositionActive->type_service_id ?? $propositionActive->reservation->type_service_id ?? 'null'); ?>)"
                                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center gap-2"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Modifier ma proposition
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Actions pour le destinataire : accepter, refuser, contre-proposer -->
                                <form action="<?php echo e(route($isGerant ? 'messagerie.accepter-proposition-gerant' : 'messagerie.accepter-proposition', $isGerant ? [$entreprise->slug, $conversation->id, $propositionActive->id] : [$entreprise->slug, $propositionActive->id])); ?>" method="POST" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Accepter
                                    </button>
                                </form>
                                <button 
                                    onclick="document.getElementById('modal-refuser-<?php echo e($propositionActive->id); ?>').classList.remove('hidden')"
                                    class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Refuser
                                </button>
                                <?php if($propositionActive->reservation_id && $propositionActive->reservation && $propositionActive->reservation->statut === 'en_attente'): ?>
                                    <?php
                                        $canModify = ($isGerant || ($entreprise->prix_negociables && !$isGerant));
                                    ?>
                                    <?php if($canModify): ?>
                                        <?php
                                            // Utiliser les données de la proposition active pour pré-remplir
                                            $heureDebut = \Carbon\Carbon::parse($propositionActive->heure_debut);
                                        ?>
                                        <button 
                                            onclick="openModifyPropositionModal(<?php echo e($propositionActive->reservation->id); ?>, '<?php echo e($propositionActive->date_rdv->format('Y-m-d')); ?>', '<?php echo e($heureDebut->format('H:i')); ?>', <?php echo e($propositionActive->duree_minutes); ?>, <?php echo e(number_format($propositionActive->prix_propose, 2, '.', '')); ?>, <?php echo json_encode($propositionActive->lieu ?? $propositionActive->reservation->lieu ?? ''); ?>, <?php echo json_encode($propositionActive->notes ?? $propositionActive->reservation->notes ?? ''); ?>, <?php echo e($isGerant ? 'true' : 'false'); ?>, <?php echo e($propositionActive->type_service_id ?? $propositionActive->reservation->type_service_id ?? 'null'); ?>)"
                                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center gap-2"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Proposer une modification
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if($propositionActive->peutEtreNegociee() && !$isGerant): ?>
                                    <button 
                                        onclick="document.getElementById('modal-negocier-<?php echo e($propositionActive->id); ?>').classList.remove('hidden')"
                                        class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center gap-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Négocier le prix
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif($propositionActive->statut === 'acceptee'): ?>
                            <div class="flex flex-col gap-3">
                                <span class="px-6 py-3 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-xl font-semibold flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Rendez-vous accepté et créé
                                </span>
                                <?php if(!isset($isGerant) || !$isGerant): ?>
                                    <?php if($propositionActive->reservation_id && $propositionActive->reservation): ?>
                                        <a 
                                            href="<?php echo e(route('reservations.show', [$entreprise->slug, $propositionActive->reservation->id])); ?>"
                                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center justify-center gap-2"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            Gérer mon rendez-vous
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif($propositionActive->statut === 'refusee'): ?>
                            <span class="px-6 py-3 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-xl font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Proposition refusée
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Zone de messages améliorée style chat moderne -->
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden flex flex-col" style="height: calc(100vh - 400px); min-height: 600px;">
                <!-- En-tête de la zone de messages -->
                <div class="bg-gradient-to-r from-green-500/10 to-orange-500/10 dark:from-green-500/20 dark:to-orange-500/20 border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">En ligne</span>
                        </div>
                        <button onclick="scrollToBottom()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Container des messages avec scroll -->
                <div id="messages-container" class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar bg-gradient-to-b from-slate-50/50 to-white dark:from-slate-900/50 dark:to-slate-800">
                    <?php
                        $lastDate = null;
                    ?>
                    <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $messageDate = $message->created_at->format('Y-m-d');
                            $showDateSeparator = $lastDate !== $messageDate;
                            $lastDate = $messageDate;
                        ?>
                        
                        <?php if($showDateSeparator): ?>
                            <div class="flex items-center justify-center my-6">
                                <div class="bg-slate-200 dark:bg-slate-700 px-4 py-1 rounded-full">
                                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400">
                                        <?php if($messageDate === now()->format('Y-m-d')): ?>
                                            Aujourd'hui
                                        <?php elseif($messageDate === now()->subDay()->format('Y-m-d')): ?>
                                            Hier
                                        <?php else: ?>
                                            <?php echo e($message->created_at->format('d/m/Y')); ?>

                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($message->estPropositionRendezVous() && $message->propositionRdv): ?>
                            <!-- Message système : Proposition de RDV (aligné selon l'auteur) -->
                            <?php 
                                $prop = $message->propositionRdv;
                                $prop->loadMissing(['auteur', 'entreprise']);
                                $auteurUserId = $prop->auteur_user_id ?? $prop->user_id ?? null;
                                $estAuteurActuel = $auteurUserId === Auth::id();
                            ?>
                            <div class="flex <?php echo e($estAuteurActuel ? 'justify-end' : 'justify-start'); ?> mb-4">
                                <div class="max-w-sm w-full">
                                    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-4 shadow-md relative overflow-hidden">
                                        <!-- Badge "Message système" -->
                                        <div class="absolute top-2 right-2">
                                            <span class="px-2 py-0.5 text-xs font-semibold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full border border-blue-200 dark:border-blue-700">
                                                ⚙️ Système
                                            </span>
                                        </div>
                                        
                                        <!-- En-tête avec icône et titre -->
                                        <div class="flex items-center gap-2 mb-3 pr-14">
                                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl shadow-md">
                                                📅
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-900 dark:text-white text-base">Proposition de rendez-vous</h4>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                    <?php echo e($message->created_at->format('d/m/Y à H:i')); ?>

                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Informations de la proposition -->
                                        <div class="space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div class="bg-white/60 dark:bg-slate-800/60 rounded-lg p-2">
                                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Date</p>
                                                    <p class="font-bold text-slate-900 dark:text-white text-sm"><?php echo e($prop->date_rdv->format('d/m/Y')); ?></p>
                                                </div>
                                                <div class="bg-white/60 dark:bg-slate-800/60 rounded-lg p-2">
                                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Heure</p>
                                                    <p class="font-bold text-slate-900 dark:text-white text-sm">
                                                        <?php echo e(\Carbon\Carbon::parse($prop->heure_debut)->format("H:i")); ?> - <?php echo e(\Carbon\Carbon::parse($prop->heure_fin)->format("H:i")); ?>

                                                    </p>
                                                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5"><?php echo e($prop->duree_minutes); ?> min</p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-white/60 dark:bg-slate-800/60 rounded-lg p-2">
                                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Prix proposé</p>
                                                <p class="text-xl font-bold text-green-600 dark:text-green-400"><?php echo e(number_format($prop->prix_propose, 2, ',', ' ')); ?> €</p>
                                            </div>
                                            
                                            <?php if($prop->lieu): ?>
                                                <div class="bg-white/60 dark:bg-slate-800/60 rounded-lg p-2">
                                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Lieu</p>
                                                    <p class="font-semibold text-slate-900 dark:text-white text-sm"><?php echo e($prop->lieu); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if($prop->notes): ?>
                                                <div class="bg-white/60 dark:bg-slate-800/60 rounded-lg p-2">
                                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Notes</p>
                                                    <p class="text-xs text-slate-700 dark:text-slate-300"><?php echo e($prop->notes); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Statut -->
                                            <div class="flex items-center justify-between pt-2 border-t border-blue-200 dark:border-blue-700">
                                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Statut :</span>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                                    <?php if($prop->statut === 'proposee'): ?> bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                                    <?php elseif($prop->statut === 'negociee'): ?> bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                                    <?php elseif($prop->statut === 'acceptee'): ?> bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                                    <?php elseif($prop->statut === 'refusee'): ?> bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                                    <?php endif; ?>">
                                                    <?php if($prop->statut === 'proposee'): ?> En attente
                                                    <?php elseif($prop->statut === 'negociee'): ?> Négociée
                                                    <?php elseif($prop->statut === 'acceptee'): ?> ✓ Acceptée
                                                    <?php elseif($prop->statut === 'refusee'): ?> ✗ Refusée
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Message utilisateur normal -->
                            <div class="flex items-start gap-3 <?php echo e($message->user_id === Auth::id() ? 'flex-row-reverse' : ''); ?> group">
                                <!-- Avatar -->
                                <div class="flex-shrink-0">
                                    <?php if (isset($component)) { $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.avatar','data' => ['user' => $message->user,'size' => 'md','class' => 'shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($message->user),'size' => 'md','class' => 'shadow-md']); ?>
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
                                </div>
                                
                                <!-- Message -->
                                <div class="flex-1 flex flex-col <?php echo e($message->user_id === Auth::id() ? 'items-end' : 'items-start'); ?> max-w-[75%]">
                                    <div class="flex items-center gap-2 mb-1 <?php echo e($message->user_id === Auth::id() ? 'flex-row-reverse' : ''); ?>">
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            <?php echo e($message->user_id === Auth::id() ? 'Vous' : $message->user->name); ?>

                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-slate-500">
                                            <?php echo e($message->created_at->format('H:i')); ?>

                                        </span>
                                        <?php if($message->user_id === Auth::id() && $message->est_lu): ?>
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="px-4 py-3 rounded-2xl <?php echo e($message->user_id === Auth::id() ? 'bg-gradient-to-r from-green-500 to-green-600 text-white rounded-tr-sm' : 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-tl-sm border border-slate-200 dark:border-slate-600'); ?> shadow-md hover:shadow-lg transition-shadow">
                                        <?php if($message->contenu): ?>
                                            <p class="whitespace-pre-wrap break-words"><?php echo e($message->contenu); ?></p>
                                        <?php endif; ?>
                                        <?php if($message->image): ?>
                                            <div class="mt-3">
                                                <img 
                                                    src="<?php echo e(asset('media/' . $message->image)); ?>" 
                                                    alt="Image"
                                                    class="max-w-full h-auto rounded-xl cursor-pointer hover:opacity-90 transition shadow-md"
                                                    onclick="openImageModal('<?php echo e(asset('media/' . $message->image)); ?>')"
                                                >
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <div id="messages-end"></div>
                </div>

                <!-- Formulaire d'envoi amélioré -->
                <div class="border-t border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-800/50 p-4">
                    <form action="<?php echo e(isset($isGerant) && $isGerant ? route('messagerie.send-gerant', [$entreprise->slug, $conversation->id]) : route('messagerie.send', $entreprise->slug)); ?>" method="POST" enctype="multipart/form-data" id="message-form">
                        <?php echo csrf_field(); ?>
                        <div class="flex items-end gap-3">
                            <div class="flex-1 relative">
                                <textarea 
                                    name="contenu" 
                                    id="message-contenu"
                                    rows="1"
                                    placeholder="Tapez votre message..."
                                    class="w-full px-4 py-3 pr-12 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none custom-scrollbar"
                                    onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); document.getElementById('message-form').submit(); }"
                                ></textarea>
                                <div class="absolute right-3 bottom-3 flex items-center gap-2">
                                    <label class="cursor-pointer p-2 hover:bg-slate-100 dark:hover:bg-slate-600 rounded-lg transition text-slate-500 hover:text-green-500 dark:hover:text-green-400" title="Ajouter une photo">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <input 
                                            type="file" 
                                            name="image" 
                                            id="message-image"
                                            accept="image/*"
                                            class="hidden"
                                            onchange="previewImage(this)"
                                        >
                                    </label>
                                </div>
                                <div id="image-preview-container" class="hidden mt-2 p-2 bg-slate-100 dark:bg-slate-700 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <span id="image-preview-name" class="text-xs text-slate-600 dark:text-slate-400"></span>
                                        <button type="button" onclick="clearImagePreview()" class="text-red-500 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button 
                                type="submit" 
                                id="send-button"
                                class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span class="hidden sm:inline">Envoyer</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section Produits et Services dépliables -->
                <?php if((!isset($isGerant) || !$isGerant) && (($produits && $produits->count() > 0) || ($prestations && $prestations->count() > 0))): ?>
                    <div class="border-t border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-900 p-4 space-y-3">
                        <!-- Volet Produits -->
                        <?php if($produits && $produits->count() > 0): ?>
                            <div>
                                <button 
                                    onclick="toggleVolet('produits')"
                                    class="w-full flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-600 hover:border-green-500 dark:hover:border-green-400 transition-all shadow-sm"
                                >
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        <span class="font-semibold text-slate-900 dark:text-slate-100">Produits</span>
                                        <span class="text-xs bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full"><?php echo e($produits->count()); ?></span>
                                    </div>
                                    <svg id="produits-icon" class="w-5 h-5 text-slate-400 dark:text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="volet-produits" class="hidden mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto custom-scrollbar bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2">
                                    <?php $__currentLoopData = $produits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $produit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $promotion = $produit->promotionActive()->first();
                                            $prixActuel = $promotion ? $promotion->prix_promotion : $produit->prix;
                                            $imageAffichee = $produit->imageCouverture ? $produit->imageCouverture : $produit->images->first();
                                        ?>
                                        <a 
                                            href="<?php echo e(route('messagerie.commander-produit', ['slug' => $entreprise->slug, 'produitId' => $produit->id])); ?>"
                                            class="text-left p-3 bg-white dark:bg-slate-700 rounded-xl border border-slate-200 dark:border-slate-600 hover:border-green-500 dark:hover:border-green-400 hover:shadow-md transition-all group"
                                        >
                                            <?php if($imageAffichee): ?>
                                                <img src="<?php echo e(asset('media/' . $imageAffichee->image_path)); ?>" alt="<?php echo e($produit->nom); ?>" class="w-full h-20 object-cover rounded-lg mb-2">
                                            <?php endif; ?>
                                            <h4 class="font-semibold text-slate-900 dark:text-white text-sm group-hover:text-green-600 dark:group-hover:text-green-400 transition truncate">
                                                <?php echo e($produit->nom); ?>

                                            </h4>
                                            <?php if($produit->description): ?>
                                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 line-clamp-2">
                                                    <?php echo e($produit->description); ?>

                                                </p>
                                            <?php endif; ?>
                                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200 dark:border-slate-600">
                                                <?php if($promotion): ?>
                                                    <span class="text-xs">
                                                        <span class="line-through text-slate-400 dark:text-slate-500"><?php echo e(number_format($produit->prix, 2, ',', ' ')); ?> €</span>
                                                        <span class="font-bold text-red-600 dark:text-red-400 ml-1"><?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="font-bold text-green-600 dark:text-green-400 text-sm">
                                                        <?php echo e(number_format($prixActuel, 2, ',', ' ')); ?> €
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Volet Services -->
                        <?php if($prestations && $prestations->count() > 0): ?>
                            <div>
                                <button 
                                    onclick="toggleVolet('services')"
                                    class="w-full flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-600 hover:border-purple-500 dark:hover:border-purple-400 transition-all shadow-sm"
                                >
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        <span class="font-semibold text-slate-900 dark:text-slate-100">Services</span>
                                        <span class="text-xs bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full"><?php echo e($prestations->count()); ?></span>
                                    </div>
                                    <svg id="services-icon" class="w-5 h-5 text-slate-400 dark:text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="volet-services" class="hidden mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto custom-scrollbar bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2">
                                    <?php $__currentLoopData = $prestations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prestation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <a 
                                            href="<?php echo e(route('messagerie.demander-service', ['slug' => $entreprise->slug, 'serviceId' => $prestation->id])); ?>"
                                            class="text-left p-3 bg-white dark:bg-slate-700 rounded-xl border border-slate-200 dark:border-slate-600 hover:border-purple-500 dark:hover:border-purple-400 hover:shadow-md transition-all group"
                                        >
                                            <?php if($prestation->imageCouverture || $prestation->images->first()): ?>
                                                <img src="<?php echo e(asset('media/' . ($prestation->imageCouverture ? $prestation->imageCouverture->image_path : $prestation->images->first()->image_path))); ?>" alt="<?php echo e($prestation->nom); ?>" class="w-full h-20 object-cover rounded-lg mb-2">
                                            <?php endif; ?>
                                            <h4 class="font-semibold text-slate-900 dark:text-white text-sm group-hover:text-purple-600 dark:group-hover:text-purple-400 transition truncate">
                                                <?php echo e($prestation->nom); ?>

                                            </h4>
                                            <?php if($prestation->description): ?>
                                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 line-clamp-2">
                                                    <?php echo e($prestation->description); ?>

                                                </p>
                                            <?php endif; ?>
                                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200 dark:border-slate-600">
                                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <?php echo e($prestation->duree_minutes); ?> min
                                                </span>
                                                <span class="font-bold text-green-600 dark:text-green-400 text-sm">
                                                    <?php echo e(number_format($prestation->prix, 2, ',', ' ')); ?> €
                                                </span>
                                            </div>
                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulaire pour proposer un RDV (gérant uniquement) -->
                <?php if(isset($isGerant) && $isGerant && (!isset($propositionActive) || !$propositionActive)): ?>
                    <div class="border-t border-slate-200 dark:border-slate-700 bg-gradient-to-r from-orange-500/10 to-green-500/10 dark:from-orange-500/20 dark:to-green-500/20 p-4">
                        <button 
                            onclick="document.getElementById('modal-proposer-rdv').classList.remove('hidden')"
                            class="w-full px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Proposer un rendez-vous
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Bouton pour proposer un RDV (client uniquement) -->
                <?php if((!isset($isGerant) || !$isGerant) && (!isset($propositionActive) || !$propositionActive)): ?>
                    <div class="border-t border-slate-200 dark:border-slate-700 bg-gradient-to-r from-green-500/10 to-blue-500/10 dark:from-green-500/20 dark:to-blue-500/20 p-4">
                        <button 
                            onclick="document.getElementById('modal-proposer-rdv-client').classList.remove('hidden')"
                            class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition-all shadow-lg flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Proposer un rendez-vous
                        </button>
                    </div>
                <?php endif; ?>
            </div>
                </div>

                <!-- Colonne latérale : Détails de la réservation et modifications -->
                <?php if(isset($conversation->reservation) && $conversation->reservation): ?>
                    <div class="lg:col-span-1">
                        <?php echo $__env->make('messagerie.partials.reservation-details', [
                            'reservation' => $conversation->reservation,
                            'entreprise' => $entreprise,
                            'conversation' => $conversation,
                            'propositionActive' => $propositionActive ?? null,
                            'isGerant' => $isGerant ?? false,
                            'prestations' => $prestations ?? collect()
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Scroll vers le bas des messages
            function scrollToBottom() {
                const messagesContainer = document.getElementById('messages-container');
                const messagesEnd = document.getElementById('messages-end');
                if (messagesContainer && messagesEnd) {
                    messagesEnd.scrollIntoView({ behavior: 'smooth' });
                }
            }

            // Scroll automatique au chargement
            window.addEventListener('load', () => {
                scrollToBottom();
            });

            // Prévisualisation de l'image
            function previewImage(input) {
                const previewContainer = document.getElementById('image-preview-container');
                const previewName = document.getElementById('image-preview-name');
                if (input.files && input.files[0]) {
                    previewName.textContent = input.files[0].name;
                    previewContainer.classList.remove('hidden');
                } else {
                    previewContainer.classList.add('hidden');
                }
            }

            function clearImagePreview() {
                const input = document.getElementById('message-image');
                const previewContainer = document.getElementById('image-preview-container');
                input.value = '';
                previewContainer.classList.add('hidden');
            }

            // Auto-resize textarea
            const textarea = document.getElementById('message-contenu');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });
            }

            // Modal pour afficher les images en grand
            function openImageModal(imageSrc) {
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4';
                modal.onclick = () => modal.remove();
                modal.innerHTML = `
                    <div class="max-w-4xl max-h-[90vh] relative">
                        <img src="${imageSrc}" alt="Image" class="max-w-full max-h-[90vh] rounded-xl shadow-2xl">
                        <button onclick="this.closest('.fixed').remove()" class="absolute top-4 right-4 w-10 h-10 bg-white/90 dark:bg-slate-800/90 rounded-full flex items-center justify-center text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 transition shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            // Fonction pour sélectionner une prestation
            function toggleVolet(type) {
                const volet = document.getElementById('volet-' + type);
                const icon = document.getElementById(type + '-icon');
                if (volet.classList.contains('hidden')) {
                    volet.classList.remove('hidden');
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    volet.classList.add('hidden');
                    icon.style.transform = 'rotate(0deg)';
                }
            }

            function selectPrestation(id, nom, prix, duree) {
                // Ouvrir le modal de proposition avec la prestation pré-remplie
                const modal = document.getElementById('modal-proposer-rdv-client');
                if (modal) {
                    modal.classList.remove('hidden');
                    const prestationIdInput = document.getElementById('prestation-selected-id');
                    const prestationName = document.getElementById('prestation-selected-name');
                    const prestationPrice = document.getElementById('prestation-selected-price');
                    const prestationDuree = document.getElementById('prestation-selected-duree');
                    const propositionPrix = document.getElementById('proposition-prix');
                    const propositionDuree = document.getElementById('proposition-duree');
                    const propositionTypeService = document.getElementById('proposition-type-service');
                    const prestationSelected = document.getElementById('prestation-selected');
                    
                    if (prestationIdInput) prestationIdInput.value = id;
                    if (prestationName) prestationName.textContent = nom;
                    if (prestationPrice) prestationPrice.textContent = prix.toFixed(2) + ' €';
                    if (prestationDuree) prestationDuree.textContent = duree + ' min';
                    if (propositionPrix) propositionPrix.value = prix;
                    if (propositionDuree) propositionDuree.value = duree;
                    if (propositionTypeService) propositionTypeService.value = id;
                    if (prestationSelected) prestationSelected.classList.remove('hidden');
                }
            }

            // Mise à jour automatique des messages toutes les 5 secondes
            let lastMessageId = <?php echo e($messages->isNotEmpty() ? $messages->last()->id : 0); ?>;
            let isScrolledToBottom = true;
            
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.addEventListener('scroll', () => {
                    const container = messagesContainer;
                    isScrolledToBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
                });
            }
            
            setInterval(() => {
                fetch('<?php echo e(url("/api/messagerie/check-new")); ?>?conversation_id=<?php echo e($conversation->id); ?>&last_id=' + lastMessageId, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.has_new && data.last_message_id > lastMessageId) {
                        // Recharger la page pour afficher les nouveaux messages
                        if (isScrolledToBottom) {
                            location.reload();
                        }
                    }
                })
                .catch(error => {
                    // Silencieux en cas d'erreur
                });
            }, 5000);
        </script>

        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 8px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgb(203, 213, 225);
                border-radius: 4px;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgb(148, 163, 184);
            }
            .dark .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgb(51, 65, 85);
            }
            .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgb(71, 85, 105);
            }
        </style>

        <!-- Modal pour proposer un RDV (gérant) -->
        <?php if(isset($isGerant) && $isGerant && (!isset($propositionActive) || !$propositionActive)): ?>
            <?php echo $__env->make('messagerie.modals.proposer-rdv', ['entreprise' => $entreprise, 'conversation' => $conversation], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <!-- Modal pour négocier le prix (client) -->
        <?php if(isset($propositionActive) && $propositionActive && $propositionActive->peutEtreNegociee() && (!isset($isGerant) || !$isGerant)): ?>
            <?php echo $__env->make('messagerie.modals.negocier-prix', ['entreprise' => $entreprise, 'propositionActive' => $propositionActive], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <!-- Modal pour refuser une proposition -->
        <?php if(isset($propositionActive) && $propositionActive && ($propositionActive->statut === 'proposee' || $propositionActive->statut === 'negociee')): ?>
            <?php echo $__env->make('messagerie.modals.refuser-proposition', ['entreprise' => $entreprise, 'conversation' => $conversation, 'propositionActive' => $propositionActive, 'isGerant' => $isGerant ?? false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <!-- Modal pour proposer un RDV (client) -->
        <?php if((!isset($isGerant) || !$isGerant) && (!isset($propositionActive) || !$propositionActive)): ?>
            <?php echo $__env->make('messagerie.modals.proposer-rdv-client', ['entreprise' => $entreprise, 'prestations' => $prestations ?? collect()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <!-- Modal pour modifier une proposition -->
        <?php if(isset($conversation->reservation) && $conversation->reservation && $conversation->reservation->statut === 'en_attente'): ?>
            <?php echo $__env->make('messagerie.modals.modify-proposition', [
                'reservation' => $conversation->reservation,
                'entreprise' => $entreprise,
                'conversation' => $conversation,
                'isGerant' => $isGerant ?? false,
                'prestations' => $prestations ?? collect()
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <!-- Script pour ouvrir automatiquement la modale de contre-proposition après un refus -->
        <?php if(session('open_contre_proposition') && session('contre_proposition_data')): ?>
            <?php
                $data = session('contre_proposition_data');
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        openModifyPropositionModal(
                            <?php echo e($data['reservation_id']); ?>,
                            '<?php echo e($data['date']); ?>',
                            '<?php echo e($data['heure']); ?>',
                            <?php echo e($data['duree']); ?>,
                            <?php echo e(number_format($data['prix'], 2, '.', '')); ?>,
                            <?php echo json_encode($data['lieu'] ?? ''); ?>,
                            <?php echo json_encode($data['notes'] ?? ''); ?>,
                            <?php echo e($isGerant ?? false ? 'true' : 'false'); ?>,
                            <?php echo e($data['type_service_id'] ?? 'null'); ?>

                        );
                    }, 500);
                });
            </script>
        <?php endif; ?>
    </body>
</html><?php /**PATH /var/www/html/resources/views/messagerie/show.blade.php ENDPATH**/ ?>