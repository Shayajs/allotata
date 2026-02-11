<?php $__env->startSection('title', 'Projets'); ?>

<?php $__env->startSection('actions'); ?>
<a href="<?php echo e(route('brightshell.projets.create')); ?>" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouveau projet
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php if(count($projets) > 0): ?>
    <div class="grid grid-3">
        <?php $__currentLoopData = $projets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $projet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold"><?php echo e($projet->nom); ?></h3>
                <?php switch($projet->statut):
                    case ('en_attente'): ?>
                        <span class="badge badge-info">En attente</span>
                        <?php break; ?>
                    <?php case ('en_cours'): ?>
                        <span class="badge badge-warning">En cours</span>
                        <?php break; ?>
                    <?php case ('termine'): ?>
                        <span class="badge badge-success">Terminé</span>
                        <?php break; ?>
                    <?php case ('annule'): ?>
                        <span class="badge badge-danger">Annulé</span>
                        <?php break; ?>
                <?php endswitch; ?>
            </div>
            <?php if($projet->client_nom): ?>
            <p class="text-muted text-sm mb-2">Client: <?php echo e($projet->client_societe ?? $projet->client_nom); ?></p>
            <?php endif; ?>
            <?php if($projet->description): ?>
            <p class="text-sm" style="margin-bottom: 1rem;"><?php echo e(Str::limit($projet->description, 100)); ?></p>
            <?php endif; ?>
            <?php if($projet->budget): ?>
            <p class="text-accent font-bold">Budget: <?php echo e(number_format($projet->budget, 2, ',', ' ')); ?> €</p>
            <?php endif; ?>
            <p class="text-muted text-xs mt-2">Créé le <?php echo e(\Carbon\Carbon::parse($projet->created_at)->format('d/m/Y')); ?></p>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Aucun projet</h3>
        <p style="margin-bottom: 1.5rem;">Créez votre premier projet.</p>
        <a href="<?php echo e(route('brightshell.projets.create')); ?>" class="btn btn-primary">Créer un projet</a>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('brightshell.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/brightshell/projets/index.blade.php ENDPATH**/ ?>