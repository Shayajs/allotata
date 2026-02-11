<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Messagerie</h2>
            <p class="text-slate-600 dark:text-slate-400">Conversations avec vos clients</p>
        </div>
    </div>

    <?php if($conversations->count() > 0): ?>
        <div class="space-y-3">
            <?php $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $dernierMessage = $conversation->messages->first();
                    $messagesNonLus = $conversation->messagesNonLus($user->id);
                ?>
                <a 
                    href="<?php echo e(route('messagerie.show-gerant', [$entreprise->slug, $conversation->id])); ?>" 
                    class="block p-4 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-green-500 dark:hover:border-green-500 transition <?php echo e($messagesNonLus > 0 ? 'border-l-4 border-l-green-500' : ''); ?>"
                >
                    <div class="flex items-start gap-4">
                        <!-- Avatar du client -->
                        <div class="relative flex-shrink-0">
                            <?php if (isset($component)) { $__componentOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ca5b43b8fff8bb34ab2ba4eb4bdd67b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.avatar','data' => ['user' => $conversation->user,'size' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($conversation->user),'size' => 'lg']); ?>
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
                            <?php if($messagesNonLus > 0): ?>
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                    <?php echo e($messagesNonLus); ?>

                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Contenu -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-slate-900 dark:text-white truncate">
                                    <?php echo e($conversation->user->name); ?>

                                </h3>
                                <?php if($dernierMessage): ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 flex-shrink-0 ml-2">
                                        <?php echo e($dernierMessage->created_at->diffForHumans()); ?>

                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                                <?php echo e($conversation->user->email); ?>

                            </p>
                            <?php if($dernierMessage): ?>
                                <p class="text-sm text-slate-600 dark:text-slate-400 truncate <?php echo e($messagesNonLus > 0 ? 'font-medium' : ''); ?>">
                                    <?php if($dernierMessage->user_id !== $conversation->user_id): ?>
                                        <span class="text-slate-400 dark:text-slate-500">Vous : </span>
                                    <?php endif; ?>
                                    <?php echo e(Str::limit($dernierMessage->contenu, 60)); ?>

                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Flèche -->
                        <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucune conversation</h3>
            <p class="text-slate-600 dark:text-slate-400">
                Vous n'avez pas encore de conversations avec vos clients.
            </p>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /var/www/html/resources/views/entreprise/dashboard/tabs/messagerie-liste.blade.php ENDPATH**/ ?>