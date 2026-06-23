const CACHE_NAME = 'dreamland-build-178230';

const CORE_ASSETS = [
  '/',
  '/index.html',
  '/css/app.css',
  '/css/onboarding.css',
  '/js/config.js',
  '/js/app.js',
  '/js/dreamland-features.js',
  '/js/dreamland-live.js',
  '/js/dreamland-ai.js',
  '/js/dreamland-social.js',
  '/js/dreamland-profile.js',
  '/js/dreamland-search.js',
  '/js/dreamland-account.js',
  '/manifest.json',
  '/assets/logo.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/apple-touch-icon.png',
  '/icons/icon-512-maskable.png',
  '/assets/community-network.svg',
  '/build-version.json',
];

const NETWORK_FIRST_PATHS = ['/env-config.js', '/build-version.json', '/sw.js'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      Promise.allSettled(CORE_ASSETS.map((asset) => cache.add(asset)))
    ).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

function isMutableAsset(pathname) {
  return pathname === '/'
    || pathname.endsWith('.html')
    || pathname.includes('/js/')
    || pathname.includes('/css/')
    || NETWORK_FIRST_PATHS.some((p) => pathname === p || pathname.endsWith(p));
}

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);
  if (url.pathname.startsWith('/v1') || url.pathname.includes('/api/')) return;

  if (NETWORK_FIRST_PATHS.some((path) => url.pathname === path || url.pathname.endsWith(path))) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  if (isMutableAsset(url.pathname)) {
    event.respondWith(
      caches.open(CACHE_NAME).then(async (cache) => {
        const cached = await cache.match(event.request);
        const networkFetch = fetch(event.request)
          .then((response) => {
            if (response.ok) cache.put(event.request, response.clone());
            return response;
          })
          .catch(() => null);
        if (cached) {
          networkFetch.catch(() => {});
          return cached;
        }
        const response = await networkFetch;
        if (response) return response;
        return new Response('Offline', { status: 503, statusText: 'Offline' });
      })
    );
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cached) => {
      if (cached) return cached;
      return fetch(event.request).then((response) => {
        if (!response.ok) return response;
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        return response;
      });
    })
  );
});

self.addEventListener('push', (event) => {
  let data = { title: 'Dreamland', body: 'New activity on your feed.', url: '/' };
  try {
    data = event.data ? { ...data, ...event.data.json() } : data;
  } catch {
    data.body = event.data ? event.data.text() : data.body;
  }
  event.waitUntil(
    self.registration.showNotification(data.title || 'Dreamland', {
      body: data.body || '',
      icon: '/icons/icon-192.png',
      badge: '/icons/icon-192.png',
      tag: 'dreamland-push',
      renotify: true,
      data: { url: data.url || '/' },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = event.notification.data?.url || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
      return undefined;
    })
  );
});
