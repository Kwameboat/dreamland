const CACHE_NAME = 'dreamland-v22';

const CORE_ASSETS = [
  '/',
  '/index.html',
  '/css/app.css',
  '/css/onboarding.css',
  '/js/config.js',
  '/js/app.js',
  '/js/dreamland-features.js',
  '/js/dreamland-live.js',
  '/manifest.json',
  '/assets/logo.png',
  '/assets/community-network.svg',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);
  if (url.pathname.startsWith('/v1') || url.pathname.includes('/api/')) return;

  event.respondWith(
    (async () => {
      const isAsset = /\.(css|js|png|svg|html|json)$/.test(url.pathname) || url.pathname === '/';
      if (isAsset && (url.pathname.includes('/js/') || url.pathname.includes('/css/'))) {
        try {
          const response = await fetch(event.request);
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          }
          return response;
        } catch {
          const cached = await caches.match(event.request);
          if (cached) return cached;
          throw new Error('Offline');
        }
      }
      const cached = await caches.match(event.request);
      if (cached) return cached;
      return fetch(event.request).then((response) => {
        if (!response.ok) return response;
        const clone = response.clone();
        if (isAsset) {
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => cached);
    })()
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
      icon: '/assets/logo.png',
      badge: '/assets/logo.png',
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
