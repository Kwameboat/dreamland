/**
 * Dreamland social engagement: likes, share, sound, bookmarks, watch tracking.
 */
export function createDreamlandSocial(ctx) {
  const {
    api, API_ROUTES, state, showToast, gateGuest, formatCount, escapeHtml, UPLOADS_BASE,
  } = ctx;

  const LIKED_KEY = 'dl_liked_posts';
  const HIDDEN_KEY = 'dl_hidden_creators';
  const MUTE_KEY = 'dl_feed_muted';
  const AUDIO_UNLOCK_KEY = 'dl_audio_unlocked';

  let likedIds = new Set(JSON.parse(localStorage.getItem(LIKED_KEY) || '[]'));
  let hiddenCreators = new Set(JSON.parse(localStorage.getItem(HIDDEN_KEY) || '[]'));
  let feedMuted = localStorage.getItem(MUTE_KEY) === '1';
  let audioUnlocked = sessionStorage.getItem(AUDIO_UNLOCK_KEY) === '1';
  const watchTrackers = new Map();

  function setVideoMuted(video, muted) {
    if (!video || video.tagName !== 'VIDEO') return;
    video.muted = muted;
    if (muted) {
      video.setAttribute('muted', '');
    } else {
      video.removeAttribute('muted');
      video.volume = 1;
    }
  }

  function syncSoundHints(container) {
    const showHint = !feedMuted && !audioUnlocked;
    container?.querySelectorAll('.reel').forEach((reel) => {
      const hint = reel.querySelector('.reel-sound-hint');
      if (!hint) return;
      hint.hidden = !(showHint && reel.classList.contains('reel--active'));
    });
  }

  function unlockAudio(container) {
    audioUnlocked = true;
    sessionStorage.setItem(AUDIO_UNLOCK_KEY, '1');
    if (!feedMuted) {
      resumeActiveReelAudio(container);
    }
    syncSoundHints(container);
  }

  function isAudioUnlocked() {
    return audioUnlocked;
  }

  function persistLiked() {
    localStorage.setItem(LIKED_KEY, JSON.stringify([...likedIds]));
  }

  function persistHidden() {
    localStorage.setItem(HIDDEN_KEY, JSON.stringify([...hiddenCreators]));
  }

  function isLiked(postId) {
    return likedIds.has(Number(postId));
  }

  function isHiddenCreator(userId) {
    return hiddenCreators.has(Number(userId));
  }

  function isMuted() {
    return feedMuted;
  }

  function setMuted(muted) {
    feedMuted = muted;
    localStorage.setItem(MUTE_KEY, muted ? '1' : '0');
    const btn = document.getElementById('sound-toggle');
    btn?.classList.toggle('sound-toggle--on', !muted);
    btn?.setAttribute('aria-pressed', muted ? 'false' : 'true');
    btn?.querySelector('.icon-muted')?.toggleAttribute('hidden', !muted);
    btn?.querySelector('.icon-unmuted')?.toggleAttribute('hidden', muted);
  }

  function applySoundToActive(container) {
    container?.querySelectorAll('.reel-video-main, .reel-video:not(.reel-video-backdrop)').forEach((video) => {
      if (video.tagName !== 'VIDEO') return;
      const reel = video.closest('.reel');
      const active = reel?.classList.contains('reel--active');
      const shouldMute = feedMuted || !active || !audioUnlocked;
      setVideoMuted(video, shouldMute);
    });
    syncSoundHints(container);
  }

  /** Unmute and resume the visible reel (call from a click/tap so audio is allowed). */
  function resumeActiveReelAudio(container) {
    const video = container?.querySelector('.reel--active .reel-video-main, .reel--active .reel-video:not(.reel-video-backdrop)');
    if (video?.tagName !== 'VIDEO' || feedMuted) {
      return Promise.resolve();
    }
    setVideoMuted(video, false);
    syncSoundHints(container);
    return video.play().catch(() => {});
  }

  function toggleSound() {
    setMuted(!feedMuted);
    showToast(feedMuted ? 'Sound off' : 'Sound on');
    const feed = document.getElementById('feed-list');
    if (!feedMuted) {
      unlockAudio(feed);
    } else {
      applySoundToActive(feed);
    }
  }

  async function syncLikedIds(postIds) {
    if (!state.token || !postIds.length) return;
    try {
      const res = await api(`${API_ROUTES.likedIds}?post_ids=${postIds.join(',')}`);
      const ids = res.data?.liked_ids || res.raw?.liked_ids || [];
      ids.forEach((id) => likedIds.add(Number(id)));
      persistLiked();
    } catch { /* offline */ }
  }

  function showHeartBurst(reel, clientX, clientY) {
    const rect = reel.getBoundingClientRect();
    const x = clientX - rect.left;
    const y = clientY - rect.top;
    const burst = document.createElement('span');
    burst.className = 'heart-burst';
    burst.textContent = '♥';
    burst.style.left = `${x}px`;
    burst.style.top = `${y}px`;
    reel.appendChild(burst);
    setTimeout(() => burst.remove(), 900);
    if (navigator.vibrate) navigator.vibrate(12);
  }

  async function toggleLike(postId, btn, countEl) {
    if (gateGuest()) return;
    const id = Number(postId);
    const wasLiked = likedIds.has(id);
    const optimistic = wasLiked ? Math.max(0, Number(countEl?.textContent?.replace(/\D/g, '') || 0) - 1) : Number(countEl?.textContent?.replace(/\D/g, '') || 0) + 1;

    if (wasLiked) likedIds.delete(id);
    else likedIds.add(id);
    persistLiked();
    btn?.classList.toggle('rail-btn--liked', !wasLiked);
    if (countEl) countEl.textContent = formatCount(optimistic);

    try {
      const res = await api(API_ROUTES.toggleLike, {
        method: 'POST',
        body: JSON.stringify({ post_id: id }),
      });
      const liked = Boolean(res.data?.liked ?? res.raw?.liked);
      const total = res.data?.total_like ?? res.raw?.total_like;
      if (liked) likedIds.add(id);
      else likedIds.delete(id);
      persistLiked();
      btn?.classList.toggle('rail-btn--liked', liked);
      if (countEl && total != null) countEl.textContent = formatCount(total);
    } catch (err) {
      if (wasLiked) likedIds.add(id);
      else likedIds.delete(id);
      persistLiked();
      btn?.classList.toggle('rail-btn--liked', wasLiked);
      if (countEl) countEl.textContent = formatCount(Math.max(0, optimistic + (wasLiked ? 1 : -1)));
      showToast(err.message || 'Could not update like');
    }
  }

  function shareUrl(postId) {
    const url = new URL(window.location.href);
    url.searchParams.set('reel', String(postId));
    if (state.user?.id) url.searchParams.set('ref', String(state.user.id));
    return url.toString();
  }

  async function shareReel(post) {
    const link = shareUrl(post.id);
    const title = post.title || 'Dreamland Reel';
    try {
      if (navigator.share) {
        await navigator.share({ title, text: `Watch on Dreamland: ${title}`, url: link });
      } else {
        await navigator.clipboard.writeText(link);
        showToast('Link copied');
      }
      if (state.token) {
        await api(API_ROUTES.shareBump, {
          method: 'POST',
          body: JSON.stringify({ post_id: Number(post.id) }),
        });
      }
    } catch (err) {
      if (err?.name !== 'AbortError') {
        try {
          await navigator.clipboard.writeText(link);
          showToast('Link copied');
        } catch {
          showToast('Could not share');
        }
      }
    }
  }

  async function toggleBookmark(postId) {
    if (gateGuest()) return;
    const id = Number(postId);
    const type = 3;
    try {
      await api(API_ROUTES.addFavorite, {
        method: 'POST',
        body: JSON.stringify({ reference_id: id, type }),
      });
      showToast('Saved to bookmarks');
    } catch (err) {
      if (String(err.message || '').toLowerCase().includes('already')) {
        await api(API_ROUTES.removeFavorite, {
          method: 'POST',
          body: JSON.stringify({ reference_id: id, type }),
        });
        showToast('Removed from bookmarks');
      } else {
        showToast(err.message || 'Could not save');
      }
    }
  }

  function hideCreator(userId) {
    hiddenCreators.add(Number(userId));
    persistHidden();
    showToast('We will show you less from this creator');
  }

  function recordView(postId) {
    if (!state.token) return;
    api(API_ROUTES.postView, {
      method: 'POST',
      body: JSON.stringify({ post_id: Number(postId) }),
    }).catch(() => {});
  }

  function startWatchTracking(reelEl, postId) {
    stopWatchTracking(postId);
    const video = reelEl.querySelector('.reel-video');
    if (!video || video.tagName !== 'VIDEO') return;

    const tracker = { accumMs: 0, lastTs: null, interval: null };
    tracker.interval = setInterval(() => {
      if (video.paused || !reelEl.classList.contains('reel--active')) return;
      const duration = video.duration || 0;
      const completion = duration > 0 ? Math.min(100, Math.round((video.currentTime / duration) * 100)) : 0;
      tracker.accumMs += 1000;
      if (tracker.accumMs >= 5000 && state.token) {
        const payload = {
          post_id: Number(postId),
          watch_ms: tracker.accumMs,
          completion_pct: completion,
        };
        api(API_ROUTES.recordWatchEvent, {
          method: 'POST',
          body: JSON.stringify(payload),
        }).catch(() => {
          api(API_ROUTES.recordWatch, {
            method: 'POST',
            body: JSON.stringify({ seconds: 5, ...payload }),
          }).catch(() => {});
        });
        tracker.accumMs = 0;
      }
    }, 1000);
    watchTrackers.set(Number(postId), tracker);
  }

  function stopWatchTracking(postId) {
    const tracker = watchTrackers.get(Number(postId));
    if (tracker?.interval) clearInterval(tracker.interval);
    watchTrackers.delete(Number(postId));
  }

  function stopAllWatchTracking() {
    watchTrackers.forEach((_, id) => stopWatchTracking(id));
  }

  function bindReelInteractions(container, postsById, onOpenProfile) {
    if (!container) return;

    container.querySelectorAll('.reel').forEach((reel) => {
      const postId = reel.dataset.id;
      const post = postsById.get(Number(postId));
      if (!post) return;
      if (isHiddenCreator(post.user?.id)) {
        reel.remove();
        return;
      }

      const likeBtn = reel.querySelector('[data-action="like"]');
      const likeCount = likeBtn?.querySelector('.rail-count');
      if (likeBtn) {
        likeBtn.classList.toggle('rail-btn--liked', isLiked(postId));
        likeBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (gateGuest()) return;
          toggleLike(postId, likeBtn, likeCount);
        });
      }

      reel.querySelector('[data-action="share"]')?.addEventListener('click', (e) => {
        e.stopPropagation();
        shareReel(post);
      });

      reel.querySelector('[data-action="save"]')?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleBookmark(postId);
      });

      reel.querySelector('.reel-creator')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (post.user?.id && onOpenProfile) onOpenProfile(post.user.id);
      });

      let lastTap = 0;
      const video = reel.querySelector('.reel-video');
      const feedRoot = reel.closest('#feed-list') || reel.parentElement;
      reel.querySelector('.reel-sound-hint')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (feedMuted) setMuted(false);
        unlockAudio(feedRoot);
        showToast('Sound on');
      });
      video?.addEventListener('click', (e) => {
        const now = Date.now();
        if (now - lastTap < 320) {
          e.preventDefault();
          showHeartBurst(reel, e.clientX, e.clientY);
          if (!isLiked(postId)) toggleLike(postId, likeBtn, likeCount);
        } else if (reel.classList.contains('reel--active') && (feedMuted || !audioUnlocked || video.muted)) {
          if (feedMuted) setMuted(false);
          unlockAudio(feedRoot);
          showToast('Sound on');
        }
        lastTap = now;
      });

      reel.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        if (confirm('Show fewer reels like this?')) hideCreator(post.user?.id);
      });
    });
  }

  function initFeedAudioUnlock() {
    if (document.body.dataset.dlAudioBound === '1') return;
    document.body.dataset.dlAudioBound = '1';
    const unlockFromGesture = () => {
      if (feedMuted) return;
      const feed = document.getElementById('feed-list');
      if (!feed?.querySelector('.reel--active')) return;
      unlockAudio(feed);
    };
    document.addEventListener('pointerdown', unlockFromGesture, { passive: true });
    document.addEventListener('keydown', (e) => {
      if (e.key === ' ' || e.key === 'Enter') unlockFromGesture();
    });
  }

  function initSoundToggle() {
    const btn = document.getElementById('sound-toggle');
    if (!btn || btn.__dlBound) return;
    btn.__dlBound = true;
    setMuted(feedMuted);
    btn.addEventListener('click', toggleSound);
    initFeedAudioUnlock();
  }

  return {
    isLiked,
    isHiddenCreator,
    isMuted,
    isAudioUnlocked,
    unlockAudio,
    toggleSound,
    syncLikedIds,
    bindReelInteractions,
    recordView,
    startWatchTracking,
    stopWatchTracking,
    stopAllWatchTracking,
    applySoundToActive,
    resumeActiveReelAudio,
    initSoundToggle,
    shareUrl,
  };
}
