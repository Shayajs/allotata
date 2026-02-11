<?php $__env->startSection('title', 'Factures'); ?>

<?php $__env->startSection('actions'); ?>
<a href="<?php echo e(route('brightshell.factures.create')); ?>" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nouvelle facture
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php if(count($factures) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Objet</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $factures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facture): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td data-label="Numéro" class="font-bold"><?php echo e($facture->numero); ?></td>
                    <td data-label="Client"><?php echo e($facture->client_societe ?? $facture->client_nom); ?></td>
                    <td data-label="Objet"><?php echo e(Str::limit($facture->objet, 40)); ?></td>
                    <td data-label="Montant" class="font-bold"><?php echo e(number_format($facture->montant_total, 2, ',', ' ')); ?> €</td>
                    <td data-label="Statut">
                        <?php if(str_starts_with($facture->numero, 'AVO')): ?>
                            <span class="badge" style="background: #8b5cf6; color: white;">Avoir</span>
                        <?php endif; ?>
                        <?php switch($facture->statut):
                            case ('brouillon'): ?>
                                <span class="badge badge-info">Brouillon</span>
                                <?php break; ?>
                            <?php case ('envoyee'): ?>
                                <span class="badge badge-warning">En attente</span>
                                <?php break; ?>
                            <?php case ('payee'): ?>
                                <span class="badge badge-success">Payée</span>
                                <?php break; ?>
                            <?php case ('annulee'): ?>
                                <span class="badge badge-danger">Annulée</span>
                                <?php break; ?>
                        <?php endswitch; ?>
                    </td>
                    <td data-label="Date" class="text-muted"><?php echo e(\Carbon\Carbon::parse($facture->created_at)->format('d/m/Y')); ?></td>
                    <td data-label="Actions">
                        <div class="flex gap-2" style="justify-content: flex-end;">
                            <a href="<?php echo e(route('brightshell.factures.show', $facture->id)); ?>" class="btn btn-secondary btn-sm">Voir</a>
                            <?php if($facture->statut !== 'payee'): ?>
                            <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('pay-modal-<?php echo e($facture->id); ?>').style.display='flex'">Payée</button>
                            
                            <!-- Modal Paiement -->
                            <div id="pay-modal-<?php echo e($facture->id); ?>" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
                                <div class="modal-content" style="background: white; padding: 2rem; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                    <h3 style="margin-top: 0; margin-bottom: 1rem; color: #0a0e1a; font-size: 1.25rem; font-weight: 700;">Enregistrer le paiement</h3>
                                    <p style="color: #6b7280; margin-bottom: 1.5rem;">Facture <?php echo e($facture->numero); ?></p>
                                    
                                    <form action="<?php echo e(route('brightshell.factures.paid', $facture->id)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label class="form-label" style="display: block; margin-bottom: 0.5rem; color: #0a0e1a; font-size: 0.875rem; font-weight: 600;">Montant payé (€)</label>
                                            <input type="number" name="montant_paye" class="form-input" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px;" value="<?php echo e($facture->montant_total); ?>" step="0.01" required>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 1.5rem;">
                                            <label class="form-label" style="display: block; margin-bottom: 0.5rem; color: #0a0e1a; font-size: 0.875rem; font-weight: 600;">Mode de paiement</label>
                                            <select name="mode_paiement" class="form-input" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px;">
                                                <option value="Virement bancaire">Virement bancaire</option>
                                                <option value="Chèque">Chèque</option>
                                                <option value="Carte bleue">Carte bleue</option>
                                                <option value="Espèces">Espèces</option>
                                            </select>
                                        </div>
                                        
                                        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('pay-modal-<?php echo e($facture->id); ?>').style.display='none'">Annuler</button>
                                            <button type="submit" class="btn btn-success">Confirmer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
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
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Aucune facture</h3>
        <p style="margin-bottom: 1.5rem;">Créez votre première facture.</p>
        <a href="<?php echo e(route('brightshell.factures.create')); ?>" class="btn btn-primary">Créer une facture</a>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('brightshell.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/brightshell/factures/index.blade.php ENDPATH**/ ?>