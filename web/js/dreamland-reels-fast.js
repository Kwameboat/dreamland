/**
 * TikTok-style virtual reel window: max 3 reels in DOM, poster-first, aggressive prefetch, HLS.
 */
export function createFastReelsEngine(ctx) {
  const {
    feedList,
    buildReelMarkup,
    onAfterWindowRender,
    onIndexChange,
    mediaUrl,
    posterUrl,
    hlsUrl,
    getSlotHeight = () => feedList?.clientHeight || window.innerHeight,
  } = ctx;

  let posts = [];
  let activeIndex = 0;
  let scrollTimer = 0;
  let rendering = false;
  const prefetchedUrls = new Set();
  const prefetchVideos = new Map();
  const posterLinks = new Set();
  const MAX_PREFETCH = 5;
  const WINDOW = 3;

  function clampIndex(index) {
    return Math.max(0, Math.min(posts.length - 1, index));
  }

  function windowRange() {
    const startIndex = Math.max(0, activeIndex - 1);
    const endIndex = Math.min(posts.length - 1, startIndex + WINDOW - 1);
    return { startIndex, endIndex };
  }

  function warmPoster(url) {
    if (!url || posterLinks.has(url)) return;
    posterLinks.add(url);
    const link = document.createElement('link');
    link.rel = 'preload';
    link.as = 'image';
    link.href = url;
    document.head.appendChild(link);
  }

  function warmVideo(url) {
    if (!url || prefetchedUrls.has(url)) return;
    prefetchedUrls.add(url);
    if (prefetchVideos.size >= MAX_PREFETCH) {
      const firstKey = prefetchVideos.keys().next().value;
      const old = prefetchVideos.get(firstKey);
      try {
        if (old) old._playGen = (old._playGen || 0) + 1;
        old?.pause?.();
        old?.removeAttribute?.('src');
        old?.load?.();
        old?.remove?.();
      } catch { /* ignore */ }
      prefetchVideos.delete(firstKey);
      prefetchedUrls.delete(firstKey);
    }
    const video = document.createElement('video');
    video.preload = 'auto';
    video.muted = true;
    video.setAttribute('muted', '');
    video.playsInline = true;
    video.setAttribute('playsinline', '');
    video.src = url;
    video.style.cssText = 'position:absolute;width:0;height:0;opacity:0;pointer-events:none';
    document.body.appendChild(video);
    prefetchVideos.set(url, video);
  }

  function prefetchAround(index) {
    if (!posts.length) return;
    const start = Math.max(0, index - 1);
    const end = Math.min(posts.length, index + MAX_PREFETCH);
    for (let i = start; i < end; i += 1) {
      const post = posts[i];
      warmPoster(posterUrl(post));
      warmVideo(mediaUrl(post));
      const hls = hlsUrl(post);
      if (hls) warmVideo(hls);
    }
  }

  function attachVideoSources(reel, post, isActive) {
    const video = reel.querySelector('.reel-video-main');
    const poster = reel.querySelector('.reel-poster');
    if (!video || video.tagName !== 'VIDEO') return;

    const posterSrc = posterUrl(post);
    const mp4 = mediaUrl(post);
    const hls = hlsUrl(post);

    if (poster) {
      if (posterSrc) {
        poster.src = posterSrc;
        poster.hidden = false;
      } else {
        poster.removeAttribute('src');
        poster.hidden = true;
      }
    }

    video.dataset.hlsUrl = hls || '';
    video.dataset.mp4Url = mp4 || '';
    video.removeAttribute('src');
    video.preload = isActive ? 'auto' : 'metadata';

    if (isActive && (mp4 || hls)) {
      if (hls && window.Hls?.isSupported?.()) {
        video.dataset.useHls = '1';
      } else if (mp4) {
        video.src = mp4;
        video.dataset.useHls = '0';
      }
    }
  }

  function hidePosterForVideo(video) {
    const poster = video?.closest('.reel-media')?.querySelector('.reel-poster');
    if (poster) poster.classList.add('reel-poster--hidden');
  }

  function renderWindow(options = {}) {
    if (!feedList || !posts.length) return;
    if (options.index != null) activeIndex = clampIndex(options.index);

    const h = getSlotHeight();
    const { startIndex, endIndex } = windowRange();
    const preserveScroll = options.preserveScroll !== false;
    const prevScroll = feedList.scrollTop;

    rendering = true;
    ctx.onBeforeWindowRender?.();
    const parts = [];
    parts.push(`<div class="reels-v-spacer reels-v-spacer--top" style="height:${startIndex * h}px" aria-hidden="true"></div>`);

    for (let i = startIndex; i <= endIndex; i += 1) {
      parts.push(buildReelMarkup(posts[i], i, i === activeIndex));
    }

    parts.push(`<div class="reels-v-spacer reels-v-spacer--bottom" style="height:${Math.max(0, posts.length - 1 - endIndex) * h}px" aria-hidden="true"></div>`);

    feedList.innerHTML = parts.join('');
    feedList.classList.add('reels-feed--virtual');

    feedList.querySelectorAll('.reel').forEach((reel) => {
      const idx = Number(reel.dataset.index);
      const post = posts[idx];
      if (!post) return;
      attachVideoSources(reel, post, idx === activeIndex);
    });

    if (preserveScroll) {
      const target = activeIndex * h;
      if (Math.abs(prevScroll - target) > h * 0.5 || options.forceScroll) {
        feedList.scrollTop = target;
      } else {
        feedList.scrollTop = prevScroll;
      }
    } else {
      feedList.scrollTop = activeIndex * h;
    }

    rendering = false;
    onAfterWindowRender?.(feedList, activeIndex);
    prefetchAround(activeIndex);
  }

  function onScroll() {
    if (rendering || !posts.length) return;
    clearTimeout(scrollTimer);
    scrollTimer = window.setTimeout(() => {
      const h = getSlotHeight();
      if (!h) return;
      const idx = clampIndex(Math.round(feedList.scrollTop / h));
      if (idx !== activeIndex) {
        activeIndex = idx;
        renderWindow({ preserveScroll: true });
        ctx.onIndexChange?.(activeIndex);
      }
      prefetchAround(activeIndex);
    }, 60);
  }

  return {
    mount() {
      feedList?.addEventListener('scroll', onScroll, { passive: true });
    },
    destroy() {
      feedList?.removeEventListener('scroll', onScroll);
      prefetchVideos.forEach((video) => {
        try { video.remove(); } catch { /* ignore */ }
      });
      prefetchVideos.clear();
      prefetchedUrls.clear();
    },
    setPosts(nextPosts, { index = 0 } = {}) {
      posts = Array.isArray(nextPosts) ? nextPosts : [];
      activeIndex = clampIndex(index);
      if (!posts.length) {
        feedList.innerHTML = '';
        feedList.classList.remove('reels-feed--virtual');
        return;
      }
      renderWindow({ index: activeIndex, forceScroll: true, preserveScroll: false });
    },
    getActiveIndex: () => activeIndex,
    getActivePost: () => posts[activeIndex] || null,
    getPosts: () => posts,
    scrollToPostId(postId) {
      const idx = posts.findIndex((p) => String(p.id) === String(postId));
      if (idx < 0) return false;
      activeIndex = idx;
      renderWindow({ index: idx, forceScroll: true, preserveScroll: false });
      return true;
    },
    scrollToIndex(index) {
      activeIndex = clampIndex(index);
      renderWindow({ index: activeIndex, forceScroll: true, preserveScroll: false });
    },
    prefetchAround,
    attachVideoSources,
    hidePosterForVideo,
    cleanupWarm() {
      prefetchVideos.forEach((video) => {
        try { video.remove(); } catch { /* ignore */ }
      });
      prefetchVideos.clear();
      prefetchedUrls.clear();
    },
  };
}

export async function attachHlsToVideo(video, hlsUrl, HlsLib) {
  if (!video || !hlsUrl) return null;
  if (video.canPlayType('application/vnd.apple.mpegurl')) {
    video.src = hlsUrl;
    return null;
  }
  if (!HlsLib?.isSupported?.()) {
    return null;
  }
  const hls = new HlsLib({ enableWorker: true, lowLatencyMode: true, maxBufferLength: 12 });
  hls.loadSource(hlsUrl);
  hls.attachMedia(video);
  return hls;
}
