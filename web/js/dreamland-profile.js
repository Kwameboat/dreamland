/**
 * Creator profile screen with reel grid.
 */
export function createDreamlandProfile(ctx) {
  const {
    api, API_ROUTES, state, showToast, gateGuest, escapeHtml, formatCount,
    mediaUrl, openPaywall, switchView, UPLOADS_BASE,
  } = ctx;

  let currentUserId = null;

  function avatarUrl(user) {
    if (!user?.picture) return null;
    if (String(user.picture).startsWith('http')) return user.picture;
    return `${UPLOADS_BASE}/${user.picture}`;
  }

  async function openProfile(userId) {
    currentUserId = Number(userId);
    switchView('profile-view');
    const root = document.getElementById('profile-content');
    if (!root) return;
    root.innerHTML = '<div class="live-loading glass-card"><p class="eyebrow">Profile</p><h2>Loading…</h2></div>';

    try {
      const [profileRes, reelsRes] = await Promise.all([
        api(`${API_ROUTES.profileMeta}?user_id=${currentUserId}`),
        api(`${API_ROUTES.userReels}?user_id=${currentUserId}&page=1`),
      ]);
      const profile = profileRes.data?.profile || profileRes.raw?.profile || {};
      const reels = parseReels(reelsRes.data || reelsRes.raw);
      renderProfile(root, profile, reels);
    } catch (err) {
      root.innerHTML = `<div class="glass-card"><p class="muted">${escapeHtml(err.message || 'Profile unavailable')}</p></div>`;
    }
  }

  function parseReels(data) {
    if (!data) return [];
    const post = data.post ?? data.items;
    if (Array.isArray(post)) return post;
    if (post?.items && Array.isArray(post.items)) return post.items;
    return [];
  }

  function renderProfile(root, profile, reels) {
    const initial = (profile.username || profile.name || 'U').charAt(0).toUpperCase();
    const pic = avatarUrl(profile);
    root.innerHTML = `
      <div class="profile-hero glass-card">
        <button type="button" class="btn-ghost profile-back" id="profile-back">← Back</button>
        <div class="profile-head">
          <div class="profile-avatar">${pic ? `<img src="${escapeHtml(pic)}" alt="" />` : escapeHtml(initial)}</div>
          <div>
            <h1>@${escapeHtml(profile.username || 'creator')}</h1>
            <p class="muted">${escapeHtml(profile.name || '')}</p>
            ${profile.bio ? `<p class="profile-bio">${escapeHtml(profile.bio)}</p>` : ''}
          </div>
        </div>
        <div class="profile-actions">
          <button type="button" class="btn-primary" id="profile-follow">Follow</button>
        </div>
      </div>
      <div class="profile-grid">
        ${reels.length ? reels.map((post) => {
          const dream = post.dreamland || {};
          const locked = dream.is_paid && !dream.is_unlocked;
          const src = mediaUrl(post);
          return `
            <button type="button" class="profile-grid-item ${locked ? 'profile-grid-item--locked' : ''}" data-post-id="${post.id}" data-price="${dream.paywall?.price_credits || post.price_credits || 0}">
              ${src ? `<video src="${src}" muted playsinline preload="metadata"></video>` : '<span class="profile-grid-fallback">▶</span>'}
              <span class="profile-grid-meta">${formatCount(post.total_view || 0)} views</span>
              ${locked ? '<span class="profile-grid-lock">🔒</span>' : ''}
            </button>`;
        }).join('') : '<p class="muted">No reels yet.</p>'}
      </div>`;

    document.getElementById('profile-back')?.addEventListener('click', () => switchView('feed-view'));
    document.getElementById('profile-follow')?.addEventListener('click', async () => {
      if (gateGuest()) return;
      try {
        await api('followers', {
          method: 'POST',
          body: JSON.stringify({ user_id: currentUserId }),
        });
        showToast('Following');
      } catch (err) {
        showToast(err.message || 'Could not follow');
      }
    });

    root.querySelectorAll('.profile-grid-item').forEach((item) => {
      item.addEventListener('click', () => {
        const postId = item.dataset.postId;
        const price = item.dataset.price;
        if (item.classList.contains('profile-grid-item--locked') && price) {
          openPaywall(postId, price);
          return;
        }
        switchView('feed-view');
        const url = new URL(window.location.href);
        url.searchParams.set('reel', postId);
        window.history.replaceState({}, '', url);
        window.__dlScrollToReel?.(postId);
      });
    });
  }

  return { openProfile };
}
