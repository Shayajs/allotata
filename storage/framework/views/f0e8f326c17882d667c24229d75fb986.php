<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <title>Paramètres - Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->make('partials.theme-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-4">
                        <!-- Menu Burger pour mobile web -->
                        <?php echo $__env->make('components.mobile-nav', ['navType' => 'dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        
                        <a href="<?php echo e(route('dashboard')); ?>" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            Allo Tata
                        </a>
                    </div>
                    <!-- Liens desktop (masqués sur mobile) -->
                    <div class="hidden lg:flex items-center gap-4">
                        <a href="<?php echo e(route('dashboard')); ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour au dashboard
                        </a>
                        <a href="<?php echo e(route('checkout.index')); ?>" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Espace Paiement
                        </a>
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            <?php echo e($user->name); ?>

                        </span>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Paramètres
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Gérez vos préférences et vos informations personnelles.
                </p>
            </div>

            <?php if(session('success')): ?>
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-400"><?php echo e(session('success')); ?></p>
                </div>
            <?php endif; ?>

            <!-- Layout avec Sidebar -->
            <div class="flex gap-6">
                <!-- Sidebar Navigation (hidden on mobile, icons only on tablet, full on desktop) -->
                <aside class="hidden md:flex flex-col w-16 xl:w-64 flex-shrink-0 sticky top-20 self-start h-[calc(100vh-6rem)] overflow-y-auto">
                    <nav class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-2 xl:p-3 space-y-1">
                        <!-- Mon compte -->
                        <button 
                            onclick="showTab('account')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"
                            data-tab="account"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="hidden xl:inline">Mon compte</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Mon compte</span>
                        </button>

                        <?php if($user->est_gerant && $entreprises->count() > 0): ?>
                        <!-- Mes entreprises -->
                        <button 
                            onclick="showTab('entreprise')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white"
                            data-tab="entreprise"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="hidden xl:inline">Mes entreprises</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Mes entreprises</span>
                        </button>
                        <?php endif; ?>

                        <!-- Notifications -->
                        <button 
                            onclick="showTab('notifications')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white"
                            data-tab="notifications"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span class="hidden xl:inline">Notifications</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Notifications</span>
                        </button>

                        <!-- Sécurité -->
                        <button 
                            onclick="showTab('security')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white"
                            data-tab="security"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span class="hidden xl:inline">Sécurité</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Sécurité</span>
                        </button>

                        <?php if($user->est_gerant): ?>
                        <div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>

                        <!-- Abonnement -->
                        <button 
                            onclick="showTab('subscription')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white"
                            data-tab="subscription"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <span class="hidden xl:inline">Abonnement</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Abonnement</span>
                        </button>
                        <?php endif; ?>

                        <div class="my-2 border-t border-slate-200 dark:border-slate-700"></div>

                        <!-- Préférences -->
                        <button 
                            onclick="showTab('preferences')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white"
                            data-tab="preferences"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="hidden xl:inline">Préférences</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Préférences</span>
                        </button>

                        <!-- Confidentialité -->
                        <button 
                            onclick="showTab('confidentialite')"
                            class="sidebar-tab w-full flex items-center justify-center xl:justify-start gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all group relative text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white"
                            data-tab="confidentialite"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span class="hidden xl:inline">Confidentialité</span>
                            <span class="xl:hidden absolute left-full ml-2 px-2 py-1 bg-slate-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-opacity">Confidentialité</span>
                        </button>
                    </nav>
                </aside>

                <!-- Main Content Area -->
                <main class="flex-1 min-w-0">
                    
                    <nav class="md:hidden mb-4 -mx-4 px-4 overflow-x-auto scrollbar-hide" aria-label="Onglets paramètres">
                        <div class="flex gap-2 pb-2 min-w-0">
                            <button type="button" onclick="showTab('account')" class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" data-tab="account">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Compte
                            </button>
                            <?php if($user->est_gerant && $entreprises->count() > 0): ?>
                            <button type="button" onclick="showTab('entreprise')" class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" data-tab="entreprise">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                Entreprises
                            </button>
                            <?php endif; ?>
                            <button type="button" onclick="showTab('notifications')" class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" data-tab="notifications">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                Notifs
                            </button>
                            <button type="button" onclick="showTab('security')" class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" data-tab="security">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Sécurité
                            </button>
                            <?php if($user->est_gerant): ?>
                            <button type="button" onclick="showTab('subscription')" class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" data-tab="subscription">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Abonnement
                            </button>
                            <?php endif; ?>
                            <button type="button" onclick="showTab('preferences')" class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" data-tab="preferences">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Préférences
                            </button>
                            <button type="button" onclick="showTab('confidentialite')" class="sidebar-tab flex items-center gap-2 flex-shrink-0 px-4 py-3 rounded-xl text-sm font-medium whitespace-nowrap transition-all border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" data-tab="confidentialite">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Confidentialité
                            </button>
                        </div>
                    </nav>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                    <!-- Onglet Compte -->
                    <div id="tab-account" class="tab-content">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Informations du compte</h2>
                        
                        <p class="text-sm italic text-slate-500 dark:text-slate-400 mb-6">
                            Toutes les informations enregistrées sont visibles uniquement par vous.
                        </p>
                        
                        <form action="<?php echo e(route('settings.account.update')); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <?php echo csrf_field(); ?>
                            
                            <!-- Photo de profil -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Photo de profil
                                </label>
                                <div class="flex items-center gap-4">
                                    <?php if (isset($component)) { $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.avatar','data' => ['user' => $user,'size' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => '2xl']); ?>
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
                                    <div class="flex-1">
                                        <input 
                                            type="file" 
                                            name="photo_profil" 
                                            accept="image/*"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Formats acceptés : JPEG, PNG, GIF, WebP (max 2MB)
                                        </p>
                                    </div>
                                </div>
                                <?php $__errorArgs = ['photo_profil'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Prénom *
                                    </label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        value="<?php echo e(old('name', $user->first_name)); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Nom de famille
                                    </label>
                                    <input 
                                        type="text" 
                                        name="surname" 
                                        value="<?php echo e(old('surname', $user->last_name)); ?>"
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    <?php $__errorArgs = ['surname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Email *
                                    </label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        value="<?php echo e(old('email', $user->email)); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <!-- Informations personnelles (optionnelles) -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations personnelles (optionnelles)</h3>
                                
                                <div class="space-y-6">
                                    <!-- Téléphone -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Téléphone
                                        </label>
                                        <input 
                                            type="tel" 
                                            name="telephone" 
                                            value="<?php echo e(old('telephone', $user->telephone)); ?>"
                                            placeholder="Ex: 06 12 34 56 78"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        <?php $__errorArgs = ['telephone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <!-- Bio -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            À propos de moi
                                        </label>
                                        <textarea 
                                            name="bio" 
                                            rows="4"
                                            placeholder="Parlez-nous un peu de vous..."
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        ><?php echo e(old('bio', $user->bio)); ?></textarea>
                                        <?php $__errorArgs = ['bio'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Maximum 1000 caractères
                                        </p>
                                    </div>

                                    <!-- Date de naissance -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Date de naissance
                                        </label>
                                        <input 
                                            type="date" 
                                            name="date_naissance" 
                                            value="<?php echo e(old('date_naissance', $user->date_naissance ? $user->date_naissance->format('Y-m-d') : '')); ?>"
                                            max="<?php echo e(date('Y-m-d', strtotime('-1 day'))); ?>"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        <?php $__errorArgs = ['date_naissance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <!-- Adresse -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Adresse
                                        </label>
                                        <input 
                                            type="text" 
                                            name="adresse" 
                                            value="<?php echo e(old('adresse', $user->adresse)); ?>"
                                            placeholder="Ex: 123 Rue de la République"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        <?php $__errorArgs = ['adresse'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <!-- Ville et Code postal -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Ville
                                            </label>
                                            <input 
                                                type="text" 
                                                name="ville" 
                                                value="<?php echo e(old('ville', $user->ville)); ?>"
                                                placeholder="Ex: Paris"
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            <?php $__errorArgs = ['ville'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Code postal
                                            </label>
                                            <input 
                                                type="text" 
                                                name="code_postal" 
                                                value="<?php echo e(old('code_postal', $user->code_postal)); ?>"
                                                placeholder="Ex: 75001"
                                                maxlength="10"
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            <?php $__errorArgs = ['code_postal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Onglet Entreprises -->
                    <?php if($user->est_gerant && $entreprises->count() > 0): ?>
                        <div id="tab-entreprise" class="tab-content hidden">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Mes entreprises</h2>
                            
                            <div class="space-y-6">
                                <?php $__currentLoopData = $entreprises; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entreprise): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6">
                                        <div class="flex items-start gap-4 mb-6">
                                            <div id="logo-preview-<?php echo e($entreprise->id); ?>" class="<?php echo e($entreprise->logo ? '' : 'hidden'); ?>">
                                                <img 
                                                    id="logo-img-<?php echo e($entreprise->id); ?>"
                                                    src="<?php echo e($entreprise->logo ? asset('media/' . $entreprise->logo) : ''); ?>" 
                                                    alt="Logo <?php echo e($entreprise->nom); ?>"
                                                    class="w-20 h-20 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700"
                                                >
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2"><?php echo e($entreprise->nom); ?></h3>
                                                <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo e($entreprise->type_activite); ?></p>
                                            </div>
                                        </div>

                                        <!-- Upload immédiat du logo et image de fond (en dehors du formulaire) -->
                                        <div class="mb-6 space-y-4 border-b border-slate-200 dark:border-slate-700 pb-6">
                                            <!-- Logo -->
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Logo / Image de l'entreprise
                                                </label>
                                                <div class="flex items-center gap-4">
                                                    <input 
                                                        type="file" 
                                                        id="logo-input-<?php echo e($entreprise->id); ?>"
                                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                                        class="flex-1 px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                                                    >
                                                    <div id="logo-loading-<?php echo e($entreprise->id); ?>" class="hidden">
                                                        <svg class="animate-spin h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </div>
                                                    <?php if($entreprise->logo): ?>
                                                        <button 
                                                            type="button"
                                                            onclick="if(confirm('Supprimer le logo ?')) { document.getElementById('delete-logo-form-<?php echo e($entreprise->id); ?>').submit(); }"
                                                            class="px-4 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition"
                                                        >
                                                            Supprimer
                                                        </button>
                                                        <form id="delete-logo-form-<?php echo e($entreprise->id); ?>" action="<?php echo e(route('settings.entreprise.logo.delete', $entreprise->slug)); ?>" method="POST" style="display: none;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                    Formats acceptés : JPEG, PNG, GIF, WebP (max 2MB). L'upload est automatique.
                                                </p>
                                            </div>

                                            <!-- Image de fond -->
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Image de fond (pour le profil public)
                                                </label>
                                                <div id="image-fond-preview-<?php echo e($entreprise->id); ?>" class="<?php echo e($entreprise->image_fond ? 'mb-3' : 'hidden'); ?>">
                                                    <img 
                                                        id="image-fond-img-<?php echo e($entreprise->id); ?>"
                                                        src="<?php echo e($entreprise->image_fond ? asset('media/' . $entreprise->image_fond) : ''); ?>" 
                                                        alt="Image de fond"
                                                        class="w-full h-48 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                                                    >
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <input 
                                                        type="file" 
                                                        id="image-fond-input-<?php echo e($entreprise->id); ?>"
                                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                                        class="flex-1 px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                                                    >
                                                    <div id="image-fond-loading-<?php echo e($entreprise->id); ?>" class="hidden">
                                                        <svg class="animate-spin h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </div>
                                                    <?php if($entreprise->image_fond): ?>
                                                        <button 
                                                            type="button"
                                                            onclick="if(confirm('Supprimer l\'image de fond ?')) { document.getElementById('delete-image-fond-form-<?php echo e($entreprise->id); ?>').submit(); }"
                                                            class="px-4 py-3 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-800 dark:text-red-400 rounded-lg transition"
                                                        >
                                                            Supprimer
                                                        </button>
                                                        <form id="delete-image-fond-form-<?php echo e($entreprise->id); ?>" action="<?php echo e(route('settings.entreprise.image-fond.delete', $entreprise->slug)); ?>" method="POST" style="display: none;">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                                    Cette image sera affichée en en-tête de votre page publique. Taille recommandée : 1920x600px (max 5MB). L'upload est automatique.
                                                </p>
                                            </div>
                                        </div>

                                        <form action="<?php echo e(route('settings.entreprise.update', $entreprise->slug)); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                                            <?php echo csrf_field(); ?>
                                            
                                            <?php if($errors->any()): ?>
                                                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                                    <div class="flex items-start gap-3">
                                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <div>
                                                            <p class="font-medium text-red-800 dark:text-red-300 mb-2">Erreurs de validation :</p>
                                                            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                                                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <li><?php echo e($error); ?></li>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Nom de l'entreprise *
                                                    </label>
                                                    <input 
                                                        type="text" 
                                                        name="nom" 
                                                        value="<?php echo e(old('nom', $entreprise->nom)); ?>"
                                                        required
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Type d'activité *
                                                    </label>
                                                    <select 
                                                        name="type_activite" 
                                                        required
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                        <optgroup label="Beauté & Bien-être">
                                                            <option value="Coiffeuse" <?php echo e($entreprise->type_activite == 'Coiffeuse' ? 'selected' : ''); ?>>Coiffure / Tressage</option>
                                                            <option value="Esthéticienne" <?php echo e($entreprise->type_activite == 'Esthéticienne' ? 'selected' : ''); ?>>Soins esthétiques</option>
                                                            <option value="Massage" <?php echo e($entreprise->type_activite == 'Massage' ? 'selected' : ''); ?>>Massage / Relaxation</option>
                                                            <option value="Onglerie" <?php echo e($entreprise->type_activite == 'Onglerie' ? 'selected' : ''); ?>>Onglerie / Manucure</option>
                                                            <option value="Maquillage" <?php echo e($entreprise->type_activite == 'Maquillage' ? 'selected' : ''); ?>>Maquillage professionnel</option>
                                                            <option value="Barbier" <?php echo e($entreprise->type_activite == 'Barbier' ? 'selected' : ''); ?>>Barbier</option>
                                                        </optgroup>
                                                        <optgroup label="Restauration & Alimentation">
                                                            <option value="Restauration" <?php echo e($entreprise->type_activite == 'Restauration' ? 'selected' : ''); ?>>Restauration</option>
                                                            <option value="Cuisinière" <?php echo e($entreprise->type_activite == 'Cuisinière' ? 'selected' : ''); ?>>Traiteur / Cuisine à domicile</option>
                                                            <option value="Pâtisserie" <?php echo e($entreprise->type_activite == 'Pâtisserie' ? 'selected' : ''); ?>>Pâtisserie / Boulangerie</option>
                                                            <option value="Catering" <?php echo e($entreprise->type_activite == 'Catering' ? 'selected' : ''); ?>>Catering / Événements</option>
                                                        </optgroup>
                                                        <optgroup label="Photo & Vidéo">
                                                            <option value="Photographie" <?php echo e($entreprise->type_activite == 'Photographie' ? 'selected' : ''); ?>>Photographie</option>
                                                            <option value="Vidéographie" <?php echo e($entreprise->type_activite == 'Vidéographie' ? 'selected' : ''); ?>>Vidéographie</option>
                                                            <option value="Photographe_Mariage" <?php echo e($entreprise->type_activite == 'Photographe_Mariage' ? 'selected' : ''); ?>>Photographe de mariage</option>
                                                            <option value="Studio_Photo" <?php echo e($entreprise->type_activite == 'Studio_Photo' ? 'selected' : ''); ?>>Studio photo</option>
                                                        </optgroup>
                                                        <optgroup label="Éducation & Formation">
                                                            <option value="Cours_Particuliers" <?php echo e($entreprise->type_activite == 'Cours_Particuliers' ? 'selected' : ''); ?>>Cours particuliers</option>
                                                            <option value="Formation" <?php echo e($entreprise->type_activite == 'Formation' ? 'selected' : ''); ?>>Formation professionnelle</option>
                                                            <option value="Soutien_Scolaire" <?php echo e($entreprise->type_activite == 'Soutien_Scolaire' ? 'selected' : ''); ?>>Soutien scolaire</option>
                                                            <option value="Langues" <?php echo e($entreprise->type_activite == 'Langues' ? 'selected' : ''); ?>>Cours de langues</option>
                                                        </optgroup>
                                                        <optgroup label="Services à domicile">
                                                            <option value="Ménage" <?php echo e($entreprise->type_activite == 'Ménage' ? 'selected' : ''); ?>>Ménage / Aide à domicile</option>
                                                            <option value="Repassage" <?php echo e($entreprise->type_activite == 'Repassage' ? 'selected' : ''); ?>>Repassage</option>
                                                            <option value="Garde_Enfants" <?php echo e($entreprise->type_activite == 'Garde_Enfants' ? 'selected' : ''); ?>>Garde d'enfants / Baby-sitting</option>
                                                            <option value="Assistant_Virtuel" <?php echo e($entreprise->type_activite == 'Assistant_Virtuel' ? 'selected' : ''); ?>>Assistant(e) virtuel(le)</option>
                                                        </optgroup>
                                                        <optgroup label="Bricolage & Rénovation">
                                                            <option value="Peinture" <?php echo e($entreprise->type_activite == 'Peinture' ? 'selected' : ''); ?>>Peinture / Rénovation</option>
                                                            <option value="Plomberie" <?php echo e($entreprise->type_activite == 'Plomberie' ? 'selected' : ''); ?>>Plomberie</option>
                                                            <option value="Électricité" <?php echo e($entreprise->type_activite == 'Électricité' ? 'selected' : ''); ?>>Électricité</option>
                                                            <option value="Menuiserie" <?php echo e($entreprise->type_activite == 'Menuiserie' ? 'selected' : ''); ?>>Menuiserie</option>
                                                        </optgroup>
                                                        <optgroup label="Événements">
                                                            <option value="Organisation_Événements" <?php echo e($entreprise->type_activite == 'Organisation_Événements' ? 'selected' : ''); ?>>Organisation d'événements</option>
                                                            <option value="Animation" <?php echo e($entreprise->type_activite == 'Animation' ? 'selected' : ''); ?>>Animation / DJ</option>
                                                            <option value="Décoration" <?php echo e($entreprise->type_activite == 'Décoration' ? 'selected' : ''); ?>>Décoration événementielle</option>
                                                        </optgroup>
                                                        <optgroup label="Santé & Sport">
                                                            <option value="Coach_Sportif" <?php echo e($entreprise->type_activite == 'Coach_Sportif' ? 'selected' : ''); ?>>Coach sportif / Fitness</option>
                                                            <option value="Yoga" <?php echo e($entreprise->type_activite == 'Yoga' ? 'selected' : ''); ?>>Yoga / Pilates</option>
                                                            <option value="Nutritionniste" <?php echo e($entreprise->type_activite == 'Nutritionniste' ? 'selected' : ''); ?>>Nutritionniste / Diététicien</option>
                                                        </optgroup>
                                                        <optgroup label="Mode & Création">
                                                            <option value="Couture" <?php echo e($entreprise->type_activite == 'Couture' ? 'selected' : ''); ?>>Couture / Retouches</option>
                                                            <option value="Styliste" <?php echo e($entreprise->type_activite == 'Styliste' ? 'selected' : ''); ?>>Styliste</option>
                                                            <option value="Accessoires" <?php echo e($entreprise->type_activite == 'Accessoires' ? 'selected' : ''); ?>>Création d'accessoires</option>
                                                        </optgroup>
                                                        <optgroup label="Autres">
                                                            <option value="Autre" <?php echo e($entreprise->type_activite == 'Autre' ? 'selected' : ''); ?>>Autre</option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Email *
                                                    </label>
                                                    <input 
                                                        type="email" 
                                                        name="email" 
                                                        value="<?php echo e(old('email', $entreprise->email)); ?>"
                                                        required
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Téléphone
                                                    </label>
                                                    <input 
                                                        type="tel" 
                                                        name="telephone" 
                                                        value="<?php echo e(old('telephone', $entreprise->telephone)); ?>"
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Description
                                                </label>
                                                <textarea 
                                                    name="description" 
                                                    rows="4"
                                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                ><?php echo e(old('description', $entreprise->description)); ?></textarea>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                    Mots-clés (séparés par des virgules)
                                                </label>
                                                <input 
                                                    type="text" 
                                                    name="mots_cles" 
                                                    value="<?php echo e(old('mots_cles', $entreprise->mots_cles)); ?>"
                                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                >
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Ville
                                                    </label>
                                                    <input 
                                                        type="text" 
                                                        name="ville" 
                                                        value="<?php echo e(old('ville', $entreprise->ville)); ?>"
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                        Rayon de déplacement (km)
                                                    </label>
                                                    <input 
                                                        type="number" 
                                                        name="rayon_deplacement" 
                                                        value="<?php echo e(old('rayon_deplacement', $entreprise->rayon_deplacement)); ?>"
                                                        min="0"
                                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                    >
                                                </div>
                                            </div>

                                            <div class="flex justify-end mt-6">
                                                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                    Enregistrer les modifications
                                                </button>
                                            </div>
                                        </form>

                                        <!-- Galerie de réalisations (en dehors du formulaire principal) -->
                                        <div class="mt-6 border-t border-slate-200 dark:border-slate-700 pt-6">
                                            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                                                📸 Photos de réalisations
                                            </h4>
                                            
                                            <?php if($entreprise->realisationPhotos->count() > 0): ?>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                                    <?php $__currentLoopData = $entreprise->realisationPhotos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div class="relative group">
                                                            <img 
                                                                src="<?php echo e(asset('media/' . $photo->photo_path)); ?>" 
                                                                alt="<?php echo e($photo->titre ?? 'Réalisation'); ?>"
                                                                class="w-full h-32 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                                                            >
                                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                                                <button 
                                                                    type="button"
                                                                    onclick="if(confirm('Supprimer cette photo ?')) { document.getElementById('delete-photo-form-<?php echo e($photo->id); ?>').submit(); }"
                                                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition"
                                                                >
                                                                    Supprimer
                                                                </button>
                                                                <form id="delete-photo-form-<?php echo e($photo->id); ?>" action="<?php echo e(route('settings.entreprise.photo.delete', [$entreprise->slug, $photo->id])); ?>" method="POST" style="display: none;">
                                                                    <?php echo csrf_field(); ?>
                                                                    <?php echo method_field('DELETE'); ?>
                                                                </form>
                                                            </div>
                                                            <?php if($photo->titre): ?>
                                                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400 truncate"><?php echo e($photo->titre); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-700/50">
                                                <form action="<?php echo e(route('settings.entreprise.photo.add', $entreprise->slug)); ?>" method="POST" enctype="multipart/form-data">
                                                    <?php echo csrf_field(); ?>
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Ajouter une photo
                                                            </label>
                                                            <input 
                                                                type="file" 
                                                                name="photo" 
                                                                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                                                required
                                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-green-900/20 file:text-green-700 dark:file:text-green-400"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Titre (optionnel)
                                                            </label>
                                                            <input 
                                                                type="text" 
                                                                name="titre" 
                                                                placeholder="Ex: Tressage cheveux crépus"
                                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                                Description (optionnel)
                                                            </label>
                                                            <textarea 
                                                                name="description" 
                                                                rows="2"
                                                                placeholder="Description de la réalisation..."
                                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                                            ></textarea>
                                                        </div>
                                                        <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                                            Ajouter la photo
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                            <div class="mt-6">
                                                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                                    <input 
                                                        type="checkbox" 
                                                        name="afficher_nom_gerant" 
                                                        value="1"
                                                        <?php echo e(old('afficher_nom_gerant', $entreprise->afficher_nom_gerant) ? 'checked' : ''); ?>

                                                        class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                                    >
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            Afficher mon nom avec l'entreprise
                                                        </span>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Si activé, votre nom sera visible sur la page publique de l'entreprise et dans les conversations.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- Prix négociables -->
                                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                                <label class="flex items-start gap-3 cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        name="prix_negociables" 
                                                        value="1"
                                                        <?php echo e(old('prix_negociables', $entreprise->prix_negociables) ? 'checked' : ''); ?>

                                                        class="mt-1 w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                                    >
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            💰 Prix négociables
                                                        </span>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Si activé, les clients pourront négocier les prix des rendez-vous proposés via la messagerie.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- RDV uniquement via messagerie -->
                                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                                <label class="flex items-start gap-3 cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        name="rdv_uniquement_messagerie" 
                                                        value="1"
                                                        <?php echo e(old('rdv_uniquement_messagerie', $entreprise->rdv_uniquement_messagerie) ? 'checked' : ''); ?>

                                                        class="mt-1 w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500"
                                                    >
                                                    <div>
                                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                            💬 Rendez-vous uniquement via messagerie
                                                        </span>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                            Si activé, les clients devront passer par la messagerie pour prendre rendez-vous. L'agenda public sera désactivé.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- Options supplémentaires -->
                                            <div class="mt-6 border-t border-slate-200 dark:border-slate-700 pt-6">
                                                <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                                                    ⚡ Options supplémentaires
                                                </h4>
                                                
                                                <?php
                                                    $abonnementSiteWeb = $entreprise->abonnementSiteWeb();
                                                    $abonnementMultiPersonnes = $entreprise->abonnementMultiPersonnes();
                                                    $aSiteWebActif = $entreprise->aSiteWebActif();
                                                    $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
                                                ?>

                                                <!-- Site Web Vitrine -->
                                                <div class="mb-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg <?php echo e($aSiteWebActif ? 'bg-green-50 dark:bg-green-900/20' : ''); ?>">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <h5 class="font-semibold text-slate-900 dark:text-white">🌐 Site Web Vitrine</h5>
                                                                <?php if($aSiteWebActif): ?>
                                                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                                        Actif
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                                                Créez une page vitrine personnalisée pour votre entreprise accessible via /w/{slug}. 
                                                                Inclut logo, phrase d'accroche, photos et sections configurables.
                                                            </p>
                                                            <?php if($aSiteWebActif && !empty($entreprise->slug_web)): ?>
                                                                <div class="text-sm text-slate-700 dark:text-slate-300 mb-3">
                                                                    <p><strong>URL de votre site :</strong> 
                                                                        <a href="<?php echo e(route('site-web.show', ['slug' => $entreprise->slug_web])); ?>" target="_blank" class="text-green-600 dark:text-green-400 hover:underline">
                                                                            <?php echo e(url('/w/' . $entreprise->slug_web)); ?>

                                                                        </a>
                                                                    </p>
                                                                </div>
                                                                <button onclick="openAbonnementModal('<?php echo e($entreprise->slug); ?>', '<?php echo e($entreprise->nom); ?>')" class="inline-block px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                                                    Gérer l'abonnement
                                                                </button>
                                                            <?php else: ?>
                                                                <div class="flex items-center gap-3">
                                                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">5€/mois</span>
                                                                    <button onclick="openAbonnementModal('<?php echo e($entreprise->slug); ?>', '<?php echo e($entreprise->nom); ?>')" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                                                        S'abonner
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Gestion Multi-Personnes -->
                                                <div class="mb-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg <?php echo e($aGestionMultiPersonnes ? 'bg-green-50 dark:bg-green-900/20' : ''); ?>">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <h5 class="font-semibold text-slate-900 dark:text-white">👥 Gestion Multi-Personnes</h5>
                                                                <?php if($aGestionMultiPersonnes): ?>
                                                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                                        Actif
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                                                Gérez plusieurs personnes pour votre entreprise. Ajoutez des administrateurs, 
                                                                accédez à des statistiques avancées et gérez plusieurs établissements.
                                                            </p>
                                                            <?php if($aGestionMultiPersonnes): ?>
                                                                <div class="flex items-center gap-3">
                                                                    <a href="<?php echo e(route('entreprise.membres.index', $entreprise->slug)); ?>" class="inline-block px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                                                                        Gérer les membres
                                                                    </a>
                                                                    <button onclick="openAbonnementModal('<?php echo e($entreprise->slug); ?>', '<?php echo e($entreprise->nom); ?>')" class="inline-block px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition text-sm">
                                                                        Gérer l'abonnement
                                                                    </button>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="flex items-center gap-3">
                                                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">20€/mois</span>
                                                                    <button onclick="openAbonnementModal('<?php echo e($entreprise->slug); ?>', '<?php echo e($entreprise->nom); ?>')" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-sm">
                                                                        S'abonner
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Site Web Externe (Gratuit) -->
                                                <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <h5 class="font-semibold text-slate-900 dark:text-white">🔗 Lier un site web externe</h5>
                                                                <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                                                                    Gratuit
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                                                Si vous avez déjà un site web, vous pouvez le lier à votre entreprise.
                                                            </p>
                                                            <div class="mt-2">
                                                                <input 
                                                                    type="url" 
                                                                    name="site_web_externe" 
                                                                    value="<?php echo e(old('site_web_externe', $entreprise->site_web_externe)); ?>"
                                                                    placeholder="https://votre-site.com"
                                                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                                                                >
                                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                                    L'URL sera visible sur votre profil public.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Onglet Notifications -->
                    <div id="tab-notifications" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Préférences de notifications</h2>
                        
                        <div class="space-y-4">
                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-slate-900 dark:text-white">Notifications par email</h3>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Recevez des emails pour les nouvelles réservations</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-slate-900 dark:text-white">Notifications de paiement</h3>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Soyez informé lorsque vous recevez un paiement</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Sécurité -->
                    <div id="tab-security" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Sécurité</h2>
                        
                        <div class="space-y-6">
                            <!-- Changer le mot de passe -->
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Changer le mot de passe</h3>
                                
                                <form action="<?php echo e(route('settings.password.update')); ?>" method="POST" class="space-y-4">
                                    <?php echo csrf_field(); ?>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                            Mot de passe actuel *
                                        </label>
                                        <input 
                                            type="password" 
                                            name="current_password" 
                                            required
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        >
                                        <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Nouveau mot de passe *
                                            </label>
                                            <input 
                                                type="password" 
                                                name="new_password" 
                                                required
                                                minlength="8"
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                            <?php $__errorArgs = ['new_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Minimum 8 caractères
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                Confirmer le mot de passe *
                                            </label>
                                            <input 
                                                type="password" 
                                                name="new_password_confirmation" 
                                                required
                                                minlength="8"
                                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                            >
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                            Mettre à jour le mot de passe
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Sessions actives -->
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Sessions actives</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    Vous êtes actuellement connecté sur cet appareil.
                                </p>
                                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Session actuelle</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400"><?php echo e(now()->format('d/m/Y à H:i')); ?></p>
                                    </div>
                                    <span class="px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                        Actif
                                    </span>
                                </div>
                            </div>

                            <!-- Zone de danger -->
                            <div class="p-6 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/20">
                                <h3 class="text-lg font-semibold text-red-900 dark:text-red-400 mb-2">Zone de danger</h3>
                                <p class="text-sm text-red-800 dark:text-red-300 mb-4">
                                    Une fois votre compte supprimé, toutes vos données seront définitivement effacées.
                                </p>
                                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                    Supprimer mon compte
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Abonnement -->
                    <?php if($user->est_gerant): ?>
                        <div id="tab-subscription" class="tab-content hidden">
                            <?php echo $__env->make('partials.settings.subscription-tab', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Onglet Confidentialité -->
                    <div id="tab-confidentialite" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Confidentialité</h2>
                        
                        <form action="<?php echo e(route('settings.confidentialite.update')); ?>" method="POST" class="space-y-6">
                            <?php echo csrf_field(); ?>
                            
                            <!-- Consentement aux trackers -->
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                                            Tracker de visites
                                        </h3>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                                            En acceptant les trackers, vous aidez les professionnels (Tata) à améliorer et simplifier leurs activités. 
                                            Ces statistiques anonymisées leur permettent de mieux comprendre les besoins de leurs clients et d'optimiser leurs services.
                                        </p>
                                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-4">
                                            <p class="text-sm text-blue-800 dark:text-blue-400">
                                                <strong>🔒 Données collectées :</strong> Les trackers enregistrent uniquement des données anonymes (durée de visite, pages consultées, services/produits cliqués). 
                                                Aucune donnée personnelle identifiable n'est collectée sans votre consentement explicite.
                                            </p>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-500 mb-4">
                                            En conformité avec le RGPD, vous pouvez à tout moment modifier votre préférence. 
                                            <a href="<?php echo e(route('legal.confidentialite')); ?>" class="text-green-600 dark:text-green-400 hover:underline">En savoir plus sur notre politique de confidentialité</a>.
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                                    <div class="flex-1">
                                        <label class="text-base font-medium text-slate-900 dark:text-white cursor-pointer" for="tracking-consent">
                                            Autoriser le tracking des visites pour améliorer les services des Tata
                                        </label>
                                    </div>
                                    <div class="ml-4">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                id="tracking-consent"
                                                name="tracking_consent" 
                                                value="1"
                                                <?php echo e(old('tracking_consent', $user->tracking_consent ?? true) ? 'checked' : ''); ?>

                                                class="sr-only peer"
                                            >
                                            <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5">
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Onglet Préférences -->
                    <div id="tab-preferences" class="tab-content hidden">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Préférences</h2>
                        
                        <div class="space-y-6">
                            <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Apparence</h3>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Thème sombre</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Activez le mode sombre pour une meilleure expérience</p>
                                    </div>
                                    <button
                                        id="theme-toggle"
                                        class="p-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
                                    >
                                        <svg class="w-6 h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        <svg class="w-6 h-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <?php if($user->is_admin): ?>
                                <!-- Mode Debug -->
                                <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Mode Debug (Admin)</h3>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-slate-900 dark:text-white">État actuel</p>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    <?php if(config('app.debug')): ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Mode Debug ACTIVÉ
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Mode Debug DÉSACTIVÉ
                                                        </span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                            <p class="text-sm text-blue-800 dark:text-blue-400 mb-2">
                                                <strong>ℹ️ Comment activer/désactiver le mode debug :</strong>
                                            </p>
                                            <ol class="list-decimal list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                                <li>Ouvrez le fichier <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">.env</code> à la racine du projet</li>
                                                <li>Modifiez la ligne <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">APP_DEBUG=true</code> (ou <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">false</code>)</li>
                                                <li>Rechargez la page pour voir le changement</li>
                                            </ol>
                                            <p class="text-xs text-blue-600 dark:text-blue-500 mt-3">
                                                ⚠️ <strong>Attention :</strong> Le mode debug doit être désactivé en production pour des raisons de sécurité.
                                            </p>
                                        </div>
                                        <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                            <p class="text-sm font-medium text-slate-900 dark:text-white mb-2">Informations de l'environnement :</p>
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <span class="text-slate-600 dark:text-slate-400">Environnement :</span>
                                                    <span class="ml-2 font-mono text-slate-900 dark:text-white"><?php echo e(config('app.env')); ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-slate-600 dark:text-slate-400">Debug :</span>
                                                    <span class="ml-2 font-mono text-slate-900 dark:text-white"><?php echo e(config('app.debug') ? 'true' : 'false'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                    $hasNotificationsColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'notifications_erreurs_actives');
                                ?>
                                <?php if($hasNotificationsColumn): ?>
                                    <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg">
                                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Notifications d'erreurs (Admin)</h3>
                                        <form action="<?php echo e(route('settings.error-notifications.update')); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-medium text-slate-900 dark:text-white">Notifications d'erreurs en temps réel</p>
                                                    <p class="text-sm text-slate-600 dark:text-slate-400">Recevez des notifications en temps réel lorsque des erreurs se produisent sur l'application</p>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input 
                                                        type="checkbox" 
                                                        name="notifications_erreurs_actives" 
                                                        value="1"
                                                        <?php echo e(isset($user->notifications_erreurs_actives) && $user->notifications_erreurs_actives ? 'checked' : ''); ?>

                                                        onchange="this.form.submit()"
                                                        class="sr-only peer"
                                                    >
                                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                                                </label>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            // Gestion des onglets
            function showTab(tabName) {
                // Masquer tous les contenus
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Réinitialiser tous les boutons de la sidebar
                document.querySelectorAll('.sidebar-tab').forEach(button => {
                    button.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
                    button.classList.add('text-slate-600', 'dark:text-slate-400');
                });

                // Afficher le contenu sélectionné
                const tabContent = document.getElementById('tab-' + tabName);
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                }

                // Activer le bouton sélectionné
                const activeButtons = document.querySelectorAll(`[data-tab="${tabName}"]`);
                activeButtons.forEach(button => {
                    button.classList.remove('text-slate-600', 'dark:text-slate-400');
                    button.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400');
                });

                // Mettre à jour l'URL sans recharger la page
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
            }

            // Afficher l'onglet par défaut ou depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'account';
            showTab(tab);

            // Upload automatique du logo
            <?php $__currentLoopData = $entreprises; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entreprise): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                document.getElementById('logo-input-<?php echo e($entreprise->id); ?>')?.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('logo', file);
                    formData.append('_token', '<?php echo e(csrf_token()); ?>');

                    const loadingEl = document.getElementById('logo-loading-<?php echo e($entreprise->id); ?>');
                    const previewEl = document.getElementById('logo-preview-<?php echo e($entreprise->id); ?>');
                    const imgEl = document.getElementById('logo-img-<?php echo e($entreprise->id); ?>');

                    loadingEl.classList.remove('hidden');

                    fetch('<?php echo e(route('settings.entreprise.logo.upload', $entreprise->slug)); ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Erreur lors de l\'upload');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadingEl.classList.add('hidden');
                        if (data.success) {
                            previewEl.classList.remove('hidden');
                            imgEl.src = data.logo_url + '?t=' + new Date().getTime();
                            // Afficher un message de succès temporaire
                            const inputContainer = e.target.closest('.flex');
                            let existingMsg = inputContainer.parentElement.querySelector('.upload-success-msg');
                            if (existingMsg) existingMsg.remove();
                            const successMsg = document.createElement('div');
                            successMsg.className = 'upload-success-msg mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-400 text-sm';
                            successMsg.textContent = data.message;
                            inputContainer.parentElement.appendChild(successMsg);
                            setTimeout(() => successMsg.remove(), 3000);
                            // Réinitialiser l'input
                            e.target.value = '';
                        } else {
                            throw new Error(data.message || 'Erreur lors de l\'upload du logo');
                        }
                    })
                    .catch(error => {
                        loadingEl.classList.add('hidden');
                        console.error('Error:', error);
                        // Afficher un message d'erreur
                        const inputContainer = e.target.closest('.flex');
                        let existingMsg = inputContainer.parentElement.querySelector('.upload-error-msg');
                        if (existingMsg) existingMsg.remove();
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'upload-error-msg mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-400 text-sm';
                        errorMsg.textContent = error.message || 'Erreur lors de l\'upload du logo';
                        inputContainer.parentElement.appendChild(errorMsg);
                        setTimeout(() => errorMsg.remove(), 5000);
                    });
                });

                // Upload automatique de l'image de fond
                document.getElementById('image-fond-input-<?php echo e($entreprise->id); ?>')?.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('image_fond', file);
                    formData.append('_token', '<?php echo e(csrf_token()); ?>');

                    const loadingEl = document.getElementById('image-fond-loading-<?php echo e($entreprise->id); ?>');
                    const previewEl = document.getElementById('image-fond-preview-<?php echo e($entreprise->id); ?>');
                    const imgEl = document.getElementById('image-fond-img-<?php echo e($entreprise->id); ?>');

                    loadingEl.classList.remove('hidden');

                    fetch('<?php echo e(route('settings.entreprise.image-fond.upload', $entreprise->slug)); ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Erreur lors de l\'upload');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadingEl.classList.add('hidden');
                        if (data.success) {
                            previewEl.classList.remove('hidden');
                            previewEl.classList.add('mb-3');
                            imgEl.src = data.image_fond_url + '?t=' + new Date().getTime();
                            // Afficher un message de succès temporaire
                            const inputContainer = e.target.closest('.flex');
                            let existingMsg = inputContainer.parentElement.querySelector('.upload-success-msg');
                            if (existingMsg) existingMsg.remove();
                            const successMsg = document.createElement('div');
                            successMsg.className = 'upload-success-msg mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-400 text-sm';
                            successMsg.textContent = data.message;
                            inputContainer.parentElement.appendChild(successMsg);
                            setTimeout(() => successMsg.remove(), 3000);
                            // Réinitialiser l'input
                            e.target.value = '';
                        } else {
                            throw new Error(data.message || 'Erreur lors de l\'upload de l\'image de fond');
                        }
                    })
                    .catch(error => {
                        loadingEl.classList.add('hidden');
                        console.error('Error:', error);
                        // Afficher un message d'erreur
                        const inputContainer = e.target.closest('.flex');
                        let existingMsg = inputContainer.parentElement.querySelector('.upload-error-msg');
                        if (existingMsg) existingMsg.remove();
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'upload-error-msg mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-400 text-sm';
                        errorMsg.textContent = error.message || 'Erreur lors de l\'upload de l\'image de fond';
                        inputContainer.parentElement.appendChild(errorMsg);
                        setTimeout(() => errorMsg.remove(), 5000);
                    });
                });
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </script>

        <!-- Modale de gestion d'abonnement entreprise -->
        <div id="abonnement-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 overflow-y-auto p-4">
            <div class="min-h-screen flex items-center justify-center py-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700 flex-shrink-0">
                        <h3 id="abonnement-modal-title" class="text-2xl font-bold text-slate-900 dark:text-white">Gestion des abonnements</h3>
                        <button onclick="closeAbonnementModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                            <svg class="w-6 h-6 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="abonnement-modal-content" class="flex-1 overflow-y-auto p-6">
                        <div class="flex items-center justify-center py-12">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-green-600 dark:text-green-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-slate-600 dark:text-slate-400">Chargement...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function openAbonnementModal(slug, nomEntreprise) {
                const modal = document.getElementById('abonnement-modal');
                const modalTitle = document.getElementById('abonnement-modal-title');
                const modalContent = document.getElementById('abonnement-modal-content');
                
                modalTitle.textContent = `Abonnements - ${nomEntreprise}`;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                
                // Afficher le loader
                modalContent.innerHTML = `
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-green-600 dark:text-green-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-slate-600 dark:text-slate-400">Chargement...</p>
                        </div>
                    </div>
                `;
                
                // Charger le contenu via AJAX
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch(`/m/${slug}/abonnements/modal`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    credentials: 'same-origin',
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 403) {
                            throw new Error('Vous n\'avez pas accès à cette entreprise.');
                        } else if (response.status === 401) {
                            throw new Error('Vous devez être connecté pour accéder à cette fonctionnalité.');
                        } else {
                            throw new Error(`Erreur ${response.status} lors du chargement`);
                        }
                    }
                    return response.text();
                })
                .then(html => {
                    modalContent.innerHTML = html;
                    initModalForms(slug);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    modalContent.innerHTML = `
                        <div class="flex items-center justify-center py-12">
                            <div class="text-center">
                                <svg class="w-12 h-12 text-red-600 dark:text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-red-600 dark:text-red-400 mb-2 font-semibold">Erreur</p>
                                <p class="text-slate-600 dark:text-slate-400 mb-4">${error.message || 'Erreur lors du chargement'}</p>
                                <button onclick="closeAbonnementModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-lg transition">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    `;
                });
            }
            
            function initModalForms(slug) {
                const modalContent = document.getElementById('abonnement-modal-content');
                const forms = modalContent.querySelectorAll('form');
                
                forms.forEach(form => {
                    // Si c'est un formulaire de checkout Stripe, laisser le comportement par défaut (redirection)
                    if (form.action.includes('checkout')) {
                        return;
                    }
                    
                    // Pour les formulaires d'annulation, gérer via AJAX puis recharger le contenu
                    if (form.action.includes('cancel')) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            if (confirm('Êtes-vous sûr de vouloir annuler cet abonnement ?')) {
                                fetch(form.action, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]')?.value,
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: new FormData(form),
                                })
                                .then(response => {
                                    if (response.redirected) {
                                        // Si redirection, recharger la page complète
                                        window.location.reload();
                                    } else {
                                        // Recharger le contenu de la modale
                                        return fetch(`/m/${slug}/abonnements/modal`, {
                                            method: 'GET',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'text/html',
                                            },
                                        });
                                    }
                                })
                                .then(response => {
                                    if (response && response.ok) {
                                        return response.text();
                                    }
                                })
                                .then(html => {
                                    if (html) {
                                        modalContent.innerHTML = html;
                                        // Réinitialiser les gestionnaires d'événements pour les nouveaux formulaires
                                        initModalForms(slug);
                                    }
                                })
                                .catch(error => {
                                    console.error('Erreur:', error);
                                    alert('Une erreur est survenue. La page va être rechargée.');
                                    window.location.reload();
                                });
                            }
                        });
                    }
                });
            }
            
            function closeAbonnementModal() {
                const modal = document.getElementById('abonnement-modal');
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
            
            // Fermer la modale en cliquant sur le fond
            document.getElementById('abonnement-modal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAbonnementModal();
                }
            });
            
            // Fermer avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('abonnement-modal');
                    if (!modal.classList.contains('hidden')) {
                        closeAbonnementModal();
                    }
                }
            });
        </script>
    </body>
</html>

<?php /**PATH /var/www/html/resources/views/settings/index.blade.php ENDPATH**/ ?>