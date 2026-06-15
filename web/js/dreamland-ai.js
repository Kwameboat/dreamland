/**
 * Dreamland AI — client helpers for smart feed, safety checks, caption assist.
 */
export function createDreamlandAi(ctx) {
  const { api, API_ROUTES, state, showToast, escapeHtml } = ctx;
  let enabled = false;
  let geminiConfigured = false;
  let capabilities = [];
  let checkTimer = null;

  async function loadStatus() {
    try {
      const res = await api(API_ROUTES.aiStatus);
      enabled = Boolean(res.data?.enabled);
      capabilities = res.data?.capabilities || [];
      geminiConfigured = Boolean(res.data?.gemini?.configured || res.data?.primary_provider === 'google-gemini');
      return res.data;
    } catch {
      enabled = false;
      geminiConfigured = false;
      capabilities = [];
      return { enabled: false };
    }
  }

  function applyBranding() {
    document.body.classList.toggle('dreamland-ai-on', enabled);
    document.body.classList.toggle('dreamland-gemini-on', geminiConfigured);
    const pill = document.getElementById('ai-powered-pill');
    if (pill) {
      pill.hidden = !enabled;
      pill.textContent = geminiConfigured ? 'Gemini AI' : (enabled ? 'AI Powered' : '');
    }
    const feedLabel = document.getElementById('feed-ai-label');
    if (feedLabel) {
      feedLabel.hidden = !enabled;
      feedLabel.textContent = geminiConfigured
        ? 'For You · ranked by Gemini + Dreamland AI'
        : 'For You · ranked by Dreamland AI';
    }
  }

  async function init() {
    await loadStatus();
    applyBranding();
    return enabled;
  }

  async function checkSignupText(name, username) {
    try {
      const res = await api(API_ROUTES.aiCheckText, {
        method: 'POST',
        body: JSON.stringify({ name, username }),
      });
      return { ok: res.data?.ok !== false, message: res.data?.summary || res.message || '' };
    } catch (err) {
      return { ok: true, message: '', offline: true, error: err.message };
    }
  }

  function bindSignupSafety(formEl, errorBoxId) {
    if (!formEl) return;
    const nameInput = formEl.querySelector('[name="name"], #signup-name, #auth-name');
    const userInput = formEl.querySelector('[name="username"], #signup-username, #auth-username');
    const hint = formEl.querySelector('.ai-signup-hint') || document.getElementById('ai-signup-hint');
    const run = async () => {
      if (!enabled || !nameInput || !userInput) return;
      const name = nameInput.value.trim();
      const username = userInput.value.trim();
      if (name.length < 2 || username.length < 2) return;
      const result = await checkSignupText(name, username);
      if (hint) {
        hint.classList.toggle('ai-signup-hint--ok', result.ok);
        hint.classList.toggle('ai-signup-hint--bad', !result.ok);
        hint.textContent = result.ok
          ? (geminiConfigured ? 'Gemini AI · profile text approved (Ghana languages)' : 'Dreamland AI · profile text looks good')
          : (result.message || 'Dreamland AI flagged this text');
      }
      if (!result.ok) {
        const box = document.getElementById(errorBoxId);
        if (box) {
          box.classList.remove('hidden');
          box.innerHTML = `<p>${escapeHtml(result.message || 'Profile text not allowed')}</p>`;
        }
      }
    };
    const schedule = () => {
      clearTimeout(checkTimer);
      checkTimer = setTimeout(run, 450);
    };
    nameInput?.addEventListener('input', schedule);
    userInput?.addEventListener('input', schedule);
  }

  async function suggestCaptions({ title, description, genre }) {
    if (!state.token) {
      showToast?.('Sign in as creator for AI captions');
      return null;
    }
    try {
      const res = await api(API_ROUTES.aiCaptionSuggest, {
        method: 'POST',
        body: JSON.stringify({ title, description, genre }),
      });
      return res.data;
    } catch (err) {
      showToast?.(err.message || 'AI caption assist unavailable');
      return null;
    }
  }

  async function applyCaptionAssist(titleEl, descEl, genreEl) {
    const suggestion = await suggestCaptions({
      title: titleEl?.value || '',
      description: descEl?.value || '',
      genre: genreEl?.selectedOptions?.[0]?.text || '',
    });
    if (!suggestion) return;
    if (titleEl && !titleEl.value.trim() && suggestion.hook) titleEl.value = suggestion.hook.slice(0, 120);
    if (descEl) {
      const tags = (suggestion.hashtags || []).join(' ');
      descEl.value = [suggestion.captions?.[0] || suggestion.hook, tags].filter(Boolean).join('\n').slice(0, 280);
    }
    showToast?.(geminiConfigured ? 'Gemini caption applied' : 'AI caption applied');
  }

  function isEnabled() {
    return enabled;
  }

  return {
    init,
    loadStatus,
    applyBranding,
    checkSignupText,
    bindSignupSafety,
    suggestCaptions,
    applyCaptionAssist,
    isEnabled,
    get capabilities() { return capabilities; },
    get geminiConfigured() { return geminiConfigured; },
  };
}
