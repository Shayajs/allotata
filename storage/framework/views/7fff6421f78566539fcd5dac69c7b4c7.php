<!-- Bandeau Impersonation Super-User -->
<?php if(session('original_admin_id')): ?>
<div class="bg-red-600 text-white px-4 py-3 flex items-center justify-between shadow-lg fixed top-0 left-0 right-0 z-[100]" style="padding-top: env(safe-area-inset-top); max-width: 100vw; box-sizing: border-box;">
    <div class="flex items-center gap-3">
        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
        <div>
            <span class="font-bold uppercase tracking-wider text-sm">Mode Super-User</span>
            <span class="text-red-100 text-sm hidden sm:inline ml-2">| Connecté en tant que <strong class="text-white underline"><?php echo e(auth()->user()->name); ?></strong></span>
        </div>
    </div>
    <form action="<?php echo e(route('stop-impersonating')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <button type="submit" class="px-4 py-1.5 bg-white dark:bg-slate-800 text-red-700 dark:text-red-300 rounded-lg text-xs sm:text-sm font-bold hover:bg-red-50 dark:hover:bg-slate-700 transition-colors uppercase tracking-wide flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Quitter
        </button>
    </form>
</div>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/partials/super-user-banner.blade.php ENDPATH**/ ?>