const DB_NAME = 'allotata-pocket';
const VERSION = 2;
const STORES = ['meta', 'reservations', 'clients', 'conversations', 'messages', 'factures', 'outbox', 'pdfs'];

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, VERSION);
        req.onupgradeneeded = () => {
            const db = req.result;
            STORES.forEach((name) => {
                if (!db.objectStoreNames.contains(name)) {
                    db.createObjectStore(name, { keyPath: 'id' });
                }
            });
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

function complete(tx) {
    return new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
    });
}

function request(req) {
    return new Promise((resolve, reject) => {
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

export async function putAll(name, rows) {
    const db = await openDb();
    const tx = db.transaction(name, 'readwrite');
    const st = tx.objectStore(name);
    st.clear();
    (rows || []).forEach((row) => st.put(row));
    return complete(tx);
}

export async function putMany(name, rows) {
    const db = await openDb();
    const tx = db.transaction(name, 'readwrite');
    const st = tx.objectStore(name);
    (rows || []).forEach((row) => st.put(row));
    return complete(tx);
}

export async function put(name, row) {
    const db = await openDb();
    const tx = db.transaction(name, 'readwrite');
    tx.objectStore(name).put(row);
    return complete(tx);
}

export async function get(name, id) {
    const db = await openDb();
    const tx = db.transaction(name, 'readonly');
    return (await request(tx.objectStore(name).get(id))) || null;
}

export async function getAll(name) {
    const db = await openDb();
    const tx = db.transaction(name, 'readonly');
    return (await request(tx.objectStore(name).getAll())) || [];
}

export async function remove(name, id) {
    const db = await openDb();
    const tx = db.transaction(name, 'readwrite');
    tx.objectStore(name).delete(id);
    return complete(tx);
}

export async function meta(key, value) {
    if (value === undefined) {
        const row = await get('meta', key);
        return row?.value ?? null;
    }
    await put('meta', { id: key, value });
    return value;
}
