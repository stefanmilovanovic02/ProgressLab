(() => {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }

  const panel = document.querySelector('[data-push-settings]');
  if (!panel) return;

  const enableButton = panel.querySelector('[data-push-enable]');
  const disableButton = panel.querySelector('[data-push-disable]');
  const testButton = panel.querySelector('[data-push-test]');
  const status = panel.querySelector('[data-push-status]');
  const installHint = panel.querySelector('[data-push-install-hint]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const publicKey = panel.dataset.publicKey;
  const storeUrl = panel.dataset.storeUrl;
  const destroyUrl = panel.dataset.destroyUrl;
  const testUrl = panel.dataset.testUrl;

  const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;
  const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

  const setStatus = (message, type = '') => {
    status.textContent = message;
    status.dataset.type = type;
  };

  const request = async (url, method, body = null) => {
    const response = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: body ? JSON.stringify(body) : null,
    });

    if (!response.ok) throw new Error('The server could not save this notification setting.');
    return response.json();
  };

  const applicationServerKey = (value) => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
  };

  const updateControls = async () => {
    if (!supported) {
      enableButton.hidden = false;
      enableButton.disabled = true;
      setStatus('This browser does not support Web Push notifications.', 'error');
      return;
    }

    if (isIos && !isStandalone) {
      installHint.hidden = false;
      enableButton.hidden = false;
      enableButton.disabled = true;
      setStatus('On iPhone, first add ProgressLab to your Home Screen, then open it from the new icon.', 'info');
      return;
    }

    if (!publicKey) {
      enableButton.hidden = false;
      enableButton.disabled = true;
      setStatus('Push delivery is not configured on this server yet.', 'error');
      return;
    }

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    const enabled = Boolean(subscription) && Notification.permission === 'granted';

    if (enabled) {
      const json = subscription.toJSON();
      await request(storeUrl, 'POST', {
        endpoint: subscription.endpoint,
        keys: json.keys,
        contentEncoding: 'aes128gcm',
      });
    }

    enableButton.hidden = enabled;
    enableButton.disabled = Notification.permission === 'denied';
    disableButton.hidden = !enabled;
    testButton.hidden = !enabled;
    setStatus(
      enabled
        ? 'Push notifications are enabled on this device.'
        : Notification.permission === 'denied'
          ? 'Notifications are blocked. Allow them in your device or browser settings.'
          : 'Enable reminders and friend achievement alerts on this device.',
      enabled ? 'success' : (Notification.permission === 'denied' ? 'error' : 'info')
    );
  };

  enableButton?.addEventListener('click', async () => {
    enableButton.disabled = true;
    setStatus('Waiting for notification permission…', 'info');

    try {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') throw new Error('Notification permission was not allowed.');

      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey(publicKey),
      });
      const json = subscription.toJSON();

      await request(storeUrl, 'POST', {
        endpoint: subscription.endpoint,
        keys: json.keys,
        contentEncoding: 'aes128gcm',
      });

      await updateControls();
    } catch (error) {
      setStatus(error.message || 'Push notifications could not be enabled.', 'error');
      enableButton.disabled = false;
    }
  });

  disableButton?.addEventListener('click', async () => {
    disableButton.disabled = true;

    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();
      if (subscription) {
        await request(destroyUrl, 'DELETE', { endpoint: subscription.endpoint });
        await subscription.unsubscribe();
      }
      await updateControls();
    } catch (error) {
      setStatus(error.message || 'Push notifications could not be disabled.', 'error');
      disableButton.disabled = false;
    }
  });

  testButton?.addEventListener('click', async () => {
    testButton.disabled = true;
    setStatus('Sending a test notification…', 'info');

    try {
      await request(testUrl, 'POST');
      setStatus('Test sent. It should appear on this device shortly.', 'success');
    } catch (error) {
      setStatus(error.message || 'The test notification could not be sent.', 'error');
    } finally {
      testButton.disabled = false;
    }
  });

  updateControls().catch(() => setStatus('Push notification status could not be checked.', 'error'));
})();
