const CACHE = 'sileuit-v3';
const PRECACHE_URLS = ['/manifest.json', '/vendor/aos/aos.css', '/vendor/aos/aos.js'];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .catch(() => {})
            .finally(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    const isHtml = req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html');

    if (isHtml) {
        // Halaman HTML selalu ambil dari server bila online; saat offline jatuh ke salinan cache terakhir.
        e.respondWith(
            fetch(req)
                .then((res) => {
                    if (res.ok && (url.pathname.startsWith('/app/') || url.pathname === '/')) {
                        const clone = res.clone();
                        caches.open(CACHE).then((c) => c.put(req, clone)).catch(() => {});
                    }
                    return res;
                })
                .catch(() => caches.match(req, { ignoreSearch: true }))
        );
        return;
    }

    // Aset statis (css/js/font/gambar): cache-first dengan pembaruan latar belakang.
    e.respondWith(
        caches.match(req).then((cached) => {
            if (cached) {
                return cached;
            }
            return fetch(req).then((res) => {
                if (res.ok && res.type === 'basic') {
                    const clone = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, clone)).catch(() => {});
                }
                return res;
            });
        })
    );
});
