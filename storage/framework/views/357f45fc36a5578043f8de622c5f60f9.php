<?php $__env->startSection('content'); ?>
    <?php echo $body; ?>

    
    <?php if(isset($signature) && $signature): ?>
        <div class="signature">
            <p>Cordialement,</p>
            <p class="team-name">L'équipe Allo Tata</p>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/emails/template.blade.php ENDPATH**/ ?>