import './bootstrap';

// ========================================
// Gestion du thème clair/foncé avec cookies
// ========================================

// Fonction pour lire un cookie
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Fonction pour définir un cookie (expire dans 1 an)
function setCookie(name, value) {
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `${name}=${value}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
}

// Fonction pour appliquer le thème
function applyTheme() {
    const savedTheme = getCookie('theme');
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (savedTheme === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        // Mode auto : suivre les paramètres du système
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}

// Fonction pour basculer le thème
function toggleTheme() {
    const html = document.documentElement;
    html.classList.toggle('dark');
    
    // Sauvegarder la préférence dans un cookie
    const theme = html.classList.contains('dark') ? 'dark' : 'light';
    setCookie('theme', theme);
}

// Exposer les fonctions globalement
window.toggleTheme = toggleTheme;
window.applyTheme = applyTheme;

// Attacher les événements aux boutons de thème après le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de thème (par classe ou par id)
    const themeButtons = document.querySelectorAll('.theme-toggle-btn, #theme-toggle');
    
    themeButtons.forEach(function(button) {
        button.addEventListener('click', toggleTheme);
    });
});

// Écouter les changements de préférence système (pour le mode auto)
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
    // Ne réagir que si aucune préférence n'est sauvegardée (mode auto)
    if (!getCookie('theme')) {
        if (e.matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
});
