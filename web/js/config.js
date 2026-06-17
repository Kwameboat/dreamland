/**
 * Dreamland PWA ↔ Yii2 API connection settings.
 */
const { hostname, port, protocol } = window.location;
const isLocalHost = hostname === 'localhost' || hostname === '127.0.0.1';
const isDevServer = isLocalHost || port === '3000';

/** Last-resort production API when env-config.js was built without Vercel env vars. */
const PRODUCTION_API = 'https://dreamland-t1ck.onrender.com/v1';
const PRODUCTION_UPLOADS = 'https://dreamland-t1ck.onrender.com/frontend/web/uploads/image';

function resolveApiBase() {
  if (window.__DL_API__) return window.__DL_API__.replace(/\/$/, '');

  const envApi = window.__DL_ENV__?.api;
  if (envApi) {
    return String(envApi).replace(/\/$/, '');
  }

  const stored = localStorage.getItem('dreamland_api');
  if (stored && !stored.includes(':3000/v1') && !stored.endsWith(':3000')) {
    return stored.replace(/\/$/, '');
  }

  if (isDevServer) {
    const apiHost = hostname === '127.0.0.1' ? '127.0.0.1' : 'localhost';
    return `${protocol}//${apiHost}:8080/v1`;
  }

  if (hostname.endsWith('.vercel.app') || hostname === 'dreamland-plum.vercel.app') {
    console.warn('[Dreamland] Using built-in production API URL.');
    return PRODUCTION_API;
  }

  console.error('[Dreamland] Missing DREAMLAND_API_URL. Set it in Vercel project settings.');
  return PRODUCTION_API;
}

export const API_BASE = resolveApiBase();

export const UPLOADS_BASE = localStorage.getItem('dreamland_uploads')
  || window.__DL_ENV__?.uploads
  || (API_BASE ? `${API_BASE.replace(/\/v1\/?$/, '')}/frontend/web/uploads/image` : PRODUCTION_UPLOADS);

/** Localhost service map for walkthrough */
export const LOCAL_SERVICES = {
  pwa: 'http://localhost:3000',
  api: API_BASE,
  admin: 'http://localhost:8081',
  uploads: UPLOADS_BASE,
  live: 'http://localhost:4443',
  moderation: 'http://localhost:4444',
};

/** Skip PWA install gate when running the local dev server. */
export const DEV_ALLOW_BROWSER = isDevServer;

export const DEV_CREATOR_LOGIN = {
  email: 'creator@dreamland.app',
  password: 'demo123',
};

export const API_ROUTES = {
  login: 'users/login',
  register: 'dreamland-auth/register',
  forgotPassword: 'dreamland-auth/forgot-password',
  verifyResetOtp: 'dreamland-auth/verify-reset-otp',
  resetPassword: 'dreamland-auth/reset-password',
  feed: 'posts/search-post?is_reel=1&expand=dreamland,user,postGallary',
  packages: 'wallet/packages',
  paystackInit: 'wallet/paystack-initialize',
  paystackVerify: 'wallet/paystack-verify',
  walletDevTopup: 'wallet/dev-topup',
  unlockVideo: 'video/unlock',
  profile: 'users/profile',
  profileUpdate: 'users/profile-update',
  updateProfileImage: 'users/update-profile-image',
  updateProfileCoverImage: 'users/update-profile-cover-image',
  updatePassword: 'users/update-password',
  logout: 'users/logout',
  viewerDashboard: 'viewer/dashboard',
  creatorDashboard: 'creator/dashboard',
  creatorUploadReel: 'creator/upload-reel',
  creatorAppealReel: 'creator/appeal-reel',
  creatorStartLive: 'creator/start-live',
  creatorEndLive: 'creator/end-live',
  creatorLiveStatus: 'creator/live-status',
  liveList: 'live/list',
  liveWatch: 'live/watch',
  liveUnlock: 'live/unlock',
  liveJoin: 'live/join',
  recordGameScore: 'gamification/record-game-score',
  streakStatus: 'gamification/streak-status',
  recordWatch: 'gamification/record-watch',
  freezeStreak: 'gamification/freeze-streak',
  stakePrediction: 'gamification/stake-prediction',
  watchPot: 'gamification/watch-pot',
  predictionsForVideo: 'gamification/predictions-for-video',
  walletTransactions: 'wallet/transactions',
  settings: 'dreamland-meta/settings',
  categories: 'dreamland-meta/categories',
  profileMeta: 'dreamland-meta/profile',
  notifications: 'notifications',
  pushRegister: 'push/register',
  pushUnregister: 'push/unregister',
  health: 'health',
  aiStatus: 'dreamland-ai/status',
  aiCheckText: 'dreamland-ai/check-text',
  aiCaptionSuggest: 'dreamland-ai/caption-suggest',
  aiRankFeed: 'dreamland-ai/rank-feed',
  toggleLike: 'dreamland-engagement/toggle-like',
  shareBump: 'dreamland-engagement/share-bump',
  likedIds: 'dreamland-engagement/liked-ids',
  recordWatchEvent: 'dreamland-engagement/record-watch',
  postView: 'posts/view-counter',
  addFavorite: 'favorites/add-favorite',
  removeFavorite: 'favorites/remove-favorite',
  search: 'dreamland-meta/search',
  userReels: 'dreamland-meta/user-reels',
  trendingHashtags: 'posts/trending-hashtag',
  creatorAnalytics: 'creator/analytics',
};
