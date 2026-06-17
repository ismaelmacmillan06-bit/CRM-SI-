const CACHE = 'macmillansi-v1';

// Cache static assets only (CSS, JS, fonts, images)
const STATIC_REGEX = /\.(css|js|woff2?|ttf|otf|png|jpg|jpeg|svg|ico|webp)(\?.*)?$/;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', e => e.waitUntil(clients.claim()));

self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;
    if (!e.request.url.startsWith(self.location.origin)) return;

    e.respondWith(
        fetch(e.request)
            .then(response => {
                if (response.ok && STATIC_REGEX.test(e.request.url)) {
                    const clone = response.clone();
                    caches.open(CACHE).then(cache => cache.put(e.request, clone));
                }
                return response;
            })
            .catch(() => caches.match(e.request))
    );
});
