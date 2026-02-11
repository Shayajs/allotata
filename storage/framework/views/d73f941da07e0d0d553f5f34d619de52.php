<?php
    use App\Helpers\SiteHelper;
    
    $logoLight = SiteHelper::getFavicon('light');
    $logoDark = SiteHelper::getFavicon('dark');
    $faviconFallback = asset('favicon.ico');
?>

<?php if($logoLight && $logoDark): ?>
    <!-- Favicon avec support dynamique du thème (utilise les logos light/dark) -->
    <link rel="icon" type="image/png" href="<?php echo e($logoLight); ?>" id="site-favicon">
    
    <script>
        (function() {
            const faviconLight = '<?php echo e($logoLight); ?>';
            const faviconDark = '<?php echo e($logoDark); ?>';
            const faviconFallback = '<?php echo e($faviconFallback); ?>';
            
            // Fonction pour mettre à jour le favicon selon le thème
            function updateFavicon() {
                const html = document.documentElement;
                const isDark = html.classList.contains('dark');
                const faviconLink = document.getElementById('site-favicon');
                
                if (faviconLink) {
                    const newHref = isDark ? faviconDark : faviconLight;
                    // Ajouter un timestamp pour forcer le rafraîchissement
                    faviconLink.href = newHref + '?t=' + Date.now();
                }
            }
            
            // Mettre à jour au chargement (après que le thème soit appliqué)
            function initFavicon() {
                // Attendre un peu pour que le thème soit appliqué
                setTimeout(updateFavicon, 50);
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initFavicon);
            } else {
                initFavicon();
            }
            
            // Observer les changements de classe sur html
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        updateFavicon();
                    }
                });
            });
            
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Intercepter les fonctions de toggle du thème
            if (typeof window.toggleTheme === 'function') {
                const originalToggleTheme = window.toggleTheme;
                window.toggleTheme = function() {
                    originalToggleTheme();
                    setTimeout(updateFavicon, 100);
                };
            }
            
            if (typeof window.applyTheme === 'function') {
                const originalApplyTheme = window.applyTheme;
                window.applyTheme = function() {
                    originalApplyTheme();
                    setTimeout(updateFavicon, 100);
                };
            }
        })();
    </script>
<?php elseif($logoLight): ?>
    <!-- Favicon mode clair uniquement -->
    <link rel="icon" type="image/png" href="<?php echo e($logoLight); ?>" id="site-favicon">
<?php elseif($logoDark): ?>
    <!-- Favicon mode sombre uniquement -->
    <link rel="icon" type="image/png" href="<?php echo e($logoDark); ?>" id="site-favicon">
<?php else: ?>
    <!-- Fallback vers le favicon statique si aucun logo n'est configuré -->
    <link rel="icon" type="image/x-icon" href="<?php echo e($faviconFallback); ?>">
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/partials/favicon.blade.php ENDPATH**/ ?>