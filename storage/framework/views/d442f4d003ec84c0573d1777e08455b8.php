
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Support</h2>
        <a href="<?php echo e(route('tickets.create')); ?>" class="px-4 py-2 text-sm font-medium bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white rounded-lg transition">
            + Nouveau ticket
        </a>
    </div>

    
    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Questions fréquentes
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="<?php echo e(route('support.faq')); ?>" class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-500 dark:hover:border-green-500 transition-all group">
                <p class="font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Comment créer une entreprise ?</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Guide de création étape par étape</p>
            </a>
            <a href="<?php echo e(route('support.faq')); ?>" class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-500 dark:hover:border-green-500 transition-all group">
                <p class="font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Comment fonctionne le paiement ?</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Modes de paiement et facturation</p>
            </a>
            <a href="<?php echo e(route('support.faq')); ?>" class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-500 dark:hover:border-green-500 transition-all group">
                <p class="font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Comment modifier une réservation ?</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Modification et annulation</p>
            </a>
            <a href="<?php echo e(route('support.faq')); ?>" class="p-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-green-500 dark:hover:border-green-500 transition-all group">
                <p class="font-medium text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Voir toute la FAQ</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Toutes les questions et réponses</p>
            </a>
        </div>
    </div>

    
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
            Mes tickets de support
        </h3>
        
        <?php
            $userTickets = \App\Models\Ticket::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        ?>

        <?php if($userTickets->count() > 0): ?>
            <div class="space-y-3">
                <?php $__currentLoopData = $userTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('tickets.show', $ticket->id)); ?>" class="block p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white"><?php echo e($ticket->sujet); ?></p>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                    Créé le <?php echo e($ticket->created_at->format('d/m/Y')); ?>

                                </p>
                            </div>
                            <span class="px-3 py-1 text-xs font-medium rounded-full
                                <?php if($ticket->statut === 'resolu'): ?> bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                <?php elseif($ticket->statut === 'ferme'): ?> bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400
                                <?php elseif($ticket->statut === 'en_cours'): ?> bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                <?php else: ?> bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                <?php endif; ?>">
                                <?php if($ticket->statut === 'resolu'): ?> Résolu
                                <?php elseif($ticket->statut === 'ferme'): ?> Fermé
                                <?php elseif($ticket->statut === 'en_cours'): ?> En cours
                                <?php else: ?> En attente
                                <?php endif; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            
            <div class="mt-4 text-center">
                <a href="<?php echo e(route('tickets.index')); ?>" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                    Voir tous mes tickets →
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Aucun ticket de support pour le moment.
                </p>
                <a href="<?php echo e(route('tickets.create')); ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                    Créer un ticket
                </a>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="mt-6 p-6 bg-gradient-to-r from-green-500 to-orange-500 rounded-xl text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold">Besoin d'aide immédiate ?</h3>
                <p class="text-white/90 text-sm mt-1">Notre équipe est disponible pour vous aider.</p>
            </div>
            <a href="mailto:support@allotata.com" class="px-6 py-3 bg-white text-green-600 font-semibold rounded-lg hover:bg-slate-100 transition text-center">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Contacter le support
            </a>
        </div>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/dashboard/tabs/support.blade.php ENDPATH**/ ?>