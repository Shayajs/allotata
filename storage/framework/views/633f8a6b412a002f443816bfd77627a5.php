<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">CA Mensuel</div>
        <div class="stat-value"><?php echo e(number_format($stats['ca_mensuel'], 2, ',', ' ')); ?> €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">CA Annuel <?php echo e(date('Y')); ?></div>
        <div class="stat-value"><?php echo e(number_format($stats['ca_annuel'], 2, ',', ' ')); ?> €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Clients</div>
        <div class="stat-value text-accent"><?php echo e($stats['clients']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Factures impayées</div>
        <div class="stat-value <?php echo e($stats['factures_impayees'] > 0 ? 'warning' : 'success'); ?>"><?php echo e($stats['factures_impayees']); ?></div>
    </div>
</div>

<!-- Seuils Micro-entreprise -->
<div class="grid grid-2 mb-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Seuil Franchise TVA</h3>
            <span class="badge badge-info"><?php echo e(number_format($stats['seuil_tva'], 0, ',', ' ')); ?> €</span>
        </div>
        <?php $progressTVA = $stats['seuil_tva'] > 0 ? min(100, ($stats['ca_annuel'] / $stats['seuil_tva']) * 100) : 0; ?>
        <div class="progress">
            <div class="progress-bar <?php echo e($progressTVA > 80 ? ($progressTVA > 95 ? 'danger' : 'warning') : ''); ?>" style="width: <?php echo e($progressTVA); ?>%"></div>
        </div>
        <p class="text-muted text-sm mt-2">
            <?php echo e(number_format($stats['ca_annuel'], 0, ',', ' ')); ?> € / <?php echo e(number_format($stats['seuil_tva'], 0, ',', ' ')); ?> € (<?php echo e(number_format($progressTVA, 1)); ?>%)
        </p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Seuil Micro-Entreprise</h3>
            <span class="badge badge-info"><?php echo e(number_format($stats['seuil_micro'], 0, ',', ' ')); ?> €</span>
        </div>
        <?php $progressMicro = $stats['seuil_micro'] > 0 ? min(100, ($stats['ca_annuel'] / $stats['seuil_micro']) * 100) : 0; ?>
        <div class="progress">
            <div class="progress-bar <?php echo e($progressMicro > 80 ? 'warning' : ''); ?>" style="width: <?php echo e($progressMicro); ?>%"></div>
        </div>
        <p class="text-muted text-sm mt-2">
            <?php echo e(number_format($stats['ca_annuel'], 0, ',', ' ')); ?> € / <?php echo e(number_format($stats['seuil_micro'], 0, ',', ' ')); ?> € (<?php echo e(number_format($progressMicro, 1)); ?>%)
        </p>
    </div>
</div>

<!-- Cotisations URSSAF -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Cotisations URSSAF Estimées</h3>
        <span class="badge badge-warning"><?php echo e(($stats['taux_cotisations'] * 100)); ?>% en <?php echo e(date('Y')); ?></span>
    </div>
    <div class="grid grid-3">
        <div>
            <p class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 1px;">CA déclarable</p>
            <p class="text-accent" style="font-size: 1.5rem; font-weight: 600;"><?php echo e(number_format($stats['ca_annuel'], 2, ',', ' ')); ?> €</p>
        </div>
        <div>
            <p class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 1px;">Taux</p>
            <p class="text-accent" style="font-size: 1.5rem; font-weight: 600;"><?php echo e(($stats['taux_cotisations'] * 100)); ?>%</p>
        </div>
        <div>
            <p class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 1px;">Cotisations estimées</p>
            <p class="text-warning" style="font-size: 1.5rem; font-weight: 600;"><?php echo e(number_format($stats['ca_annuel'] * $stats['taux_cotisations'], 2, ',', ' ')); ?> €</p>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="grid grid-4">
    <a href="<?php echo e(route('brightshell.clients.create')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(91, 188, 228, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#5bbce4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Nouveau client</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Ajouter un client</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.devis.create')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#10b981" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Nouveau devis</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Créer un devis</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.factures.create')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#f59e0b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Nouvelle facture</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Créer une facture</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.mailing.compose')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(139, 92, 246, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#8b5cf6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Envoyer un email</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Composer un mail</p>
            </div>
        </div>
    </a>
</div>

<!-- Plus d'outils -->
<h3 style="margin: 2rem 0 1rem; font-size: 1rem; font-weight: 600; color: #8b9dc3; text-transform: uppercase; letter-spacing: 1px;">Plus d'outils</h3>
<div class="grid grid-4">
    <a href="<?php echo e(route('brightshell.taches')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(236, 72, 153, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#ec4899" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Tâches</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">To-do list</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.notes')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(34, 211, 238, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#22d3ee" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Notes</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Bloc-notes</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.agenda')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(251, 146, 60, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#fb923c" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Agenda</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Calendrier</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.legals')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(91, 188, 228, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#5bbce4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Legals</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Générateur</p>
            </div>
        </div>
    </a>

    <a href="<?php echo e(route('brightshell.documents')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(168, 85, 247, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#a855f7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Documents</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Fichiers</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.statistiques')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(34, 197, 94, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#22c55e" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Statistiques</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Rapports</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.fournisseurs')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(99, 102, 241, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#6366f1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Fournisseurs</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Contacts pro</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.achats.create')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(239, 68, 68, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#ef4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Nouvel achat</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Dépense</p>
            </div>
        </div>
    </a>
    
    <a href="<?php echo e(route('brightshell.exports')); ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="flex items-center gap-4">
            <div style="width: 48px; height: 48px; background: rgba(148, 163, 184, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            </div>
            <div>
                <h4 style="font-weight: 600; margin: 0; color: #fff;">Exports</h4>
                <p style="margin: 0; font-size: 0.875rem; color: #8b9dc3;">Télécharger</p>
            </div>
        </div>
    </a>
</div>

<!-- Infos entreprise -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Informations Entreprise</h3>
    </div>
    <div class="grid grid-2">
        <div>
            <p class="text-muted text-sm">Raison sociale</p>
            <p class="font-bold"><?php echo e($entreprise['forme_juridique']); ?> <?php echo e($entreprise['nom']); ?></p>
        </div>
        <div>
            <p class="text-muted text-sm">Responsable</p>
            <p class="font-bold"><?php echo e($entreprise['responsable']); ?></p>
        </div>
        <div>
            <p class="text-muted text-sm">SIRET</p>
            <p class="font-bold"><?php echo e($entreprise['siret']); ?></p>
        </div>
        <div>
            <p class="text-muted text-sm">Email</p>
            <p class="font-bold"><?php echo e($entreprise['email']); ?></p>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('brightshell.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/brightshell/dashboard.blade.php ENDPATH**/ ?>