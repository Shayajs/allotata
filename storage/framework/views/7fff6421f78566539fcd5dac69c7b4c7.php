<!-- Bandeau Impersonation Super-User -->
<?php if(session('original_admin_id')): ?>

<div class="relative z-50 bg-gradient-to-r from-slate-900 via-red-950 to-slate-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-9">
            
            <div class="flex items-center gap-2.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                <span class="text-xs font-semibold tracking-widest uppercase text-red-300">Super-User</span>
                <span class="hidden sm:inline text-xs text-slate-400">—</span>
                <span class="hidden sm:inline text-xs text-slate-300">Connecté en tant que <strong class="text-white"><?php echo e(auth()->user()->name); ?></strong></span>
            </div>

            
            <form action="<?php echo e(route('stop-impersonating')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" class="group flex items-center gap-1.5 text-xs font-semibold text-red-300 hover:text-white transition-colors duration-200">
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Quitter
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/partials/super-user-banner.blade.php ENDPATH**/ ?>