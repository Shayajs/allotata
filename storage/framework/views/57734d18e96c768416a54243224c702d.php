<?php $__env->startSection('title', 'Tâches'); ?>

<?php $__env->startSection('content'); ?>
<div class="grid grid-2">
    <!-- Formulaire ajout -->
    <div class="card">
        <h3 class="card-title mb-4">Nouvelle tâche</h3>
        <form action="<?php echo e(route('brightshell.taches.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <input type="text" name="titre" class="form-input" placeholder="Titre de la tâche" required>
            </div>
            <div class="form-group">
                <textarea name="description" class="form-textarea" rows="2" placeholder="Description (optionnel)"></textarea>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Priorité</label>
                    <select name="priorite" class="form-input">
                        <option value="basse">Basse</option>
                        <option value="normale" selected>Normale</option>
                        <option value="haute">Haute</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Échéance</label>
                    <input type="date" name="echeance" class="form-input">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
    
    <!-- Stats -->
    <div class="card">
        <h3 class="card-title mb-4">Résumé</h3>
        <?php
            $total = count($taches);
            $completed = collect($taches)->where('completed', true)->count();
            $pending = $total - $completed;
            $urgent = collect($taches)->where('priorite', 'urgente')->where('completed', false)->count();
        ?>
        <div class="grid grid-2" style="gap: 1rem;">
            <div style="background: var(--bs-bg-dark); padding: 1rem; border-radius: 8px; text-align: center;">
                <p class="text-muted text-xs" style="text-transform: uppercase;">En cours</p>
                <p class="text-accent" style="font-size: 2rem; font-weight: 700;"><?php echo e($pending); ?></p>
            </div>
            <div style="background: var(--bs-bg-dark); padding: 1rem; border-radius: 8px; text-align: center;">
                <p class="text-muted text-xs" style="text-transform: uppercase;">Terminées</p>
                <p class="text-success" style="font-size: 2rem; font-weight: 700;"><?php echo e($completed); ?></p>
            </div>
        </div>
        <?php if($urgent > 0): ?>
        <div style="background: rgba(239, 68, 68, 0.15); padding: 1rem; border-radius: 8px; margin-top: 1rem; text-align: center;">
            <p class="text-danger font-bold"><?php echo e($urgent); ?> tâche(s) urgente(s)</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Liste des tâches -->
<div class="card mt-4">
    <h3 class="card-title mb-4">Liste des tâches</h3>
    <?php if(count($taches) > 0): ?>
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        <?php $__currentLoopData = $taches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tache): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="tache-item" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bs-bg-dark); border-radius: 8px; flex-wrap: wrap; <?php echo e($tache->completed ? 'opacity: 0.6;' : ''); ?>">
            <form action="<?php echo e(route('brightshell.taches.toggle', $tache->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <button type="submit" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid <?php echo e($tache->completed ? 'var(--bs-success)' : 'var(--bs-border)'); ?>; background: <?php echo e($tache->completed ? 'var(--bs-success)' : 'transparent'); ?>; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    <?php if($tache->completed): ?>
                    <svg width="14" height="14" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <?php endif; ?>
                </button>
            </form>
            <div style="flex: 1; min-width: 200px;">
                <p style="font-weight: 600; margin: 0; <?php echo e($tache->completed ? 'text-decoration: line-through;' : ''); ?>"><?php echo e($tache->titre); ?></p>
                <?php if($tache->description): ?>
                <p class="text-muted text-sm" style="margin: 0.25rem 0 0;"><?php echo e(Str::limit($tache->description, 80)); ?></p>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2" style="margin-left: auto;">
                <?php if($tache->echeance): ?>
                <span class="text-muted text-sm"><?php echo e(\Carbon\Carbon::parse($tache->echeance)->format('d/m')); ?></span>
                <?php endif; ?>
                <span class="badge <?php echo e($tache->priorite === 'urgente' ? 'badge-danger' : ($tache->priorite === 'haute' ? 'badge-warning' : ($tache->priorite === 'basse' ? 'badge-info' : 'badge-success'))); ?>">
                    <?php echo e(ucfirst($tache->priorite ?? 'normale')); ?>

                </span>
                <form action="<?php echo e(route('brightshell.taches.delete', $tache->id)); ?>" method="POST" onsubmit="return confirm('Supprimer ?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-danger btn-sm">×</button>
                </form>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php else: ?>
    <p class="text-muted text-center">Aucune tâche. Ajoutez-en une !</p>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('brightshell.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/brightshell/taches/index.blade.php ENDPATH**/ ?>