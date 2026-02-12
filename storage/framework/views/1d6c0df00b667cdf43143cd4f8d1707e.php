<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <meta name="stripe-publishable-key" content="<?php echo e(config('services.stripe.key')); ?>">
        <title>Espace Paiement – Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/js/checkout.js']); ?>
        <?php echo $__env->make('partials.theme-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="<?php echo e(route('dashboard')); ?>" class="text-xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">Allo Tata</a>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <span class="hidden sm:inline text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Espace Paiement</span>
                        <a href="<?php echo e(route('settings.index', ['tab' => 'subscription'])); ?>" class="min-h-[44px] inline-flex items-center px-3 py-2.5 sm:py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 touch-manipulation">Abonnement</a>
                        <a href="<?php echo e(route('dashboard')); ?>" class="min-h-[44px] inline-flex items-center px-3 py-2.5 sm:py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 touch-manipulation">Dashboard</a>
                    </div>
                </div>
            </div>
        </nav>

        
        <div id="checkout-toast" class="hidden fixed top-20 left-4 right-4 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 sm:max-w-md z-50 px-4 py-3 rounded-xl shadow-lg border text-center font-medium" role="alert"></div>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            
            <header class="mb-8">
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white tracking-tight">Espace Paiement</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400 text-lg max-w-2xl">
                    Gestion de vos tarifs et r&egrave;glements. Vous consultez ici les montants dus, appliquez vos codes promo et r&eacute;glez en toute transparence.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Paiement s&eacute;curis&eacute;
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        Prix fix&eacute;s au moment du r&egrave;glement
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <?php if($hasPaymentMethod ?? false): ?> Carte enregistr&eacute;e &bull;&bull;&bull;&bull; <?php echo e($user->pm_last_four ?? '****'); ?> <?php else: ?> Aucune carte enregistr&eacute;e <?php endif; ?>
                    </span>
                </div>
            </header>

            
            <?php if(session('success')): ?>
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-green-800 dark:text-green-400 font-medium"><?php echo e(session('success')); ?></p>
                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-red-800 dark:text-red-400 font-medium"><?php echo e(session('error')); ?></p>
                </div>
            <?php endif; ?>
            <?php if(session('info')): ?>
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-blue-800 dark:text-blue-400 font-medium"><?php echo e(session('info')); ?></p>
                </div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p class="text-red-800 dark:text-red-400 font-medium"><?php echo e($err); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            
            
            
            
            <div class="lg:grid lg:grid-cols-5 lg:gap-8 lg:items-start">

                
                <div class="lg:col-span-3 space-y-6">
                    <?php if(!($hasAnything ?? false)): ?>
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div class="p-10 sm:p-14 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 mb-6">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Tout est &agrave; jour</h2>
                                <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-sm mx-auto">Vous n'avez aucune &eacute;ch&eacute;ance &agrave; r&eacute;gler pour le moment.</p>
                                <a href="<?php echo e(route('settings.index', ['tab' => 'subscription'])); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-semibold rounded-xl transition">Voir mon abonnement</a>
                            </div>
                        </div>
                    <?php else: ?>
                        
                        <?php if($echeancesEchec->isNotEmpty()): ?>
                            <section>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                                    <h2 class="text-base font-bold text-red-700 dark:text-red-400">Paiements &eacute;chou&eacute;s &ndash; R&eacute;gularisation requise</h2>
                                </div>
                                <div class="space-y-4">
                                    <?php $__currentLoopData = $echeancesEchec; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $calc = $calculs[$e->id] ?? []; $montantFinal = $calc['montant_final'] ?? $e->montant_final ?? 0; ?>
                                        <article class="bg-white dark:bg-slate-800 rounded-2xl border-2 border-red-300 dark:border-red-700 shadow-sm overflow-hidden ring-1 ring-red-100 dark:ring-red-900/30">
                                            <div class="bg-red-50 dark:bg-red-900/20 px-5 py-2.5 border-b border-red-200 dark:border-red-800 flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700 dark:text-red-400 uppercase tracking-wider">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                                                    Paiement &eacute;chou&eacute;
                                                </span>
                                            </div>
                                            <div class="p-5">
                                                <div class="flex flex-wrap items-baseline gap-2 mb-3">
                                                    <h3 class="text-base font-bold text-slate-900 dark:text-white"><?php echo e($e->libelle()); ?></h3>
                                                    <span class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($e->periode_debut->format('d/m/Y')); ?> &rarr; <?php echo e($e->periode_fin->format('d/m/Y')); ?></span>
                                                </div>
                                                <?php echo $__env->make('checkout._echeance-lignes', ['calc' => $calc, 'e' => $e], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                <div class="flex flex-wrap items-center justify-between gap-4 mt-4 pt-4 border-t border-red-100 dark:border-red-900/30">
                                                    <div>
                                                        <p class="text-xs font-medium text-red-500 dark:text-red-400 uppercase tracking-wider mb-0.5">Montant d&ucirc;</p>
                                                        <p class="text-2xl font-bold text-red-700 dark:text-red-400 tabular-nums"><?php echo e(number_format($montantFinal, 2, ',', ' ')); ?> &euro;</p>
                                                    </div>
                                                    <button type="button" class="checkout-regler-btn inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition shadow-sm hover:shadow disabled:opacity-60 disabled:cursor-not-allowed touch-manipulation" data-echeance-id="<?php echo e($e->id); ?>" <?php if($codePromo): ?> data-code-promo="<?php echo e($codePromo); ?>" <?php endif; ?>>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                        <span class="checkout-regler-label">R&eacute;gulariser</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        
                        <?php if(($pendingItems ?? collect())->isNotEmpty()): ?>
                            <section>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    <h2 class="text-base font-bold text-slate-700 dark:text-slate-300">Nouvelles souscriptions</h2>
                                </div>
                                <div class="space-y-4">
                                    <?php $__currentLoopData = $pendingItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pendingKey => $pItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $pCalc = $pendingCalculs[$pendingKey] ?? [];
                                            $pMontant = $pCalc['montant_final'] ?? $pItem['montant_final'] ?? 0;
                                            $pType = $pItem['subscription_type'] ?? '';
                                            $pNom = $pItem['entreprise_nom'] ?? '?';
                                            $pLabel = ($pType === 'site_web' ? 'Site Web' : ($pType === 'multi_personnes' ? 'Multi-Personnes' : $pType)) . ' – ' . $pNom;
                                            $pDebut = \Carbon\Carbon::parse($pItem['periode_debut'])->format('d/m/Y');
                                            $pFin = \Carbon\Carbon::parse($pItem['periode_fin'])->format('d/m/Y');
                                        ?>
                                        <article class="bg-white dark:bg-slate-800 rounded-2xl border border-blue-200 dark:border-blue-800 shadow-sm overflow-hidden">
                                            <div class="bg-blue-50 dark:bg-blue-900/20 px-5 py-2.5 border-b border-blue-200 dark:border-blue-800 flex items-center justify-between">
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wider">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>
                                                    Nouvelle souscription
                                                </span>
                                                <a href="<?php echo e(route('checkout.index', ['cancel_pending' => $pendingKey])); ?>" class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition underline underline-offset-2" onclick="return confirm('Annuler cette souscription\u00a0?');">Annuler</a>
                                            </div>
                                            <div class="p-5">
                                                <div class="flex flex-wrap items-baseline gap-2 mb-3">
                                                    <h3 class="text-base font-bold text-slate-900 dark:text-white"><?php echo e($pLabel); ?></h3>
                                                    <span class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($pDebut); ?> &rarr; <?php echo e($pFin); ?></span>
                                                </div>
                                                
                                                <?php if(!empty($pCalc['lignes'])): ?>
                                                    <div class="space-y-1 text-sm">
                                                        <?php $__currentLoopData = $pCalc['lignes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ligne): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div class="flex justify-between">
                                                                <span class="text-slate-600 dark:text-slate-400"><?php echo e($ligne['label'] ?? '–'); ?></span>
                                                                <span class="font-medium text-slate-900 dark:text-white tabular-nums"><?php echo e(number_format($ligne['montant'] ?? 0, 2, ',', ' ')); ?> &euro;</span>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex flex-wrap items-center justify-between gap-4 mt-4 pt-4 border-t border-blue-100 dark:border-blue-900/30">
                                                    <div>
                                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Total &agrave; r&eacute;gler</p>
                                                        <p class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums"><?php echo e(number_format($pMontant, 2, ',', ' ')); ?> &euro;</p>
                                                    </div>
                                                    <button type="button" class="checkout-regler-btn inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition shadow-sm hover:shadow disabled:opacity-60 disabled:cursor-not-allowed touch-manipulation" data-pending-key="<?php echo e($pendingKey); ?>" <?php if($codePromo): ?> data-code-promo="<?php echo e($codePromo); ?>" <?php endif; ?>>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                                        <span class="checkout-regler-label">R&eacute;gler cette &eacute;ch&eacute;ance</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        
                        <?php if($echeancesAPayer->isNotEmpty()): ?>
                            <section>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <h2 class="text-base font-bold text-slate-700 dark:text-slate-300">&Eacute;ch&eacute;ances &agrave; r&eacute;gler</h2>
                                </div>
                                <div class="space-y-4">
                                    <?php $__currentLoopData = $echeancesAPayer; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $calc = $calculs[$e->id] ?? []; $montantFinal = $calc['montant_final'] ?? $e->montant_final ?? 0; ?>
                                        <article class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                                            <div class="p-5">
                                                <div class="flex flex-wrap items-baseline gap-2 mb-3">
                                                    <h3 class="text-base font-bold text-slate-900 dark:text-white"><?php echo e($e->libelle()); ?></h3>
                                                    <span class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($e->periode_debut->format('d/m/Y')); ?> &rarr; <?php echo e($e->periode_fin->format('d/m/Y')); ?></span>
                                                </div>
                                                <?php echo $__env->make('checkout._echeance-lignes', ['calc' => $calc, 'e' => $e], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                <div class="flex flex-wrap items-center justify-between gap-4 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                                                    <div>
                                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Total &agrave; r&eacute;gler</p>
                                                        <p class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums"><?php echo e(number_format($montantFinal, 2, ',', ' ')); ?> &euro;</p>
                                                    </div>
                                                    <button type="button" class="checkout-regler-btn inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition shadow-sm hover:shadow disabled:opacity-60 disabled:cursor-not-allowed touch-manipulation" data-echeance-id="<?php echo e($e->id); ?>" <?php if($codePromo): ?> data-code-promo="<?php echo e($codePromo); ?>" <?php endif; ?>>
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                                        <span class="checkout-regler-label">R&eacute;gler cette &eacute;ch&eacute;ance</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        
                        <?php if($echeancesEnAttente->isNotEmpty()): ?>
                            <section>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <h2 class="text-base font-bold text-amber-700 dark:text-amber-400">Paiements en cours de traitement</h2>
                                </div>
                                <div class="space-y-4">
                                    <?php $__currentLoopData = $echeancesEnAttente; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $calc = $calculs[$e->id] ?? []; $montantFinal = $calc['montant_final'] ?? $e->montant_final ?? 0; ?>
                                        <article class="bg-amber-50/50 dark:bg-amber-900/10 rounded-2xl border border-amber-200 dark:border-amber-800 shadow-sm overflow-hidden">
                                            <div class="bg-amber-50 dark:bg-amber-900/20 px-5 py-2.5 border-b border-amber-200 dark:border-amber-800">
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.828a1 1 0 101.415-1.414L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                                    En cours de traitement
                                                </span>
                                            </div>
                                            <div class="p-5">
                                                <div class="flex flex-wrap items-baseline gap-2 mb-3">
                                                    <h3 class="text-base font-bold text-slate-900 dark:text-white"><?php echo e($e->libelle()); ?></h3>
                                                    <span class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($e->periode_debut->format('d/m/Y')); ?> &rarr; <?php echo e($e->periode_fin->format('d/m/Y')); ?></span>
                                                </div>
                                                <?php echo $__env->make('checkout._echeance-lignes', ['calc' => $calc, 'e' => $e], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                                <div class="flex flex-wrap items-center justify-between gap-4 mt-4 pt-4 border-t border-amber-100 dark:border-amber-900/30">
                                                    <div>
                                                        <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-0.5">&Agrave; titre indicatif</p>
                                                        <p class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums"><?php echo e(number_format($montantFinal, 2, ',', ' ')); ?> &euro;</p>
                                                    </div>
                                                    <span class="inline-flex items-center gap-2 text-sm font-medium text-amber-600 dark:text-amber-400">
                                                        <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.828a1 1 0 101.415-1.414L11 9.586V6z" clip-rule="evenodd"></path></svg>
                                                        Paiement en cours&hellip;
                                                    </span>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                
                <div class="lg:col-span-2 mt-8 lg:mt-0">
                    <div class="lg:sticky lg:top-6 space-y-5">

                        
                        <?php if($hasAnything ?? false): ?>
                            <?php
                                // Somme des échéances DB réglables
                                $totalReglable = 0;
                                $countReglable = 0;
                                foreach ($echeances as $e) {
                                    if ($e->estReglable()) {
                                        $c = $calculs[$e->id] ?? [];
                                        $totalReglable += (float) ($c['montant_final'] ?? $e->montant_final ?? 0);
                                        $countReglable++;
                                    }
                                }
                                // + items session (nouvelles souscriptions)
                                foreach (($pendingItems ?? []) as $pk => $pi) {
                                    $pc = $pendingCalculs[$pk] ?? [];
                                    $totalReglable += (float) ($pc['montant_final'] ?? $pi['montant_final'] ?? 0);
                                    $countReglable++;
                                }
                            ?>
                            <section class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <h2 class="text-base font-bold text-slate-900 dark:text-white">R&eacute;capitulatif</h2>
                                </div>
                                <div class="p-5">
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600 dark:text-slate-400">&Eacute;ch&eacute;ances &agrave; r&eacute;gler</span>
                                            <span class="font-medium text-slate-900 dark:text-white"><?php echo e($countReglable); ?></span>
                                        </div>
                                        <?php if($echeancesEnAttente->isNotEmpty()): ?>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-amber-600 dark:text-amber-400">En cours de traitement</span>
                                                <span class="font-medium text-amber-600 dark:text-amber-400"><?php echo e($echeancesEnAttente->count()); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                                        <div class="flex justify-between items-end">
                                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Total</span>
                                            <span class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums"><?php echo e(number_format($totalReglable, 2, ',', ' ')); ?>&nbsp;&euro;</span>
                                        </div>
                                    </div>

                                    
                                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                                        <?php if($codePromo): ?>
                                            <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
                                                <span>Code : <code class="font-semibold"><?php echo e($codePromo); ?></code></span>
                                                <form action="<?php echo e(route('checkout.retirer-promo')); ?>" method="POST" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="text-green-600 dark:text-green-500 hover:underline text-xs">Retirer</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <form action="<?php echo e(route('checkout.appliquer-promo')); ?>" method="POST" class="flex gap-2">
                                                <?php echo csrf_field(); ?>
                                                <input type="text" name="code" placeholder="Code promo" class="flex-1 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm" maxlength="64">
                                                <button type="submit" class="px-3 py-2 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-800 dark:text-slate-200 font-semibold rounded-lg transition text-sm">OK</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>

                        
                        <?php if($showCardForm ?? false): ?>
                            <section class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Moyen de paiement</h2>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                        <?php if(($hasPaymentMethod ?? false) && request('change_card')): ?>
                                            Remplacer la carte enregistr&eacute;e. Aucun d&eacute;bit imm&eacute;diat.
                                        <?php else: ?>
                                            Enregistrez une carte pour r&eacute;gler vos &eacute;ch&eacute;ances. Aucun d&eacute;bit imm&eacute;diat.
                                        <?php endif; ?>
                                    </p>
                                    <?php if(($hasPaymentMethod ?? false) && request('change_card')): ?>
                                        <a href="<?php echo e(route('checkout.index')); ?>" class="mt-2 inline-block text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">&larr; Annuler</a>
                                    <?php endif; ?>
                                </div>
                                <div class="p-5">
                                    <form id="checkout-save-card-form">
                                        <div id="checkout-payment-element" class="min-h-[200px]"></div>
                                        <p id="checkout-card-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert"></p>
                                        <div class="mt-4 flex flex-col gap-3">
                                            <button type="submit" class="w-full min-h-[44px] px-5 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition touch-manipulation">
                                                <?php echo e(($hasPaymentMethod ?? false) && request('change_card') ? 'Remplacer la carte' : 'Enregistrer ma carte'); ?>

                                            </button>
                                            <?php if(($hasPaymentMethod ?? false) && request('change_card')): ?>
                                                <a href="<?php echo e(route('checkout.index')); ?>" class="w-full min-h-[44px] inline-flex items-center justify-center px-5 py-3 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-800 dark:text-slate-200 font-semibold rounded-xl transition">Annuler</a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </section>
                        <?php else: ?>
                            <section class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Moyen de paiement</h2>
                                </div>
                                <div class="p-5">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-10 h-7 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-900 dark:text-white text-sm"><?php echo e(ucfirst($user->pm_type ?? 'carte')); ?> &bull;&bull;&bull;&bull; <?php echo e($user->pm_last_four ?? '****'); ?></p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Carte enregistr&eacute;e</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <a href="<?php echo e(route('checkout.index', ['change_card' => 1])); ?>" class="w-full min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-semibold rounded-xl transition text-sm touch-manipulation">
                                            Changer la carte
                                        </a>
                                        <form action="<?php echo e(route('checkout.remove-payment-method')); ?>" method="POST" onsubmit="return confirm('Supprimer la carte enregistr\u00e9e\u00a0?');">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="w-full min-h-[44px] px-4 py-2.5 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 font-semibold rounded-xl transition text-sm touch-manipulation border border-red-200 dark:border-red-800">
                                                Supprimer la carte
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>

                        
                        <div class="flex flex-col items-center gap-3 text-xs text-slate-400 dark:text-slate-500 py-2">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                <span>Paiement chiffr&eacute; SSL</span>
                            </div>
                            <span>Propuls&eacute; par Stripe</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </body>
</html>
<?php /**PATH /var/www/html/resources/views/checkout/index.blade.php ENDPATH**/ ?>