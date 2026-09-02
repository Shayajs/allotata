import { currentHost, getHosts } from './config.js';
import { getToken } from './auth.js';

function headers(token, options = {}) {
    return {
        Accept: 'application/json',
        'X-Capacitor': '1',
        'X-AlloTata-Native': '1',
        ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(options.headers || {}),
    };
}

function asError(data, status) {
    const first = data.errors && Object.values(data.errors).flat().find(Boolean);
    const error = new Error(first || data.message || `Erreur ${status}`);
    error.code = data.code;
    error.status = status;
    error.data = data;
    return error;
}

function reseau(error) {
    if (! (error instanceof TypeError) && ! /failed to fetch|networkerror|load failed/i.test(error.message || '')) {
        return error;
    }
    const host = currentHost();
    const wrapped = new Error(host.id === 'prod'
        ? 'Production injoignable depuis ici. Passe en local pour tester.'
        : 'Le serveur local ne répond pas (api.allotata.test).');
    wrapped.code = 'reseau';
    wrapped.cause = error;
    return wrapped;
}

export async function api(path, options = {}) {
    const token = await getToken();
    const host = await getHosts();
    let response;
    try {
        response = await fetch(`${host.api}${path}`, { ...options, headers: headers(token, options) });
    } catch (error) {
        throw reseau(error);
    }
    if (response.status === 401) {
        throw new Error('jeton_invalide');
    }
    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw asError(data, response.status);
    }
    if ((response.headers.get('content-type') || '').includes('pdf')) {
        return response.blob();
    }
    return response.json();
}

export function post(path, body) {
    return api(path, { method: 'POST', body: JSON.stringify(body) });
}

export async function publicGet(path) {
    const host = await getHosts();
    let response;
    try {
        response = await fetch(`${host.api}${path}`, { headers: headers(null) });
    } catch (error) {
        throw reseau(error);
    }
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw asError(data, response.status);
    }
    return data;
}

export async function publicPost(path, body) {
    const host = await getHosts();
    let response;
    try {
        response = await fetch(`${host.api}${path}`, {
            method: 'POST',
            headers: headers(null, { body: '{}' }),
            body: JSON.stringify(body),
        });
    } catch (error) {
        throw reseau(error);
    }
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw asError(data, response.status);
    }
    return data;
}
