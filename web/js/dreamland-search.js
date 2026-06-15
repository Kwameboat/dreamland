/**
 * Search users, reels, and hashtags.
 */
export function createDreamlandSearch(ctx) {
  const {
    api, API_ROUTES, escapeHtml, formatCount, switchView, openProfile,
  } = ctx;

  let debounceTimer = null;

  function openSearch() {
    switchView('search-view');
    const input = document.getElementById('search-input');
    input?.focus();
  }

  async function runSearch(q) {
    const results = document.getElementById('search-results');
    if (!results) return;
    if (!q || q.length < 2) {
      results.innerHTML = '<p class="muted">Type at least 2 characters.</p>';
      return;
    }
    results.innerHTML = '<p class="muted">Searching…</p>';
    try {
      const res = await api(`${API_ROUTES.search}?q=${encodeURIComponent(q)}`);
      const data = res.data || res.raw || {};
      renderResults(results, data, q);
    } catch (err) {
      results.innerHTML = `<p class="muted">${escapeHtml(err.message || 'Search failed')}</p>`;
    }
  }

  function renderResults(container, data, q) {
    const users = data.users || [];
    const reels = data.reels || [];
    const hashtags = data.hashtags || [];
    if (!users.length && !reels.length && !hashtags.length) {
      container.innerHTML = `<p class="muted">No results for "${escapeHtml(q)}".</p>`;
      return;
    }

    container.innerHTML = `
      ${users.length ? `<section class="search-section"><p class="eyebrow">Creators</p>${users.map((u) => `
        <button type="button" class="search-row" data-user-id="${u.id}">
          <strong>@${escapeHtml(u.username || '')}</strong>
          <span class="muted">${escapeHtml(u.name || '')}</span>
        </button>`).join('')}</section>` : ''}
      ${hashtags.length ? `<section class="search-section"><p class="eyebrow">Hashtags</p>${hashtags.map((h) => `
        <div class="search-row search-row--static"><strong>#${escapeHtml(h.hashtag || h)}</strong><span class="muted">${formatCount(h.count || 0)}</span></div>`).join('')}</section>` : ''}
      ${reels.length ? `<section class="search-section"><p class="eyebrow">Reels</p>${reels.map((r) => `
        <button type="button" class="search-row" data-reel-id="${r.id}">
          <strong>${escapeHtml(r.title || 'Reel')}</strong>
          <span class="muted">${formatCount(r.total_view || 0)} views · ${formatCount(r.total_like || 0)} likes</span>
        </button>`).join('')}</section>` : ''}`;

    container.querySelectorAll('[data-user-id]').forEach((btn) => {
      btn.addEventListener('click', () => openProfile?.(btn.dataset.userId));
    });
    container.querySelectorAll('[data-reel-id]').forEach((btn) => {
      btn.addEventListener('click', () => {
        switchView('feed-view');
        const url = new URL(window.location.href);
        url.searchParams.set('reel', btn.dataset.reelId);
        window.history.replaceState({}, '', url);
        window.__dlScrollToReel?.(btn.dataset.reelId);
      });
    });
  }

  function bindSearchUi() {
    const input = document.getElementById('search-input');
    const back = document.getElementById('search-back');
    const openBtn = document.getElementById('search-open-btn');
    if (openBtn && !openBtn.__dlBound) {
      openBtn.__dlBound = true;
      openBtn.addEventListener('click', openSearch);
    }
    if (back && !back.__dlBound) {
      back.__dlBound = true;
      back.addEventListener('click', () => switchView('feed-view'));
    }
    if (input && !input.__dlBound) {
      input.__dlBound = true;
      input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => runSearch(input.value.trim()), 280);
      });
    }
  }

  return { openSearch, bindSearchUi, runSearch };
}
