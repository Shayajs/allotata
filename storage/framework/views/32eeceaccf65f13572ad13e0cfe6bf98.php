<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($entreprise->nom); ?> - Allo Tata</title>
    <meta name="description" content="<?php echo e($entreprise->phrase_accroche ?? $entreprise->description); ?>">
    
    
    <meta property="og:title" content="<?php echo e($entreprise->nom); ?>">
    <meta property="og:description" content="<?php echo e($entreprise->phrase_accroche ?? $entreprise->description); ?>">
    <?php if(!empty($entreprise->logo)): ?>
        <meta property="og:image" content="<?php echo e(route('storage.serve', ['path' => $entreprise->logo])); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Oswald:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Source+Sans+Pro:wght@400;600;700&family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
    
    <?php
        $theme = $entreprise->getSiteWebTheme();
    ?>
    
    <style>
        :root {
            --site-primary: <?php echo e($theme['colors']['primary'] ?? '#22c55e'); ?>;
            --site-secondary: <?php echo e($theme['colors']['secondary'] ?? '#f97316'); ?>;
            --site-accent: <?php echo e($theme['colors']['accent'] ?? '#3b82f6'); ?>;
            --site-background: <?php echo e($theme['colors']['background'] ?? '#ffffff'); ?>;
            --site-text: <?php echo e($theme['colors']['text'] ?? '#1e293b'); ?>;
            --site-font-heading: '<?php echo e($theme['fonts']['heading'] ?? 'Poppins'); ?>', sans-serif;
            --site-font-body: '<?php echo e($theme['fonts']['body'] ?? 'Inter'); ?>', sans-serif;
            --site-button-radius: <?php echo e(($theme['buttons']['style'] ?? 'rounded') === 'rounded' ? '0.5rem' : (($theme['buttons']['style'] ?? 'rounded') === 'pill' ? '9999px' : '0')); ?>;
            --site-button-shadow: <?php echo e(($theme['buttons']['shadow'] ?? true) ? '0 4px 6px -1px rgba(0, 0, 0, 0.1)' : 'none'); ?>;
        }
        
        body {
            font-family: var(--site-font-body);
            background: var(--site-background);
            color: var(--site-text);
        }
        
        /* Animations */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .animate-fadeIn { animation: fadeIn 0.6s ease forwards; }
        .animate-slideUp { animation: slideUp 0.6s ease forwards; }
        .animate-slideLeft { animation: slideLeft 0.6s ease forwards; }
        .animate-zoomIn { animation: zoomIn 0.6s ease forwards; }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideLeft {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="antialiased">
    
    <?php if(isset($isOwner) && $isOwner && !empty($entreprise->slug_web)): ?>
        <div class="fixed top-0 left-0 right-0 z-50 bg-slate-900 text-white py-2 px-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <span class="text-sm">Vous visualisez votre site en mode public</span>
                <a href="<?php echo e(route('site-web.show', ['slug' => $entreprise->slug_web])); ?>" 
                   class="px-4 py-1 text-sm font-medium bg-green-600 hover:bg-green-700 rounded-lg transition">
                    Retour à l'édition
                </a>
            </div>
        </div>
        <div class="h-10"></div>
    <?php endif; ?>
    
    
    <main>
        <?php
            $blocks = $entreprise->getSiteWebBlocks();
        ?>
        
        <?php if(count($blocks) > 0): ?>
            <?php $__currentLoopData = $blocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $block): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $animation = $block['animation'] ?? 'none';
                    $animationClass = $animation !== 'none' ? "animate-on-scroll" : '';
                ?>
                
                <div class="<?php echo e($animationClass); ?>" data-animation="<?php echo e($animation); ?>">
                    <?php switch($block['type']):
                        case ('hero'): ?>
                            <?php if (isset($component)) { $__componentOriginal7745d108ebf7deee2b7bc694469f3ca2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7745d108ebf7deee2b7bc694469f3ca2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.hero','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.hero'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7745d108ebf7deee2b7bc694469f3ca2)): ?>
<?php $attributes = $__attributesOriginal7745d108ebf7deee2b7bc694469f3ca2; ?>
<?php unset($__attributesOriginal7745d108ebf7deee2b7bc694469f3ca2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7745d108ebf7deee2b7bc694469f3ca2)): ?>
<?php $component = $__componentOriginal7745d108ebf7deee2b7bc694469f3ca2; ?>
<?php unset($__componentOriginal7745d108ebf7deee2b7bc694469f3ca2); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('text'): ?>
                            <?php if (isset($component)) { $__componentOriginal64ffcd5500967c73d2e1958da0416329 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal64ffcd5500967c73d2e1958da0416329 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.text','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal64ffcd5500967c73d2e1958da0416329)): ?>
<?php $attributes = $__attributesOriginal64ffcd5500967c73d2e1958da0416329; ?>
<?php unset($__attributesOriginal64ffcd5500967c73d2e1958da0416329); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal64ffcd5500967c73d2e1958da0416329)): ?>
<?php $component = $__componentOriginal64ffcd5500967c73d2e1958da0416329; ?>
<?php unset($__componentOriginal64ffcd5500967c73d2e1958da0416329); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('image'): ?>
                            <?php if (isset($component)) { $__componentOriginal8503e750abbf21a2ac209fab58ac98d8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8503e750abbf21a2ac209fab58ac98d8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.image','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.image'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8503e750abbf21a2ac209fab58ac98d8)): ?>
<?php $attributes = $__attributesOriginal8503e750abbf21a2ac209fab58ac98d8; ?>
<?php unset($__attributesOriginal8503e750abbf21a2ac209fab58ac98d8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8503e750abbf21a2ac209fab58ac98d8)): ?>
<?php $component = $__componentOriginal8503e750abbf21a2ac209fab58ac98d8; ?>
<?php unset($__componentOriginal8503e750abbf21a2ac209fab58ac98d8); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('gallery'): ?>
                            <?php if (isset($component)) { $__componentOriginalf17a9807bb1654906e2038773e485502 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf17a9807bb1654906e2038773e485502 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.gallery','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.gallery'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf17a9807bb1654906e2038773e485502)): ?>
<?php $attributes = $__attributesOriginalf17a9807bb1654906e2038773e485502; ?>
<?php unset($__attributesOriginalf17a9807bb1654906e2038773e485502); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf17a9807bb1654906e2038773e485502)): ?>
<?php $component = $__componentOriginalf17a9807bb1654906e2038773e485502; ?>
<?php unset($__componentOriginalf17a9807bb1654906e2038773e485502); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('contact'): ?>
                            <?php if (isset($component)) { $__componentOriginal090daa7cd73c0eeaaa8d2caf2c211970 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal090daa7cd73c0eeaaa8d2caf2c211970 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.contact','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.contact'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal090daa7cd73c0eeaaa8d2caf2c211970)): ?>
<?php $attributes = $__attributesOriginal090daa7cd73c0eeaaa8d2caf2c211970; ?>
<?php unset($__attributesOriginal090daa7cd73c0eeaaa8d2caf2c211970); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal090daa7cd73c0eeaaa8d2caf2c211970)): ?>
<?php $component = $__componentOriginal090daa7cd73c0eeaaa8d2caf2c211970; ?>
<?php unset($__componentOriginal090daa7cd73c0eeaaa8d2caf2c211970); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('video'): ?>
                            <?php if (isset($component)) { $__componentOriginalf7aeca9d0d1205d3a9387af58eddb8d1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf7aeca9d0d1205d3a9387af58eddb8d1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.video','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.video'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf7aeca9d0d1205d3a9387af58eddb8d1)): ?>
<?php $attributes = $__attributesOriginalf7aeca9d0d1205d3a9387af58eddb8d1; ?>
<?php unset($__attributesOriginalf7aeca9d0d1205d3a9387af58eddb8d1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf7aeca9d0d1205d3a9387af58eddb8d1)): ?>
<?php $component = $__componentOriginalf7aeca9d0d1205d3a9387af58eddb8d1; ?>
<?php unset($__componentOriginalf7aeca9d0d1205d3a9387af58eddb8d1); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('services'): ?>
                            <?php if (isset($component)) { $__componentOriginal4244f3ed034654166e8ea1db61f02e90 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4244f3ed034654166e8ea1db61f02e90 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.services','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.services'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4244f3ed034654166e8ea1db61f02e90)): ?>
<?php $attributes = $__attributesOriginal4244f3ed034654166e8ea1db61f02e90; ?>
<?php unset($__attributesOriginal4244f3ed034654166e8ea1db61f02e90); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4244f3ed034654166e8ea1db61f02e90)): ?>
<?php $component = $__componentOriginal4244f3ed034654166e8ea1db61f02e90; ?>
<?php unset($__componentOriginal4244f3ed034654166e8ea1db61f02e90); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('testimonials'): ?>
                            <?php if (isset($component)) { $__componentOriginal40d8476e28c7ec8ca175ec4ce824db4b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal40d8476e28c7ec8ca175ec4ce824db4b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.testimonials','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.testimonials'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal40d8476e28c7ec8ca175ec4ce824db4b)): ?>
<?php $attributes = $__attributesOriginal40d8476e28c7ec8ca175ec4ce824db4b; ?>
<?php unset($__attributesOriginal40d8476e28c7ec8ca175ec4ce824db4b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal40d8476e28c7ec8ca175ec4ce824db4b)): ?>
<?php $component = $__componentOriginal40d8476e28c7ec8ca175ec4ce824db4b; ?>
<?php unset($__componentOriginal40d8476e28c7ec8ca175ec4ce824db4b); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('cta'): ?>
                            <?php if (isset($component)) { $__componentOriginal974c75a2ab2fdd49254c582b0ebcfedb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal974c75a2ab2fdd49254c582b0ebcfedb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.cta','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.cta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal974c75a2ab2fdd49254c582b0ebcfedb)): ?>
<?php $attributes = $__attributesOriginal974c75a2ab2fdd49254c582b0ebcfedb; ?>
<?php unset($__attributesOriginal974c75a2ab2fdd49254c582b0ebcfedb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal974c75a2ab2fdd49254c582b0ebcfedb)): ?>
<?php $component = $__componentOriginal974c75a2ab2fdd49254c582b0ebcfedb; ?>
<?php unset($__componentOriginal974c75a2ab2fdd49254c582b0ebcfedb); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('divider'): ?>
                            <?php if (isset($component)) { $__componentOriginal41b21e422f2a1294d433c5954ff15f5f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41b21e422f2a1294d433c5954ff15f5f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.divider','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.divider'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41b21e422f2a1294d433c5954ff15f5f)): ?>
<?php $attributes = $__attributesOriginal41b21e422f2a1294d433c5954ff15f5f; ?>
<?php unset($__attributesOriginal41b21e422f2a1294d433c5954ff15f5f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41b21e422f2a1294d433c5954ff15f5f)): ?>
<?php $component = $__componentOriginal41b21e422f2a1294d433c5954ff15f5f; ?>
<?php unset($__componentOriginal41b21e422f2a1294d433c5954ff15f5f); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('iframe'): ?>
                            <?php if (isset($component)) { $__componentOriginal4fa597dd0540b5b80c6a09b2fcc0360d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4fa597dd0540b5b80c6a09b2fcc0360d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.iframe','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.iframe'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4fa597dd0540b5b80c6a09b2fcc0360d)): ?>
<?php $attributes = $__attributesOriginal4fa597dd0540b5b80c6a09b2fcc0360d; ?>
<?php unset($__attributesOriginal4fa597dd0540b5b80c6a09b2fcc0360d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4fa597dd0540b5b80c6a09b2fcc0360d)): ?>
<?php $component = $__componentOriginal4fa597dd0540b5b80c6a09b2fcc0360d; ?>
<?php unset($__componentOriginal4fa597dd0540b5b80c6a09b2fcc0360d); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('faq'): ?>
                            <?php if (isset($component)) { $__componentOriginala12e82fcdca41d9cee5421aceb8fa852 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala12e82fcdca41d9cee5421aceb8fa852 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.faq','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.faq'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala12e82fcdca41d9cee5421aceb8fa852)): ?>
<?php $attributes = $__attributesOriginala12e82fcdca41d9cee5421aceb8fa852; ?>
<?php unset($__attributesOriginala12e82fcdca41d9cee5421aceb8fa852); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala12e82fcdca41d9cee5421aceb8fa852)): ?>
<?php $component = $__componentOriginala12e82fcdca41d9cee5421aceb8fa852; ?>
<?php unset($__componentOriginala12e82fcdca41d9cee5421aceb8fa852); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('team'): ?>
                            <?php if (isset($component)) { $__componentOriginal5f4e0664069fe21f498197ee9673e277 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f4e0664069fe21f498197ee9673e277 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.team','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.team'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5f4e0664069fe21f498197ee9673e277)): ?>
<?php $attributes = $__attributesOriginal5f4e0664069fe21f498197ee9673e277; ?>
<?php unset($__attributesOriginal5f4e0664069fe21f498197ee9673e277); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5f4e0664069fe21f498197ee9673e277)): ?>
<?php $component = $__componentOriginal5f4e0664069fe21f498197ee9673e277; ?>
<?php unset($__componentOriginal5f4e0664069fe21f498197ee9673e277); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('stats'): ?>
                            <?php if (isset($component)) { $__componentOriginald69b6139a1ecbd279dd2d39b328d1ac6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald69b6139a1ecbd279dd2d39b328d1ac6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.stats','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.stats'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald69b6139a1ecbd279dd2d39b328d1ac6)): ?>
<?php $attributes = $__attributesOriginald69b6139a1ecbd279dd2d39b328d1ac6; ?>
<?php unset($__attributesOriginald69b6139a1ecbd279dd2d39b328d1ac6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald69b6139a1ecbd279dd2d39b328d1ac6)): ?>
<?php $component = $__componentOriginald69b6139a1ecbd279dd2d39b328d1ac6; ?>
<?php unset($__componentOriginald69b6139a1ecbd279dd2d39b328d1ac6); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('features'): ?>
                            <?php if (isset($component)) { $__componentOriginal42bab4acb632131c6552c79e63ad30dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal42bab4acb632131c6552c79e63ad30dc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.features','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.features'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal42bab4acb632131c6552c79e63ad30dc)): ?>
<?php $attributes = $__attributesOriginal42bab4acb632131c6552c79e63ad30dc; ?>
<?php unset($__attributesOriginal42bab4acb632131c6552c79e63ad30dc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal42bab4acb632131c6552c79e63ad30dc)): ?>
<?php $component = $__componentOriginal42bab4acb632131c6552c79e63ad30dc; ?>
<?php unset($__componentOriginal42bab4acb632131c6552c79e63ad30dc); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('map'): ?>
                            <?php if (isset($component)) { $__componentOriginal407c715ad084f558bd7fb5f75606fe98 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal407c715ad084f558bd7fb5f75606fe98 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.map','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.map'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal407c715ad084f558bd7fb5f75606fe98)): ?>
<?php $attributes = $__attributesOriginal407c715ad084f558bd7fb5f75606fe98; ?>
<?php unset($__attributesOriginal407c715ad084f558bd7fb5f75606fe98); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal407c715ad084f558bd7fb5f75606fe98)): ?>
<?php $component = $__componentOriginal407c715ad084f558bd7fb5f75606fe98; ?>
<?php unset($__componentOriginal407c715ad084f558bd7fb5f75606fe98); ?>
<?php endif; ?>
                            <?php break; ?>
                        <?php case ('columns'): ?>
                            <?php if (isset($component)) { $__componentOriginal4936e6ec4d341e85550108c1a5ae0b49 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4936e6ec4d341e85550108c1a5ae0b49 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-web.blocks.columns','data' => ['block' => $block,'entreprise' => $entreprise]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-web.blocks.columns'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['block' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($block),'entreprise' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($entreprise)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4936e6ec4d341e85550108c1a5ae0b49)): ?>
<?php $attributes = $__attributesOriginal4936e6ec4d341e85550108c1a5ae0b49; ?>
<?php unset($__attributesOriginal4936e6ec4d341e85550108c1a5ae0b49); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4936e6ec4d341e85550108c1a5ae0b49)): ?>
<?php $component = $__componentOriginal4936e6ec4d341e85550108c1a5ae0b49; ?>
<?php unset($__componentOriginal4936e6ec4d341e85550108c1a5ae0b49); ?>
<?php endif; ?>
                            <?php break; ?>
                    <?php endswitch; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            
            <div class="min-h-screen flex items-center justify-center">
                <div class="text-center p-8">
                    <?php if(!empty($entreprise->logo)): ?>
                        <img src="<?php echo e(route('storage.serve', ['path' => $entreprise->logo])); ?>" alt="<?php echo e($entreprise->nom); ?>" class="w-32 h-32 mx-auto mb-6 rounded-xl object-cover">
                    <?php endif; ?>
                    <h1 class="text-4xl font-bold mb-4" style="font-family: var(--site-font-heading);">
                        <?php echo e($entreprise->nom); ?>

                    </h1>
                    <?php if($entreprise->phrase_accroche): ?>
                        <p class="text-xl text-slate-600 dark:text-slate-400 mb-6">
                            <?php echo e($entreprise->phrase_accroche); ?>

                        </p>
                    <?php endif; ?>
                    <a href="<?php echo e(route('public.entreprise', ['slug' => $entreprise->slug])); ?>" 
                       class="inline-block px-8 py-4 text-lg font-semibold text-white transition hover:opacity-90"
                       style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                        Voir la page entreprise
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    
    <footer class="py-8 px-4 text-center border-t border-slate-200 dark:border-slate-700">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            © <?php echo e(date('Y')); ?> <?php echo e($entreprise->nom); ?>. Tous droits réservés.
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
            Propulsé par <a href="<?php echo e(route('home')); ?>" class="hover:underline" style="color: var(--site-primary);">Allo Tata</a>
        </p>
    </footer>
    
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const animation = entry.target.dataset.animation;
                        entry.target.classList.add('visible');
                        if (animation && animation !== 'none') {
                            entry.target.classList.add('animate-' + animation);
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            animatedElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/public/site-web.blade.php ENDPATH**/ ?>