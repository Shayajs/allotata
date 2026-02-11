<?php $__env->startSection('title', 'Devis'); ?>

<?php $__env->startSection('actions'); ?>
<a href="<?php echo e(route('brightshell.devis.create')); ?>" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouveau devis
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php if(count($devis) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Objet</th>
                    <th>Montant HT</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $devis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="Numéro" class="font-bold"><?php echo e($d->numero); ?></td>
                    <td data-label="Client"><?php echo e($d->client_societe ?? $d->client_nom); ?></td>
                    <td data-label="Objet"><?php echo e(Str::limit($d->objet, 40)); ?></td>
                    <td data-label="Montant HT"><?php echo e(number_format($d->montant_ht, 2, ',', ' ')); ?> €</td>
                    <td data-label="Statut">
                        <?php switch($d->statut):
                            case ('brouillon'): ?>
                                <span class="badge badge-info">Brouillon</span>
                                <?php break; ?>
                            <?php case ('envoye'): ?>
                                <span class="badge badge-warning">Envoyé</span>
                                <?php break; ?>
                            <?php case ('accepte'): ?>
                                <span class="badge badge-success">Accepté</span>
                                <?php break; ?>
                            <?php case ('refuse'): ?>
                                <span class="badge badge-danger">Refusé</span>
                                <?php break; ?>
                        <?php endswitch; ?>
                    </td>
                    <td data-label="Date" class="text-muted"><?php echo e(\Carbon\Carbon::parse($d->created_at)->format('d/m/Y')); ?></td>
                    <td data-label="Actions">
                        <div class="flex gap-2" style="justify-content: flex-end;">
                            <a href="<?php echo e(route('brightshell.devis.show', $d->id)); ?>" class="btn btn-secondary btn-sm">Voir</a>
                            <?php if($d->statut !== 'accepte'): ?>
                            <form action="<?php echo e(route('brightshell.devis.convert', $d->id)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-success btn-sm">→ Facture</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Aucun devis</h3>
        <p style="margin-bottom: 1.5rem;">Créez votre premier devis.</p>
        <a href="<?php echo e(route('brightshell.devis.create')); ?>" class="btn btn-primary">Créer un devis</a>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('brightshell.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/brightshell/devis/index.blade.php ENDPATH**/ ?>