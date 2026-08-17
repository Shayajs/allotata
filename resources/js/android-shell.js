function isCapacitorShell() {
    return Boolean(window.Capacitor);
}

function isAllotataHost(hostname) {
    return hostname === 'allotata.fr'
        || hostname.endsWith('.allotata.fr')
        || hostname === window.location.hostname;
}

function closeAndroidSheets() {
    document.querySelectorAll('[id^="android-more-sheet-"]').forEach((sheet) => {
        sheet.classList.add('translate-y-full');
    });
    document.querySelectorAll('[id^="android-more-overlay-"]').forEach((overlay) => {
        overlay.classList.add('hidden');
    });
}

function androidSheetIsOpen() {
    return [...document.querySelectorAll('[id^="android-more-sheet-"]')]
        .some((sheet) => !sheet.classList.contains('translate-y-full'));
}

function syncAndroidNav(tabName) {
    document.querySelectorAll('.android-tab-btn[data-tab]').forEach((btn) => {
        const active = btn.dataset.tab === tabName;
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('text-slate-400', !active);
        const pill = btn.querySelector('span');
        if (pill) {
            pill.classList.toggle('bg-green-500/25', active);
        }
        const svg = btn.querySelector('svg');
        if (svg) {
            svg.setAttribute('stroke-width', active ? '2.4' : '1.7');
        }
    });
}

function wrapShowTab() {
    const original = window.showTab;
    if (typeof original !== 'function' || original.__androidWrapped) {
        return;
    }

    const wrapped = function showTab(tabName) {
        if (tabName === 'installer') {
            tabName = 'accueil';
        }
        original(tabName);
        syncAndroidNav(tabName);
    };
    wrapped.__androidWrapped = true;
    window.showTab = wrapped;
}

function isAppRootPath() {
    const path = (window.location.pathname.replace(/\/+$/, '') || '/').toLowerCase();
    return path === '/'
        || path === '/signin'
        || path === '/signup'
        || path === '/login'
        || path === '/dashboard';
}

function bindBackButton() {
    const App = window.Capacitor?.Plugins?.App;
    if (!App?.addListener) {
        return;
    }

    App.addListener('backButton', ({ canGoBack }) => {
        if (androidSheetIsOpen()) {
            closeAndroidSheets();
            return;
        }

        if (isAppRootPath()) {
            App.exitApp?.();
            return;
        }

        if (canGoBack !== false && window.history.length > 1) {
            window.history.back();
            return;
        }

        App.exitApp?.();
    });
}

function bindExternalLinks() {
    const Browser = window.Capacitor?.Plugins?.Browser;
    if (!Browser?.open) {
        return;
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[href]');
        if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey) {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        let url;
        try {
            url = new URL(href, window.location.href);
        } catch {
            return;
        }

        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
            return;
        }

        const external = !isAllotataHost(url.hostname);
        if (!external && link.target !== '_blank') {
            return;
        }

        if (!external) {
            return;
        }

        event.preventDefault();
        Browser.open({ url: url.href });
    });
}

function leaveInstallerTab() {
    const params = new URLSearchParams(window.location.search);
    const installerOpen = params.get('tab') === 'installer'
        || document.getElementById('tab-installer')?.classList.contains('hidden') === false;

    if (!installerOpen) {
        return;
    }

    if (typeof window.showTab === 'function') {
        window.showTab('accueil');
    }
}

export function initAndroidShell() {
    if (!isCapacitorShell()) {
        return;
    }

    document.documentElement.classList.add('is-capacitor');
    wrapShowTab();
    leaveInstallerTab();
    bindBackButton();
    bindExternalLinks();
}

document.addEventListener('DOMContentLoaded', initAndroidShell);
