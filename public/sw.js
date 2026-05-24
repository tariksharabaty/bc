const CACHE_NAME = 'kumbara-cache-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/manifest.json',
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event - Stale-While-Revalidate
self.addEventListener('fetch', event => {
  const requestUrl = new URL(event.request.url);

  // Cache static assets (CSS, JS, Fonts, Images)
  if (
    event.request.method === 'GET' &&
    (
      requestUrl.origin === self.location.origin || 
      event.request.url.includes('fonts.googleapis.com') || 
      event.request.url.includes('fonts.gstatic.com')
    ) &&
    (
      event.request.url.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/) ||
      event.request.url.includes('manifest.json')
    )
  ) {
    event.respondWith(
      caches.open(CACHE_NAME).then(cache => {
        return cache.match(event.request).then(cachedResponse => {
          const fetchPromise = fetch(event.request).then(networkResponse => {
            if (networkResponse && networkResponse.status === 200) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          }).catch(() => {
            // Fail silently if offline and fetch fails
          });

          return cachedResponse || fetchPromise;
        });
      })
    );
  }
});
