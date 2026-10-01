self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open('progresslab-static-v3');
    await cache.addAll([
      '/images/branding/progresslab-app-192.png',
      '/images/branding/progresslab-app-512.png',
      '/images/branding/progresslab-maskable-512.png',
    ]);
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys
      .filter((key) => key.startsWith('progresslab-static-') && key !== 'progresslab-static-v3')
      .map((key) => caches.delete(key)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const requestUrl = new URL(event.request.url);
  if (requestUrl.origin !== self.location.origin) return;

  const isStaticAsset = ['/css/', '/js/', '/images/', '/sfx/']
    .some((prefix) => requestUrl.pathname.startsWith(prefix));

  if (isStaticAsset) {
    // Browsers use range requests for media and may return HTTP 206. The
    // Cache API cannot store partial responses, so always serve them directly.
    if (event.request.headers.has('range')) {
      event.respondWith(fetch(event.request));
      return;
    }

    event.respondWith((async () => {
      const cache = await caches.open('progresslab-static-v3');
      const cached = await cache.match(event.request);
      if (cached) return cached;

      const response = await fetch(event.request);

      // response.ok also includes 206 responses. Only complete same-origin
      // responses are safe to persist in Cache Storage.
      if (response.status === 200 && response.type === 'basic') {
        await cache.put(event.request, response.clone()).catch(() => {});
      }

      return response;
    })());

    return;
  }

  // Account documents always remain network-only and are never placed in a
  // service-worker cache.
  event.respondWith(fetch(event.request));
});

self.addEventListener('push', (event) => {
  let payload = {};

  try {
    payload = event.data ? event.data.json() : {};
  } catch (_error) {
    payload = { body: event.data ? event.data.text() : 'You have a new ProgressLab notification.' };
  }

  const title = payload.title || 'ProgressLab';
  const options = {
    body: payload.body || 'You have a new notification.',
    icon: payload.icon || '/images/branding/progresslab-app-192.png',
    badge: payload.badge || '/images/branding/progresslab-favicon.png',
    tag: payload.tag || 'progresslab-notification',
    renotify: true,
    data: { url: payload.url || '/notifications' },
  };

  const tasks = [self.registration.showNotification(title, options)];

  if (Number.isFinite(payload.badgeCount) && 'setAppBadge' in self.navigator) {
    tasks.push(self.navigator.setAppBadge(payload.badgeCount));
  }

  event.waitUntil(Promise.all(tasks));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const targetUrl = new URL(event.notification.data?.url || '/notifications', self.location.origin).href;

  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const existing = windows.find((client) => new URL(client.url).origin === self.location.origin);

    if (existing) {
      await existing.navigate(targetUrl);
      return existing.focus();
    }

    return self.clients.openWindow(targetUrl);
  })());
});
