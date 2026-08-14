<script>
    window.deferredPrompt = window.deferredPrompt || null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.deferredPrompt = e;
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    }
    window.installPwa = async function () {
        if (window.deferredPrompt) {
            window.deferredPrompt.prompt();
            await window.deferredPrompt.userChoice;
            window.deferredPrompt = null;
            return;
        }
        if (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream) {
            alert("Pour installer l'application sur iOS :\n1. Appuyez sur Partager\n2. Choisissez « Sur l'écran d'accueil »");
            return;
        }
        alert("Ouvrez le menu du navigateur et choisissez « Installer l'application ».");
    };
</script>
