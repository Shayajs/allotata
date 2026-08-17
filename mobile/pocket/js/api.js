import { API_BASE } from './config.js';
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
    const error = new Error(data.message || `Erreur ${status}`);
    error.code = data.code;
    error.status = status;
    error.data = data;
    return error;
}

export async function api(path, options = {}) {
    const token = await getToken();
    const response = await fetch(`${API_BASE}${path}`, { ...options, headers: headers(token, options) });
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

export async function publicPost(path, body) {
    const response = await fetch(`${API_BASE}${path}`, {
        method: 'POST',
        headers: headers(null, { body: '{}' }),
        body: JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw asError(data, response.status);
    }
    return data;
}
