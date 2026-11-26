const DB_NAME = 'sileuit_offline_v1';
const DB_VERSION = 1;
const STORE_OUTBOX = 'outbox';
const STORE_MASTER = 'master_cache';

function openDb() {
    return new Promise((resolve, reject) => {
        if (!('indexedDB' in window)) {
            reject(new Error('Browser ini tidak mendukung IndexedDB.'));
            return;
        }

        const req = window.indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_OUTBOX)) {
                const store = db.createObjectStore(STORE_OUTBOX, { keyPath: 'idempotency_key' });
                store.createIndex('created_at', 'created_at', { unique: false });
                store.createIndex('status', 'status', { unique: false });
            }
            if (!db.objectStoreNames.contains(STORE_MASTER)) {
                db.createObjectStore(STORE_MASTER, { keyPath: 'key' });
            }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error || new Error('Gagal membuka IndexedDB.'));
    });
}

function txDone(tx) {
    return new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
    });
}

function reqPromise(req) {
    return new Promise((resolve, reject) => {
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

function notifyUpdated() {
    window.dispatchEvent(new CustomEvent('sileuit-offline-updated'));
}

function generateUuid() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }
    return 'off-' + Date.now() + '-' + Math.random().toString(16).slice(2, 10);
}

async function saveMasterCache(payload) {
    const db = await openDb();
    const tx = db.transaction(STORE_MASTER, 'readwrite');
    tx.objectStore(STORE_MASTER).put({
        key: 'receiving_master',
        ...payload,
        saved_at: new Date().toISOString(),
    });
    await txDone(tx);
    db.close();
}

async function loadMasterCache() {
    const db = await openDb();
    const tx = db.transaction(STORE_MASTER, 'readonly');
    const result = await reqPromise(tx.objectStore(STORE_MASTER).get('receiving_master'));
    db.close();
    return result || null;
}

async function listOutbox() {
    const db = await openDb();
    const tx = db.transaction(STORE_OUTBOX, 'readonly');
    const all = await reqPromise(tx.objectStore(STORE_OUTBOX).getAll());
    db.close();
    return (all || []).sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
}

async function getOutboxItem(key) {
    const db = await openDb();
    const tx = db.transaction(STORE_OUTBOX, 'readonly');
    const item = await reqPromise(tx.objectStore(STORE_OUTBOX).get(key));
    db.close();
    return item || null;
}

async function enqueueReceipt(entry) {
    const record = {
        ...entry,
        idempotency_key: entry.idempotency_key || generateUuid(),
        created_at: new Date().toISOString(),
        status: 'QUEUED',
        sync_result: null,
        duplikat_teruji: false,
    };

    const db = await openDb();
    const tx = db.transaction(STORE_OUTBOX, 'readwrite');
    tx.objectStore(STORE_OUTBOX).put(record);
    await txDone(tx);
    db.close();
    notifyUpdated();
    return record;
}

async function updateOutbox(key, patch) {
    const db = await openDb();
    const tx = db.transaction(STORE_OUTBOX, 'readwrite');
    const store = tx.objectStore(STORE_OUTBOX);
    const existing = await reqPromise(store.get(key));
    if (existing) {
        store.put({ ...existing, ...patch });
    }
    await txDone(tx);
    db.close();
    notifyUpdated();
}

async function removeOutbox(key) {
    const db = await openDb();
    const tx = db.transaction(STORE_OUTBOX, 'readwrite');
    tx.objectStore(STORE_OUTBOX).delete(key);
    await txDone(tx);
    db.close();
    notifyUpdated();
}

async function clearSynced() {
    const items = await listOutbox();
    const syncedKeys = items.filter((item) => item.status === 'SYNCED').map((item) => item.idempotency_key);
    if (syncedKeys.length === 0) return;

    const db = await openDb();
    const tx = db.transaction(STORE_OUTBOX, 'readwrite');
    const store = tx.objectStore(STORE_OUTBOX);
    syncedKeys.forEach((k) => store.delete(k));
    await txDone(tx);
    db.close();
    notifyUpdated();
}

function compressPhotoToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onerror = () => reject(new Error('Gagal membaca berkas foto.'));
        reader.onload = () => {
            const img = new Image();
            img.onerror = () => {
                const raw = String(reader.result || '');
                resolve(raw.includes(',') ? raw.split(',')[1] : raw);
            };
            img.onload = () => {
                const maxWidth = 1200;
                const scale = img.width > maxWidth ? maxWidth / img.width : 1;
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(img.width * scale));
                canvas.height = Math.max(1, Math.round(img.height * scale));
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    const raw = String(reader.result || '');
                    resolve(raw.includes(',') ? raw.split(',')[1] : raw);
                    return;
                }
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.78);
                resolve(dataUrl.split(',')[1]);
            };
            img.src = String(reader.result || '');
        };
        reader.readAsDataURL(file);
    });
}

function createSampleReceiptBase64() {
    const canvas = document.createElement('canvas');
    canvas.width = 480;
    canvas.height = 280;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }
    ctx.fillStyle = '#FBF3D5';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#2F5A27';
    ctx.lineWidth = 4;
    ctx.strokeRect(14, 14, canvas.width - 28, canvas.height - 28);
    ctx.fillStyle = '#2F5A27';
    ctx.font = 'bold 20px sans-serif';
    ctx.fillText('SiLeuit.id — Foto Sampel Offline', 36, 72);
    ctx.fillStyle = '#1F2A1C';
    ctx.font = '15px monospace';
    ctx.fillText('Waktu: ' + new Date().toLocaleString('id-ID'), 36, 125);
    ctx.fillText('Pos Timbang Mode Offline (IndexedDB)', 36, 158);
    ctx.fillText('Status: Bukti Lokal Terkompresi', 36, 192);
    return canvas.toDataURL('image/jpeg', 0.85).split(',')[1];
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function syncOne(key) {
    const item = await getOutboxItem(key);
    if (!item) {
        return { ok: false, message: 'Data antrean tidak ditemukan.' };
    }

    const payload = {
        idempotency_key: item.idempotency_key,
        contract_id: Number(item.contract_id),
        location_id: Number(item.location_id),
        harvest_plan_id: item.harvest_plan_id ? Number(item.harvest_plan_id) : null,
        bruto_kg: Number(item.bruto_kg),
        tara_kg: Number(item.tara_kg),
        kadar_air: Number(item.kadar_air),
        kadar_hampa: Number(item.kadar_hampa),
        butir_patah: item.butir_patah !== '' && item.butir_patah !== null ? Number(item.butir_patah) : null,
        varietas: item.varietas,
        selisih_alasan: item.selisih_alasan || null,
        keputusan: item.keputusan || 'TERIMA',
        alasan: item.alasan || null,
        foto_base64: item.foto_base64,
    };

    try {
        const res = await fetch('/api/v1/procurement/sync', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            await updateOutbox(key, {
                status: 'SYNCED',
                sync_result: data,
                synced_at: new Date().toISOString(),
                duplikat_teruji: Boolean(data.duplikat) || Boolean(item.duplikat_teruji),
            });
            return { ok: true, status: res.status, data };
        }

        const errMessage =
            res.status === 401 || res.status === 419
                ? 'Sesi login atau CSRF kedaluwarsa. Muat ulang halaman lalu sinkronkan kembali.'
                : data.message || 'Gagal sinkronisasi ke server (HTTP ' + res.status + ').';

        await updateOutbox(key, {
            status: 'ERROR',
            sync_result: { message: errMessage },
        });
        return { ok: false, status: res.status, message: errMessage };
    } catch (err) {
        return { ok: false, status: 0, message: 'Masih offline atau server belum terjangkau.' };
    }
}

let isSyncingBatch = false;

async function syncAllPending() {
    if (isSyncingBatch || !navigator.onLine) {
        return { synced: 0, failed: 0 };
    }

    isSyncingBatch = true;
    let synced = 0;
    let failed = 0;

    try {
        const items = await listOutbox();
        const pending = items.filter((item) => item.status === 'QUEUED' || item.status === 'ERROR');
        for (const item of pending) {
            const result = await syncOne(item.idempotency_key);
            if (result.ok) {
                synced += 1;
            } else {
                failed += 1;
            }
        }
    } finally {
        isSyncingBatch = false;
        notifyUpdated();
    }

    return { synced, failed };
}

window.SileuitOffline = {
    DB_NAME,
    saveMasterCache,
    loadMasterCache,
    listOutbox,
    enqueueReceipt,
    updateOutbox,
    removeOutbox,
    clearSynced,
    compressPhotoToBase64,
    createSampleReceiptBase64,
    syncOne,
    syncAllPending,
    generateUuid,
};

window.sileuitOfflineWizard = function (initialMaster) {
    return {
        isOnline: navigator.onLine,
        simulateOffline: sessionStorage.getItem('sileuit_sim_offline') === '1',
        master: initialMaster || { location_id: null, location_nama: '', contracts: [], grades: [] },
        outbox: [],
        busy: false,
        feedback: null,
        errorMsg: null,
        form: {
            contract_id: '',
            harvest_plan_id: '',
            bruto_kg: '',
            tara_kg: '',
            selisih_alasan: '',
            kadar_air: '',
            kadar_hampa: '',
            butir_patah: '',
            varietas: 'Ciherang',
            keputusan: 'KARANTINA',
            alasan: '',
            foto_base64: '',
            foto_label: '',
        },

        get isOfflineMode() {
            return !this.isOnline || this.simulateOffline;
        },

        get selectedContract() {
            return (this.master.contracts || []).find((c) => String(c.id) === String(this.form.contract_id)) || null;
        },

        get availablePlans() {
            return this.selectedContract ? this.selectedContract.plans || [] : [];
        },

        get nettoKg() {
            const b = parseFloat(String(this.form.bruto_kg).replace(',', '.')) || 0;
            const t = parseFloat(String(this.form.tara_kg).replace(',', '.')) || 0;
            return Math.max(0, Math.round((b - t) * 1000) / 1000);
        },

        get plannedKg() {
            const p = this.availablePlans.find((item) => String(item.id) === String(this.form.harvest_plan_id));
            return p ? Number(p.kg_perkiraan) : null;
        },

        get selisihPersen() {
            if (!this.plannedKg || this.plannedKg <= 0 || this.nettoKg <= 0) return null;
            return Math.round(((this.nettoKg - this.plannedKg) / this.plannedKg) * 10000) / 100;
        },

        get needsDiscrepancyReason() {
            return this.selisihPersen !== null && Math.abs(this.selisihPersen) > 1.0;
        },

        get estimatedGrade() {
            const ka = parseFloat(String(this.form.kadar_air).replace(',', '.'));
            if (Number.isNaN(ka)) return null;
            const sorted = [...(this.master.grades || [])].sort((a, b) => a.kadar_air_max - b.kadar_air_max);
            return sorted.find((g) => ka <= g.kadar_air_max) || null;
        },

        get needsManualDecision() {
            const ka = parseFloat(String(this.form.kadar_air).replace(',', '.'));
            return !Number.isNaN(ka) && !this.estimatedGrade;
        },

        get pendingCount() {
            return this.outbox.filter((item) => item.status === 'QUEUED' || item.status === 'ERROR').length;
        },

        async init() {
            if (initialMaster && Array.isArray(initialMaster.contracts)) {
                await saveMasterCache(initialMaster).catch(() => {});
            } else {
                const cached = await loadMasterCache().catch(() => null);
                if (cached) {
                    this.master = cached;
                }
            }
            await this.refreshOutbox();

            window.addEventListener('sileuit-offline-updated', () => this.refreshOutbox());
            window.addEventListener('sileuit-network-changed', (e) => {
                this.isOnline = Boolean(e.detail?.online);
            });
            window.addEventListener('online', () => {
                this.isOnline = true;
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });
        },

        async refreshOutbox() {
            this.outbox = await listOutbox().catch(() => []);
        },

        toggleSimulateOffline() {
            this.simulateOffline = !this.simulateOffline;
            sessionStorage.setItem('sileuit_sim_offline', this.simulateOffline ? '1' : '0');
            this.feedback = null;
            this.errorMsg = null;
        },

        async handlePhotoChange(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.errorMsg = null;
            try {
                this.form.foto_base64 = await compressPhotoToBase64(file);
                this.form.foto_label = file.name + ' (terkompresi lokal)';
            } catch (e) {
                this.errorMsg = e.message || 'Gagal memproses foto.';
            }
        },

        useSamplePhoto() {
            this.form.foto_base64 = createSampleReceiptBase64();
            this.form.foto_label = 'Sampel Timbangan Lokal (JPEG)';
        },

        async saveOfflineEntry() {
            this.feedback = null;
            this.errorMsg = null;

            const bruto = parseFloat(String(this.form.bruto_kg).replace(',', '.'));
            const tara = parseFloat(String(this.form.tara_kg).replace(',', '.'));
            const ka = parseFloat(String(this.form.kadar_air).replace(',', '.'));
            const kh = parseFloat(String(this.form.kadar_hampa).replace(',', '.'));
            const bpRaw = String(this.form.butir_patah || '').trim();
            const bp = bpRaw !== '' ? parseFloat(bpRaw.replace(',', '.')) : '';

            if (!this.form.contract_id) {
                this.errorMsg = 'Pilih kontrak petani terlebih dahulu.';
                return;
            }
            if (!(bruto > 0) || Number.isNaN(tara) || tara < 0 || bruto <= tara) {
                this.errorMsg = 'Bruto harus lebih besar dari Tara dan bernilai positif.';
                return;
            }
            if (this.needsDiscrepancyReason && !String(this.form.selisih_alasan || '').trim()) {
                this.errorMsg = 'Deviasi melebihi ±1%. Isi alasan selisih terlebih dahulu.';
                return;
            }
            if (Number.isNaN(ka) || ka < 0 || ka > 100 || Number.isNaN(kh) || kh < 0 || kh > 100) {
                this.errorMsg = 'Kadar air dan kadar hampa wajib diisi (0–100%).';
                return;
            }
            if (!String(this.form.varietas || '').trim()) {
                this.errorMsg = 'Varietas gabah wajib diisi.';
                return;
            }
            if (this.needsManualDecision && (!this.form.keputusan || !String(this.form.alasan || '').trim())) {
                this.errorMsg = 'Mutu di bawah standar minimum: pilih keputusan KARANTINA/TOLAK dan tulis alasannya.';
                return;
            }
            if (!this.form.foto_base64) {
                this.errorMsg = 'Foto bukti timbang wajib dipilih (atau klik "Pakai Foto Sampel Uji").';
                return;
            }

            const record = await enqueueReceipt({
                contract_id: Number(this.form.contract_id),
                harvest_plan_id: this.form.harvest_plan_id ? Number(this.form.harvest_plan_id) : null,
                location_id: Number(this.master.location_id),
                farmer_label: this.selectedContract?.label || ('Kontrak #' + this.form.contract_id),
                bruto_kg: bruto,
                tara_kg: tara,
                netto_kg: this.nettoKg,
                selisih_alasan: this.needsDiscrepancyReason ? this.form.selisih_alasan.trim() : null,
                kadar_air: ka,
                kadar_hampa: kh,
                butir_patah: bp,
                varietas: this.form.varietas.trim(),
                keputusan: this.needsManualDecision ? this.form.keputusan : 'TERIMA',
                alasan: this.needsManualDecision ? this.form.alasan.trim() : null,
                foto_base64: this.form.foto_base64,
            });

            this.feedback = 'Tersimpan lokal ke IndexedDB dengan Idempotency Key: ' + record.idempotency_key;
            this.form = {
                contract_id: '',
                harvest_plan_id: '',
                bruto_kg: '',
                tara_kg: '',
                selisih_alasan: '',
                kadar_air: '',
                kadar_hampa: '',
                butir_patah: '',
                varietas: 'Ciherang',
                keputusan: 'KARANTINA',
                alasan: '',
                foto_base64: '',
                foto_label: '',
            };
        },

        async syncSingle(key) {
            if (this.isOfflineMode) {
                this.errorMsg = 'Aktifkan koneksi internet (dan matikan simulasi offline) untuk menyinkronkan ke server.';
                return;
            }
            this.busy = true;
            this.feedback = null;
            this.errorMsg = null;
            const res = await syncOne(key);
            this.busy = false;
            if (res.ok) {
                this.feedback = res.data.duplikat
                    ? `Uji Idempotency (TC-14) Sukses: Server menolak duplikasi untuk ${res.data.no_dok} (duplikat: true).`
                    : `Berhasil disinkronkan! Terbit dokumen ${res.data.no_dok} (status: ${res.data.status}, duplikat: false).`;
            } else {
                this.errorMsg = res.message;
            }
            await this.refreshOutbox();
        },

        async syncAll() {
            if (this.isOfflineMode) {
                this.errorMsg = 'Masih dalam mode offline. Nyalakan koneksi internet untuk menyinkronkan.';
                return;
            }
            this.busy = true;
            this.feedback = null;
            this.errorMsg = null;
            const summary = await syncAllPending();
            this.busy = false;
            await this.refreshOutbox();
            this.feedback = `Sinkronisasi selesai: ${summary.synced} berhasil, ${summary.failed} gagal.`;
        },

        async deleteItem(key) {
            await removeOutbox(key);
        },

        async cleanSynced() {
            await clearSynced();
        },
    };
};

window.sileuitOfflineOutboxWidget = function () {
    return {
        isOnline: navigator.onLine,
        outbox: [],
        busy: false,
        note: null,

        get pendingCount() {
            return this.outbox.filter((item) => item.status === 'QUEUED' || item.status === 'ERROR').length;
        },

        async init() {
            await this.refresh();
            window.addEventListener('sileuit-offline-updated', () => this.refresh());
            window.addEventListener('online', () => {
                this.isOnline = true;
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });
        },

        async refresh() {
            this.outbox = await listOutbox().catch(() => []);
        },

        async syncAllAndReload() {
            this.busy = true;
            const summary = await syncAllPending();
            await this.refresh();
            this.busy = false;
            if (summary.synced > 0) {
                window.location.reload();
            } else {
                this.note = 'Tidak ada data antrean baru yang tersinkron.';
            }
        },
    };
};

window.addEventListener('online', () => {
    window.dispatchEvent(new CustomEvent('sileuit-network-changed', { detail: { online: true } }));
    if (sessionStorage.getItem('sileuit_sim_offline') !== '1') {
        syncAllPending();
    }
});

window.addEventListener('offline', () => {
    window.dispatchEvent(new CustomEvent('sileuit-network-changed', { detail: { online: false } }));
});
