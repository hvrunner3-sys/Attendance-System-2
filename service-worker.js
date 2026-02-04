/*
 * SERVICE WORKER
 * Offline support and app caching for PWA
 */

const CACHE_NAME = 'attendance-app-v1';
const ASSETS_TO_CACHE = [
    './',
    './index.php',
    './style.css',
    './app.js',
    './manifest.json'
];

// Install event - cache assets
self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('Caching assets');
            // Note: PHP files may not cache well, but we cache static assets
            return cache.addAll(
                ASSETS_TO_CACHE.filter(url => !url.includes('.php'))
            ).catch(err => {
                console.log('Cache error (expected for PHP files):', err);
            });
        })
    );

    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== CACHE_NAME)
                    .map((cacheName) => {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        })
    );

    self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // Skip POST requests (API calls) - always go to network
    if (request.method !== 'GET') {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response('Network error', { status: 503 });
            })
        );
        return;
    }

    // For GET requests, try cache first, then network
    event.respondWith(
        caches.match(request).then((response) => {
            if (response) {
                console.log('Serving from cache:', request.url);
                return response;
            }

            console.log('Fetching from network:', request.url);
            return fetch(request).then((response) => {
                // Cache successful responses
                if (response.status === 200) {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return response;
            }).catch(() => {
                // Return offline page if network fails
                return new Response(
                    'Offline - Please check your internet connection',
                    { status: 503 }
                );
            });
        })
    );
});

// Handle messages from client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
