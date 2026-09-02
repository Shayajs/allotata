const KEY = 'pocket_host';

export const HOSTS = {
    local: {
        id: 'local',
        label: 'Local',
        hint: 'allotata.test',
        api: 'https://api.allotata.test/v1',
        dash: 'https://dash.allotata.test',
        settings: 'https://dash.allotata.test/settings',
        checkout: 'https://dash.allotata.test/checkout',
        site: 'https://allotata.test',
        cgu: 'https://allotata.test/legal/cgu',
        cgv: 'https://allotata.test/legal/cgv',
        confidentialite: 'https://allotata.test/legal/confidentialite',
    },
    prod: {
        id: 'prod',
        label: 'Production',
        hint: 'allotata.fr',
        api: 'https://api.allotata.fr/v1',
        dash: 'https://dash.allotata.fr',
        settings: 'https://dash.allotata.fr/settings',
        checkout: 'https://dash.allotata.fr/checkout',
        site: 'https://allotata.fr',
        cgu: 'https://allotata.fr/legal/cgu',
        cgv: 'https://allotata.fr/legal/cgv',
        confidentialite: 'https://allotata.fr/legal/confidentialite',
    },
};

const BUILD_DEFAULT = import.meta.env.VITE_POCKET_ENV === 'prod' ? 'prod' : 'local';

let cached = HOSTS[BUILD_DEFAULT];

export function currentHost() {
    return cached;
}

export async function getHosts() {
    let id = BUILD_DEFAULT;
    try {
        const Preferences = window.Capacitor?.Plugins?.Preferences;
        const stored = Preferences?.get
            ? (await Preferences.get({ key: KEY })).value
            : localStorage.getItem(KEY);
        if (stored && HOSTS[stored]) {
            id = stored;
        }
    } catch {
        const stored = localStorage.getItem(KEY);
        if (stored && HOSTS[stored]) {
            id = stored;
        }
    }
    cached = HOSTS[id];
    return cached;
}

export async function setHost(id) {
    if (!HOSTS[id]) {
        return cached;
    }
    cached = HOSTS[id];
    localStorage.setItem(KEY, id);
    try {
        await window.Capacitor?.Plugins?.Preferences?.set?.({ key: KEY, value: id });
    } catch {
        // ignore
    }
    return cached;
}

export function nextHostId(id) {
    return id === 'prod' ? 'local' : 'prod';
}

export function hostChipHtml() {
    const host = currentHost();
    return `<button type="button" class="auth-host" id="auth-host" data-id="${host.id}">
        <span class="auth-host-dot ${host.id}"></span>
        ${host.label} · ${host.hint}
    </button>`;
}

export function bindHostChip(redraw) {
    document.getElementById('auth-host')?.addEventListener('click', async () => {
        await setHost(nextHostId(currentHost().id));
        redraw();
    });
}
