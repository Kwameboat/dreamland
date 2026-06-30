/* Dreamland PWA service worker — cache version follows build-version.json */

const CACHE_PREFIX = 'dreamland-';
let activeCacheName = `${CACHE_PREFIX}boot`;

async function fetchBuildMeta() {
  const res = await fetch('/build-version.json', { cache: 'no-store' });
  if (!res.ok) throw new Error('build-version unavailable');
  const data = await res.json();
  const version = String(data.version || 'build-unknown');
  return { version, builtAt: String(data.builtAt || ''), cacheName: `${CACHE_PREFIX}${version}` };
}

const CORE_ASSETS = [
  '/',
  '/index.html',
  '/css/app.css',
  '/css/onboarding.css',
  '/js/config.js',
  '/js/app.js',
  '/js/dreamland-features.js',
  '/js/dreamland-live.js',
  '/js/vendor/socket.io.esm.min.js',
  '/js/vendor/mediasoup-client.esm.js',
  '/js/dreamland-ai.js',
  '/js/dreamland-reels-fast.js',
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
];

function isBypassPath(pathname) {
  return pathname === '/sw.js'
    || pathname.endsWith('/sw.js')
    || pathname === '/build-version.json'
    || pathname.endsWith('/build-version.json');
}

function isMutableAsset(pathname) {
  return pathname === '/'
    || pathname.endsWith('.html')
    || pathname.includes('/js/')
    || pathname.includes('/css/')
    || pathname === '/env-config.js'
    || pathname.endsWith('/env-config.js');
}

function isJavaScriptResponse(response) {
  const ct = (response.headers.get('content-type') || '').toLowerCase();
  return ct.includes('javascript') || ct.includes('ecmascript') || ct.includes('module');
}

async function networkFirst(request, cacheName) {
  try {
    const response = await fetch(request, { cache: 'no-store' });
    if (response.ok && cacheName) {
      const path = new URL(request.url).pathname;
      const isJs = path.includes('/js/') && path.endsWith('.js');
      if (!isJs || isJavaScriptResponse(response)) {
        const cache = await caches.open(cacheName);
        cache.put(request, response.clone()).catch(() => {});
      }
    }
    return response;
  } catch {
    const cached = cacheName ? await caches.match(request) : null;
    if (cached) return cached;
    return new Response('Offline', { status: 503, statusText: 'Offline' });
  }
}

async function purgeOldCaches(keepName) {
  const keys = await caches.keys();
  await Promise.all(
    keys
      .filter((key) => key.startsWith(CACHE_PREFIX) && key !== keepName)
      .map((key) => caches.delete(key)),
  );
}

async function notifyClients(meta) {
  const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
  clients.forEach((client) => {
    client.postMessage({
      type: 'SW_ACTIVATED',
      version: meta.version,
      builtAt: meta.builtAt,
    });
  });
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    fetchBuildMeta()
      .then((meta) => {
        activeCacheName = meta.cacheName;
        return caches.open(activeCacheName).then((cache) =>
          Promise.allSettled(CORE_ASSETS.map((asset) => cache.add(asset))),
        );
      })
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    fetchBuildMeta()
      .then(async (meta) => {
        activeCacheName = meta.cacheName;
        await purgeOldCaches(activeCacheName);
        await self.clients.claim();
        await notifyClients(meta);
      })
      .catch(async () => {
        await purgeOldCaches(activeCacheName);
        await self.clients.claim();
      }),
  );
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data?.type === 'CLEAR_CACHES') {
    event.waitUntil(
      caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key)))),
    );
  }
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.startsWith('/v1') || url.pathname.includes('/api/')) return;

  if (isBypassPath(url.pathname)) {
    event.respondWith(fetch(event.request, { cache: 'no-store' }));
    return;
  }

  if (event.request.mode === 'navigate' || isMutableAsset(url.pathname)) {
    event.respondWith(networkFirst(event.request, activeCacheName));
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cached) => {
      if (cached) return cached;
      return fetch(event.request).then((response) => {
        if (!response.ok) return response;
        const path = url.pathname;
        const isJs = path.includes('/js/') && path.endsWith('.js');
        if (isJs && !isJavaScriptResponse(response)) return response;
        const clone = response.clone();
        caches.open(activeCacheName).then((cache) => cache.put(event.request, clone)).catch(() => {});
        return response;
      });
    }),
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
    }),
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
    }),
  );
});
