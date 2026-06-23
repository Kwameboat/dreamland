/**
 * Dreamland extended features: gamification, discovery, notifications, legal.
 */
export function createDreamlandFeatures(ctx) {
  const {
    api, API_ROUTES, state, showToast, gateGuest, openAuthModal, escapeHtml, formatCount,
  } = ctx;

  let previewSeconds = 3;
  let vapidPublicKey = '';
  let maxReelDurationSeconds = 60;
  let maxReelUploadMb = 128;
  let maxLiveDurationSeconds = 0;
  let categories = [];
  let watchTimer = null;
  let watchAccum = 0;
  const notifications = [];

  async function loadSettings() {
    try {
      const res = await api(API_ROUTES.settings);
      previewSeconds = Number(res.data?.preview_seconds) || 3;
      localStorage.setItem('dreamland_preview_seconds', String(previewSeconds));
      vapidPublicKey = res.data?.vapid_public_key || localStorage.getItem('dreamland_vapid') || '';
      maxReelDurationSeconds = Number(res.data?.max_reel_duration_seconds) || 60;
      maxReelUploadMb = Number(res.data?.max_reel_upload_mb) || 128;
      maxLiveDurationSeconds = Number(res.data?.max_live_duration_seconds) || 3600;
      const uploadsBase = res.data?.uploads_base || res.raw?.data?.uploads_base;
      if (uploadsBase) {
        localStorage.setItem('dreamland_uploads', uploadsBase);
      }
      if (vapidPublicKey) {
        localStorage.setItem('dreamland_vapid', vapidPublicKey);
      }
      localStorage.setItem('dreamland_upload_limits', JSON.stringify({
        maxReelDurationSeconds,
        maxReelUploadMb,
        maxLiveDurationSeconds,
      }));
    } catch {
      previewSeconds = Number(localStorage.getItem('dreamland_preview_seconds')) || 3;
      vapidPublicKey = localStorage.getItem('dreamland_vapid') || '';
      try {
        const cached = JSON.parse(localStorage.getItem('dreamland_upload_limits') || '{}');
        maxReelDurationSeconds = Number(cached.maxReelDurationSeconds) || 60;
        maxReelUploadMb = Number(cached.maxReelUploadMb) || 128;
        maxLiveDurationSeconds = Number(cached.maxLiveDurationSeconds) || 0;
      } catch { /* defaults */ }
    }
    return previewSeconds;
  }

  async function loadCategories() {
    try {
      const res = await api(API_ROUTES.categories);
      categories = res.data?.categories || [];
    } catch {
      categories = [];
    }
    return categories;
  }

  function getPreviewSeconds() {
    return previewSeconds;
  }

  function getMaxReelDurationSeconds() {
    return maxReelDurationSeconds;
  }

  function getMaxReelUploadMb() {
    return maxReelUploadMb;
  }

  function getMaxReelUploadBytes() {
    return maxReelUploadMb * 1024 * 1024;
  }

  function getMaxLiveDurationSeconds() {
    return maxLiveDurationSeconds;
  }

  function renderGenreFilter(container, onChange) {
    if (!container) return;
    const chips = [{ id: '', name: 'All' }, ...categories];
    container.innerHTML = chips.map((c) =>
      `<button type="button" class="genre-chip ${state.feedGenre === String(c.id) ? 'active' : ''}" data-genre="${c.id}">${escapeHtml(c.name)}</button>`
    ).join('');
    container.querySelectorAll('.genre-chip').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.feedGenre = String(btn.dataset.genre || '');
        container.querySelectorAll('.genre-chip').forEach((b) => b.classList.toggle('active', b === btn));
        onChange(state.feedGenre);
      });
    });
  }

  function pushNotification(title, body) {
    notifications.unshift({ title, body, at: Date.now() });
    if (notifications.length > 20) notifications.pop();
    renderNotificationBell();
  }

  function renderNotificationBell() {
    const bell = document.getElementById('notif-btn');
    const list = document.getElementById('notif-list');
    if (bell) {
      bell.dataset.count = notifications.length ? String(notifications.length) : '';
    }
    if (list) {
      list.innerHTML = notifications.length
        ? notifications.map((n) => `<div class="notif-item"><strong>${escapeHtml(n.title)}</strong><p class="muted">${escapeHtml(n.body)}</p></div>`).join('')
        : '<p class="muted">No notifications yet.</p>';
    }
  }

  async function followCreator(userId) {
    if (gateGuest()) return;
    try {
      await api('followers', {
        method: 'POST',
        body: JSON.stringify({ user_id: Number(userId) }),
      });
      showToast('Following creator');
      pushNotification('Following', 'You will see more from this creator.');
    } catch (err) {
      showToast(err.message || 'Could not follow');
    }
  }

  function startWatchTracking() {
    if (watchTimer || !state.token) return;
    watchTimer = setInterval(async () => {
      if (!state.token) return;
      watchAccum += 15;
      if (watchAccum >= 15) {
        try {
          const res = await api(API_ROUTES.recordWatch, {
            method: 'POST',
            body: JSON.stringify({ seconds: 15 }),
          });
          watchAccum = 0;
          if (res.data?.streak?.milestone_reached) {
            pushNotification('Streak milestone', `Day ${res.data.streak.current_streak} reward unlocked!`);
            showToast('Streak milestone reached!');
          }
        } catch { /* ignore */ }
      }
    }, 15000);
  }

  async function freezeStreak() {
    if (gateGuest()) return;
    try {
      const res = await api(API_ROUTES.freezeStreak, { method: 'POST', body: JSON.stringify({}) });
      showToast(res.message || 'Streak frozen');
      pushNotification('Streak saved', 'Your streak is protected.');
      return res.data;
    } catch (err) {
      showToast(err.message || 'Could not freeze streak');
      return null;
    }
  }

  async function loadStreakPanel(container) {
    if (!container || !state.token) return;
    try {
      const res = await api(API_ROUTES.streakStatus);
      const streak = res.data?.streak || {};
      const compact = container.classList.contains('feed-streak-bar--compact')
        || container.id === 'feed-streak-bar';
      if (compact) {
        const days = formatCount(streak.current_streak || 0);
        if (!days || days === '0') {
          container.innerHTML = '';
          return;
        }
        container.innerHTML = `
          <div class="streak-panel streak-panel--inline">
            <span class="streak-inline-label">🔥</span>
            <span class="streak-inline-count">${days}</span>
            <span class="streak-inline-label">day streak</span>
          </div>`;
        return;
      }
      container.innerHTML = `
        <div class="streak-panel glass-card">
          <p class="eyebrow">Daily streak</p>
          <h3>${formatCount(streak.current_streak || 0)} days</h3>
          <p class="muted">Watch ${formatCount(streak.watch_threshold || 300)}s or play to keep your streak.</p>
          <button type="button" class="btn-ghost" id="freeze-streak-btn">Freeze streak (5 cr)</button>
        </div>`;
      document.getElementById('freeze-streak-btn')?.addEventListener('click', freezeStreak);
    } catch {
      container.innerHTML = '';
    }
  }

  async function fetchWatchPot(videoId) {
    try {
      const res = await api(`${API_ROUTES.watchPot}?video_id=${encodeURIComponent(videoId)}`);
      return res.data?.pot || null;
    } catch {
      return null;
    }
  }

  async function fetchPredictions(videoId) {
    try {
      const res = await api(`${API_ROUTES.predictionsForVideo}?video_id=${encodeURIComponent(videoId)}`);
      return res.data?.predictions || [];
    } catch {
      return [];
    }
  }

  async function stakePrediction(predictionId, side, credits) {
    if (gateGuest()) return;
    try {
      const res = await api(API_ROUTES.stakePrediction, {
        method: 'POST',
        body: JSON.stringify({ prediction_id: predictionId, prediction_side: side, stake_credits: credits }),
      });
      showToast(res.message || 'Stake placed');
      pushNotification('Prediction staked', `You staked ${credits} credits.`);
    } catch (err) {
      showToast(err.message || 'Stake failed');
    }
  }

  async function loadWalletTransactions(container) {
    if (!container || !state.token) return;
    try {
      const res = await api(API_ROUTES.walletTransactions);
      const rows = res.data?.transactions || [];
      container.innerHTML = rows.length
        ? `<div class="ledger-list">${rows.map((r) =>
          `<div class="ledger-row"><span>${escapeHtml(r.type)}</span><strong>${r.credits > 0 ? '+' : ''}${r.credits} cr</strong></div>`
        ).join('')}</div>`
        : '<p class="muted">No transactions yet.</p>';
    } catch {
      container.innerHTML = '<p class="muted">Could not load transactions.</p>';
    }
  }

  function renderCategorySelect(selectEl) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">Choose genre…</option>' + categories.map((c) =>
      `<option value="${c.id}">${escapeHtml(c.name)}</option>`
    ).join('');
  }

  async function fetchNotifications() {
    if (!state.token) return;
    try {
      const res = await api(API_ROUTES.notifications);
      const items = res.notification?.items || res.items || [];
      notifications.length = 0;
      items.slice(0, 30).forEach((row) => {
        notifications.push({
          title: row.title || 'Dreamland',
          body: row.message || row.body || '',
          at: (row.created_at || 0) * 1000,
        });
      });
      renderNotificationBell();
    } catch {
      /* keep local notifications */
    }
  }

  async function registerPush() {
    if (!state.token) return false;
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
      return false;
    }

    await loadSettings();
    if (!vapidPublicKey) {
      console.warn('[Dreamland] Push not configured on server (missing VAPID key).');
      return false;
    }

    const permission = Notification.permission === 'granted'
      ? 'granted'
      : await Notification.requestPermission();
    if (permission !== 'granted') {
      showToast('Enable notifications in browser settings');
      return false;
    }

    try {
      const reg = await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });
      }

      await api(API_ROUTES.pushRegister, {
        method: 'POST',
        body: JSON.stringify(sub.toJSON()),
      });

      pushNotification('Notifications on', 'Alerts work even from your home screen.');
      return true;
    } catch (err) {
      showToast(err.message || 'Could not enable push notifications');
      return false;
    }
  }

  function urlBase64ToUint8Array(base64String) {
    if (!base64String) return new Uint8Array();
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
  }

  async function init() {
    await Promise.allSettled([
      loadSettings(),
      loadCategories(),
      fetchNotifications(),
    ]);
    renderNotificationBell();
    startWatchTracking();
  }

  return {
    init,
    getPreviewSeconds,
    getMaxReelDurationSeconds,
    getMaxReelUploadMb,
    getMaxReelUploadBytes,
    getMaxLiveDurationSeconds,
    loadSettings,
    renderGenreFilter,
    followCreator,
    loadStreakPanel,
    fetchWatchPot,
    fetchPredictions,
    stakePrediction,
    loadWalletTransactions,
    renderCategorySelect,
    registerPush,
    fetchNotifications,
    pushNotification,
    loadCategories,
  };
}
