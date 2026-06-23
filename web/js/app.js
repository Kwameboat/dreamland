import {
  API_BASE,
  API_ROUTES,
  DEV_ALLOW_BROWSER,
  DEV_CREATOR_LOGIN,
  UPLOADS_BASE,
  apiUploadsBase,
} from './config.js';
import { createDreamlandFeatures } from './dreamland-features.js';
import { createDreamlandAi } from './dreamland-ai.js';
import { createDreamlandSocial } from './dreamland-social.js';
import { createDreamlandProfile } from './dreamland-profile.js';
import { createDreamlandSearch } from './dreamland-search.js';
import { createDreamlandAccount } from './dreamland-account.js';

const ONBOARDING_KEY = 'has_completed_onboarding';
const WALKTHROUGH_KEY = 'has_seen_walkthrough';
const PWA_INSTALLED_KEY = 'dreamland_pwa_installed';
let PAYSTACK_KEY = localStorage.getItem('paystack_public_key') || '';
let deferredPwaPrompt = null;
let onboardingGlide = null;
let standalonePollTimer = null;
let apiOnline = null;

const INSTALL_GUIDES = {
  ios: {
    label: 'iPhone / iPad (iOS Safari)',
    steps: [
      'Open this page in Safari (not Chrome or in-app browser).',
      'Tap the Share button — the square icon with an arrow pointing up at the bottom of the screen.',
      'Scroll the share menu and tap "Add to Home Screen".',
      'Tap "Add" in the top-right corner to confirm.',
      'Open Dreamland from your new home screen icon — the app will launch automatically.',
    ],
  },
  android: {
    label: 'Android (Chrome)',
    steps: [
      'Tap the menu button — three dots in the top-right corner of Chrome.',
      'Tap "Install app" or "Add to Home screen".',
      'Confirm by tapping "Install" or "Add".',
      'Find the Dreamland icon on your home screen and open it from there.',
    ],
  },
  samsung: {
    label: 'Android (Samsung Internet)',
    steps: [
      'Tap the menu button — three lines at the bottom-right.',
      'Tap "Add page to" then choose "Home screen".',
      'Confirm the shortcut name "Dreamland" and tap "Add".',
      'Launch Dreamland from your home screen — do not use the browser tab.',
    ],
  },
  'android-firefox': {
    label: 'Android (Firefox)',
    steps: [
      'Tap the menu button — three dots in the top-right.',
      'Tap "Install" or "Add to Home screen".',
      'Confirm the install prompt.',
      'Open Dreamland from your home screen icon.',
    ],
  },
  windows: {
    label: 'Windows (Chrome or Edge)',
    steps: [
      'Look for the install icon in the address bar — a monitor with a down arrow (⊕ or computer icon).',
      'Click "Install" in the popup, or open the browser menu (⋮) and choose "Install Dreamland…".',
      'Click "Install" in the confirmation dialog.',
      'Open Dreamland from your Start menu, taskbar, or desktop shortcut.',
    ],
  },
  macos: {
    label: 'Mac (Safari or Chrome)',
    steps: [
      'In Safari: File → "Add to Dock", or Share → "Add to Dock".',
      'In Chrome: click the install icon in the address bar, or Menu (⋮) → "Install Dreamland…".',
      'Confirm the install when prompted.',
      'Launch Dreamland from your Dock or Applications — not from a browser tab.',
    ],
  },
  other: {
    label: 'Your device',
    steps: [
      'Use your browser menu to find "Install app", "Add to Home screen", or "Install Dreamland".',
      'Confirm the installation when your browser asks.',
      'Open Dreamland from your home screen, app list, or desktop shortcut.',
      'Do not continue in the browser tab — use the installed app icon.',
    ],
  },
};

function isStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches
    || window.matchMedia('(display-mode: fullscreen)').matches
    || window.navigator.standalone === true;
}

function isPwaInstalled() {
  if (DEV_ALLOW_BROWSER) return true;
  return isStandalone() || localStorage.getItem(PWA_INSTALLED_KEY) === 'true';
}

function detectInstallPlatform() {
  const ua = navigator.userAgent || '';
  const isIOS = /iPad|iPhone|iPod/.test(ua)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  if (isIOS) return 'ios';
  if (/SamsungBrowser/i.test(ua)) return 'samsung';
  if (/Android/i.test(ua)) {
    return /Firefox/i.test(ua) ? 'android-firefox' : 'android';
  }
  if (/Windows/i.test(ua)) return 'windows';
  if (/Macintosh|Mac OS X/i.test(ua)) return 'macos';
  return 'other';
}

function renderInstallGuide() {
  const platform = detectInstallPlatform();
  const guide = INSTALL_GUIDES[platform] || INSTALL_GUIDES.other;
  const osLabel = document.getElementById('installOsLabel');
  const stepsList = document.getElementById('installStepsList');
  if (!osLabel || !stepsList) return;
  osLabel.textContent = `Detected: ${guide.label}`;
  stepsList.innerHTML = guide.steps.map((step) => `<li>${step}</li>`).join('');
  updateInstallButtonForPlatform(platform);
}

function updateInstallButtonForPlatform(platform = detectInstallPlatform()) {
  const btn = document.getElementById('pwaInstallBtn');
  const label = document.getElementById('pwaInstallBtnLabel');
  if (!btn || !label) return;

  btn.classList.remove('ob-pwa-btn--ready', 'ob-pwa-btn--ios');

  if (isStandalone()) {
    label.textContent = 'OPENING APP…';
    return;
  }

  if (deferredPwaPrompt) {
    label.textContent = platform === 'ios' ? 'ADD TO HOME SCREEN' : 'INSTALL NOW';
    btn.classList.add('ob-pwa-btn--ready');
    return;
  }

  if (platform === 'ios') {
    label.textContent = 'SHOW iOS STEPS';
    btn.classList.add('ob-pwa-btn--ios');
    return;
  }

  label.textContent = 'ADD TO HOME SCREEN';
}

async function attemptPwaInstall() {
  renderInstallGuide();

  if (isStandalone()) {
    markPwaInstalled();
    completeOnboarding();
    return true;
  }

  if (deferredPwaPrompt) {
    try {
      deferredPwaPrompt.prompt();
      const { outcome } = await deferredPwaPrompt.userChoice;
      deferredPwaPrompt = null;
      updateInstallButtonForPlatform();
      if (outcome === 'accepted') {
        markPwaInstalled();
        completeOnboarding();
        return true;
      }
    } catch (err) {
      console.warn('[Dreamland] PWA install prompt failed', err);
    }
  }

  const platform = detectInstallPlatform();
  const guide = document.getElementById('installGuide');
  guide?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  guide?.classList.add('ob-install-guide--pulse');
  setTimeout(() => guide?.classList.remove('ob-install-guide--pulse'), 1200);

  if (platform === 'ios') {
    updateInstallButtonForPlatform('ios');
  }
  return false;
}

function markPwaInstalled() {
  localStorage.setItem(PWA_INSTALLED_KEY, 'true');
}

function hasCompletedOnboarding() {
  return localStorage.getItem(ONBOARDING_KEY) === 'true' && isPwaInstalled();
}

function completeOnboarding() {
  if (!isPwaInstalled()) return;
  localStorage.setItem(ONBOARDING_KEY, 'true');
  localStorage.setItem(WALKTHROUGH_KEY, 'true');
  if (standalonePollTimer) clearInterval(standalonePollTimer);
  els.onboarding?.classList.add('fade-out');
  setTimeout(() => {
    els.onboarding?.classList.add('hidden');
    els.appShell?.classList.remove('hidden');
    bootMainApp();
  }, 500);
}

function tryUnlockFromStandalone() {
  if (!isStandalone()) return;
  markPwaInstalled();
  completeOnboarding();
}

function startStandaloneWatcher() {
  tryUnlockFromStandalone();
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) tryUnlockFromStandalone();
  });
  window.addEventListener('focus', tryUnlockFromStandalone);
  standalonePollTimer = setInterval(tryUnlockFromStandalone, 1500);
}

function readStoredUser() {
  try {
    return JSON.parse(localStorage.getItem('dreamland_user') || 'null');
  } catch {
    localStorage.removeItem('dreamland_user');
    return null;
  }
}

function setFeedLoadingMessage(title = 'Loading reels…') {
  if (!els.feedList) return;
  els.feedList.innerHTML = `
    <div class="live-loading glass-card" style="margin:16px;padding:16px">
      <p class="eyebrow">Watch</p>
      <h2>${title}</h2>
    </div>`;
}

function isFeedBootStuck() {
  const text = els.feedList?.textContent || '';
  return text.includes('Starting Dreamland') || text.includes('Loading reels');
}

const state = {
  token: localStorage.getItem('dreamland_token') || null,
  user: readStoredUser(),
  feed: [],
  lives: [],
  feedMode: 'reels',
  feedSource: 'foryou',
  feedGenre: '',
  feedPage: 1,
  feedHasMore: true,
  feedLoading: false,
  feedError: '',
  packages: [],
  currentPaywallVideo: null,
  currentPaywallLive: null,
  paywallType: 'video',
  activeLiveWatch: null,
  creatorDashboard: null,
  viewerDashboard: null,
  studioDraft: null,
};

let dashboardRefreshTimer = null;
const DASHBOARD_REFRESH_MS = 60000;

function stopDashboardAutoRefresh() {
  if (dashboardRefreshTimer) {
    clearInterval(dashboardRefreshTimer);
    dashboardRefreshTimer = null;
  }
}

function touchDashboardRefreshHint() {
  const el = document.getElementById('dashboard-refresh-hint');
  if (!el) return;
  el.textContent = `Updated ${new Date().toLocaleTimeString()} · auto-refresh every 60s`;
}

function startDashboardAutoRefresh(viewId) {
  stopDashboardAutoRefresh();
  if (!state.token) return;

  if (viewId === 'creator-view' && isCreator()) {
    dashboardRefreshTimer = setInterval(() => {
      if (document.hidden) return;
      if (!document.getElementById('creator-view')?.classList.contains('active')) return;
      loadCreatorDashboard(true);
    }, DASHBOARD_REFRESH_MS);
    return;
  }

  if (viewId === 'viewer-view' && !isCreator()) {
    dashboardRefreshTimer = setInterval(() => {
      if (document.hidden) return;
      if (!document.getElementById('viewer-view')?.classList.contains('active')) return;
      loadViewerDashboard(true);
    }, DASHBOARD_REFRESH_MS);
  }
}

const MAX_UPLOAD_BYTES_DEFAULT = 128 * 1024 * 1024;

function maxUploadBytes() {
  return dlFeatures?.getMaxReelUploadBytes?.() || MAX_UPLOAD_BYTES_DEFAULT;
}

function maxReelDurationSeconds() {
  return dlFeatures?.getMaxReelDurationSeconds?.() || 60;
}

function formatReelDurationLimit() {
  const sec = maxReelDurationSeconds();
  if (sec < 60) return `${sec}s`;
  const mins = Math.round(sec / 60);
  return mins === 1 ? '1 minute' : `${mins} minutes`;
}

function getDraftClipDuration(draft) {
  if (!draft) return 0;
  const duration = draft.duration || 0;
  const start = draft.trimStart || 0;
  const end = draft.trimEnd ?? duration;
  const clip = Math.max(0, end - start);
  const speed = draft.speed || 1;
  return clip / speed;
}

function maxLiveDurationSeconds() {
  return dlFeatures?.getMaxLiveDurationSeconds?.() || 0;
}

const STUDIO_EDIT_FILTERS = {
  none: { label: 'Original', css: 'none' },
  vivid: { label: 'Vivid', css: 'contrast(1.12) saturate(1.35) brightness(1.04)' },
  noir: { label: 'Noir', css: 'grayscale(1) contrast(1.15) brightness(0.92)' },
  warm: { label: 'Warm', css: 'sepia(0.28) saturate(1.2) brightness(1.05)' },
  cool: { label: 'Cool', css: 'hue-rotate(200deg) saturate(0.9) brightness(1.02)' },
  dream: { label: 'Dream', css: 'saturate(1.45) contrast(1.08) hue-rotate(-12deg) brightness(1.06)' },
};

const STUDIO_EDIT_SPEEDS = [0.75, 1, 1.25, 1.5];

let studioEditMusicEl = null;

const els = {
  onboarding: document.getElementById('onboarding'),
  appShell: document.getElementById('app'),
  feedList: document.getElementById('feed-list'),
  liveList: document.getElementById('live-list'),
  liveCards: document.getElementById('live-cards'),
  packageGrid: document.getElementById('package-grid'),
  authModal: document.getElementById('auth-modal'),
  paywallModal: document.getElementById('paywall-modal'),
  apiStatus: document.getElementById('api-status'),
  viewerDashboard: document.getElementById('viewer-dashboard'),
  creatorDashboard: document.getElementById('creator-dashboard'),
  walletCredits: document.getElementById('wallet-credits'),
  toast: document.getElementById('toast'),
};

const PREVIEW_SECONDS = 3;
let reelObserver = null;
let toastTimer = null;
let authMode = 'signin';
let authForgotStep = 'email';
let passwordResetToken = null;
let cameraStream = null;
let mediaRecorder = null;
let recordedChunks = [];
let recordFacingMode = 'user';
let recordCaptureOpen = false;
let liveBroadcastOpen = false;
let liveBroadcastActive = false;
let liveMaxTimer = null;
let liveFacingMode = 'user';
let recordTimerInterval = null;
let recordElapsedSec = 0;
let dlFeatures = null;
let dlAi = null;
let dlSocial = null;
let dlProfile = null;
let dlSearch = null;
let dlAccount = null;
let dreamlandLive = null;
let feedScrollBound = false;

async function ensureDreamlandLive() {
  if (dreamlandLive) return dreamlandLive;
  const { createDreamlandLive } = await import('./dreamland-live.js');
  dreamlandLive = createDreamlandLive({ showToast, formatCount });
  return dreamlandLive;
}

function unwrapPayload(json, httpStatus = 200) {
  if (json && typeof json === 'object' && 'data' in json && ('status' in json || 'message' in json)) {
    const status = json.status ?? httpStatus;
    return {
      ok: status >= 200 && status < 300,
      status,
      message: json.message || '',
      data: json.data ?? {},
      raw: json,
    };
  }

  if (json && typeof json === 'object' && (json.statusCode >= 400 || json.status >= 400)) {
    const status = json.statusCode || json.status || httpStatus;
    return {
      ok: false,
      status,
      message: String(json.message || 'Request failed'),
      data: json,
      raw: json,
    };
  }

  if (json && typeof json === 'object' && json.message && (json.status >= 400 || json.name)) {
    return {
      ok: false,
      status: json.status || httpStatus,
      message: String(json.message),
      data: json,
      raw: json,
    };
  }

  return { ok: httpStatus >= 200 && httpStatus < 300, status: httpStatus, message: '', data: json ?? {}, raw: json };
}

function apiErrorMessage(payload) {
  const errors = payload?.data?.errors;
  if (errors && typeof errors === 'object') {
    const parts = [];
    Object.values(errors).forEach((val) => {
      if (Array.isArray(val)) parts.push(...val);
      else if (val) parts.push(String(val));
    });
    if (parts.length) return parts.join(' ');
  }
  const raw = String(payload?.raw?.message || payload?.message || payload?.data?.message || '');
  if (/SQLSTATE|Database Exception|relation .* does not exist|yii\\\\db/i.test(raw)) {
    return DEV_ALLOW_BROWSER
      ? 'API database error — check Supabase migrations.'
      : 'Could not load from Dreamland API — tap Reload feed.';
  }
  if (payload?.message) return payload.message;
  if (payload?.data?.message) return String(payload.data.message);
  if (payload?.raw?.message) return String(payload.raw.message);
  return 'Request failed';
}

function humanizePostText(value, fallback = '') {
  if (value == null || value === '') return fallback;
  if (typeof value === 'object') {
    if (typeof value.title === 'string') return humanizePostText(value.title, fallback);
    return fallback;
  }
  const text = String(value).trim();
  if (!text || text === '[object Object]' || /^\\x[0-9a-f]+$/i.test(text)) return fallback;
  if (text.startsWith('{') && text.endsWith('}')) {
    try {
      const parsed = JSON.parse(text);
      if (typeof parsed === 'string') return parsed;
      if (parsed?.title) return humanizePostText(parsed.title, fallback);
    } catch {
      return fallback;
    }
  }
  if (looksLikeUploadFilename(text)) return fallback;
  return text;
}

function looksLikeUploadFilename(text) {
  if (!text || text.length < 20) return false;
  if (/\s/.test(text)) return false;
  return /^[A-Za-z0-9._-]+$/.test(text);
}

function reelGallery(post) {
  const gallery = post?.postGallary;
  if (!gallery) return null;
  const items = Array.isArray(gallery) ? gallery : (gallery.items || []);
  if (!items.length) return null;
  return items.find((item) => Number(item.media_type) === 2) || items[0];
}

function reelMediaCandidates(post) {
  if (post?.reel_video_url) {
    return [post.reel_video_url];
  }

  const gallery = reelGallery(post);
  const filename = gallery?.filename || '';
  const textHints = [post?.description, post?.title, post?.image]
    .map((value) => String(value || '').trim())
    .filter((value) => looksLikeUploadFilename(value));

  const filenames = [...new Set([filename, ...textHints].filter(Boolean))];
  const expanded = [];
  filenames.forEach((name) => {
    expanded.push(name);
    if (!/\.[a-z0-9]{2,5}$/i.test(name)) {
      ['mp4', 'mov', 'webm', 'm4v'].forEach((ext) => expanded.push(`${name}.${ext}`));
    }
  });

  const candidates = [];
  if (gallery?.filenameUrl) candidates.push(gallery.filenameUrl);
  if (gallery?.fileUrl) candidates.push(gallery.fileUrl);
  expanded.forEach((name) => {
    candidates.push(`${API_BASE}/media/reel?name=${encodeURIComponent(name)}`);
    candidates.push(`${apiUploadsBase()}/${name}`);
    candidates.push(`${UPLOADS_BASE}/${name}`);
    if (window.__DL_ENV__?.uploads) {
      candidates.push(`${window.__DL_ENV__.uploads}/${name}`);
    }
  });

  return [...new Set(candidates.filter(Boolean))];
}

function clearSession() {
  state.token = null;
  state.user = null;
  state.creatorDashboard = null;
  state.viewerDashboard = null;
  stopCameraStream();
  localStorage.removeItem('dreamland_token');
  localStorage.removeItem('dreamland_user');
  updateAuthUi();
}

async function validateSession() {
  if (!state.token) return;
  try {
    const res = await api(API_ROUTES.profile);
    if (res.data?.user) {
      state.user = res.data.user;
      localStorage.setItem('dreamland_user', JSON.stringify(state.user));
      updateAuthUi();
    }
  } catch (err) {
    if (err.status === 401) clearSession();
  }
}

function apiTimeoutMs() {
  return DEV_ALLOW_BROWSER ? 12000 : 45000;
}

async function apiWithRetry(path, options = {}, attempts = DEV_ALLOW_BROWSER ? 1 : 3) {
  let lastErr;
  for (let i = 0; i < attempts; i += 1) {
    try {
      return await api(path, { ...options, timeoutMs: options.timeoutMs ?? apiTimeoutMs() });
    } catch (err) {
      lastErr = err;
      if (i < attempts - 1) {
        await new Promise((resolve) => window.setTimeout(resolve, 2500 * (i + 1)));
      }
    }
  }
  throw lastErr;
}

async function api(path, options = {}) {
  if (!API_BASE) {
    throw new Error('Dreamland API URL is not configured. Set DREAMLAND_API_URL on Vercel.');
  }
  const opts = { ...(options || {}) };
  const timeoutMs = opts.timeoutMs ?? apiTimeoutMs();
  delete opts.timeoutMs;
  const method = (opts.method || 'GET').toUpperCase();
  const headers = { ...(opts.headers || {}) };
  if (opts.body && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
  }
  if (state.token) headers.Authorization = `Bearer ${state.token}`;

  const url = `${API_BASE.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  let res;
  try {
    res = await fetch(url, { ...opts, headers, signal: controller.signal });
  } catch (err) {
    setApiStatus(false, 'Offline');
    if (err.name === 'AbortError') {
      throw new Error(DEV_ALLOW_BROWSER
        ? 'Request timed out — is the API running on port 8080?'
        : 'Request timed out — the API may be waking up. Try again.');
    }
    throw new Error(`Network error: ${err.message}`);
  } finally {
    clearTimeout(timer);
  }

  const text = await res.text();
  let json = {};
  if (text) {
    try {
      json = JSON.parse(text);
    } catch {
      throw new Error(`Invalid JSON from API (${res.status})`);
    }
  }

  const payload = unwrapPayload(json, res.status);
  if (res.ok && payload.ok) {
    setApiStatus(true);
    return payload;
  }

  const message = apiErrorMessage(payload) || res.statusText || 'Request failed';
  const error = new Error(message);
  error.status = payload.status || res.status;
  error.payload = payload;
  if (error.status === 401) clearSession();
  throw error;
}

async function apiUpload(path, formData, options = {}) {
  const headers = {};
  if (state.token) headers.Authorization = `Bearer ${state.token}`;
  const url = `${API_BASE.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;

  const file = formData.get('videoFile') || formData.get('imageFile');
  const fileSize = file?.size || 0;
  const timeoutMs = options.timeoutMs
    ?? Math.min(1800000, Math.max(300000, 300000 + Math.floor(fileSize / 1024)));

  const parseUploadResponse = (res, text) => {
    let json = {};
    if (text) {
      try { json = JSON.parse(text); } catch { throw new Error(`Invalid JSON from API (${res.status})`); }
    }
    const payload = unwrapPayload(json, res.status);
    if (res.ok && payload.ok) return payload;
    const message = apiErrorMessage(payload) || res.statusText || 'Upload failed';
    const error = new Error(message);
    error.status = payload.status || res.status;
    if (error.status === 401) clearSession();
    throw error;
  };

  if (typeof XMLHttpRequest !== 'undefined') {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', url);
      if (state.token) xhr.setRequestHeader('Authorization', `Bearer ${state.token}`);
      xhr.timeout = timeoutMs;
      xhr.upload.onprogress = (ev) => {
        if (!ev.lengthComputable || typeof options.onProgress !== 'function') return;
        options.onProgress(Math.min(99, Math.round((ev.loaded / ev.total) * 100)));
      };
      xhr.onload = () => {
        try {
          resolve(parseUploadResponse(
            { ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, statusText: xhr.statusText },
            xhr.responseText,
          ));
        } catch (err) {
          reject(err);
        }
      };
      xhr.onerror = () => reject(new Error('Upload failed — check your connection'));
      xhr.ontimeout = () => reject(new Error('Upload timed out — try a shorter clip or stronger Wi‑Fi'));
      xhr.onabort = () => reject(new Error('Upload cancelled'));
      xhr.send(formData);
    });
  }

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  let res;
  try {
    res = await fetch(url, { method: 'POST', headers, body: formData, signal: controller.signal });
  } catch (err) {
    if (err.name === 'AbortError') {
      throw new Error('Upload timed out — try a shorter clip or stronger Wi‑Fi');
    }
    throw new Error(`Upload failed: ${err.message}`);
  } finally {
    clearTimeout(timer);
  }

  const text = await res.text();
  return parseUploadResponse(res, text);
}

function setApiStatus(online, label) {
  apiOnline = online;
  if (!els.apiStatus || !DEV_ALLOW_BROWSER) return;
  const onFeed = document.getElementById('feed-view')?.classList.contains('active');
  els.apiStatus.hidden = onFeed;
  els.apiStatus.textContent = label || (online ? 'Live' : 'Offline');
  els.apiStatus.classList.toggle('online', online === true);
  els.apiStatus.classList.toggle('offline', online === false);
}

function showToast(message) {
  if (!els.toast) return;
  els.toast.textContent = message;
  els.toast.classList.remove('hidden');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => els.toast.classList.add('hidden'), 3200);
}

function userInitials(user) {
  const name = user?.name || user?.username || '?';
  return name.trim().charAt(0).toUpperCase();
}

function userAvatarHtml(user, className = 'dl-avatar-icon') {
  const pic = dlAccount?.avatarUrl?.(user)
    || (user?.picture && String(user.picture).startsWith('http') ? user.picture : null)
    || (user?.image ? `${UPLOADS_BASE}/${user.image}` : null);
  if (pic) {
    return `<img src="${escapeHtml(pic)}" alt="" class="${className} dl-avatar-img" />`;
  }
  return `<span class="${className}">${escapeHtml(userInitials(user))}</span>`;
}

function userAvatarBlock(user) {
  const pic = dlAccount?.avatarUrl?.(user)
    || (user?.picture && String(user.picture).startsWith('http') ? user.picture : null)
    || (user?.image ? `${UPLOADS_BASE}/${user.image}` : null);
  if (pic) return `<img src="${escapeHtml(pic)}" alt="" class="profile-avatar-img" />`;
  return escapeHtml(userInitials(user));
}

function formatCount(n) {
  const num = Number(n) || 0;
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
  return String(num);
}

function formatAppraisalStatus(status) {
  const map = {
    active: 'Live',
    pending_safety: 'Scanning',
    pending_review: 'In review',
    rejected: 'Rejected',
  };
  return map[status] || 'Processing';
}

function reelStatusBadge(reel) {
  if (Number(reel.status) === 10 && reel.appraisal_status === 'active') {
    return reel.is_paid
      ? `<span class="studio-reel-badge studio-reel-badge--premium">Exclusive · ${reel.price_credits || '—'} cr</span>`
      : '<span class="studio-reel-badge">Free · Live</span>';
  }
  if (reel.appraisal_status === 'rejected') {
    return '<span class="studio-reel-badge studio-reel-badge--rejected">Rejected</span>';
  }
  const label = formatAppraisalStatus(reel.appraisal_status);
  return `<span class="studio-reel-badge studio-reel-badge--pending">${escapeHtml(label)}</span>`;
}

function renderRejectedReelActions(reel) {
  if (reel.appraisal_status !== 'rejected') return '';
  const reason = reel.rejection_reason
    ? `<p class="studio-reel-reject-reason"><strong>Admin reason:</strong> ${escapeHtml(reel.rejection_reason)}</p>`
    : '<p class="studio-reel-reject-reason muted">This reel did not pass moderation.</p>';
  const appealPending = reel.appeal_status === 'pending';
  const appealBlock = appealPending
    ? `<p class="studio-reel-appeal-status">Appeal submitted — awaiting admin review.</p>`
    : `<label class="studio-field studio-reel-appeal-field">
        <span>Appeal message</span>
        <textarea class="studio-textarea studio-reel-appeal-input" data-reel-id="${reel.id}" rows="2" placeholder="Explain why this should be reconsidered"></textarea>
      </label>
      <button type="button" class="btn-ghost studio-reel-appeal-btn" data-reel-id="${reel.id}">Submit appeal</button>`;
  return `
    <div class="studio-reel-reject-box">
      ${reason}
      ${appealBlock}
      <button type="button" class="btn-primary studio-reel-upload-new" data-reel-id="${reel.id}">Upload new version</button>
    </div>`;
}

async function pingApi() {
  try {
    const res = await apiWithRetry(API_ROUTES.health, {}, 2);
    const services = res.data?.services || {};
    const checks = res.data?.checks || {};
    const parts = ['API connected'];
    if (checks.moderation_agent === false) parts.push('moderation offline');
    if (checks.ai_powered === false) parts.push('AI offline');
    if (checks.gemini_multimodal === false) parts.push('Gemini off');
    if (checks.live_server === false) parts.push('live offline');
    if (checks.dev_mode) parts.push('dev wallet');
    setApiStatus(true, DEV_ALLOW_BROWSER ? parts.join(' · ') : 'API connected');
    if (services.uploads) {
      localStorage.setItem('dreamland_uploads', services.uploads);
    }
  } catch {
    setApiStatus(
      false,
      DEV_ALLOW_BROWSER ? 'API offline — run .\\start-walkthrough.ps1' : 'API waking up — tap Reload feed'
    );
  }
}

function isGuest() {
  return !state.token || !state.user;
}

function mediaUrl(post) {
  const candidates = reelMediaCandidates(post);
  return candidates[0] || '';
}

function playReelVideo(video) {
  if (!video || video.tagName !== 'VIDEO') return;
  video.playsInline = true;
  video.setAttribute('playsinline', '');
  video.setAttribute('webkit-playsinline', '');
  // Browsers require muted autoplay; unmute happens via sound toggle or tap (user gesture).
  video.muted = true;
  const attempt = () => {
    video.play()
      .then(() => {
        if (!dlSocial?.isMuted?.()) {
          video.muted = false;
          video.volume = 1;
        }
      })
      .catch((err) => {
        console.warn('Reel play blocked:', err?.message || err, video.currentSrc || video.src);
      });
  };
  if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
    attempt();
    return;
  }
  video.addEventListener('loadeddata', attempt, { once: true });
  video.addEventListener('canplay', attempt, { once: true });
  video.addEventListener('error', () => {
    console.warn('Reel video failed to load:', video.currentSrc || video.src);
  }, { once: true });
  try {
    video.load();
  } catch {
    attempt();
  }
}

function showReelVideoError(reel, message) {
  if (!reel) return;
  reel.classList.add('reel--video-error');
  let banner = reel.querySelector('.reel-video-error');
  if (!banner) {
    banner = document.createElement('div');
    banner.className = 'reel-video-error';
    banner.setAttribute('role', 'alert');
    reel.appendChild(banner);
  }
  banner.textContent = message || 'Video unavailable — creator may need to re-upload.';
}

function bindReelVideoFallback(video, post) {
  if (!video || video.dataset.fallbackBound === '1') return;
  video.dataset.fallbackBound = '1';
  const candidates = reelMediaCandidates(post);
  if (candidates.length < 1) {
    showReelVideoError(video.closest('.reel'), 'No video file linked to this reel.');
    return;
  }
  let index = 0;
  video.addEventListener('error', () => {
    index += 1;
    if (index >= candidates.length) {
      console.warn('Reel video exhausted URL fallbacks for post', post?.id, candidates);
      showReelVideoError(video.closest('.reel'));
      return;
    }
    console.warn('Reel video retrying URL:', candidates[index]);
    video.src = candidates[index];
    playReelVideo(video);
  });
}

function isCreator(user = state.user) {
  if (!user) return false;
  if (user.dreamland_account_type === 'creator') return true;
  if (Number(user.role) === 4) return true;
  const email = String(user.email || '').toLowerCase();
  const username = String(user.username || '').toLowerCase();
  return email === DEV_CREATOR_LOGIN.email || username === 'dreamcreator';
}

function creatorApprovalStatus(user = state.user) {
  const status = String(user?.dreamland_creator_status || 'none').toLowerCase();
  if (status === 'approved') return 'approved';
  if (status === 'pending') return 'pending';
  if (status === 'rejected') return 'rejected';
  if (isCreator(user)) return 'pending';
  return 'none';
}

function canPublishReels(user = state.user) {
  if (!isCreator(user)) return false;
  return creatorApprovalStatus(user) === 'approved';
}

function requireApprovedCreator(actionLabel = 'publish reels') {
  if (!state.user) {
    openAuthModal('signin');
    showToast('Sign in to continue');
    return false;
  }
  if (!isCreator()) {
    showToast('Creator account required');
    switchView('signup-view');
    return false;
  }
  if (!canPublishReels()) {
    const status = creatorApprovalStatus();
    if (status === 'pending') {
      showToast('Creator approval pending — you cannot upload yet');
    } else if (status === 'rejected') {
      showToast('Creator application was not approved');
    } else {
      showToast(`Approved creator account required to ${actionLabel}`);
    }
    return false;
  }
  return true;
}

function accountHomeView() {
  return isCreator() ? 'creator-view' : 'viewer-view';
}

function updateAuthUi() {
  const btn = document.getElementById('auth-btn');
  if (btn) {
    if (state.user) {
      btn.innerHTML = userAvatarHtml(state.user, 'dl-avatar-icon');
      btn.setAttribute('aria-label', 'Account settings');
    } else {
      btn.innerHTML = '<span class="dl-avatar-icon">◉</span>';
      btn.setAttribute('aria-label', 'Sign in');
    }
  }

  const accountDock = document.querySelector('.dock-item[data-role="account"]');
  if (accountDock) {
    const creatorMode = isCreator();
    accountDock.dataset.view = creatorMode ? 'creator-view' : 'viewer-view';
    accountDock.setAttribute('aria-label', creatorMode ? 'Studio' : 'Home');
    const label = accountDock.querySelector('.dock-label');
    if (label) label.textContent = creatorMode ? 'Studio' : 'Home';
  }

  updateWalletBalance();
  if (isCreator() && state.creatorDashboard) renderCreatorDashboard(state.creatorDashboard);
  else if (!isCreator() && state.viewerDashboard) renderViewerDashboard(state.viewerDashboard);
  else if (!isCreator()) renderViewerDashboard(null);
}

function updateWalletBalance() {
  if (!els.walletCredits) return;
  if (state.user) {
    const credits = state.user.available_coin ?? state.user.availableCoin ?? 0;
    els.walletCredits.textContent = formatCount(credits);
  } else {
    els.walletCredits.textContent = '—';
  }
}

function renderViewerDashboard(data) {
  if (!els.viewerDashboard) return;
  if (!state.user || isCreator()) {
    els.viewerDashboard.innerHTML = `
      <div class="profile-guest glass-card">
        <h2>Viewer Home</h2>
        <p class="muted">Sign in as a viewer to track credits and unlocked premium reels.</p>
        <button type="button" class="btn-primary full" id="viewer-signin">Sign in</button>
        <button type="button" class="btn-ghost full" id="viewer-signup">Create free account</button>
      </div>`;
    document.getElementById('viewer-signin')?.addEventListener('click', () => openAuthModal('signin'));
    document.getElementById('viewer-signup')?.addEventListener('click', () => switchView('signup-view'));
    return;
  }
  if (!data) {
    els.viewerDashboard.innerHTML = `<div class="creator-loading glass-card"><p class="eyebrow">Viewer Home</p><h2>Loading…</h2></div>`;
    return;
  }
  const viewer = data.viewer || state.user;
  const stats = data.stats || {};
  els.viewerDashboard.innerHTML = `
    <div class="viewer-hero glass-card">
      <div class="creator-hero-top">
        <div class="profile-avatar-lg">${userAvatarBlock(viewer)}</div>
        <div>
          <p class="eyebrow">Viewer Dashboard</p>
          <h2>${escapeHtml(viewer.name || viewer.username || 'Viewer')}</h2>
          <p class="muted">@${escapeHtml(viewer.username || 'viewer')} · Watch · Play · Earn</p>
        </div>
      </div>
      <div class="creator-stats">
        <div class="stat stat-accent"><strong>${formatCount(stats.credits_balance ?? viewer.available_coin ?? 0)}</strong><span>Credits</span></div>
        <div class="stat"><strong>${formatCount(stats.premium_unlocks || 0)}</strong><span>Reel unlocks</span></div>
        <div class="stat"><strong>${formatCount(stats.live_unlocks || 0)}</strong><span>Live unlocks</span></div>
        <div class="stat"><strong>${formatCount(stats.credits_spent || 0)}</strong><span>Spent</span></div>
        <div class="stat"><strong>${formatCount(stats.daily_streak || 0)}</strong><span>Streak</span></div>
      </div>
    </div>
    <div class="viewer-actions">
      <button type="button" class="btn-primary" id="viewer-watch-feed">Continue watching</button>
      <button type="button" class="btn-primary" id="viewer-watch-live">Browse live</button>
      <button type="button" class="btn-ghost" id="viewer-refresh-dashboard">Update now</button>
      <button type="button" class="btn-ghost" id="viewer-open-wallet">Top up wallet</button>
      <button type="button" class="btn-ghost" id="viewer-open-account">Account settings</button>
    </div>
    <p class="muted viewer-refresh-hint" id="dashboard-refresh-hint">Auto-refresh every 60s</p>
    <section class="creator-section glass-card">
      <h3>Your activity</h3>
      <p class="muted">Unlock premium reels, spin daily rewards, and climb streak milestones from the Watch and Play tabs.</p>
    </section>
    <div id="viewer-streak-panel"></div>`;
  document.getElementById('viewer-watch-feed')?.addEventListener('click', () => switchView('feed-view'));
  document.getElementById('viewer-refresh-dashboard')?.addEventListener('click', () => loadViewerDashboard(true));
  document.getElementById('viewer-watch-live')?.addEventListener('click', () => {
    switchView('feed-view');
    switchFeedMode('live');
  });
  document.getElementById('viewer-open-wallet')?.addEventListener('click', () => switchView('wallet-view'));
  document.getElementById('viewer-open-account')?.addEventListener('click', () => dlAccount?.openAccount());
  dlFeatures?.loadStreakPanel(document.getElementById('viewer-streak-panel'));
}

async function loadViewerDashboard(force = false) {
  if (!state.token || isCreator()) return;
  if (!force && state.viewerDashboard) {
    renderViewerDashboard(state.viewerDashboard);
    return;
  }
  renderViewerDashboard(null);
  try {
    const res = await api(API_ROUTES.viewerDashboard);
    state.viewerDashboard = res.data;
    if (res.data?.viewer) {
      state.user = { ...state.user, ...res.data.viewer };
      localStorage.setItem('dreamland_user', JSON.stringify(state.user));
    }
    renderViewerDashboard(state.viewerDashboard);
    updateWalletBalance();
    touchDashboardRefreshHint();
  } catch (err) {
    renderViewerDashboard({
      viewer: state.user,
      stats: {
        credits_balance: state.user?.available_coin ?? 0,
        premium_unlocks: 0,
        credits_spent: 0,
        daily_streak: 0,
      },
    });
  }
}

function renderCreatorDashboard(data) {
  if (!els.creatorDashboard) return;

  if (!state.user || !isCreator()) {
    els.creatorDashboard.innerHTML = `
      <div class="studio-guest glass-card">
        <p class="eyebrow">Dreamland Studio</p>
        <h2>Creator dashboard</h2>
        <p class="muted">Sign in with a creator account to upload reels, go live, and track earnings.</p>
        <button type="button" class="btn-primary full studio-cta" id="creator-signin">Sign in as creator</button>
        <button type="button" class="btn-ghost full" id="creator-signup">Create creator account</button>
      </div>`;
    document.getElementById('creator-signin')?.addEventListener('click', () => openAuthModal('signin'));
    document.getElementById('creator-signup')?.addEventListener('click', () => {
      switchView('signup-view');
      const creatorRadio = document.querySelector('#signup-form input[value="creator"]');
      if (creatorRadio) {
        creatorRadio.checked = true;
        creatorRadio.dispatchEvent(new Event('change'));
      }
    });
    return;
  }

  if (!data) {
    els.creatorDashboard.innerHTML = `
      <div class="studio-loading glass-card">
        <p class="eyebrow">Dreamland Studio</p>
        <h2>Loading your studio…</h2>
        <p class="muted">Fetching reels, stats, and live status.</p>
      </div>`;
    return;
  }

  const creator = data.creator || state.user;
  const totals = data.totals || {};
  const reels = data.reels || [];
  const live = data.live || null;
  const liveActive = live && Number(live.status) === 1;
  const approved = canPublishReels(creator);
  const approvalStatus = creatorApprovalStatus(creator);

  els.creatorDashboard.innerHTML = `
    <div class="studio-page">
      ${!approved && isCreator(creator) ? `
      <div class="studio-approval-banner glass-card">
        <p class="eyebrow">${approvalStatus === 'rejected' ? 'Application update' : 'Creator review'}</p>
        <h3>${approvalStatus === 'rejected' ? 'Creator application not approved' : 'Awaiting creator approval'}</h3>
        <p class="muted">${approvalStatus === 'rejected'
    ? 'You can watch and play on Dreamland, but uploading and going live are disabled.'
    : 'Dreamland is reviewing your application. Tap refresh after admin approves you in the dashboard.'}</p>
        ${approvalStatus === 'pending' ? '<button type="button" class="btn-ghost full" id="creator-refresh-approval">Check approval status</button>' : ''}
      </div>` : ''}
      ${approved && isCreator(creator) ? `
      <div class="studio-approval-banner studio-approval-banner--ok glass-card">
        <p class="eyebrow">Creator approved</p>
        <h3>Studio unlocked</h3>
        <p class="muted">Upload reels, record in-app, and go live.</p>
      </div>` : ''}
      <header class="studio-hero glass-card">
        <div class="studio-hero__glow" aria-hidden="true"></div>
        <div class="studio-hero__top">
          <div class="studio-avatar-wrap">
            <div class="profile-avatar-lg studio-avatar">${userAvatarBlock(creator)}</div>
            ${liveActive ? '<span class="studio-live-pill"><span class="live-dot"></span> Live</span>' : ''}
          </div>
          <div class="studio-hero__copy">
            <span class="studio-badge">Dreamland Studio</span>
            <h2>${escapeHtml(creator.name || creator.username || 'Creator')}</h2>
            <p class="studio-handle">@${escapeHtml(creator.username || 'creator')}</p>
          </div>
        </div>
        <div class="studio-stats">
          <div class="studio-stat">
            <strong>${formatCount(totals.reels || 0)}</strong>
            <span>Reels</span>
          </div>
          <div class="studio-stat">
            <strong>${formatCount(totals.views || 0)}</strong>
            <span>Views</span>
          </div>
          <div class="studio-stat">
            <strong>${formatCount(totals.unlocks || 0)}</strong>
            <span>Unlocks</span>
          </div>
          <div class="studio-stat studio-stat--accent">
            <strong>${formatCount(totals.earned_credits || 0)}</strong>
            <span>Earned</span>
          </div>
        </div>
        <div class="studio-quick-actions">
          <button type="button" class="btn-ghost studio-account-btn" id="creator-open-account">Account settings</button>
        </div>
      </header>

      ${liveActive ? `
        <div class="studio-live-banner glass-card" id="studio-live-banner" role="button" tabindex="0">
          <span class="live-dot"></span>
          <div class="studio-live-banner__copy">
            <strong>You're live now</strong>
            <p class="muted">${escapeHtml(live.title || 'Dreamland Live')}${live.is_monetized ? ` · ${live.price_credits} credits to watch` : ' · Free to watch'}</p>
          </div>
          <span class="studio-live-banner__cta">Return to broadcast →</span>
        </div>` : ''}

      <nav class="studio-tabs" role="tablist" aria-label="Studio workspace">
        <button type="button" class="studio-tab studio-tab--active" data-studio-panel="upload" role="tab" aria-selected="true">Upload</button>
        ${state.studioDraft ? '<button type="button" class="studio-tab studio-tab--accent" data-studio-panel="edit" role="tab" aria-selected="false">Edit</button>' : ''}
        <button type="button" class="studio-tab" data-studio-panel="record" role="tab" aria-selected="false">Record</button>
        <button type="button" class="studio-tab" data-studio-panel="live" role="tab" aria-selected="false">Go live</button>
      </nav>

      <div class="studio-panels">
        <section id="studio-panel-upload" class="studio-panel studio-panel--active glass-card" role="tabpanel">
          <div class="studio-panel-head">
            <h3>Upload reel</h3>
            <p class="muted">Publish polished clips from your gallery. Max length: <strong>${escapeHtml(formatReelDurationLimit())}</strong> · max ${dlFeatures?.getMaxReelUploadMb?.() || 128} MB.</p>
          </div>
          <label class="studio-field">
            <span>Title</span>
            <input type="text" class="studio-input" id="upload-title" placeholder="Give your reel a headline" />
          </label>
          <label class="studio-field">
            <span>Description</span>
            <textarea class="studio-textarea" id="upload-desc" rows="3" placeholder="Tell viewers what this moment is about"></textarea>
          </label>
          <button type="button" class="studio-ai-btn ai-caption-btn" id="creator-ai-caption">
            <span class="studio-ai-btn__icon" aria-hidden="true">✦</span>
            <span>Gemini caption &amp; hashtags</span>
          </button>
          <label class="studio-field">
            <span>Genre / category</span>
            <div class="studio-select-wrap">
              <select class="studio-select" id="upload-category" required></select>
            </div>
          </label>
          <label class="studio-switch">
            <input type="checkbox" id="upload-premium" />
            <span class="studio-switch__track" aria-hidden="true"></span>
            <span class="studio-switch__label">Mark as premium <small>Review before pricing</small></span>
          </label>
          <label class="studio-file${approved ? '' : ' studio-file--locked'}" id="upload-file-label">
            <input type="file" id="upload-file" accept="video/*" hidden ${approved ? '' : 'disabled'} />
            <span class="studio-file__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 16V4"/><path d="m8 8 4-4 4 4"/><path d="M4 18v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2"/></svg>
            </span>
            <span class="studio-file__copy">
              <strong class="studio-file__name">Choose video file</strong>
              <small>MP4, MOV, WEBM · up to your device limit</small>
            </span>
          </label>
          <button type="button" class="btn-primary full studio-cta${approved ? '' : ' studio-cta--locked'}" id="creator-upload-btn">${state.studioDraft ? 'Continue editing' : 'Choose video to edit'}</button>
        </section>

        ${state.studioDraft ? `
        <section id="studio-panel-edit" class="studio-panel glass-card studio-panel--premium-edit" role="tabpanel" hidden>
          <div class="studio-panel-head">
            <span class="studio-premium-badge">Premium bench</span>
            <h3>Edit bench</h3>
            <p class="muted">Mix audio, apply filters, trim, caption — then publish. Max length: <strong>${escapeHtml(formatReelDurationLimit())}</strong>.</p>
          </div>
          <div class="studio-edit-preview">
            <video id="studio-edit-video" class="studio-edit-video" playsinline loop></video>
            <audio id="studio-edit-music" loop playsinline hidden></audio>
            <div id="studio-edit-overlay" class="studio-edit-overlay studio-edit-overlay--center hidden"></div>
          </div>

          <div class="studio-edit-premium-block">
            <div class="studio-edit-block-head">
              <span class="studio-edit-block-icon" aria-hidden="true">♪</span>
              <div>
                <strong>Audio studio</strong>
                <p class="muted">Mute camera audio or layer your own track.</p>
              </div>
            </div>
            <label class="studio-switch studio-switch--compact">
              <input type="checkbox" id="studio-edit-mute-original" />
              <span class="studio-switch__track" aria-hidden="true"></span>
              <span class="studio-switch__label">Mute original sound</span>
            </label>
            <label class="studio-edit-slider">
              <span>Original volume</span>
              <input type="range" id="studio-edit-original-volume" min="0" max="100" value="100" />
              <output id="studio-edit-original-volume-label">100%</output>
            </label>
            <div class="studio-edit-music-row">
              <input type="file" id="studio-edit-music-file" accept="audio/*,.mp3,.wav,.m4a,.aac,.ogg,.webm" hidden />
              <button type="button" class="btn-ghost studio-edit-music-btn" id="studio-edit-add-music">Add sound track</button>
              <button type="button" class="btn-ghost studio-edit-music-btn hidden" id="studio-edit-remove-music">Remove</button>
            </div>
            <p id="studio-edit-music-name" class="studio-edit-music-name muted">No added sound — import MP3, WAV, or M4A</p>
            <label class="studio-edit-slider">
              <span>Music volume</span>
              <input type="range" id="studio-edit-music-volume" min="0" max="100" value="85" />
              <output id="studio-edit-music-volume-label">85%</output>
            </label>
            <label class="studio-edit-slider">
              <span>Music start offset</span>
              <input type="range" id="studio-edit-music-offset" min="0" max="120" value="0" step="0.5" />
              <output id="studio-edit-music-offset-label">0:00</output>
            </label>
          </div>

          <div class="studio-edit-premium-block">
            <div class="studio-edit-block-head">
              <span class="studio-edit-block-icon" aria-hidden="true">✦</span>
              <div>
                <strong>Look &amp; pace</strong>
                <p class="muted">Cinematic filters and playback speed.</p>
              </div>
            </div>
            <div class="studio-edit-tool-row">
              <span class="studio-edit-tool-label">Filter</span>
              <div class="studio-edit-chips" id="studio-edit-filters" role="group" aria-label="Video filter">
                ${Object.entries(STUDIO_EDIT_FILTERS).map(([id, f]) => `
                  <button type="button" class="studio-edit-chip${id === 'none' ? ' studio-edit-chip--active' : ''}" data-studio-filter="${id}">${f.label}</button>`).join('')}
              </div>
            </div>
            <div class="studio-edit-tool-row">
              <span class="studio-edit-tool-label">Speed</span>
              <div class="studio-edit-chips" id="studio-edit-speeds" role="group" aria-label="Playback speed">
                ${STUDIO_EDIT_SPEEDS.map((s) => `
                  <button type="button" class="studio-edit-chip${s === 1 ? ' studio-edit-chip--active' : ''}" data-studio-speed="${s}">${s === 1 ? '1×' : `${s}×`}</button>`).join('')}
              </div>
            </div>
            <div class="studio-edit-tool-row">
              <span class="studio-edit-tool-label">Caption position</span>
              <div class="studio-edit-chips" id="studio-edit-overlay-pos" role="group" aria-label="Caption position">
                <button type="button" class="studio-edit-chip" data-studio-overlay-pos="top">Top</button>
                <button type="button" class="studio-edit-chip studio-edit-chip--active" data-studio-overlay-pos="center">Center</button>
                <button type="button" class="studio-edit-chip" data-studio-overlay-pos="bottom">Bottom</button>
              </div>
            </div>
          </div>

          <div class="studio-edit-trim">
            <div class="studio-edit-trim__labels">
              <span>Trim clip</span>
              <span id="studio-edit-trim-label" class="muted">Full clip</span>
            </div>
            <label class="studio-edit-trim__field">
              <span>Start</span>
              <input type="range" id="studio-edit-trim-start" min="0" max="100" value="0" step="0.1" />
            </label>
            <label class="studio-edit-trim__field">
              <span>End</span>
              <input type="range" id="studio-edit-trim-end" min="0" max="100" value="100" step="0.1" />
            </label>
          </div>
          <label class="studio-field">
            <span>On-screen caption</span>
            <input type="text" class="studio-input" id="studio-edit-overlay-text" placeholder="Optional text overlay on preview" maxlength="80" />
          </label>
          <label class="studio-field">
            <span>Title</span>
            <input type="text" class="studio-input" id="studio-edit-title" placeholder="Give your reel a headline" />
          </label>
          <label class="studio-field">
            <span>Description</span>
            <textarea class="studio-textarea" id="studio-edit-desc" rows="3" placeholder="Tell viewers what this moment is about"></textarea>
          </label>
          <label class="studio-field">
            <span>Hashtags</span>
            <input type="text" class="studio-input" id="studio-edit-hashtags" placeholder="#Accra #Dreamland #Creator" />
          </label>
          <button type="button" class="studio-ai-btn" id="studio-edit-ai-caption">
            <span class="studio-ai-btn__icon" aria-hidden="true">✦</span>
            <span>Gemini caption &amp; hashtags</span>
          </button>
          <label class="studio-field">
            <span>Genre / category</span>
            <div class="studio-select-wrap">
              <select class="studio-select" id="studio-edit-category" required></select>
            </div>
          </label>
          <label class="studio-switch">
            <input type="checkbox" id="studio-edit-premium" />
            <span class="studio-switch__track" aria-hidden="true"></span>
            <span class="studio-switch__label">Mark as premium <small>Review before pricing</small></span>
          </label>
          <p id="studio-edit-status" class="studio-edit-status hidden" role="status"></p>
          <div class="studio-edit-actions">
            <button type="button" class="btn-ghost studio-edit-actions__btn" id="studio-edit-discard">Discard</button>
            <button type="button" class="btn-primary studio-edit-actions__btn" id="studio-edit-publish">Publish reel</button>
          </div>
        </section>` : ''}

        <section id="studio-panel-record" class="studio-panel glass-card" role="tabpanel" hidden>
          <div class="studio-record-launch">
            <div class="studio-record-launch__visual" aria-hidden="true">
              <span class="studio-record-launch__ring"></span>
              <span class="studio-record-launch__dot"></span>
            </div>
            <h3>Record a reel</h3>
            <p class="muted">Opens full-screen camera — tap to record, flip lens, then publish.</p>
            <button type="button" class="btn-primary full studio-cta${approved ? '' : ' studio-cta--locked'}" id="creator-open-record">Open camera</button>
          </div>
        </section>

        <section id="studio-panel-live" class="studio-panel glass-card" role="tabpanel" hidden>
          <div class="studio-record-launch">
            <div class="studio-record-launch__visual studio-record-launch__visual--live" aria-hidden="true">
              <span class="studio-record-launch__ring studio-record-launch__ring--live"></span>
              <span class="live-dot studio-record-launch__dot"></span>
            </div>
            <h3>Go live</h3>
            <p class="muted">Opens full-screen broadcast studio — camera, chat, and controls outside the app shell.</p>
            <button type="button" class="btn-primary full studio-cta${approved ? '' : ' studio-cta--locked'}" id="creator-open-live">${liveActive ? 'Return to broadcast' : 'Open broadcast studio'}</button>
          </div>
        </section>
      </div>

      <div class="studio-quick glass-card">
        <button type="button" class="btn-primary studio-quick-btn" id="creator-watch-feed">Watch feed</button>
        <button type="button" class="btn-ghost studio-quick-btn" id="creator-refresh">Update now</button>
        <p class="muted studio-refresh-hint" id="dashboard-refresh-hint">Auto-refresh every 60s</p>
      </div>

      <section class="studio-library">
        <div class="studio-library-head">
          <div>
            <p class="eyebrow">Library</p>
            <h3>Your reels</h3>
          </div>
          <span class="chip">${reels.filter((r) => Number(r.status) === 10).length} live · ${reels.length} total</span>
        </div>
        <div class="studio-reel-grid">
          ${reels.length ? reels.map((reel) => `
            <article class="studio-reel-card glass-card${reel.appraisal_status === 'rejected' ? ' studio-reel-card--rejected' : ''}">
              <div class="studio-reel-card__top">
                <h4>${escapeHtml(String(reel.title || 'Untitled reel').replace(/^Dreamland Demo:\\s*/i, ''))}</h4>
                ${reelStatusBadge(reel)}
              </div>
              <p class="studio-reel-desc muted">${escapeHtml(reel.description || 'No description yet')}</p>
              ${renderRejectedReelActions(reel)}
              <div class="studio-reel-metrics">
                <span>${formatCount(reel.total_view)} views</span>
                <span>${formatCount(reel.unlocks)} unlocks</span>
                <span class="studio-reel-earn">${formatCount(reel.earned_credits)} earned</span>
              </div>
            </article>`).join('') : `
            <div class="studio-empty glass-card">
              <p class="eyebrow">Start creating</p>
              <p class="muted">No reels yet — upload or record your first one above.</p>
            </div>`}
        </div>
      </section>
    </div>`;

  bindCreatorStudioEvents();
  dlFeatures?.renderCategorySelect(document.getElementById('upload-category'));
  if (state.studioDraft) {
    dlFeatures?.renderCategorySelect(document.getElementById('studio-edit-category'));
    bindStudioEditBench();
    if (state.studioDraft.openEditTab) {
      activateStudioPanel('edit');
      state.studioDraft.openEditTab = false;
    }
  }
}

function bindCreatorStudioEvents() {
  document.querySelectorAll('[data-studio-panel]').forEach((tab) => {
    tab.addEventListener('click', () => {
      const panelId = tab.dataset.studioPanel;
      document.querySelectorAll('[data-studio-panel]').forEach((btn) => {
        const active = btn === tab;
        btn.classList.toggle('studio-tab--active', active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      document.querySelectorAll('.studio-panel').forEach((panel) => {
        const active = panel.id === `studio-panel-${panelId}`;
        panel.classList.toggle('studio-panel--active', active);
        panel.hidden = !active;
      });
      if (panelId === 'live') {
        openLiveBroadcast();
      }
    });
  });

  document.getElementById('creator-open-record')?.addEventListener('click', openRecordCapture);
  document.getElementById('creator-open-live')?.addEventListener('click', openLiveBroadcast);
  document.getElementById('creator-open-account')?.addEventListener('click', () => dlAccount?.openAccount());
  document.getElementById('studio-live-banner')?.addEventListener('click', openLiveBroadcast);
  document.getElementById('studio-live-banner')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      openLiveBroadcast();
    }
  });

  document.getElementById('upload-file')?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    const nameEl = document.querySelector('#upload-file-label .studio-file__name');
    if (nameEl) nameEl.textContent = file?.name || 'Choose video file';
    if (!file) return;
    if (!requireApprovedCreator('upload reels')) {
      e.target.value = '';
      if (nameEl) nameEl.textContent = 'Choose video file';
      return;
    }
    if (file.size > maxUploadBytes()) {
      const mb = dlFeatures?.getMaxReelUploadMb?.() || 128;
      showToast(`Video must be under ${mb} MB`);
      e.target.value = '';
      if (nameEl) nameEl.textContent = 'Choose video file';
      return;
    }
    openStudioEditBench(file, file.name, 'file');
  });

  document.getElementById('creator-upload-btn')?.addEventListener('click', () => {
    if (!requireApprovedCreator('upload reels')) return;
    if (state.studioDraft) {
      activateStudioPanel('edit');
      return;
    }
    document.getElementById('upload-file')?.click();
  });
  document.getElementById('creator-watch-feed')?.addEventListener('click', () => switchView('feed-view'));
  document.getElementById('creator-refresh')?.addEventListener('click', () => loadCreatorDashboard(true));
  document.getElementById('creator-refresh-approval')?.addEventListener('click', async () => {
    await validateSession();
    await loadCreatorDashboard(true);
    if (canPublishReels()) showToast('You are approved — studio unlocked!');
    else showToast('Still pending admin approval');
  });
  document.getElementById('creator-ai-caption')?.addEventListener('click', () => {
    dlAi?.applyCaptionAssist(
      document.getElementById('upload-title'),
      document.getElementById('upload-desc'),
      document.getElementById('upload-category'),
    );
  });

  document.querySelectorAll('.studio-reel-appeal-btn').forEach((btn) => {
    btn.addEventListener('click', () => submitReelAppeal(btn.dataset.reelId));
  });
  document.querySelectorAll('.studio-reel-upload-new').forEach((btn) => {
    btn.addEventListener('click', () => {
      activateStudioPanel('upload');
      showToast('Upload a new version with your fixes');
      document.getElementById('upload-title')?.focus();
    });
  });
}

async function submitReelAppeal(reelId) {
  const id = Number(reelId);
  if (!id) return;
  const input = document.querySelector(`.studio-reel-appeal-input[data-reel-id="${id}"]`);
  const message = input?.value?.trim() || '';
  if (!message) {
    showToast('Explain why your reel should be reconsidered');
    input?.focus();
    return;
  }
  try {
    const res = await api(API_ROUTES.creatorAppealReel, {
      method: 'POST',
      body: JSON.stringify({ post_id: id, message }),
    });
    showToast(res.message || 'Appeal submitted');
    state.creatorDashboard = null;
    await loadCreatorDashboard(true);
  } catch (err) {
    showToast(err.message || 'Could not submit appeal');
  }
}

async function stopCameraStream() {
  if (mediaRecorder && mediaRecorder.state === 'recording') {
    mediaRecorder.stop();
  }
  mediaRecorder = null;
  if (cameraStream) {
    cameraStream.getTracks().forEach((t) => t.stop());
    cameraStream = null;
  }
}

function bindRecordCaptureUi() {
  document.getElementById('record-capture-close')?.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
      showToast('Tap End record to save your clip');
      return;
    }
    if (recordedChunks.length) {
      finishRecordCaptureToStudio();
      return;
    }
    closeRecordCapture({ discard: true });
  });
  document.getElementById('record-capture-flip')?.addEventListener('click', flipRecordCamera);
  document.getElementById('record-capture-shutter')?.addEventListener('click', toggleRecordCaptureRecording);
  document.getElementById('record-capture-end')?.addEventListener('click', stopRecordCaptureRecording);
  document.getElementById('record-capture-retake')?.addEventListener('click', retakeRecordCapture);
  document.getElementById('record-capture-publish')?.addEventListener('click', publishRecordCapture);
  document.getElementById('record-capture-ai')?.addEventListener('click', () => {
    dlAi?.applyCaptionAssist(
      document.getElementById('record-capture-title'),
      document.getElementById('record-capture-desc'),
      document.getElementById('record-capture-category'),
    );
  });
}

function setRecordCaptureUi(mode = 'camera') {
  const overlay = document.getElementById('record-capture');
  const stage = document.getElementById('record-capture-stage');
  const review = document.getElementById('record-capture-review');
  const timer = document.getElementById('record-capture-timer');
  const flip = document.getElementById('record-capture-flip');
  const hint = document.getElementById('record-capture-hint');
  const shutter = document.getElementById('record-capture-shutter');
  const endBtn = document.getElementById('record-capture-end');
  if (!overlay) return;
  overlay.dataset.mode = mode;
  const showStage = mode === 'camera' || mode === 'recording';
  stage?.classList.toggle('hidden', !showStage);
  review?.classList.toggle('hidden', mode !== 'review');
  timer?.classList.toggle('hidden', mode !== 'recording');
  flip?.toggleAttribute('disabled', mode === 'recording' || mode === 'review');
  hint?.classList.toggle('hidden', mode === 'recording');
  shutter?.classList.toggle('hidden', mode === 'recording');
  endBtn?.classList.toggle('hidden', mode !== 'recording');
  overlay.classList.toggle('record-capture--recording', mode === 'recording');
}

async function openRecordCapture() {
  if (!requireApprovedCreator('record reels')) return;
  if (recordCaptureOpen) return;
  recordCaptureOpen = true;
  recordedChunks = [];
  recordElapsedSec = 0;
  recordFacingMode = 'user';

  const overlay = document.getElementById('record-capture');
  overlay?.classList.remove('hidden');
  overlay?.setAttribute('aria-hidden', 'false');
  els.appShell?.classList.add('app-shell--recording');
  document.body.classList.add('record-capture-open');

  dlFeatures?.renderCategorySelect(document.getElementById('record-capture-category'));
  setRecordCaptureUi('camera');
  document.getElementById('record-capture-shutter')?.classList.remove('record-capture-shutter--recording');
  const recordTitle = document.getElementById('record-capture-title');
  const recordDesc = document.getElementById('record-capture-desc');
  if (recordTitle) recordTitle.value = document.getElementById('upload-title')?.value || '';
  if (recordDesc) recordDesc.value = document.getElementById('upload-desc')?.value || '';
  const premium = document.getElementById('record-capture-premium');
  if (premium) premium.checked = document.getElementById('upload-premium')?.checked || false;

  await startRecordCamera();
}

async function closeRecordCapture(options = {}) {
  const { discard = true } = options;
  if (!recordCaptureOpen) return;

  const wasRecording = mediaRecorder && mediaRecorder.state === 'recording';
  if (wasRecording) {
    stopRecordTimer();
    mediaRecorder.stop();
    await new Promise((resolve) => {
      const prev = mediaRecorder?.onstop;
      if (mediaRecorder) {
        mediaRecorder.onstop = () => {
          if (typeof prev === 'function') prev();
          resolve();
        };
      } else {
        resolve();
      }
    });
  }

  if (!discard && recordedChunks.length) {
    recordCaptureOpen = false;
    await finishRecordCaptureToStudio();
    return;
  }

  recordCaptureOpen = false;
  stopRecordTimer();
  mediaRecorder = null;
  recordedChunks = [];

  const playback = document.getElementById('record-capture-playback');
  const video = document.getElementById('record-capture-video');
  if (video) {
    video.srcObject = null;
    video.classList.remove('record-capture__video--mirror');
  }
  if (playback) {
    playback.pause();
    playback.removeAttribute('src');
    playback.srcObject = null;
  }

  await stopCameraStream();

  document.getElementById('record-capture')?.classList.add('hidden');
  document.getElementById('record-capture')?.setAttribute('aria-hidden', 'true');
  els.appShell?.classList.remove('app-shell--recording');
  document.body.classList.remove('record-capture-open');
  setRecordCaptureUi('camera');
}

async function startRecordCamera() {
  try {
    if (cameraStream) {
      cameraStream.getTracks().forEach((t) => t.stop());
      cameraStream = null;
    }
    cameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: recordFacingMode },
      audio: true,
    });
    const video = document.getElementById('record-capture-video');
    if (video) {
      video.srcObject = cameraStream;
      video.classList.toggle('record-capture__video--mirror', recordFacingMode === 'user');
      await video.play().catch(() => {});
    }
  } catch (err) {
    showToast('Camera access denied or unavailable');
    closeRecordCapture();
  }
}

async function flipRecordCamera() {
  if (mediaRecorder && mediaRecorder.state === 'recording') return;
  recordFacingMode = recordFacingMode === 'user' ? 'environment' : 'user';
  await startRecordCamera();
}

function formatRecordTimer(sec) {
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
}

function stopRecordTimer() {
  if (recordTimerInterval) {
    clearInterval(recordTimerInterval);
    recordTimerInterval = null;
  }
}

function startRecordTimer() {
  stopRecordTimer();
  recordElapsedSec = 0;
  const timerEl = document.getElementById('record-capture-timer');
  if (timerEl) timerEl.textContent = formatRecordTimer(0);
  recordTimerInterval = setInterval(() => {
    recordElapsedSec += 1;
    if (timerEl) timerEl.textContent = formatRecordTimer(recordElapsedSec);
    if (recordElapsedSec >= maxReelDurationSeconds()) stopRecordCaptureRecording();
  }, 1000);
}

function toggleRecordCaptureRecording() {
  if (mediaRecorder && mediaRecorder.state === 'recording') {
    stopRecordCaptureRecording();
    return;
  }
  if (!cameraStream) {
    showToast('Camera not ready');
    return;
  }
  recordedChunks = [];
  const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus')
    ? 'video/webm;codecs=vp9,opus'
    : (MediaRecorder.isTypeSupported('video/webm') ? 'video/webm' : '');
  mediaRecorder = new MediaRecorder(cameraStream, mimeType ? { mimeType } : undefined);
  mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) recordedChunks.push(e.data); };
  mediaRecorder.onstop = showRecordCaptureReview;
  mediaRecorder.start(250);
  document.getElementById('record-capture-shutter')?.classList.add('record-capture-shutter--recording');
  setRecordCaptureUi('recording');
  startRecordTimer();
}

function stopRecordCaptureRecording() {
  if (!mediaRecorder || mediaRecorder.state !== 'recording') return;
  mediaRecorder.stop();
  stopRecordTimer();
  document.getElementById('record-capture-shutter')?.classList.remove('record-capture-shutter--recording');
}

function showRecordCaptureReview() {
  if (!recordedChunks.length) {
    setRecordCaptureUi('camera');
    return;
  }
  finishRecordCaptureToStudio();
}

async function finishRecordCaptureToStudio() {
  if (!recordedChunks.length) {
    showToast('Record a clip first');
    return;
  }
  const blob = new Blob(recordedChunks, { type: recordedChunks[0]?.type || 'video/webm' });
  const title = document.getElementById('record-capture-title')?.value?.trim()
    || document.getElementById('upload-title')?.value?.trim()
    || `Dreamland take ${new Date().toLocaleTimeString()}`;
  const description = document.getElementById('record-capture-desc')?.value?.trim()
    || document.getElementById('upload-desc')?.value?.trim()
    || '';
  const categoryId = document.getElementById('record-capture-category')?.value
    || document.getElementById('upload-category')?.value
    || '';
  const isPaid = document.getElementById('record-capture-premium')?.checked
    || document.getElementById('upload-premium')?.checked;

  recordedChunks = [];
  mediaRecorder = null;
  stopRecordTimer();

  const playback = document.getElementById('record-capture-playback');
  const video = document.getElementById('record-capture-video');
  if (video) video.srcObject = null;
  if (playback) {
    playback.pause();
    playback.removeAttribute('src');
  }

  recordCaptureOpen = false;
  document.getElementById('record-capture')?.classList.add('hidden');
  document.getElementById('record-capture')?.setAttribute('aria-hidden', 'true');
  els.appShell?.classList.remove('app-shell--recording');
  document.body.classList.remove('record-capture-open');
  setRecordCaptureUi('camera');
  await stopCameraStream();

  switchView('creator-view');
  openStudioEditBench(blob, `recording-${Date.now()}.webm`, 'record', {
    title,
    description,
    categoryId,
    isPaid,
  });
  showToast('Clip saved — finish editing in Studio');
}

function retakeRecordCapture() {
  const playback = document.getElementById('record-capture-playback');
  if (playback?.src) URL.revokeObjectURL(playback.src);
  if (playback) {
    playback.pause();
    playback.removeAttribute('src');
  }
  recordedChunks = [];
  mediaRecorder = null;
  setRecordCaptureUi('camera');
  startRecordCamera();
}

async function publishRecordCapture() {
  await finishRecordCaptureToStudio();
}

async function startLiveBroadcastCamera() {
  if (recordCaptureOpen) return;
  try {
    if (cameraStream && !liveBroadcastOpen) {
      cameraStream.getTracks().forEach((t) => t.stop());
      cameraStream = null;
    }
    if (!cameraStream) {
      cameraStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: liveFacingMode, width: { ideal: 720 }, height: { ideal: 1280 } },
        audio: true,
      });
    }
    const video = document.getElementById('live-broadcast-video');
    if (video) {
      video.srcObject = cameraStream;
      video.classList.toggle('live-broadcast__video--mirror', liveFacingMode === 'user');
    }
  } catch {
    showToast('Camera access denied or unavailable');
  }
}

async function flipLiveBroadcastCamera() {
  liveFacingMode = liveFacingMode === 'user' ? 'environment' : 'user';
  if (cameraStream) {
    cameraStream.getTracks().forEach((t) => t.stop());
    cameraStream = null;
  }
  await startLiveBroadcastCamera();
}

function setLiveBroadcastUiStage(isLive) {
  document.getElementById('live-broadcast-setup')?.classList.toggle('hidden', isLive);
  document.getElementById('live-broadcast-stage')?.classList.toggle('hidden', !isLive);
  document.getElementById('live-broadcast-badge')?.classList.toggle('hidden', !isLive);
  document.getElementById('live-broadcast-viewers')?.classList.toggle('hidden', !isLive);
  document.getElementById('live-broadcast-flip')?.classList.toggle('hidden', isLive);
}

function updateLiveBroadcastViewerCount(count) {
  const label = `${formatCount(count || 0)} watching`;
  const hostEl = document.getElementById('live-broadcast-viewers');
  const watchEl = document.getElementById('live-watch-viewers');
  if (hostEl) hostEl.textContent = label;
  if (watchEl) watchEl.textContent = label;
}

async function resumeLiveBroadcast() {
  const live = state.creatorDashboard?.live;
  if (!live?.rtc) return;
  try {
    const liveClient = await ensureDreamlandLive();
    if (!liveClient.isBroadcasting()) {
      await liveClient.startBroadcast({
        rtc: live.rtc,
        userId: state.user?.id,
        localStream: cameraStream,
        onStats: (stats) => {
          if (stats?.viewerCount != null) updateLiveBroadcastViewerCount(stats.viewerCount);
        },
        onChat: (msg) => appendLiveChatMessage(msg, 'live-broadcast-chat-list'),
      });
    }
  } catch (err) {
    console.warn('Resume live broadcast failed:', err.message);
  }
}

async function openLiveBroadcast() {
  if (!requireApprovedCreator('go live')) return;
  const overlay = document.getElementById('live-broadcast');
  if (!overlay) return;

  const live = state.creatorDashboard?.live;
  liveBroadcastActive = Boolean(live && Number(live.status) === 1);
  liveBroadcastOpen = true;

  overlay.classList.remove('hidden');
  overlay.setAttribute('aria-hidden', 'false');
  document.body.classList.add('live-broadcast-open');

  const titleInput = document.getElementById('live-title');
  const monetizedInput = document.getElementById('live-monetized');
  const priceInput = document.getElementById('live-price');
  if (titleInput) titleInput.value = live?.title || titleInput.value || 'Dreamland Live';
  if (monetizedInput) monetizedInput.checked = Boolean(live?.is_monetized);
  if (priceInput) priceInput.value = live?.price_credits || priceInput.value || '15';
  document.querySelector('.live-broadcast__price-field')?.classList.toggle('hidden', !monetizedInput?.checked);

  const chatList = document.getElementById('live-broadcast-chat-list');
  if (chatList) chatList.innerHTML = '';

  setLiveBroadcastUiStage(liveBroadcastActive);
  await startLiveBroadcastCamera();

  if (liveBroadcastActive) {
    updateLiveBroadcastViewerCount(live.viewer_count || 1);
    await resumeLiveBroadcast();
  }
}

async function closeLiveBroadcast(forceEnd = false) {
  if (liveBroadcastActive) {
    if (forceEnd) {
      await endLiveSession();
      return;
    }
    showToast('End your broadcast before closing');
    return;
  }

  liveBroadcastOpen = false;
  document.getElementById('live-broadcast')?.classList.add('hidden');
  document.getElementById('live-broadcast')?.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('live-broadcast-open');

  const video = document.getElementById('live-broadcast-video');
  if (video) {
    video.srcObject = null;
    video.classList.remove('live-broadcast__video--mirror');
  }
  if (cameraStream && !recordCaptureOpen) {
    cameraStream.getTracks().forEach((t) => t.stop());
    cameraStream = null;
  }
  setLiveBroadcastUiStage(false);
}

async function startLiveSession() {
  const title = document.getElementById('live-title')?.value?.trim() || 'Dreamland Live';
  const isMonetized = document.getElementById('live-monetized')?.checked ? '1' : '0';
  const price = document.getElementById('live-price')?.value || '15';
  const startBtn = document.getElementById('live-broadcast-start');

  if (startBtn) {
    startBtn.disabled = true;
    startBtn.textContent = 'Starting…';
  }

  try {
    if (!cameraStream) await startLiveBroadcastCamera();
    const form = new FormData();
    form.append('title', title);
    form.append('is_monetized', isMonetized);
    form.append('price_credits', price);

    const res = await apiUpload(API_ROUTES.creatorStartLive, form);
    const live = res.data?.live;
    if (live?.rtc) {
      const liveClient = await ensureDreamlandLive();
      await liveClient.startBroadcast({
        rtc: live.rtc,
        userId: state.user?.id,
        localStream: cameraStream,
        onStats: (stats) => {
          if (stats?.viewerCount != null) updateLiveBroadcastViewerCount(stats.viewerCount);
        },
        onChat: (msg) => appendLiveChatMessage(msg, 'live-broadcast-chat-list'),
      });
    }

    liveBroadcastActive = true;
    setLiveBroadcastUiStage(true);
    updateLiveBroadcastViewerCount(1);
    if (liveMaxTimer) clearTimeout(liveMaxTimer);
    const liveLimitSec = maxLiveDurationSeconds();
    if (liveLimitSec > 0) {
      liveMaxTimer = window.setTimeout(async () => {
        if (!liveBroadcastActive) return;
        showToast(`Live limit reached (${Math.round(liveLimitSec / 60)} min)`);
        await endLiveSession();
      }, liveLimitSec * 1000);
    }
    showToast(res.message || 'You are live');
    state.creatorDashboard = null;
    await loadCreatorDashboard(true);
  } catch (err) {
    showToast(err.message || 'Could not go live');
  } finally {
    if (startBtn) {
      startBtn.disabled = false;
      startBtn.textContent = 'Go live';
    }
  }
}

async function endLiveSession() {
  if (liveMaxTimer) {
    clearTimeout(liveMaxTimer);
    liveMaxTimer = null;
  }
  const endBtn = document.getElementById('live-broadcast-end');
  if (endBtn) {
    endBtn.disabled = true;
    endBtn.textContent = 'Ending…';
  }
  try {
    if (dreamlandLive) await dreamlandLive.stopBroadcast();
    const res = await api(API_ROUTES.creatorEndLive, { method: 'POST', body: JSON.stringify({}) });
    liveBroadcastActive = false;
    showToast(res.message || 'Live ended');
    state.creatorDashboard = null;
    await loadCreatorDashboard(true);
    await closeLiveBroadcast(false);
  } catch (err) {
    showToast(err.message || 'Could not end live');
  } finally {
    if (endBtn) {
      endBtn.disabled = false;
      endBtn.innerHTML = '<span class="live-dot"></span><span>End broadcast</span>';
    }
  }
}

function setUploadBusy(busy, label = 'Uploading…') {
  ['creator-upload-btn', 'studio-edit-publish', 'record-capture-publish'].forEach((id) => {
    const btn = document.getElementById(id);
    if (!btn || btn.offsetParent === null) return;
    if (busy) {
      if (!btn.dataset.prevLabel) btn.dataset.prevLabel = btn.textContent;
      btn.disabled = true;
      btn.textContent = label;
    } else {
      btn.disabled = false;
      btn.textContent = btn.dataset.prevLabel || (id === 'studio-edit-publish' ? 'Publish reel' : 'Upload reel');
      delete btn.dataset.prevLabel;
    }
  });
}

function setStudioEditStatus(message, isError = false) {
  const el = document.getElementById('studio-edit-status');
  if (!el) return;
  if (!message) {
    el.classList.add('hidden');
    el.textContent = '';
    return;
  }
  el.textContent = message;
  el.classList.toggle('studio-edit-status--error', isError);
  el.classList.remove('hidden');
}

function activateStudioPanel(panelId) {
  document.querySelectorAll('[data-studio-panel]').forEach((btn) => {
    const active = btn.dataset.studioPanel === panelId;
    btn.classList.toggle('studio-tab--active', active);
    btn.setAttribute('aria-selected', active ? 'true' : 'false');
  });
  document.querySelectorAll('.studio-panel').forEach((panel) => {
    const active = panel.id === `studio-panel-${panelId}`;
    panel.classList.toggle('studio-panel--active', active);
    panel.hidden = !active;
  });
}

function closeStudioEditBench() {
  stopStudioEditMusic();
  if (state.studioDraft?.objectUrl) URL.revokeObjectURL(state.studioDraft.objectUrl);
  if (state.studioDraft?.musicObjectUrl) URL.revokeObjectURL(state.studioDraft.musicObjectUrl);
  state.studioDraft = null;
  setStudioEditStatus('');
  const fileInput = document.getElementById('upload-file');
  if (fileInput) fileInput.value = '';
  const nameEl = document.querySelector('#upload-file-label .studio-file__name');
  if (nameEl) nameEl.textContent = 'Choose video file';
}

function getStudioEditFilterCss(filterId = 'none') {
  return STUDIO_EDIT_FILTERS[filterId]?.css || 'none';
}

function stopStudioEditMusic() {
  if (studioEditMusicEl) {
    studioEditMusicEl.pause();
    studioEditMusicEl.removeAttribute('src');
    studioEditMusicEl.load();
  }
  const previewMusic = document.getElementById('studio-edit-music');
  if (previewMusic) {
    previewMusic.pause();
    previewMusic.removeAttribute('src');
    previewMusic.load();
  }
}

function setStudioEditMusicName(text) {
  const el = document.getElementById('studio-edit-music-name');
  if (el) el.textContent = text;
}

function loadStudioEditMusic(file) {
  const draft = state.studioDraft;
  if (!draft || !file) return;
  if (file.size > 25 * 1024 * 1024) {
    showToast('Audio track must be under 25MB');
    return;
  }
  if (draft.musicObjectUrl) URL.revokeObjectURL(draft.musicObjectUrl);
  draft.musicBlob = file;
  draft.musicFilename = file.name;
  draft.musicObjectUrl = URL.createObjectURL(file);
  setStudioEditMusicName(file.name);
  document.getElementById('studio-edit-remove-music')?.classList.remove('hidden');
  updateStudioEditPreview();
  showToast('Sound track added');
}

function clearStudioEditMusic() {
  const draft = state.studioDraft;
  if (!draft) return;
  if (draft.musicObjectUrl) URL.revokeObjectURL(draft.musicObjectUrl);
  draft.musicBlob = null;
  draft.musicFilename = '';
  draft.musicObjectUrl = null;
  draft.musicStart = 0;
  stopStudioEditMusic();
  setStudioEditMusicName('No added sound — import MP3, WAV, or M4A');
  document.getElementById('studio-edit-remove-music')?.classList.add('hidden');
  const fileInput = document.getElementById('studio-edit-music-file');
  if (fileInput) fileInput.value = '';
  const offsetInput = document.getElementById('studio-edit-music-offset');
  if (offsetInput) offsetInput.value = '0';
  updateStudioEditPreview();
}

function syncStudioEditMusicPlayback(video, draft) {
  const music = document.getElementById('studio-edit-music');
  if (!music || !draft?.musicObjectUrl) return;
  if (music.src !== draft.musicObjectUrl) {
    music.src = draft.musicObjectUrl;
    music.load();
  }
  music.volume = draft.musicVolume ?? 0.85;
  const start = draft.trimStart || 0;
  const offset = draft.musicStart || 0;
  const target = Math.max(0, (video.currentTime - start) + offset);
  if (Math.abs(music.currentTime - target) > 0.35) {
    music.currentTime = target % (music.duration || target || 1);
  }
  if (!video.paused && music.paused) music.play().catch(() => {});
  if (video.paused && !music.paused) music.pause();
}

function normalizeStudioFilename(filename, mimeType = 'video/webm') {
  const base = String(filename || 'dreamland-reel').trim() || 'dreamland-reel';
  if (/\.(webm|mp4|mov|m4v)$/i.test(base)) return base;
  const ext = mimeType.includes('mp4') ? 'mp4' : 'webm';
  return `${base.replace(/\.[^.]+$/, '')}.${ext}`;
}

function toStudioUploadFile(blob, filename) {
  const mimeType = blob?.type || 'video/webm';
  const safeName = normalizeStudioFilename(filename, mimeType);
  if (blob instanceof File && blob.name === safeName && blob.type) return blob;
  return new File([blob], safeName, { type: mimeType });
}

async function ensureStudioDraftBlob(draft) {
  if (draft?.blob instanceof Blob && draft.blob.size > 1024) {
    return draft.blob;
  }
  if (!draft?.objectUrl) {
    throw new Error('No video in edit bench — record or upload again');
  }
  const res = await fetch(draft.objectUrl);
  if (!res.ok) {
    throw new Error('Could not read your clip — record or upload again');
  }
  const blob = await res.blob();
  if (!blob || blob.size < 1024) {
    throw new Error('Video file is empty — record or upload again');
  }
  draft.blob = blob;
  return blob;
}

function openStudioEditBench(blob, filename, source = 'file', seed = {}) {
  if (!requireApprovedCreator('upload reels')) return;
  if (!blob) return;
  if (blob.size > maxUploadBytes()) {
    const mb = dlFeatures?.getMaxReelUploadMb?.() || 128;
    showToast(`Video must be under ${mb} MB`);
    return;
  }
  closeStudioEditBench();
  state.studioDraft = {
    blob,
    filename: normalizeStudioFilename(filename, blob.type || 'video/webm'),
    source,
    objectUrl: URL.createObjectURL(blob),
    duration: 0,
    trimStart: 0,
    trimEnd: null,
    openEditTab: true,
    seed,
    muteOriginal: false,
    originalVolume: 1,
    musicBlob: null,
    musicFilename: '',
    musicObjectUrl: null,
    musicVolume: 0.85,
    musicStart: 0,
    filter: 'none',
    speed: 1,
    overlayPosition: 'center',
  };

  const rerender = () => {
    if (state.creatorDashboard) renderCreatorDashboard(state.creatorDashboard);
    else loadCreatorDashboard(true);
  };

  if (document.getElementById('creator-dashboard')?.querySelector('.studio-page')) {
    rerender();
  } else {
    switchView('creator-view');
    rerender();
  }
}

function formatEditTime(sec) {
  const s = Math.max(0, Math.floor(sec));
  const m = Math.floor(s / 60);
  const r = s % 60;
  return `${m}:${String(r).padStart(2, '0')}`;
}

function updateStudioEditPreview() {
  const draft = state.studioDraft;
  if (!draft) return;
  const video = document.getElementById('studio-edit-video');
  const overlay = document.getElementById('studio-edit-overlay');
  const overlayInput = document.getElementById('studio-edit-overlay-text');
  const trimLabel = document.getElementById('studio-edit-trim-label');
  const startInput = document.getElementById('studio-edit-trim-start');
  const endInput = document.getElementById('studio-edit-trim-end');

  if (video) {
    video.style.filter = getStudioEditFilterCss(draft.filter);
    video.playbackRate = draft.speed || 1;
    video.muted = Boolean(draft.muteOriginal);
    video.volume = draft.muteOriginal ? 0 : Math.min(1, draft.originalVolume ?? 1);
  }

  if (video && video.src !== draft.objectUrl) {
    video.src = draft.objectUrl;
    video.load();
    video.onloadedmetadata = () => {
      draft.duration = video.duration || 0;
      const maxDur = maxReelDurationSeconds();
      if (draft.duration > maxDur + 0.5) {
        showToast(`Clip is ${Math.ceil(draft.duration)}s — max is ${formatReelDurationLimit()}. Trim before publishing.`);
      }
      draft.trimEnd = draft.duration;
      if (startInput) {
        startInput.max = String(draft.duration || 100);
        startInput.value = '0';
      }
      if (endInput) {
        endInput.max = String(draft.duration || 100);
        endInput.value = String(draft.duration || 100);
      }
      const offsetInput = document.getElementById('studio-edit-music-offset');
      if (offsetInput) offsetInput.max = String(Math.max(30, Math.ceil(draft.duration || 120)));
      updateStudioEditPreview();
    };
    video.onplay = () => {
      if (draft.musicObjectUrl) syncStudioEditMusicPlayback(video, draft);
    };
    video.onpause = () => {
      document.getElementById('studio-edit-music')?.pause();
    };
    video.ontimeupdate = () => {
      const end = draft.trimEnd ?? draft.duration;
      if (end && video.currentTime >= end - 0.05) {
        video.currentTime = draft.trimStart || 0;
      }
      if (draft.musicObjectUrl) syncStudioEditMusicPlayback(video, draft);
    };
    video.play().catch(() => {});
  }

  const overlayText = overlayInput?.value?.trim() || '';
  if (overlay) {
    overlay.textContent = overlayText;
    overlay.classList.toggle('hidden', !overlayText);
    overlay.classList.remove('studio-edit-overlay--top', 'studio-edit-overlay--center', 'studio-edit-overlay--bottom');
    overlay.classList.add(`studio-edit-overlay--${draft.overlayPosition || 'center'}`);
  }

  const start = Number(startInput?.value || draft.trimStart || 0);
  const end = Number(endInput?.value || draft.trimEnd || draft.duration || 0);
  draft.trimStart = start;
  draft.trimEnd = end;
  if (trimLabel && draft.duration) {
    const clipLen = Math.max(0, end - start);
    trimLabel.textContent = `${formatEditTime(start)} – ${formatEditTime(end)} · ${formatEditTime(clipLen)}`;
  }
  if (video && draft.duration && video.currentTime < start) video.currentTime = start;

  const music = document.getElementById('studio-edit-music');
  if (music && draft.musicObjectUrl) {
    if (music.src !== draft.musicObjectUrl) {
      music.src = draft.musicObjectUrl;
      music.load();
    }
    music.volume = draft.musicVolume ?? 0.85;
  } else if (music) {
    music.pause();
    music.removeAttribute('src');
    music.load();
  }

  const origVolLabel = document.getElementById('studio-edit-original-volume-label');
  const musicVolLabel = document.getElementById('studio-edit-music-volume-label');
  const musicOffsetLabel = document.getElementById('studio-edit-music-offset-label');
  if (origVolLabel) origVolLabel.textContent = `${Math.round((draft.originalVolume ?? 1) * 100)}%`;
  if (musicVolLabel) musicVolLabel.textContent = `${Math.round((draft.musicVolume ?? 0.85) * 100)}%`;
  if (musicOffsetLabel) musicOffsetLabel.textContent = formatEditTime(draft.musicStart || 0);
}

function bindStudioEditBench() {
  const draft = state.studioDraft;
  const panel = document.getElementById('studio-panel-edit');
  if (!draft || !panel) return;

  const seed = draft.seed || {};
  const titleEl = document.getElementById('studio-edit-title');
  const descEl = document.getElementById('studio-edit-desc');
  const catEl = document.getElementById('studio-edit-category');
  const premiumEl = document.getElementById('studio-edit-premium');

  if (titleEl) titleEl.value = seed.title || document.getElementById('upload-title')?.value || draft.filename || '';
  if (descEl) descEl.value = seed.description || document.getElementById('upload-desc')?.value || '';
  if (catEl && seed.categoryId) catEl.value = seed.categoryId;
  else if (catEl && document.getElementById('upload-category')?.value) catEl.value = document.getElementById('upload-category').value;
  if (premiumEl) premiumEl.checked = Boolean(seed.isPaid || document.getElementById('upload-premium')?.checked);

  const muteEl = document.getElementById('studio-edit-mute-original');
  const origVolEl = document.getElementById('studio-edit-original-volume');
  const musicVolEl = document.getElementById('studio-edit-music-volume');
  const musicOffsetEl = document.getElementById('studio-edit-music-offset');
  if (muteEl) muteEl.checked = Boolean(draft.muteOriginal);
  if (origVolEl) origVolEl.value = String(Math.round((draft.originalVolume ?? 1) * 100));
  if (musicVolEl) musicVolEl.value = String(Math.round((draft.musicVolume ?? 0.85) * 100));
  if (musicOffsetEl) musicOffsetEl.value = String(draft.musicStart || 0);
  if (draft.musicFilename) {
    setStudioEditMusicName(draft.musicFilename);
    document.getElementById('studio-edit-remove-music')?.classList.remove('hidden');
  }

  updateStudioEditPreview();

  if (panel.__dlEditBound) return;
  panel.__dlEditBound = true;
  document.getElementById('studio-edit-overlay-text')?.addEventListener('input', updateStudioEditPreview);
  document.getElementById('studio-edit-mute-original')?.addEventListener('change', (e) => {
    draft.muteOriginal = e.target.checked;
    if (draft.muteOriginal) {
      const vol = document.getElementById('studio-edit-original-volume');
      if (vol) vol.value = '0';
      draft.originalVolume = 0;
    } else {
      draft.originalVolume = 1;
      const vol = document.getElementById('studio-edit-original-volume');
      if (vol) vol.value = '100';
    }
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-original-volume')?.addEventListener('input', (e) => {
    draft.originalVolume = Math.max(0, Math.min(1, Number(e.target.value) / 100));
    draft.muteOriginal = draft.originalVolume <= 0.01;
    const muteEl2 = document.getElementById('studio-edit-mute-original');
    if (muteEl2) muteEl2.checked = draft.muteOriginal;
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-add-music')?.addEventListener('click', () => {
    document.getElementById('studio-edit-music-file')?.click();
  });
  document.getElementById('studio-edit-music-file')?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (file) loadStudioEditMusic(file);
  });
  document.getElementById('studio-edit-remove-music')?.addEventListener('click', clearStudioEditMusic);
  document.getElementById('studio-edit-music-volume')?.addEventListener('input', (e) => {
    draft.musicVolume = Math.max(0, Math.min(1, Number(e.target.value) / 100));
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-music-offset')?.addEventListener('input', (e) => {
    draft.musicStart = Number(e.target.value) || 0;
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-filters')?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-studio-filter]');
    if (!btn) return;
    draft.filter = btn.dataset.studioFilter || 'none';
    document.querySelectorAll('[data-studio-filter]').forEach((chip) => {
      chip.classList.toggle('studio-edit-chip--active', chip === btn);
    });
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-speeds')?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-studio-speed]');
    if (!btn) return;
    draft.speed = Number(btn.dataset.studioSpeed) || 1;
    document.querySelectorAll('[data-studio-speed]').forEach((chip) => {
      chip.classList.toggle('studio-edit-chip--active', chip === btn);
    });
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-overlay-pos')?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-studio-overlay-pos]');
    if (!btn) return;
    draft.overlayPosition = btn.dataset.studioOverlayPos || 'center';
    document.querySelectorAll('[data-studio-overlay-pos]').forEach((chip) => {
      chip.classList.toggle('studio-edit-chip--active', chip === btn);
    });
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-trim-start')?.addEventListener('input', () => {
    const startInput = document.getElementById('studio-edit-trim-start');
    const endInput = document.getElementById('studio-edit-trim-end');
    if (startInput && endInput && Number(startInput.value) > Number(endInput.value)) {
      endInput.value = startInput.value;
    }
    updateStudioEditPreview();
  });
  document.getElementById('studio-edit-trim-end')?.addEventListener('input', () => {
    const startInput = document.getElementById('studio-edit-trim-start');
    const endInput = document.getElementById('studio-edit-trim-end');
    if (startInput && endInput && Number(endInput.value) < Number(startInput.value)) {
      startInput.value = endInput.value;
    }
    updateStudioEditPreview();
  });

  document.getElementById('studio-edit-ai-caption')?.addEventListener('click', () => {
    dlAi?.applyCaptionAssist(
      document.getElementById('studio-edit-title'),
      document.getElementById('studio-edit-desc'),
      document.getElementById('studio-edit-category'),
      document.getElementById('studio-edit-hashtags'),
    );
  });

  document.getElementById('studio-edit-discard')?.addEventListener('click', () => {
    closeStudioEditBench();
    activateStudioPanel('upload');
    if (state.creatorDashboard) renderCreatorDashboard(state.creatorDashboard);
  });

  document.getElementById('studio-edit-publish')?.addEventListener('click', publishStudioDraft);
}

function studioDraftNeedsExport(draft) {
  const duration = draft.duration || 0;
  const start = draft.trimStart || 0;
  const end = draft.trimEnd ?? duration;
  const needsTrim = duration > 0 && (start > 0.2 || end < duration - 0.2);
  const origVol = draft.originalVolume ?? 1;
  const hasAudioEdit = Boolean(
    draft.muteOriginal
    || draft.musicBlob
    || origVol < 0.98
    || origVol > 1.02,
  );
  const hasFilter = draft.filter && draft.filter !== 'none';
  const hasSpeed = draft.speed && draft.speed !== 1;
  return needsTrim || hasAudioEdit || hasFilter || hasSpeed;
}

function pickStudioRecorderMime() {
  const types = [
    'video/webm;codecs=vp9,opus',
    'video/webm;codecs=vp8,opus',
    'video/webm;codecs=vp8',
    'video/webm',
  ];
  return types.find((t) => MediaRecorder.isTypeSupported(t)) || 'video/webm';
}

function waitForMediaEvent(el, eventName, timeoutMs = 8000) {
  return new Promise((resolve, reject) => {
    if (!el) {
      resolve();
      return;
    }
    let settled = false;
    const done = (ok = true) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      el.removeEventListener(eventName, onOk);
      el.removeEventListener('error', onErr);
      if (ok) resolve();
      else reject(new Error('Could not read media for export'));
    };
    const onOk = () => done(true);
    const onErr = () => done(false);
    const timer = setTimeout(() => done(true), timeoutMs);
    el.addEventListener(eventName, onOk, { once: true });
    el.addEventListener('error', onErr, { once: true });
    if (eventName === 'loadedmetadata' && el.readyState >= 1) done(true);
    if (eventName === 'canplay' && el.readyState >= 3) done(true);
  });
}

function seekExportVideo(video, time) {
  return new Promise((resolve) => {
    const target = Math.max(0, time || 0);
    if (Math.abs(video.currentTime - target) < 0.05) {
      resolve();
      return;
    }
    const finish = () => {
      video.removeEventListener('seeked', finish);
      resolve();
    };
    video.addEventListener('seeked', finish);
    try {
      video.currentTime = target;
    } catch {
      finish();
      return;
    }
    setTimeout(finish, 600);
  });
}

function waitForAudioReady(audioEl, timeoutMs = 6000) {
  return new Promise((resolve) => {
    if (!audioEl) {
      resolve();
      return;
    }
    const done = () => {
      clearTimeout(timer);
      audioEl.removeEventListener('canplaythrough', done);
      audioEl.removeEventListener('loadeddata', done);
      audioEl.removeEventListener('error', done);
      resolve();
    };
    const timer = setTimeout(done, timeoutMs);
    audioEl.addEventListener('canplaythrough', done, { once: true });
    audioEl.addEventListener('loadeddata', done, { once: true });
    audioEl.addEventListener('error', done, { once: true });
    audioEl.load();
  });
}

async function exportStudioDraftBlob(draft) {
  if (!studioDraftNeedsExport(draft)) return draft.blob;

  const exportJob = exportStudioDraftBlobInner(draft);
  const timeoutJob = new Promise((_, reject) => {
    setTimeout(() => reject(new Error('Render timed out — try a shorter clip or fewer effects')), 90000);
  });

  try {
    const blob = await Promise.race([exportJob, timeoutJob]);
    if (!blob || blob.size < 1024) {
      throw new Error('Render produced an empty clip');
    }
    return blob;
  } catch (err) {
    console.error('Studio export failed:', err);
    throw err instanceof Error ? err : new Error('Could not render premium edit');
  }
}

async function exportStudioDraftBlobInner(draft) {
  const video = document.createElement('video');
  video.src = draft.objectUrl;
  video.playsInline = true;
  video.muted = true;
  video.preload = 'auto';
  video.setAttribute('playsinline', '');
  video.style.cssText = 'position:fixed;left:-9999px;top:0;width:2px;height:2px;opacity:0;pointer-events:none';
  document.body.appendChild(video);

  const detachVideo = () => {
    video.pause();
    video.removeAttribute('src');
    video.load();
    if (video.parentNode) video.parentNode.removeChild(video);
  };

  try {
    video.load();
    await waitForMediaEvent(video, 'loadedmetadata', 12000);
    await waitForMediaEvent(video, 'canplay', 12000);
  } catch (err) {
    detachVideo();
    throw err;
  }

  const duration = draft.duration || video.duration || 0;
  const start = draft.trimStart || 0;
  const end = draft.trimEnd ?? duration;
  const clipDuration = Math.max(0.5, end - start);
  const speed = draft.speed || 1;
  const recordDurationMs = Math.ceil(((clipDuration / speed) * 1000) + 800);
  const filterCss = getStudioEditFilterCss(draft.filter);
  const useCanvas = draft.filter && draft.filter !== 'none';

  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth || 720;
  canvas.height = video.videoHeight || 1280;
  const ctx = useCanvas ? canvas.getContext('2d') : null;

  const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  if (audioCtx.state === 'suspended') {
    await audioCtx.resume();
  }

  const dest = audioCtx.createMediaStreamDestination();
  const includeOriginalAudio = !draft.muteOriginal && (draft.originalVolume ?? 1) > 0.01;

  if (includeOriginalAudio) {
    const videoSource = audioCtx.createMediaElementSource(video);
    const gain = audioCtx.createGain();
    gain.gain.value = draft.originalVolume ?? 1;
    videoSource.connect(gain);
    gain.connect(dest);
  }

  let musicEl = null;
  if (draft.musicObjectUrl) {
    musicEl = new Audio(draft.musicObjectUrl);
    musicEl.preload = 'auto';
    await waitForAudioReady(musicEl);
    const musicSource = audioCtx.createMediaElementSource(musicEl);
    const musicGain = audioCtx.createGain();
    musicGain.gain.value = draft.musicVolume ?? 0.85;
    musicSource.connect(musicGain);
    musicGain.connect(dest);
  }

  let stream = useCanvas && ctx
    ? canvas.captureStream(30)
    : (video.captureStream?.() || video.mozCaptureStream?.());

  if (!stream) {
    detachVideo();
    await audioCtx.close().catch(() => {});
    return draft.blob;
  }

  const tracks = [...stream.getVideoTracks()];
  if (dest.stream.getAudioTracks().length) {
    tracks.push(...dest.stream.getAudioTracks());
  }
  const combined = new MediaStream(tracks);
  const mimeType = pickStudioRecorderMime();

  return new Promise((resolve, reject) => {
    let finished = false;
    let rafId = 0;
    let stopTimer = null;

    const finish = (blob) => {
      if (finished) return;
      finished = true;
      if (stopTimer) clearTimeout(stopTimer);
      cancelAnimationFrame(rafId);
      if (musicEl) musicEl.pause();
      detachVideo();
      audioCtx.close().catch(() => {});
      resolve(blob);
    };

    const fail = (message) => {
      if (finished) return;
      finished = true;
      if (stopTimer) clearTimeout(stopTimer);
      cancelAnimationFrame(rafId);
      if (musicEl) musicEl.pause();
      detachVideo();
      audioCtx.close().catch(() => {});
      reject(new Error(message));
    };

    let recorder;
    try {
      recorder = new MediaRecorder(combined, { mimeType, videoBitsPerSecond: 2500000 });
    } catch (err) {
      fail(err?.message || 'MediaRecorder not supported in this browser');
      return;
    }

    const chunks = [];

    recorder.ondataavailable = (e) => {
      if (e.data?.size > 0) chunks.push(e.data);
    };
    recorder.onstop = () => {
      finish(new Blob(chunks, { type: mimeType }));
    };
    recorder.onerror = () => fail('Could not render premium edit');

    const stopRecording = () => {
      if (recorder.state === 'recording') {
        try {
          if (typeof recorder.requestData === 'function') recorder.requestData();
          recorder.stop();
        } catch {
          fail('Could not finish render');
        }
      }
    };

    const beginRecording = async () => {
      try {
        video.playbackRate = speed;
        await seekExportVideo(video, start);

        if (musicEl) {
          musicEl.currentTime = draft.musicStart || 0;
          await musicEl.play().catch(() => {});
        }

        const played = await video.play().then(() => true).catch(() => false);
        if (!played && useCanvas) {
          fail('Browser blocked playback — tap Publish again');
          return;
        }

        try {
          recorder.start(250);
        } catch (err) {
          fail(err?.message || 'Could not start recorder');
          return;
        }

        const drawFrame = () => {
          if (finished) return;
          if (useCanvas && ctx && video.readyState >= 2) {
            ctx.filter = filterCss;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
          }
          if (video.currentTime < end - 0.08 && recorder.state === 'recording') {
            rafId = requestAnimationFrame(drawFrame);
          }
        };
        if (useCanvas) drawFrame();

        video.ontimeupdate = () => {
          if (video.currentTime >= end - 0.08) stopRecording();
        };

        stopTimer = setTimeout(stopRecording, recordDurationMs);
      } catch (err) {
        fail(err?.message || 'Could not start render');
      }
    };

    beginRecording();
  });
}

let studioPublishInFlight = false;

async function publishStudioDraft() {
  if (!requireApprovedCreator('publish reels')) return;
  if (studioPublishInFlight) return;
  const draft = state.studioDraft;
  if (!draft) {
    showToast('Choose or record a video first');
    return;
  }

  const title = document.getElementById('studio-edit-title')?.value?.trim() || draft.filename;
  const descriptionBase = document.getElementById('studio-edit-desc')?.value?.trim() || '';
  const hashtags = document.getElementById('studio-edit-hashtags')?.value?.trim() || '';
  const overlayText = document.getElementById('studio-edit-overlay-text')?.value?.trim() || '';
  const categoryId = document.getElementById('studio-edit-category')?.value;
  const isPaid = document.getElementById('studio-edit-premium')?.checked;

  if (!categoryId) {
    showToast('Choose a genre/category');
    setStudioEditStatus('Pick a category before publishing.', true);
    return;
  }

  const descriptionParts = [descriptionBase];
  if (overlayText) descriptionParts.push(`Caption (${draft.overlayPosition || 'center'}): ${overlayText}`);
  if (hashtags) descriptionParts.push(hashtags);
  if (draft.filter && draft.filter !== 'none') descriptionParts.push(`Filter: ${STUDIO_EDIT_FILTERS[draft.filter]?.label || draft.filter}`);
  if (draft.speed && draft.speed !== 1) descriptionParts.push(`Speed: ${draft.speed}×`);
  if (draft.muteOriginal) descriptionParts.push('Original audio muted');
  if (draft.musicFilename) descriptionParts.push(`Sound: ${draft.musicFilename}`);
  const description = descriptionParts.filter(Boolean).join('\n\n');

  const clipSec = getDraftClipDuration(draft);
  const maxSec = maxReelDurationSeconds();
  if (clipSec > maxSec + 0.5) {
    showToast(`Clip is ${Math.ceil(clipSec)}s — max is ${formatReelDurationLimit()}. Trim before publishing.`);
    setStudioEditStatus(`Too long — trim to ${formatReelDurationLimit()} or less.`, true);
    return;
  }

  const needsExport = studioDraftNeedsExport(draft);
  studioPublishInFlight = true;
  setUploadBusy(true, needsExport ? 'Rendering…' : 'Uploading…');
  setStudioEditStatus(needsExport ? 'Rendering premium edit…' : 'Starting upload…');

  try {
    let blob = await ensureStudioDraftBlob(draft);
    if (needsExport) {
      setStudioEditStatus('Rendering your edit (this can take a minute)…');
      blob = await exportStudioDraftBlob({ ...draft, blob });
      draft.blob = blob;
    }
    const uploadName = normalizeStudioFilename(draft.filename, blob.type || 'video/webm');
    const sizeMb = (blob.size / (1024 * 1024)).toFixed(1);
    setStudioEditStatus(`Uploading ${sizeMb} MB…`);
    await uploadReelBlob(blob, uploadName, title, description, isPaid, categoryId, {
      skipBusy: true,
      durationSeconds: clipSec,
      onProgress: (pct) => setStudioEditStatus(`Uploading… ${pct}%`),
    });
    closeStudioEditBench();
    activateStudioPanel('upload');
    if (state.creatorDashboard) renderCreatorDashboard(state.creatorDashboard);
  } catch (err) {
    setStudioEditStatus(err.message || 'Upload failed', true);
    showToast(err.message || 'Upload failed');
  } finally {
    studioPublishInFlight = false;
    setUploadBusy(false);
  }
}

async function uploadReelFromFile() {
  if (state.studioDraft) {
    activateStudioPanel('edit');
    return;
  }
  document.getElementById('upload-file')?.click();
}

async function uploadReelBlob(blob, filename, title, description, isPaid, categoryId, options = {}) {
  if (!blob || !(blob instanceof Blob)) {
    throw new Error('Video file is missing — record or choose a clip again');
  }
  if (blob.size < 1024) {
    throw new Error('Video file is empty — record or choose a clip again');
  }
  if (blob.size > maxUploadBytes()) {
    const mb = dlFeatures?.getMaxReelUploadMb?.() || 128;
    throw new Error(`Video must be under ${mb} MB`);
  }

  const uploadFile = toStudioUploadFile(blob, filename);
  const form = new FormData();
  form.append('videoFile', uploadFile, uploadFile.name);
  form.append('title', title);
  form.append('description', description);
  form.append('is_paid', isPaid ? '1' : '0');
  form.append('profile_category_id', categoryId || '');
  if (options.durationSeconds > 0) {
    form.append('duration_seconds', String(Math.round(options.durationSeconds * 10) / 10));
  }

  if (!options.skipBusy) setUploadBusy(true, 'Uploading…');
  if (options.onProgress) setStudioEditStatus('Uploading… 0%');

  try {
    const res = await apiUpload(API_ROUTES.creatorUploadReel, form, {
      onProgress: options.onProgress,
    });
    const msg = res.message || res.data?.message || 'Reel uploaded';
    showToast(msg);
    setStudioEditStatus('');
    state.creatorDashboard = null;
    void loadCreatorDashboard(true);
    void loadFeed(true);
    if (Number(res.data?.status) !== 10) {
      window.setTimeout(() => loadFeed(true), 5000);
    }
    return res;
  } catch (err) {
    throw err;
  } finally {
    if (!options.skipBusy) setUploadBusy(false);
  }
}

async function loadCreatorDashboard(force = false) {
  if (!state.token || !isCreator()) return;
  if (!force && state.creatorDashboard) {
    renderCreatorDashboard(state.creatorDashboard);
    return;
  }
  renderCreatorDashboard(null);
  try {
    const res = await api(API_ROUTES.creatorDashboard);
    state.creatorDashboard = res.data;
    if (res.data?.creator) {
      state.user = { ...state.user, ...res.data.creator };
      localStorage.setItem('dreamland_user', JSON.stringify(state.user));
    }
    renderCreatorDashboard(state.creatorDashboard);
    updateWalletBalance();
    touchDashboardRefreshHint();
  } catch (err) {
    els.creatorDashboard.innerHTML = `
      <div class="creator-empty glass-card">
        <h3>Could not load studio</h3>
        <p class="muted">${escapeHtml(err.message || 'Dashboard unavailable')}</p>
        <button type="button" class="btn-primary" id="creator-retry">Retry</button>
      </div>`;
    document.getElementById('creator-retry')?.addEventListener('click', () => loadCreatorDashboard(true));
  }
}

let mainAppBooted = false;

function bindMobileOptimizations() {
  const root = document.documentElement;
  const coarsePointer = window.matchMedia('(pointer: coarse)').matches;
  const narrow = window.innerWidth < 768;
  if (coarsePointer || narrow) root.classList.add('is-mobile');

  const syncViewportVars = () => {
    const vv = window.visualViewport;
    const height = vv?.height ?? window.innerHeight;
    root.style.setProperty('--dl-vh', `${height * 0.01}px`);
    if (vv) {
      const keyboardInset = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
      root.style.setProperty('--dl-keyboard-inset', `${keyboardInset}px`);
    } else {
      root.style.setProperty('--dl-keyboard-inset', '0px');
    }
  };

  syncViewportVars();
  window.addEventListener('resize', syncViewportVars, { passive: true });
  window.addEventListener('orientationchange', syncViewportVars, { passive: true });
  window.visualViewport?.addEventListener('resize', syncViewportVars, { passive: true });
  window.visualViewport?.addEventListener('scroll', syncViewportVars, { passive: true });

  document.addEventListener('focusin', (e) => {
    const target = e.target;
    if (!(target instanceof HTMLElement)) return;
    if (!target.matches('input, textarea, select')) return;
    window.setTimeout(() => {
      target.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }, 280);
  });
}

function bootMainApp() {
  if (mainAppBooted) return;
  mainAppBooted = true;
  window.__DL_APP_READY__ = true;
  if (window.__DL_BOOT_TIMER__) clearTimeout(window.__DL_BOOT_TIMER__);
  try {
    bindMobileOptimizations();
    dlFeatures = createDreamlandFeatures({
      api, API_ROUTES, state, showToast, gateGuest, openAuthModal, escapeHtml, formatCount,
    });
    dlAi = createDreamlandAi({ api, API_ROUTES, state, showToast, escapeHtml });
    dlSocial = createDreamlandSocial({
      api, API_ROUTES, state, showToast, gateGuest, formatCount, escapeHtml, UPLOADS_BASE,
    });
    dlProfile = createDreamlandProfile({
      api, API_ROUTES, state, showToast, gateGuest, escapeHtml, formatCount, mediaUrl, openPaywall, switchView, UPLOADS_BASE,
    });
    dlSearch = createDreamlandSearch({
      api, API_ROUTES, escapeHtml, formatCount, switchView,
      openProfile: (userId) => dlProfile?.openProfile(userId),
    });
    dlAccount = createDreamlandAccount({
      api,
      apiUpload,
      API_ROUTES,
      state,
      showToast,
      escapeHtml,
      switchView,
      isCreator,
      clearSession,
      validateSession,
      UPLOADS_BASE,
      accountHomeView,
      openAuthModal,
      userInitials,
      updateAuthUi,
      creatorApprovalStatus,
    });
    els.appShell?.classList.add('app-shell--feed-nav');
    updateAuthUi();
    try { bindUi(); } catch (err) { console.error('bindUi failed:', err); }
    try { dlSocial.initSoundToggle(); } catch (err) { console.error('sound toggle failed:', err); }
    try { dlSearch.bindSearchUi(); } catch (err) { console.error('search ui failed:', err); }
    bindFeedScroll();
    loadReferralFromUrl();
    updateFeedHeaderUi();
    window.__dlScrollToReel = scrollToReel;
    setFeedLoadingMessage();
    pingApi();
    const bootFeed = () => {
      if (document.getElementById('feed-view')?.classList.contains('active')) loadFeed(true);
    };
    if (typeof requestIdleCallback === 'function') {
      requestIdleCallback(bootFeed, { timeout: 1500 });
    } else {
      window.setTimeout(bootFeed, 80);
    }
    Promise.allSettled([
      dlAi.init(),
      dlFeatures.init(),
    ]).then(() => {
      dlAi.bindSignupSafety(document.getElementById('signup-form'), 'signup-errors');
      dlAi.bindSignupSafety(document.getElementById('auth-form'), 'auth-errors');
      dlFeatures.renderGenreFilter(document.getElementById('genre-filter'), () => loadFeed(true));
      dlFeatures.loadStreakPanel(document.getElementById('feed-streak-bar'));
      if (!state.feed.length) loadFeed(true);
    }).catch((err) => {
      console.error('Dreamland features init failed:', err);
    });
    window.setTimeout(() => {
      if (isFeedBootStuck()) {
        state.feedLoading = false;
        loadFeed(true);
      }
    }, 8000);
    validateSession().finally(() => {
      if (state.user) {
        if (isCreator()) loadCreatorDashboard(true);
        else loadViewerDashboard(true);
      }
      loadPackages();
      dlFeatures?.loadWalletTransactions(document.getElementById('wallet-ledger'));
      dlFeatures?.registerPush?.();
    });
  } catch (err) {
    console.error('Dreamland boot failed:', err);
    showBootError(err.message || 'App failed to start');
  }
}

function parseFeedItems(data) {
  if (!data) return [];
  if (Array.isArray(data)) return data;
  const post = data.post ?? data.items;
  if (Array.isArray(post)) return post;
  if (post?.items && Array.isArray(post.items)) return post.items;
  return [];
}

function initOnboarding() {
  if (DEV_ALLOW_BROWSER || hasCompletedOnboarding() || isPwaInstalled()) {
    markPwaInstalled();
    localStorage.setItem(ONBOARDING_KEY, 'true');
    els.onboarding?.classList.add('hidden');
    els.appShell?.classList.remove('hidden');
    bootMainApp();
    return;
  }

  els.onboarding?.classList.remove('hidden');
  els.appShell?.classList.add('hidden');
  renderInstallGuide();
  startStandaloneWatcher();

  onboardingGlide = new Glide('.glide', {
    type: 'slider',
    startAt: 0,
    perView: 1,
    rewind: false,
    gap: 0,
    touchRatio: 1,
    touchAngle: 35,
    animationDuration: 350,
    keyboard: false,
  });

  onboardingGlide.mount();

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPwaPrompt = e;
    updateInstallButtonForPlatform();
  });

  window.addEventListener('appinstalled', () => {
    markPwaInstalled();
    completeOnboarding();
  });

  onboardingGlide.on('run.after', () => {
    if (onboardingGlide.index === 2) {
      renderInstallGuide();
      if (deferredPwaPrompt && detectInstallPlatform() !== 'ios') {
        updateInstallButtonForPlatform();
      }
    }
  });

  document.getElementById('pwaInstallBtn')?.addEventListener('click', () => {
    if (onboardingGlide && onboardingGlide.index !== 2) {
      onboardingGlide.go('=2');
    }
    attemptPwaInstall();
  });
}

function closeAuthModal() {
  els.authModal.classList.add('hidden');
}

function gateGuest() {
  if (!isGuest()) return false;
  openAuthModal();
  return true;
}

function updateFeedHeaderUi() {
  const onFeed = document.getElementById('feed-view')?.classList.contains('active');
  const isLive = state.feedMode === 'live';
  const isReels = state.feedMode === 'reels';

  document.querySelector('.header-mode-switch')?.toggleAttribute('hidden', !onFeed);
  document.getElementById('feed-back-btn')?.toggleAttribute('hidden', !onFeed || !isLive);
  document.getElementById('brand-home')?.toggleAttribute('hidden', !onFeed ? false : isLive);
  document.getElementById('sound-toggle')?.toggleAttribute('hidden', !onFeed || isLive);
  document.getElementById('notif-btn')?.toggleAttribute('hidden', false);

  if (els.apiStatus && DEV_ALLOW_BROWSER) {
    els.apiStatus.toggleAttribute('hidden', onFeed);
  }

  els.appShell?.classList.toggle('app-shell--watch', onFeed && isReels);
  els.appShell?.classList.toggle('app-shell--live-mode', onFeed && isLive);
  els.appShell?.classList.toggle('app-shell--feed-nav', onFeed);
  els.appShell?.classList.toggle('app-shell--page-nav', !onFeed);
}

function switchView(viewId) {
  document.querySelectorAll('.view').forEach((v) => v.classList.remove('active'));
  document.getElementById(viewId)?.classList.add('active');
  document.querySelectorAll('.dock-item').forEach((n) => n.classList.toggle('active', n.dataset.view === viewId));
  if (viewId === 'feed-view' && state.feedMode === 'reels') setupReelPlayback();
  if (viewId === 'feed-view' && state.feedMode === 'live') loadLives(true);
  if (viewId === 'creator-view') {
    loadCreatorDashboard();
    startDashboardAutoRefresh('creator-view');
  } else if (viewId === 'viewer-view') {
    loadViewerDashboard();
    startDashboardAutoRefresh('viewer-view');
  } else {
    stopDashboardAutoRefresh();
  }
  if (viewId === 'account-view') dlAccount?.renderAccount();
  if (viewId === 'signup-view') setPageSignupStep('role');
  if (viewId !== 'creator-view') {
    closeRecordCapture();
    stopCameraStream();
  }
  if (viewId !== 'feed-view') dlSocial?.stopAllWatchTracking?.();
  updateFeedHeaderUi();
}

const RAIL_ICONS = {
  like: '<svg viewBox="0 0 24 24"><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z"/></svg>',
  share: '<svg viewBox="0 0 24 24"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7"/><path d="M12 16V4"/><path d="m8 8 4-4 4 4"/></svg>',
  save: '<svg viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/></svg>',
  unlock: '<svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
  follow: '<svg viewBox="0 0 24 24"><path d="M16 11c1.7 0 3-1.3 3-3S17.7 5 16 5s-3 1.3-3 3 1.3 3 3 3Z"/><path d="M8 12c1.7 0 3-1.3 3-3S9.7 6 8 6 5 7.3 5 9s1.3 3 3 3Z"/><path d="M2 20c0-3 2.7-5 6-5"/><path d="M10 20c0-2.5 2-4.5 4.5-4.5 1.2 0 2.3.5 3.1 1.2"/></svg>',
};

function renderFeed() {
  if (!state.feed.length) {
    const hint = state.feedError
      || (DEV_ALLOW_BROWSER ? 'Make sure the API is running on port 8080.' : 'The API may need a moment to wake up on Render free tier.');
    els.feedList.innerHTML = `
      <div class="empty-feed">
        <div class="empty-feed-logo-wrap dl-logo-stage dl-logo-stage--feed">
          <img src="/assets/logo.png" alt="Dreamland" width="140" height="140" class="empty-feed-logo dreamland-logo-img" />
        </div>
        <p>No reels loaded yet</p>
        <p class="muted">${escapeHtml(hint)}</p>
        <button type="button" id="feed-retry" class="btn-primary">Reload feed</button>
      </div>`;
    document.getElementById('feed-retry')?.addEventListener('click', () => loadFeed(true));
    return;
  }

  els.feedList.innerHTML = state.feed.map((post) => {
    const dream = post.dreamland || {};
    const locked = dream.is_paid && !dream.is_unlocked;
    const src = mediaUrl(post);
    const creator = post.user?.username || post.user?.name || 'creator';
    const initial = creator.charAt(0).toUpperCase();
    const price = dream.paywall?.price_credits || post.price_credits || 0;
    const title = humanizePostText(post.title, 'Dreamland Reel');
    const desc = humanizePostText(post.description, '');
    const descHtml = desc && !looksLikeUploadFilename(desc) ? `<p class="reel-desc">${escapeHtml(desc)}</p>` : '';
    const likes = formatCount(post.total_like);

    const previewSec = dlFeatures?.getPreviewSeconds?.() || PREVIEW_SECONDS;

    return `
      <article class="reel ${locked ? 'reel--locked reel--previewing' : ''}" data-id="${post.id}">
        ${src ? `<video class="reel-video" src="${escapeHtml(src)}" muted playsinline preload="auto" ${locked ? `data-preview="${previewSec}"` : 'loop'}></video>` : '<div class="reel-video" style="background:#111"></div>'}
        <div class="reel-vignette" aria-hidden="true"></div>
        <div class="reel-gradient"></div>
        ${dream.is_paid && locked ? `
          <div class="reel-preview-progress" aria-hidden="true"><span class="reel-preview-progress__fill"></span></div>
          <div class="reel-exclusive-tag" aria-live="polite">
            <span class="reel-exclusive-tag__mark" aria-hidden="true">◆</span>
            <span class="reel-exclusive-tag__body">
              <span class="reel-exclusive-tag__label">Exclusive</span>
              <span class="reel-exclusive-tag__meta"><span class="reel-preview-count">${previewSec}</span>s preview · ${price} credits</span>
            </span>
          </div>
          <div class="reel-unlock reel-unlock--sheet">
            <div class="reel-unlock-card">
              <div class="reel-unlock-card__crest" aria-hidden="true">◆</div>
              <div class="reel-unlock-card__copy">
                <strong class="reel-unlock-card__title">Exclusive access</strong>
                <span class="reel-unlock-card__sub">Support @${escapeHtml(creator)} · full experience</span>
              </div>
              <button type="button" class="reel-unlock-cta unlock-btn" data-id="${post.id}" data-price="${price}">
                <span>Access</span>
                <strong>${price} credits</strong>
              </button>
            </div>
          </div>` : ''}
        ${!locked && dream.is_paid ? '<span class="reel-badge reel-badge--unlocked"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg> Unlocked</span>' : ''}
        <div class="reel-stage-foot">
          <div class="reel-info">
            <div class="reel-creator reel-creator--link">
              <div class="reel-avatar">${escapeHtml(initial)}</div>
              <div class="reel-creator-meta">
                <span class="reel-creator-name">@${escapeHtml(creator)}</span>
                <span class="reel-creator-badge">Dreamland Creator</span>
              </div>
            </div>
            <h2 class="reel-title">${escapeHtml(title.replace(/^Dreamland Demo:\s*/i, ''))}</h2>
            ${locked ? `
              <p class="reel-premium-foot">
                <span class="reel-premium-foot__chip">Exclusive</span>
                <span class="reel-premium-foot__text">${previewSec}s preview · ${price} credits for the full reel</span>
              </p>` : descHtml}
          </div>
          <div class="reel-rail reel-rail--lux">
            <button type="button" class="rail-btn rail-btn--lux guest-gate ${dlSocial?.isLiked?.(post.id) ? 'rail-btn--liked' : ''}" data-action="like" aria-label="Like">
              ${RAIL_ICONS.like}<span class="rail-count">${likes}</span>
            </button>
            <button type="button" class="rail-btn rail-btn--lux guest-gate" data-action="share" aria-label="Share">
              ${RAIL_ICONS.share}<span class="rail-count">${formatCount(post.total_share || 0)}</span>
            </button>
            <button type="button" class="rail-btn rail-btn--lux guest-gate" data-action="save" aria-label="Save">
              ${RAIL_ICONS.save}<span class="rail-label">Save</span>
            </button>
            <button type="button" class="rail-btn rail-btn--lux guest-gate" data-action="follow" data-user-id="${post.user?.id || ''}" aria-label="Follow">
              ${RAIL_ICONS.follow}<span class="rail-label">Follow</span>
            </button>
            ${locked ? `<button type="button" class="rail-btn rail-btn--lux rail-btn--unlock unlock-btn" data-id="${post.id}" data-price="${price}" aria-label="Unlock">${RAIL_ICONS.unlock}<span class="rail-count">${price}</span></button>` : ''}
          </div>
        </div>
      </article>`;
  }).join('');

  els.feedList.querySelectorAll('.unlock-btn').forEach((btn) => {
    btn.onclick = (e) => {
      e.stopPropagation();
      openPaywall(btn.dataset.id, btn.dataset.price);
    };
  });

  els.feedList.querySelectorAll('.guest-gate').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (el.dataset.action && gateGuest()) e.stopPropagation();
    });
  });

  els.feedList.querySelectorAll('[data-action="follow"]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (gateGuest()) return;
      dlFeatures?.followCreator(btn.dataset.userId);
    });
  });

  const postsById = new Map(state.feed.map((p) => [Number(p.id), p]));
  dlSocial?.bindReelInteractions(els.feedList, postsById, (userId) => dlProfile?.openProfile(userId));
  if (state.token) {
    dlSocial?.syncLikedIds(state.feed.map((p) => p.id));
  }

  attachReelGamificationCards();
  setupReelPlayback();
  scrollToReelFromUrl();
}

async function attachReelGamificationCards() {
  if (!dlFeatures || !state.token) return;
  for (const reel of els.feedList.querySelectorAll('.reel--locked')) {
    const id = reel.dataset.id;
    if (!id || reel.querySelector('.earn-card')) continue;
    const pot = await dlFeatures.fetchWatchPot(id);
    const preds = await dlFeatures.fetchPredictions(id);
    if (!pot && !preds.length) continue;
    const card = document.createElement('div');
    card.className = 'earn-card glass-card';
    let html = '';
    if (pot) {
      html += `<p class="earn-card-pot">Co-op pot: ${pot.current_unlocks}/${pot.target_unlocks} unlocks · ${pot.bonus_pool_credits} cr bonus</p>`;
    }
    if (preds[0]) {
      const p = preds[0];
      html += `<button type="button" class="btn-ghost earn-stake-yes" data-pid="${p.id}">Bet 2 cr: hits ${p.target_value} views?</button>`;
    }
    card.innerHTML = html;
    reel.querySelector('.reel-info')?.appendChild(card);
    card.querySelector('.earn-stake-yes')?.addEventListener('click', (e) => {
      e.stopPropagation();
      dlFeatures.stakePrediction(preds[0].id, 'yes', 2);
    });
  }
}

function setupLockedReelPreview(reel) {
  const video = reel.querySelector('video[data-preview]');
  if (!video) return;

  const seconds = Number(video.dataset.preview) || dlFeatures?.getPreviewSeconds?.() || PREVIEW_SECONDS;
  const countEl = reel.querySelector('.reel-preview-count');
  const progressFill = reel.querySelector('.reel-preview-progress__fill');

  const endPreview = () => {
    if (reel.classList.contains('reel--preview-ended')) return;
    reel.classList.remove('reel--previewing');
    reel.classList.add('reel--preview-ended');
    video.pause();
    if (progressFill) progressFill.style.width = '100%';
    if (Number.isFinite(video.duration) && video.duration > 0) {
      video.currentTime = Math.min(seconds, video.duration);
    }
  };

  video.addEventListener('timeupdate', () => {
    if (reel.classList.contains('reel--preview-ended')) return;
    const remaining = Math.max(0, Math.ceil(seconds - video.currentTime));
    if (countEl) countEl.textContent = String(remaining);
    if (progressFill) {
      const pct = Math.min(100, (video.currentTime / seconds) * 100);
      progressFill.style.width = `${pct}%`;
    }
    if (video.currentTime >= seconds) endPreview();
  });

  reel._endPreview = endPreview;
}

function setupReelPlayback() {
  if (reelObserver) reelObserver.disconnect();
  dlSocial?.stopAllWatchTracking?.();

  els.feedList.querySelectorAll('.reel--locked.reel--previewing').forEach(setupLockedReelPreview);

  reelObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const reel = entry.target;
      const video = reel.querySelector('.reel-video');
      if (!video || video.tagName !== 'VIDEO') return;
      const post = state.feed.find((item) => String(item.id) === String(reel.dataset.id));

      if (entry.isIntersecting && entry.intersectionRatio >= 0.35) {
        els.feedList.querySelectorAll('.reel').forEach((r) => r.classList.remove('reel--active'));
        reel.classList.add('reel--active');
        if (post) bindReelVideoFallback(video, post);
        if (reel.classList.contains('reel--previewing')) {
          video.currentTime = 0;
          const countEl = reel.querySelector('.reel-preview-count');
          const progressFill = reel.querySelector('.reel-preview-progress__fill');
          const seconds = Number(video.dataset.preview) || dlFeatures?.getPreviewSeconds?.() || PREVIEW_SECONDS;
          if (countEl) countEl.textContent = String(seconds);
          if (progressFill) progressFill.style.width = '0%';
        }
        playReelVideo(video);
        dlSocial?.applySoundToActive?.(els.feedList);
        if (!dlSocial?.isMuted?.()) {
          dlSocial?.resumeActiveReelAudio?.(els.feedList);
        }
        dlSocial?.recordView?.(reel.dataset.id);
        dlSocial?.startWatchTracking?.(reel, reel.dataset.id);
      } else {
        video.pause();
        if (reel.classList.contains('reel--active')) {
          reel.classList.remove('reel--active');
          dlSocial?.stopWatchTracking?.(reel.dataset.id);
        }
        if (reel.classList.contains('reel--previewing') && !reel.classList.contains('reel--preview-ended')) {
          video.currentTime = 0;
          const countEl = reel.querySelector('.reel-preview-count');
          const progressFill = reel.querySelector('.reel-preview-progress__fill');
          const seconds = Number(video.dataset.preview) || dlFeatures?.getPreviewSeconds?.() || PREVIEW_SECONDS;
          if (countEl) countEl.textContent = String(seconds);
          if (progressFill) progressFill.style.width = '0%';
        }
      }
    });
    dlSocial?.applySoundToActive?.(els.feedList);
  }, { threshold: [0.35, 0.6] });

  els.feedList.querySelectorAll('.reel').forEach((reel) => reelObserver.observe(reel));

  const firstReel = els.feedList.querySelector('.reel');
  const first = firstReel?.querySelector('.reel-video');
  if (first?.tagName === 'VIDEO') {
    const post = state.feed.find((item) => String(item.id) === String(firstReel.dataset.id));
    if (post) bindReelVideoFallback(first, post);
    firstReel.classList.add('reel--active');
    playReelVideo(first);
    dlSocial?.applySoundToActive?.(els.feedList);
  }
}

function openPaywall(videoId, price) {
  if (gateGuest()) return;
  state.paywallType = 'video';
  state.currentPaywallVideo = videoId;
  state.currentPaywallLive = null;
  document.getElementById('paywall-eyebrow').textContent = 'Premium content';
  document.getElementById('paywall-title').textContent = 'Unlock full video';
  document.getElementById('paywall-message').textContent = `${price} credits`;
  document.getElementById('paywall-copy').textContent = 'Support creators. Watch the full reel without limits.';
  els.paywallModal.classList.remove('hidden');
}

function switchFeedMode(mode) {
  state.feedMode = mode;
  document.querySelectorAll('.feed-mode-tab').forEach((tab) => {
    const active = tab.dataset.feedMode === mode;
    tab.classList.toggle('active', active);
    tab.setAttribute('aria-selected', active ? 'true' : 'false');
  });
  document.querySelectorAll('.feed-mode-panel').forEach((panel) => {
    const show = panel.dataset.feedMode === mode;
    panel.classList.toggle('feed-mode-panel--active', show);
    panel.hidden = !show;
  });
  const chrome = document.querySelector('.watch-chrome');
  if (chrome) chrome.hidden = mode !== 'reels';
  updateFeedHeaderUi();
  if (mode === 'live') loadLives(true);
  else setupReelPlayback();
}

async function loadLives(force = false) {
  if (!force && state.lives.length) {
    renderLiveFeed();
    return;
  }
  const cards = els.liveCards || els.liveList;
  if (cards) {
    cards.innerHTML = `<div class="live-loading glass-card"><p class="eyebrow">Live now</p><h2>Loading streams…</h2></div>`;
  }
  try {
    const res = await api(API_ROUTES.liveList);
    state.lives = res.data?.lives || [];
  } catch (err) {
    console.warn('Live list failed:', err.message);
    state.lives = [];
  }
  renderLiveFeed();
}

function renderLiveFeed() {
  const cards = els.liveCards || els.liveList;
  if (!cards) return;
  if (!state.lives.length) {
    cards.innerHTML = `
      <div class="live-empty glass-card">
        <span class="live-empty-icon">📡</span>
        <h2>No one is live</h2>
        <p class="muted">When creators go live, they will appear here.</p>
        <button type="button" class="btn-ghost" id="live-back-reels">← Back to reels</button>
        <button type="button" class="btn-primary" id="live-retry">Refresh</button>
      </div>`;
    document.getElementById('live-retry')?.addEventListener('click', () => loadLives(true));
    document.getElementById('live-back-reels')?.addEventListener('click', () => switchFeedMode('reels'));
    return;
  }

  cards.innerHTML = state.lives.map((live) => {
    const creator = live.creator || {};
    const dream = live.dreamland || {};
    const locked = dream.is_monetized && !dream.is_unlocked;
    const initial = (creator.username || creator.name || 'C').charAt(0).toUpperCase();
    const price = dream.paywall?.price_credits || dream.price_credits || live.price_credits || 0;
    return `
      <article class="live-card glass-card" data-id="${live.id}">
        <div class="live-card-top">
          <div class="live-card-avatar">${escapeHtml(initial)}</div>
          <div class="live-card-meta">
            <span class="live-card-badge"><span class="live-dot"></span> LIVE</span>
            <h3>${escapeHtml(live.title || 'Dreamland Live')}</h3>
            <p class="muted">@${escapeHtml(creator.username || 'creator')}</p>
          </div>
        </div>
        <div class="live-card-tags">
          ${dream.is_monetized
            ? `<span class="chip chip-premium">${locked ? `🔒 ${price} credits` : 'Unlocked'}</span>`
            : '<span class="chip">Free to watch</span>'}
        </div>
        <button type="button" class="btn-primary full live-watch-btn" data-id="${live.id}" data-price="${price}" data-locked="${locked ? '1' : '0'}" data-title="${escapeHtml(live.title || 'Dreamland Live')}">
          ${locked ? `Unlock & watch · ${price} cr` : 'Watch live'}
        </button>
      </article>`;
  }).join('');

  (els.liveCards || els.liveList)?.querySelectorAll('.live-watch-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const live = state.lives.find((item) => String(item.id) === btn.dataset.id);
      if (!live) return;
      attemptWatchLive(live);
    });
  });
}

async function attemptWatchLive(live) {
  if (gateGuest()) return;
  const dream = live.dreamland || {};
  if (dream.is_monetized && !dream.is_unlocked) {
    openLivePaywall(live.id, dream.paywall?.price_credits || dream.price_credits || 0, live.title);
    return;
  }
  await enterLiveRoom(live.id);
}

function openLivePaywall(liveId, price, title) {
  if (gateGuest()) return;
  state.paywallType = 'live';
  state.currentPaywallLive = liveId;
  state.currentPaywallVideo = null;
  document.getElementById('paywall-eyebrow').textContent = 'Monetized live';
  document.getElementById('paywall-title').textContent = title || 'Unlock live stream';
  document.getElementById('paywall-message').textContent = `${price} credits`;
  document.getElementById('paywall-copy').textContent = 'Support creators. Watch the full live session without limits.';
  els.paywallModal.classList.remove('hidden');
}

async function enterLiveRoom(liveId) {
  try {
    const res = await api(`${API_ROUTES.liveWatch}?live_id=${encodeURIComponent(liveId)}`);
    if (Number(res.data?.statusCode) === 402) {
      const live = res.data?.live || state.lives.find((item) => String(item.id) === String(liveId));
      const price = res.data?.dreamland?.paywall?.price_credits || live?.dreamland?.price_credits || 0;
      openLivePaywall(liveId, price, live?.title);
      return;
    }
    const live = res.data?.live;
    if (!live) throw new Error(res.data?.message || 'Live unavailable');
    const joinRes = await api(API_ROUTES.liveJoin, {
      method: 'POST',
      body: JSON.stringify({ live_id: Number(liveId) }),
    });
    if (joinRes.data?.rtc) live.rtc = joinRes.data.rtc;
    if (joinRes.data?.viewer_count != null) live.viewer_count = joinRes.data.viewer_count;
    await openLiveWatchRoom(live);
    loadLives(true);
  } catch (err) {
    if (err.status === 402) {
      const live = err.payload?.data?.live || state.lives.find((item) => String(item.id) === String(liveId));
      const price = err.payload?.data?.dreamland?.paywall?.price_credits || live?.dreamland?.price_credits || 0;
      openLivePaywall(liveId, price, live?.title);
      return;
    }
    showToast(err.message || 'Could not join live');
  }
}

async function openLiveWatchRoom(live) {
  state.activeLiveWatch = live;
  const creator = live.creator || {};
  const initial = (creator.username || creator.name || 'C').charAt(0).toUpperCase();
  document.getElementById('live-watch-avatar').textContent = initial;
  document.getElementById('live-watch-title').textContent = live.title || 'Dreamland Live';
  document.getElementById('live-watch-creator').textContent = `@${creator.username || 'creator'}`;
  updateLiveBroadcastViewerCount(live.viewer_count || 1);

  const chatList = document.getElementById('live-chat-list');
  if (chatList) chatList.innerHTML = '';

  const room = document.getElementById('live-watch');
  room?.classList.remove('hidden');
  room?.setAttribute('aria-hidden', 'false');
  document.body.classList.add('live-room-open');

  if (live.rtc) {
    try {
      const liveClient = await ensureDreamlandLive();
      await liveClient.startWatching({
        rtc: live.rtc,
        userId: state.user?.id,
        videoEl: document.getElementById('live-watch-video'),
        onChat: (msg) => appendLiveChatMessage(msg, 'live-chat-list'),
      });
    } catch (err) {
      showToast(err.message || 'Could not connect to live video');
    }
  }
}

async function closeLiveWatchRoom() {
  if (dreamlandLive) await dreamlandLive.stopWatching();
  state.activeLiveWatch = null;
  const room = document.getElementById('live-watch');
  room?.classList.add('hidden');
  room?.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('live-room-open');
  const chatList = document.getElementById('live-chat-list');
  if (chatList) chatList.innerHTML = '';
}

function appendLiveChatMessage(msg, listId = 'live-chat-list') {
  const list = document.getElementById(listId);
  if (!list || !msg) return;
  const row = document.createElement('div');
  row.className = listId === 'live-broadcast-chat-list' ? 'live-broadcast__chat-msg' : 'live-room__chat-msg';
  row.innerHTML = `<strong>@${escapeHtml(msg.username || msg.user || 'viewer')}</strong> ${escapeHtml(msg.text || '')}`;
  list.appendChild(row);
  while (list.children.length > 24) list.removeChild(list.firstChild);
  list.scrollTop = list.scrollHeight;
}

function sendLiveChatMessage() {
  const input = document.getElementById('live-chat-input');
  const text = input?.value?.trim();
  if (!text || !dreamlandLive) return;
  dreamlandLive.sendChat(text);
  appendLiveChatMessage({ username: state.user?.username || 'you', text });
  if (input) input.value = '';
}

async function loadFeed(force = false, append = false) {
  if (state.feedLoading) return;
  if (force) {
    state.feedPage = 1;
    state.feedHasMore = true;
    if (!append) state.feed = [];
  }
  if (!state.feedHasMore && append) return;

  state.feedLoading = true;
  state.feedError = '';
  try {
    let path = `${API_ROUTES.feed}&page=${state.feedPage}`;
    if (state.feedSource === 'following') {
      path += '&is_following_user_post=1';
    } else {
      path += '&is_ai_feed=1';
    }
    if (state.feedGenre) path += `&category_id=${encodeURIComponent(state.feedGenre)}`;
    const res = await apiWithRetry(path);
    const items = parseFeedItems(res.data).filter((post) => !dlSocial?.isHiddenCreator?.(post.user?.id));
    if (append) state.feed = [...state.feed, ...items];
    else state.feed = items;

    state.feedHasMore = items.length >= 20;
    if (items.length) state.feedPage += 1;

    if (state.feedSource === 'foryou' && state.feed.length && dlAi?.isEnabled?.()) {
      await maybeRankFeedClient();
    }

    if (!state.feed.length && !append) {
      console.warn('Feed API returned 0 reels from', API_BASE);
    }
  } catch (err) {
    console.warn('Feed API failed:', err.message, API_BASE);
    if (!append) state.feed = [];
    state.feedError = err.message || 'Could not load feed';
  } finally {
    state.feedLoading = false;
  }
  renderFeed();
}

async function maybeRankFeedClient() {
  if (!state.token || state.feed.length < 2) return;
  try {
    const res = await api(API_ROUTES.aiRankFeed, {
      method: 'POST',
      body: JSON.stringify({
        posts: state.feed.slice(0, 20).map((p) => ({
          id: p.id,
          title: p.title,
          description: p.description,
          total_view: p.total_view,
          total_like: p.total_like,
          total_share: p.total_share,
          category_id: p.category_id,
          created_at: p.created_at,
        })),
        preferences: { genre: state.feedGenre || null },
      }),
    });
    const ranked = res.data?.posts || res.raw?.posts;
    if (Array.isArray(ranked) && ranked.length) {
      const tail = state.feed.slice(20);
      const byId = new Map(state.feed.map((p) => [Number(p.id), p]));
      const head = ranked.map((r) => byId.get(Number(r.id))).filter(Boolean);
      state.feed = [...head, ...tail];
    }
  } catch { /* SQL order fallback */ }
}

function scrollToReel(postId) {
  const reel = els.feedList?.querySelector(`.reel[data-id="${postId}"]`);
  if (reel) {
    reel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    setupReelPlayback();
  }
}

function scrollToReelFromUrl() {
  const reelId = new URLSearchParams(window.location.search).get('reel');
  if (reelId) scrollToReel(reelId);
}

window.__dlScrollToReel = scrollToReel;

function bindFeedScroll() {
  if (feedScrollBound || !els.feedList) return;
  feedScrollBound = true;
  els.feedList.addEventListener('scroll', () => {
    if (state.feedMode !== 'reels' || state.feedLoading || !state.feedHasMore) return;
    const nearBottom = els.feedList.scrollTop + els.feedList.clientHeight >= els.feedList.scrollHeight - 320;
    if (nearBottom) loadFeed(false, true);
  }, { passive: true });
}

function switchFeedSource(source) {
  state.feedSource = source;
  document.querySelectorAll('.feed-source-tab').forEach((tab) => {
    const active = tab.dataset.feedSource === source;
    tab.classList.toggle('active', active);
    tab.setAttribute('aria-selected', active ? 'true' : 'false');
  });
  loadFeed(true);
}

function loadReferralFromUrl() {
  const ref = new URLSearchParams(window.location.search).get('ref');
  if (ref) localStorage.setItem('dreamland_ref', ref);
}

async function loadPackages() {
  try {
    const res = await api(API_ROUTES.packages);
    state.packages = res.data?.packages || [];
    const key = res.data?.paystack_public_key;
    if (key) {
      PAYSTACK_KEY = key;
      localStorage.setItem('paystack_public_key', key);
    }
  } catch (err) {
    console.warn('Packages API failed:', err.message);
    if (!state.packages.length) {
      state.packages = [
        { id: 'demo-1', credit_amount: 50, fiat_cost: 5, currency: 'GHS', label: '50 Credits — GHS 5.00' },
        { id: 'demo-2', credit_amount: 120, fiat_cost: 10, currency: 'GHS', label: '120 Credits — GHS 10.00' },
      ];
    }
  }

  els.packageGrid.innerHTML = state.packages.map((pkg) => `
    <div class="package-card" data-id="${pkg.id}" data-amount="${pkg.fiat_cost}" data-credits="${pkg.credit_amount}">
      <div>
        <strong>${escapeHtml(pkg.label || `${pkg.credit_amount} Credits`)}</strong>
        <p class="muted" style="margin:0;font-size:0.82rem">${DEV_ALLOW_BROWSER ? 'Tap to add demo credits (localhost)' : 'Instant delivery via Paystack'}</p>
      </div>
      <span class="pkg-price">${escapeHtml(pkg.currency || 'GHS')} ${Number(pkg.fiat_cost).toFixed(2)}</span>
    </div>`).join('');

  els.packageGrid.querySelectorAll('.package-card').forEach((card) => {
    card.onclick = () => checkoutPackage(card.dataset);
  });
}

async function checkoutPackage(pkg) {
  if (gateGuest()) return;
  try {
    const init = await api(API_ROUTES.paystackInit, {
      method: 'POST',
      body: JSON.stringify({ package_id: pkg.id, email: state.user?.email }),
    });
    const checkout = init.data?.data || init.data || {};
    if (checkout.authorization_url) {
      window.location.href = checkout.authorization_url;
      return;
    }
    if (checkout.public_key) PAYSTACK_KEY = checkout.public_key;
  } catch (e) {
    if (DEV_ALLOW_BROWSER) {
      try {
        const dev = await api(API_ROUTES.walletDevTopup, {
          method: 'POST',
          body: JSON.stringify({ package_id: pkg.id }),
        });
        const payload = dev.data?.data || dev.data || {};
        const granted = payload.credits_granted;
        if (state.user) {
          if (payload.available_coin != null) state.user.available_coin = payload.available_coin;
          else if (granted) state.user.available_coin = (Number(state.user.available_coin) || 0) + Number(granted);
        }
        localStorage.setItem('dreamland_user', JSON.stringify(state.user));
        updateWalletBalance();
        showToast(dev.message || `+${granted || pkg.credits} credits (local dev)`);
        return;
      } catch (devErr) {
        showToast(devErr.message || 'Could not add demo credits');
        return;
      }
    }
    console.warn('Server initialize failed, falling back to inline Paystack', e.message);
  }

  if (!PAYSTACK_KEY) {
    showToast('Paystack not configured — use local dev top-up on localhost');
    return;
  }
  if (!window.PaystackPop) {
    const script = document.createElement('script');
    script.src = 'https://js.paystack.co/v1/inline.js';
    script.onload = () => checkoutPackage(pkg);
    document.body.appendChild(script);
    return;
  }
  const handler = PaystackPop.setup({
    key: PAYSTACK_KEY,
    email: state.user?.email || 'guest@dreamland.app',
    amount: Math.round(Number(pkg.amount) * 100),
    currency: 'GHS',
    callback: async (response) => {
      try {
        await api(`${API_ROUTES.paystackVerify}?reference=${encodeURIComponent(response.reference)}`);
        showToast('Top-up complete — credits added to your wallet');
      } catch (err) {
        alert(err.message || 'Payment verification failed.');
      }
    },
  });
  handler.openIframe();
}

async function unlockPaywallContent() {
  if (gateGuest()) return;
  if (state.paywallType === 'live') {
    if (!state.currentPaywallLive) return;
    try {
      const res = await api(API_ROUTES.liveUnlock, {
        method: 'POST',
        body: JSON.stringify({ live_id: Number(state.currentPaywallLive) }),
      });
      els.paywallModal.classList.add('hidden');
      showToast(res.message || 'Live unlocked');
      const spent = res.data?.credits_spent;
      if (state.user && spent) {
        state.user.available_coin = Math.max(0, (state.user.available_coin || 0) - spent);
        localStorage.setItem('dreamland_user', JSON.stringify(state.user));
        updateAuthUi();
      } else if (state.user) {
        await validateSession();
      }
      await loadLives(true);
      await enterLiveRoom(state.currentPaywallLive);
    } catch (err) {
      if (err.status === 401) {
        openAuthModal();
        showToast('Sign in to unlock live streams.');
        return;
      }
      showToast(err.message || 'Unlock failed');
    }
    return;
  }

  if (!state.currentPaywallVideo) return;
  try {
    const res = await api(API_ROUTES.unlockVideo, {
      method: 'POST',
      body: JSON.stringify({ video_id: Number(state.currentPaywallVideo) }),
    });
    els.paywallModal.classList.add('hidden');
    showToast(res.message || 'Video unlocked');
    const spent = res.data?.credits_spent;
    if (state.user && spent) {
      state.user.available_coin = Math.max(0, (state.user.available_coin || 0) - spent);
      localStorage.setItem('dreamland_user', JSON.stringify(state.user));
      updateAuthUi();
    } else if (state.user) {
      await validateSession();
    }
    await loadFeed();
  } catch (err) {
    if (err.status === 401) {
      openAuthModal();
      showToast('Sign in to unlock premium reels.');
      return;
    }
    showToast(err.message || 'Unlock failed');
  }
}

async function unlockVideo() {
  await unlockPaywallContent();
}

function openAuthModal(mode = 'signin') {
  setAuthMode(mode);
  els.authModal.classList.remove('hidden');
}

const ROLE_META = {
  viewer: { label: 'Viewer', badge: 'Play · Watch · Earn' },
  creator: { label: 'Creator', badge: 'Create · Live · Monetize' },
};

let authSignupStep = 'role';

function getSelectedRole(formEl) {
  if (!formEl) return 'viewer';
  return formEl.querySelector('input[name="account_type"]:checked')?.value || 'viewer';
}

function renderRoleChip(chipEl, role) {
  if (!chipEl) return;
  const meta = ROLE_META[role] || ROLE_META.viewer;
  chipEl.innerHTML = `<span class="role-selected-chip-text"><strong>${meta.label}</strong> · ${meta.badge}</span><span class="role-selected-chip-tag">Selected</span>`;
  chipEl.classList.toggle('role-selected-chip--creator', role === 'creator');
}

function updateRoleCardSelection(scope) {
  const root = scope?.querySelector ? scope : document;
  root.querySelectorAll('.role-card, .account-type-option').forEach((opt) => {
    const checked = opt.querySelector('input[type="radio"]')?.checked;
    opt.classList.toggle('account-type-option--selected', checked);
    opt.classList.toggle('role-card--selected', checked);
  });
}

function isSignupRoleStepActive(root) {
  if (root?.id === 'signup-view' || root?.querySelector?.('#signup-step-role')) {
    const roleStep = document.getElementById('signup-step-role');
    return Boolean(roleStep && !roleStep.classList.contains('hidden'));
  }
  if (root?.id === 'auth-modal' || root?.querySelector?.('#auth-signup-step-role')) {
    const roleStep = root.querySelector?.('#auth-signup-step-role') || document.getElementById('auth-signup-step-role');
    return authMode === 'signup' && authSignupStep === 'role' && roleStep && !roleStep.classList.contains('hidden');
  }
  return false;
}

function advanceSignupToDetails(root) {
  if (!isSignupRoleStepActive(root)) return;

  if (root?.id === 'auth-modal' || root?.querySelector?.('#auth-signup-step-role')) {
    setAuthSignupStep('details');
    window.requestAnimationFrame(() => document.getElementById('auth-name')?.focus());
    return;
  }

  setPageSignupStep('details');
  window.requestAnimationFrame(() => document.getElementById('signup-name')?.focus());
}

function setPageSignupStep(step) {
  const roleStep = document.getElementById('signup-step-role');
  const detailStep = document.getElementById('signup-step-details');
  roleStep?.classList.toggle('hidden', step !== 'role');
  roleStep?.classList.toggle('signup-step--active', step === 'role');
  detailStep?.classList.toggle('hidden', step !== 'details');
  detailStep?.classList.toggle('signup-step--active', step === 'details');
  const eyebrow = document.getElementById('signup-eyebrow');
  const heading = document.getElementById('signup-heading');
  const lede = document.getElementById('signup-lede');
  if (step === 'role') {
    if (eyebrow) eyebrow.textContent = 'Join Dreamland';
    if (heading) heading.textContent = 'Choose your path';
    if (lede) lede.textContent = 'Premium access tailored to how you play, watch, or create.';
  } else {
    if (eyebrow) eyebrow.textContent = 'Almost there';
    if (heading) heading.textContent = 'Create your account';
    if (lede) lede.textContent = 'Finish your profile to enter Dreamland.';
    renderRoleChip(document.getElementById('signup-role-chip'), getSelectedRole(document.getElementById('signup-form')));
  }
}

function setAuthSignupStep(step) {
  authSignupStep = step;
  const modal = els.authModal;
  modal?.querySelector('#auth-signup-step-role')?.classList.toggle('hidden', step !== 'role');
  modal?.querySelector('#auth-signup-step-details')?.classList.toggle('hidden', step !== 'details');
  modal?.querySelectorAll('.auth-signup-detail').forEach((el) => {
    el.classList.toggle('hidden', step !== 'details');
  });
  const submit = document.getElementById('auth-submit');
  const signupFooters = modal?.querySelectorAll('.auth-switch.auth-signup-only') || [];
  if (submit) submit.classList.toggle('hidden', authMode === 'signup' && step === 'role');
  signupFooters.forEach((el) => {
    el.classList.toggle('hidden', authMode !== 'signup' || step === 'role');
  });
  syncAuthSignupRequirements();
  const title = document.getElementById('auth-modal-title');
  const sub = document.getElementById('auth-modal-sub');
  if (authMode !== 'signup') return;
  if (step === 'role') {
    if (title) title.textContent = 'Choose your path';
    if (sub) sub.textContent = 'Select Viewer or Creator — your premium Dreamland experience starts here.';
  } else {
    if (title) title.textContent = 'Create your account';
    if (sub) sub.textContent = 'Finish your profile to unlock Play, Watch & Earn.';
    renderRoleChip(document.getElementById('auth-role-chip'), getSelectedRole(document.getElementById('auth-form')));
  }
}

function syncAuthSignupRequirements() {
  const detailRequired = authMode === 'signup' && authSignupStep === 'details';
  ['auth-username', 'auth-name', 'auth-password-confirm', 'auth-terms', 'auth-email', 'auth-password'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.required = detailRequired;
  });
  const signinEmail = document.getElementById('auth-signin-email');
  const signinPassword = document.getElementById('auth-signin-password');
  if (signinEmail) signinEmail.required = authMode === 'signin';
  if (signinPassword) signinPassword.required = authMode === 'signin';
}

function setAuthForgotStep(step, options = {}) {
  const { preserveToken = false } = options;
  authForgotStep = step;
  if (!preserveToken && step === 'email') passwordResetToken = null;

  document.getElementById('auth-forgot-step-email')?.classList.toggle('hidden', step !== 'email');
  document.getElementById('auth-forgot-step-otp')?.classList.toggle('hidden', step !== 'otp');
  document.getElementById('auth-forgot-step-password')?.classList.toggle('hidden', step !== 'password');

  const title = document.getElementById('auth-modal-title');
  const sub = document.getElementById('auth-modal-sub');
  if (step === 'email') {
    if (title) title.textContent = 'Reset password';
    if (sub) sub.textContent = 'Enter your email and we will send a verification code.';
    window.requestAnimationFrame(() => document.getElementById('forgot-email')?.focus());
  } else if (step === 'otp') {
    if (title) title.textContent = 'Enter verification code';
    if (sub) sub.textContent = 'Check your email for the 6-digit reset code.';
    window.requestAnimationFrame(() => document.getElementById('forgot-otp')?.focus());
  } else {
    if (title) title.textContent = 'Choose new password';
    if (sub) sub.textContent = 'Create a new password with at least 6 characters.';
    window.requestAnimationFrame(() => document.getElementById('forgot-new-password')?.focus());
  }
}

function bindPasswordToggles(scope = document) {
  scope.querySelectorAll('.password-toggle').forEach((btn) => {
    if (btn.__passwordToggleBound) return;
    btn.__passwordToggleBound = true;
    btn.addEventListener('click', () => {
      const wrap = btn.closest('.password-input-wrap');
      const input = wrap?.querySelector('input');
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      btn.querySelector('.password-toggle-icon--show')?.toggleAttribute('hidden', show);
      btn.querySelector('.password-toggle-icon--hide')?.toggleAttribute('hidden', !show);
    });
  });
}

async function requestPasswordReset(email) {
  const res = await api(API_ROUTES.forgotPassword, {
    method: 'POST',
    body: JSON.stringify({ email: String(email || '').trim() }),
  });
  passwordResetToken = res.data?.token || null;
  if (!passwordResetToken) throw new Error('Could not start password reset.');
  return res;
}

async function verifyPasswordResetOtp(otp) {
  const res = await api(API_ROUTES.verifyResetOtp, {
    method: 'POST',
    body: JSON.stringify({ token: passwordResetToken, otp: String(otp || '').trim() }),
  });
  passwordResetToken = res.data?.token || passwordResetToken;
  return res;
}

async function savePasswordReset(password) {
  return api(API_ROUTES.resetPassword, {
    method: 'POST',
    body: JSON.stringify({ token: passwordResetToken, password }),
  });
}

function setAuthMode(mode, options = {}) {
  const { preserveSignupStep = false, forgotStep = 'email' } = options;
  authMode = mode;
  const signinTab = document.getElementById('auth-tab-signin');
  const signupTab = document.getElementById('auth-tab-signup');
  const signupBlocks = document.querySelectorAll('.auth-signup-only');
  const signinFields = document.querySelectorAll('.auth-signin-only');
  const title = document.getElementById('auth-modal-title');
  const sub = document.getElementById('auth-modal-sub');
  const submit = document.getElementById('auth-submit');
  const isForgot = mode === 'forgot';

  document.querySelector('.auth-tabs')?.classList.toggle('hidden', isForgot);
  document.getElementById('auth-forgot-panel')?.classList.toggle('hidden', !isForgot);

  signinTab?.classList.toggle('active', mode === 'signin');
  signupTab?.classList.toggle('active', mode === 'signup');
  signinTab?.setAttribute('aria-selected', mode === 'signin' ? 'true' : 'false');
  signupTab?.setAttribute('aria-selected', mode === 'signup' ? 'true' : 'false');
  signupBlocks.forEach((el) => el.classList.toggle('hidden', mode !== 'signup'));
  signinFields.forEach((el) => el.classList.toggle('hidden', mode !== 'signin'));

  if (title && mode === 'signin') title.textContent = 'Welcome back';
  if (sub && mode === 'signin') sub.textContent = 'Sign in to watch, play, and earn on Dreamland.';
  if (submit) {
    submit.textContent = mode === 'signup' ? 'Create account' : 'Sign in';
    submit.classList.toggle('hidden', isForgot || (mode === 'signup' && authSignupStep === 'role'));
  }

  showAuthErrors('auth-errors', []);

  if (isForgot) {
    setAuthForgotStep(forgotStep, { preserveToken: forgotStep !== 'email' });
    return;
  }

  passwordResetToken = null;
  document.getElementById('forgot-dev-otp')?.classList.add('hidden');

  if (mode === 'signup') {
    if (!preserveSignupStep) setAuthSignupStep('role');
    else syncAuthSignupRequirements();
    bindAccountTypeCards(els.authModal);
  } else {
    authSignupStep = 'role';
    syncAuthSignupRequirements();
  }
}

function showAuthErrors(containerId, messages) {
  const box = document.getElementById(containerId);
  if (!box) return;
  if (!messages.length) {
    box.classList.add('hidden');
    box.innerHTML = '';
    return;
  }
  box.classList.remove('hidden');
  box.innerHTML = messages.map((msg) => `<p>${escapeHtml(msg)}</p>`).join('');
}

function validateSignupPayload({ name, username, email, password, passwordConfirm, termsAccepted }) {
  const errors = [];
  if (!name || String(name).trim().length < 2) errors.push('Enter your full name (at least 2 characters).');
  if (!username || String(username).trim().length < 2) errors.push('Choose a username (at least 2 characters).');
  else if (!/^[a-zA-Z0-9_]+$/.test(String(username).trim())) errors.push('Username can only use letters, numbers, and underscores.');
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).trim())) errors.push('Enter a valid email address.');
  if (!password || String(password).length < 6) errors.push('Password must be at least 6 characters.');
  if (password !== passwordConfirm) errors.push('Passwords do not match.');
  if (!termsAccepted) errors.push('Accept the terms to create your account.');
  return errors;
}

function bindAccountTypeCards(scope = document) {
  const root = scope?.querySelector ? scope : document;
  if (root.__rolePickerBound) {
    updateRoleCardSelection(root);
    return;
  }
  root.__rolePickerBound = true;
  root.addEventListener('change', (e) => {
    if (!e.target.matches('input[name="account_type"]')) return;
    updateRoleCardSelection(root);
    const chip = root.querySelector('#signup-role-chip') || root.querySelector('#auth-role-chip');
    if (chip) renderRoleChip(chip, e.target.value);
    advanceSignupToDetails(root);
  });
  root.querySelectorAll('.role-card').forEach((card) => {
    card.addEventListener('click', () => {
      const input = card.querySelector('input[type="radio"]');
      if (!input) return;
      input.checked = true;
      updateRoleCardSelection(root);
      const chip = root.querySelector('#signup-role-chip') || root.querySelector('#auth-role-chip');
      if (chip) renderRoleChip(chip, input.value);
      advanceSignupToDetails(root);
    });
  });
  updateRoleCardSelection(root);
}

function bindSignupFlowUi() {
  document.getElementById('signup-role-back')?.addEventListener('click', () => setPageSignupStep('role'));
  document.getElementById('auth-role-back')?.addEventListener('click', () => {
    setAuthSignupStep('role');
    setAuthMode('signup', { preserveSignupStep: true });
  });
  bindAccountTypeCards(document.getElementById('signup-view'));
  setPageSignupStep('role');
}

async function registerUser({ name, username, email, password, accountType }) {
  const referralUserId = localStorage.getItem('dreamland_ref') || new URLSearchParams(window.location.search).get('ref');
  const payload = {
    name: String(name || username).trim(),
    username: String(username).trim(),
    email: String(email).trim(),
    password,
    account_type: accountType,
    device_type: '3',
  };
  if (referralUserId) payload.referral_user_id = Number(referralUserId);
  const res = await api(API_ROUTES.register, {
    method: 'POST',
    body: JSON.stringify(payload),
  });
  const user = res.data?.user;
  const token = res.data?.auth_key || user?.auth_key;
  if (!token) throw new Error(res.message || 'Registration failed.');
  state.token = token;
  state.user = user;
  state.creatorDashboard = null;
  state.viewerDashboard = null;
  localStorage.setItem('dreamland_token', token);
  localStorage.setItem('dreamland_user', JSON.stringify(user));
  updateAuthUi();
  closeAuthModal();
  if (isCreator(user)) {
    await loadCreatorDashboard(true);
    switchView('creator-view');
    if (!canPublishReels(user)) {
      showToast(res.message || 'Creator account created — awaiting admin approval before you can upload.');
    }
  } else {
    await loadViewerDashboard(true);
    switchView('viewer-view');
  }
}

function finishAuthSession(user, token, options = {}) {
  state.token = token;
  state.user = user;
  state.creatorDashboard = null;
  state.viewerDashboard = null;
  localStorage.setItem('dreamland_token', token);
  localStorage.setItem('dreamland_user', JSON.stringify(user));
  updateAuthUi();
  if (!options.silent) closeAuthModal();
}

async function loginUser(email, password, options = {}) {
  const res = await api(API_ROUTES.login, {
    method: 'POST',
    body: JSON.stringify({
      email,
      password,
      device_type: '3',
      device_token: '',
      device_token_voip_ios: '',
      login_ip: '127.0.0.1',
      login_location: '',
    }),
  });

  const user = res.data?.user;
  const token = res.data?.auth_key || user?.auth_key;
  if (!token) throw new Error(res.message || 'Login failed — no auth token returned.');

  finishAuthSession(user, token, options);
  if (isCreator(user)) {
    await loadCreatorDashboard(true);
    if (options.goDashboard !== false) switchView('creator-view');
  } else {
    await loadViewerDashboard(true);
    if (options.goDashboard !== false) switchView('viewer-view');
  }
}

function demoFeed() {
  return [{
    id: 1,
    title: 'Welcome to Dreamland (demo — API offline or empty feed)',
    user: { username: 'dreamland' },
    dreamland: { is_paid: true, is_unlocked: false, preview_seconds: 5, paywall: { price_credits: 15, preview_loop_seconds: 5, message: 'Unlock full video for 15 Credits' } },
    postGallary: [],
  }];
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function bindLiveBroadcastUi() {
  document.getElementById('live-broadcast-close')?.addEventListener('click', () => closeLiveBroadcast(false));
  document.getElementById('live-broadcast-flip')?.addEventListener('click', flipLiveBroadcastCamera);
  document.getElementById('live-broadcast-flip-live')?.addEventListener('click', flipLiveBroadcastCamera);
  document.getElementById('live-broadcast-start')?.addEventListener('click', startLiveSession);
  document.getElementById('live-broadcast-end')?.addEventListener('click', endLiveSession);
  document.getElementById('live-monetized')?.addEventListener('change', (e) => {
    document.querySelector('.live-broadcast__price-field')?.classList.toggle('hidden', !e.target.checked);
  });
}

function bindUi() {
  bindRecordCaptureUi();
  bindLiveBroadcastUi();

  document.querySelectorAll('.dock-item[data-view]').forEach((btn) => {
    btn.onclick = () => {
      if (btn.classList.contains('guest-gate') && gateGuest()) return;
      if (btn.dataset.view === 'feed-view' && state.feedMode === 'live') {
        switchFeedMode('reels');
      }
      switchView(btn.dataset.view);
    };
  });

  document.querySelectorAll('.guest-gate').forEach((el) => {
    if (el.closest('.reel-rail') || el.closest('.reels-feed')) return;
    el.addEventListener('click', (e) => {
      if (el.dataset.action && gateGuest()) e.stopPropagation();
    });
  });

  document.getElementById('brand-home')?.addEventListener('click', (e) => {
    e.preventDefault();
    switchView('feed-view');
  });

  document.getElementById('auth-btn')?.addEventListener('click', () => {
    if (state.user) dlAccount?.openAccount();
    else openAuthModal('signin');
  });
  document.getElementById('auth-close')?.addEventListener('click', closeAuthModal);
  document.getElementById('auth-tab-signin')?.addEventListener('click', () => setAuthMode('signin'));
  document.getElementById('auth-tab-signup')?.addEventListener('click', () => setAuthMode('signup'));
  document.getElementById('auth-go-signup')?.addEventListener('click', () => setAuthMode('signup'));
  document.getElementById('auth-go-signup-page')?.addEventListener('click', () => {
    closeAuthModal();
    switchView('signup-view');
  });
  document.getElementById('signup-back')?.addEventListener('click', () => switchView('feed-view'));
  document.getElementById('signup-go-signin')?.addEventListener('click', () => {
    switchView('feed-view');
    openAuthModal('signin');
  });
  document.querySelectorAll('.feed-mode-tab').forEach((tab) => {
    tab.addEventListener('click', () => switchFeedMode(tab.dataset.feedMode));
  });
  document.getElementById('feed-back-btn')?.addEventListener('click', () => switchFeedMode('reels'));
  document.getElementById('genre-filter-toggle')?.addEventListener('click', () => {
    const filter = document.getElementById('genre-filter');
    const btn = document.getElementById('genre-filter-toggle');
    const open = filter?.classList.toggle('genre-filter--collapsed') === false;
    btn?.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn?.classList.toggle('watch-filter-btn--active', open);
  });
  document.querySelectorAll('.feed-source-tab').forEach((tab) => {
    tab.addEventListener('click', () => switchFeedSource(tab.dataset.feedSource));
  });
  document.getElementById('create-dock-btn')?.addEventListener('click', () => {
    if (gateGuest()) return;
    if (isCreator()) {
      if (!canPublishReels()) {
        requireApprovedCreator('create reels');
        switchView('creator-view');
        return;
      }
      switchView('creator-view');
      openRecordCapture();
    } else {
      switchView('signup-view');
      document.querySelector('#signup-form input[value="creator"]')?.click();
    }
  });
  document.getElementById('live-chat-send')?.addEventListener('click', sendLiveChatMessage);
  document.getElementById('live-chat-input')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') sendLiveChatMessage();
  });
  document.getElementById('live-watch-close')?.addEventListener('click', closeLiveWatchRoom);
  document.getElementById('live-watch-leave')?.addEventListener('click', closeLiveWatchRoom);
  document.getElementById('live-watch-share')?.addEventListener('click', async () => {
    const live = state.activeLiveWatch;
    if (!live?.id) return;
    try {
      if (dlSocial?.sharePost) {
        await dlSocial.sharePost({ id: live.id, title: live.title, type: 'live' });
      } else if (navigator.share) {
        await navigator.share({ title: live.title || 'Dreamland Live', url: `${window.location.origin}/?live=${live.id}` });
      } else {
        showToast('Link copied to clipboard');
      }
    } catch {
      showToast('Could not share live');
    }
  });
  document.querySelectorAll('.auth-demo-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const demo = btn.dataset.demo === 'creator'
        ? DEV_CREATOR_LOGIN
        : { email: 'viewer@dreamland.app', password: 'demo123' };
      try {
        await loginUser(demo.email, demo.password);
        showToast(`Welcome, ${state.user?.username || 'Dreamlander'}`);
        closeAuthModal();
      } catch (err) {
        showToast(err.message || 'Demo login failed');
      }
    });
  });
  bindSignupFlowUi();
  bindPasswordToggles(document);
  document.getElementById('auth-forgot-open')?.addEventListener('click', () => {
    const email = document.getElementById('auth-signin-email')?.value?.trim();
    setAuthMode('forgot', { forgotStep: 'email' });
    if (email) document.getElementById('forgot-email').value = email;
  });
  document.getElementById('auth-forgot-back')?.addEventListener('click', () => setAuthMode('signin'));
  document.getElementById('forgot-send-code')?.addEventListener('click', async () => {
    const email = document.getElementById('forgot-email')?.value?.trim();
    if (!email) {
      showAuthErrors('auth-errors', ['Enter your email address.']);
      return;
    }
    try {
      showAuthErrors('auth-errors', []);
      const res = await requestPasswordReset(email);
      const devOtp = res.data?.otp || res.otp;
      const devHint = document.getElementById('forgot-dev-otp');
      if (devHint && devOtp) {
        devHint.textContent = `Dev reset code: ${devOtp}`;
        devHint.classList.remove('hidden');
      } else {
        devHint?.classList.add('hidden');
      }
      setAuthMode('forgot', { forgotStep: 'otp' });
      showToast(res.message || res.data?.message || 'Reset code sent.');
    } catch (err) {
      showAuthErrors('auth-errors', [err.message || 'Could not send reset code.']);
    }
  });
  document.getElementById('forgot-verify-otp')?.addEventListener('click', async () => {
    const otp = document.getElementById('forgot-otp')?.value?.trim();
    if (!otp) {
      showAuthErrors('auth-errors', ['Enter the verification code.']);
      return;
    }
    try {
      showAuthErrors('auth-errors', []);
      const res = await verifyPasswordResetOtp(otp);
      setAuthMode('forgot', { forgotStep: 'password' });
      showToast(res.message || res.data?.message || 'Code verified.');
    } catch (err) {
      showAuthErrors('auth-errors', [err.message || 'Invalid verification code.']);
    }
  });
  document.getElementById('forgot-save-password')?.addEventListener('click', async () => {
    const password = document.getElementById('forgot-new-password')?.value || '';
    const confirm = document.getElementById('forgot-new-password-confirm')?.value || '';
    if (password.length < 6) {
      showAuthErrors('auth-errors', ['Password must be at least 6 characters.']);
      return;
    }
    if (password !== confirm) {
      showAuthErrors('auth-errors', ['Passwords do not match.']);
      return;
    }
    try {
      showAuthErrors('auth-errors', []);
      const res = await savePasswordReset(password);
      showToast(res.message || res.data?.message || 'Password updated.');
      setAuthMode('signin');
    } catch (err) {
      showAuthErrors('auth-errors', [err.message || 'Could not update password.']);
    }
  });
  const paywallClose = document.getElementById('paywall-close');
  if (paywallClose) paywallClose.onclick = () => els.paywallModal?.classList.add('hidden');
  const paywallUnlock = document.getElementById('paywall-unlock');
  if (paywallUnlock) paywallUnlock.onclick = unlockPaywallContent;

  const authForm = document.getElementById('auth-form');
  if (authForm) authForm.onsubmit = async (e) => {
    e.preventDefault();
    if (authMode === 'forgot') return;
    const form = new FormData(e.target);
    try {
      if (authMode === 'signup') {
        const errors = validateSignupPayload({
          name: form.get('name'),
          username: form.get('username'),
          email: form.get('email'),
          password: form.get('password'),
          passwordConfirm: form.get('password_confirm'),
          termsAccepted: form.get('terms'),
        });
        if (errors.length) {
          showAuthErrors('auth-errors', errors);
          return;
        }
        await registerUser({
          name: form.get('name'),
          username: form.get('username'),
          email: form.get('email'),
          password: form.get('password'),
          accountType: form.get('account_type') || 'viewer',
        });
      } else {
        await loginUser(form.get('signin_email'), form.get('signin_password'));
      }
      showToast(`Welcome, ${state.user?.name || state.user?.username || 'Dreamlander'}`);
    } catch (err) {
      showAuthErrors('auth-errors', [err.message || (authMode === 'signup' ? 'Sign up failed' : 'Login failed')]);
      showToast(err.message || (authMode === 'signup' ? 'Sign up failed' : 'Login failed'));
    }
  };

  document.getElementById('signup-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    const errors = validateSignupPayload({
      name: form.get('name'),
      username: form.get('username'),
      email: form.get('email'),
      password: form.get('password'),
      passwordConfirm: form.get('password_confirm'),
      termsAccepted: form.get('terms'),
    });
    if (errors.length) {
      showAuthErrors('signup-errors', errors);
      return;
    }
    try {
      await registerUser({
        name: form.get('name'),
        username: form.get('username'),
        email: form.get('email'),
        password: form.get('password'),
        accountType: form.get('account_type') || 'viewer',
      });
      showToast(`Welcome, ${state.user?.name || state.user?.username || 'Dreamlander'}`);
    } catch (err) {
      showAuthErrors('signup-errors', [err.message || 'Sign up failed']);
      showToast(err.message || 'Sign up failed');
    }
  });

  document.getElementById('notif-btn')?.addEventListener('click', async () => {
    document.getElementById('notif-panel')?.classList.remove('hidden');
    await dlFeatures?.fetchNotifications?.();
    dlFeatures?.renderNotificationBell?.();
    if (state.token && Notification.permission !== 'granted') {
      await dlFeatures?.registerPush?.();
    }
  });
  document.getElementById('notif-close')?.addEventListener('click', () => {
    document.getElementById('notif-panel')?.classList.add('hidden');
  });
  document.getElementById('legal-back')?.addEventListener('click', () => switchView('feed-view'));

  const wheel = document.getElementById('wheel');
  if (wheel) {
    wheel.onclick = async () => {
    if (gateGuest()) return;
    try {
      const res = await api(API_ROUTES.recordGameScore, { method: 'POST', body: JSON.stringify({ score: 120 + Math.floor(Math.random() * 80) }) });
      const streak = res.data?.streak;
      const reward = streak?.milestone_reward ? `+${streak.milestone_reward} credits` : '+1 credit';
      document.getElementById('spin-result').textContent = reward;
      if (streak?.milestone_reward && state.user) {
        state.user.available_coin = (state.user.available_coin || 0) + streak.milestone_reward;
        localStorage.setItem('dreamland_user', JSON.stringify(state.user));
        updateWalletBalance();
      }
      dlFeatures?.pushNotification?.('Spin reward', reward);
    } catch {
      document.getElementById('spin-result').textContent = '+1 credit (demo)';
    }
    };
    wheel.onkeydown = (e) => { if (e.key === 'Enter' || e.key === ' ') wheel.click(); };
  }
}

function registerPwaUpdates() {
  if (!('serviceWorker' in navigator) || DEV_ALLOW_BROWSER) return;

  let refreshing = false;
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (refreshing) return;
    refreshing = true;
    window.location.reload();
  });

  navigator.serviceWorker.register('/sw.js').then((registration) => {
    registration.addEventListener('updatefound', () => {
      const worker = registration.installing;
      if (!worker) return;
      worker.addEventListener('statechange', () => {
        if (worker.state === 'installed' && navigator.serviceWorker.controller) {
          showPwaUpdatePrompt(worker);
        }
      });
    });

    if (registration.waiting) {
      showPwaUpdatePrompt(registration.waiting);
    }

    setInterval(() => registration.update().catch(() => {}), 5 * 60 * 1000);
  }).catch(() => {});

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      navigator.serviceWorker.getRegistration().then((reg) => reg?.update().catch(() => {}));
    }
  });
}

function showPwaUpdatePrompt(worker) {
  if (document.getElementById('pwa-update-toast')) return;
  const el = document.createElement('div');
  el.id = 'pwa-update-toast';
  el.className = 'pwa-update-toast';
  el.innerHTML = `
    <span>Dreamland update ready</span>
    <button type="button" class="btn-primary" id="pwa-update-apply">Refresh</button>
    <button type="button" class="btn-ghost" id="pwa-update-dismiss">Later</button>`;
  document.body.appendChild(el);
  document.getElementById('pwa-update-apply')?.addEventListener('click', () => {
    worker.postMessage({ type: 'SKIP_WAITING' });
    el.remove();
  });
  document.getElementById('pwa-update-dismiss')?.addEventListener('click', () => el.remove());
}

registerPwaUpdates();

function showBootError(message) {
  const app = document.getElementById('app');
  app?.classList.remove('hidden');
  const main = document.getElementById('views') || app;
  if (main) {
    main.innerHTML = `<div class="boot-error glass-card" style="margin:24px;padding:24px;text-align:center">
      <h2>Dreamland could not start</h2>
      <p class="muted">${message}</p>
      <p class="muted">Hard refresh (Ctrl+Shift+R) then run .\\start-walkthrough.ps1</p>
      <button type="button" class="btn-primary" onclick="location.reload()">Reload</button>
    </div>`;
  }
}

try {
  initOnboarding();
} catch (err) {
  console.error('Dreamland boot failed:', err);
  showBootError(err.message || 'JavaScript failed to load');
}
