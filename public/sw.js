self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
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
    icon: payload.icon || '/images/branding/progresslab-logo.png',
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
