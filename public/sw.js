const CACHE_NAME = 'pios-cache-v2';
const ASSETS_TO_CACHE = [
    '/',
    '/market',
    '/manifest.json',
    '/favicon.ico',
    '/favicon-192x192.png',
    '/favicon-512x512.png',
    '/apple-touch-icon.png',
    '/brand/pak-itam-logo.png',
    '/brand/pios-wordmark.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            await Promise.allSettled(
                ASSETS_TO_CACHE.map((url) => cache.add(url).catch((err) => {
                    console.warn('PWA Precache skipped:', url, err);
                }))
            );
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);
    // Don't intercept livewire or auth logout
    if (url.pathname.startsWith('/livewire/') || url.pathname.startsWith('/logout')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // If live fetch works, cache the copy dynamically
                if (response && response.status === 200 && response.type === 'basic') {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return response;
            })
            .catch(() => {
                // Fallback to cache if network fails
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html')) {
                        return caches.match('/').then((homeMatch) => homeMatch || caches.match('/market'));
                    }
                });
            })
    );
});
