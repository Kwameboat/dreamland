/**
 * Account management — profile editing, photos, password, sign out.
 */
export function createDreamlandAccount(ctx) {
  const {
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
  } = ctx;

  let categoriesCache = null;

  function avatarUrl(user) {
    if (user?.picture && String(user.picture).startsWith('http')) return user.picture;
    const raw = user?.image;
    if (!raw) return null;
    if (String(raw).startsWith('http')) return raw;
    const uploadsRoot = String(UPLOADS_BASE || '').replace(/\/image\/?$/, '');
    return `${uploadsRoot}/user/${raw}`;
  }

  function coverUrl(user) {
    const cover = user?.coverImageUrl || user?.cover_image;
    if (!cover) return null;
    if (String(cover).startsWith('http')) return cover;
    const uploadsRoot = String(UPLOADS_BASE || '').replace(/\/image\/?$/, '');
    return `${uploadsRoot}/user/${cover}`;
  }

  function roleLabel(user) {
    if (isCreator(user)) return 'Dreamland Creator';
    return 'Dreamland Viewer';
  }

  function flattenErrors(payload) {
    if (typeof payload === 'string' && payload) return payload;
    const errs = payload?.errors || payload?.data?.errors;
    if (!errs) {
      const msg = payload?.message || payload?.data?.message || payload?.raw?.message;
      return msg || 'Request failed';
    }
    if (Array.isArray(errs.message)) return errs.message[0];
    const flat = [];
    Object.values(errs).forEach((v) => {
      if (Array.isArray(v)) flat.push(...v);
      else if (typeof v === 'string') flat.push(v);
    });
    return flat[0] || payload?.message || 'Request failed';
  }

  async function loadCategories() {
    if (categoriesCache) return categoriesCache;
    try {
      const res = await api(API_ROUTES.categories);
      categoriesCache = res.data?.categories || res.raw?.categories || [];
    } catch {
      categoriesCache = [];
    }
    return categoriesCache;
  }

  async function fetchProfile() {
    const res = await api(API_ROUTES.profile);
    const user = res.data?.user || res.raw?.user;
    if (user) {
      state.user = { ...state.user, ...user };
      localStorage.setItem('dreamland_user', JSON.stringify(state.user));
      updateAuthUi?.();
    }
    return user || state.user;
  }

  async function signOut() {
    try {
      if (state.token) {
        await api(API_ROUTES.logout, { method: 'POST', body: JSON.stringify({}) });
      }
    } catch {
      /* clear local session even if API logout fails */
    }
    clearSession();
    showToast('Signed out');
    switchView('feed-view');
  }

  async function saveProfile(form) {
    const payload = {
      name: String(form.get('name') || '').trim(),
      username: String(form.get('username') || '').trim(),
      bio: String(form.get('bio') || '').trim(),
      description: String(form.get('description') || '').trim(),
      phone: String(form.get('phone') || '').trim(),
      country: String(form.get('country') || '').trim(),
      city: String(form.get('city') || '').trim(),
      website: String(form.get('website') || '').trim(),
    };
    const sex = form.get('sex');
    if (sex) payload.sex = Number(sex);
    const dob = form.get('dob');
    if (dob) payload.dob = String(dob);
    const categoryId = form.get('profile_category_type');
    if (categoryId) payload.profile_category_type = Number(categoryId);

    await api(API_ROUTES.profileUpdate, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    await fetchProfile();
    showToast('Profile updated');
  }

  async function uploadProfileImage(file) {
    const fd = new FormData();
    fd.append('imageFile', file);
    await apiUpload(API_ROUTES.updateProfileImage, fd);
    await fetchProfile();
    showToast('Profile photo updated');
  }

  async function uploadCoverImage(file) {
    const fd = new FormData();
    fd.append('imageFile', file);
    await apiUpload(API_ROUTES.updateProfileCoverImage, fd);
    await fetchProfile();
    showToast('Cover photo updated');
  }

  async function changePassword(oldPassword, password) {
    await api(API_ROUTES.updatePassword, {
      method: 'POST',
      body: JSON.stringify({ old_password: oldPassword, password }),
    });
    showToast('Password updated');
  }

  function showErrors(root, messages) {
    const box = root.querySelector('#account-errors');
    if (!box) return;
    if (!messages?.length) {
      box.classList.add('hidden');
      box.innerHTML = '';
      return;
    }
    box.classList.remove('hidden');
    box.innerHTML = messages.map((m) => `<p>${escapeHtml(m)}</p>`).join('');
  }

  async function renderAccount() {
    const root = document.getElementById('account-content');
    if (!root) return;

    if (!state.token) {
      root.innerHTML = `
        <div class="account-guest glass-card">
          <h2>Account</h2>
          <p class="muted">Sign in to manage your profile, photos, and security settings.</p>
          <button type="button" class="btn-primary full" id="account-guest-signin">Sign in</button>
        </div>`;
      root.querySelector('#account-guest-signin')?.addEventListener('click', () => openAuthModal?.('signin'));
      return;
    }

    root.innerHTML = `
      <div class="account-loading glass-card">
        <p class="eyebrow">Account</p>
        <h2>Loading your profile…</h2>
      </div>`;

    let user;
    try {
      user = await fetchProfile();
    } catch (err) {
      user = state.user;
      if (!user) {
        root.innerHTML = `<div class="glass-card"><p>Could not load profile.</p><button type="button" class="btn-ghost" id="account-retry">Retry</button></div>`;
        root.querySelector('#account-retry')?.addEventListener('click', () => renderAccount());
        return;
      }
    }

    const cats = isCreator(user) ? await loadCategories() : [];
    const pic = avatarUrl(user);
    const cover = coverUrl(user);
    const selectedCategory = Number(user.profile_category_type || 0);
    const approval = creatorApprovalStatus?.(user) || 'none';
    let roleChipClass = 'account-role-chip';
    let roleChipText = roleLabel(user);
    if (isCreator(user) && approval === 'pending') {
      roleChipClass += ' account-role-chip--pending';
      roleChipText = 'Creator · Pending approval';
    } else if (isCreator(user)) {
      roleChipClass += ' account-role-chip--creator';
    }

    root.innerHTML = `
      <div class="account-page">
        <header class="account-topbar">
          <button type="button" class="btn-ghost account-back" id="account-back" aria-label="Go back">← Back</button>
          <h1>Account</h1>
          <button type="button" class="btn-ghost account-signout-top" id="account-signout-top">Sign out</button>
        </header>

        <div class="account-hero">
          <div class="account-cover ${cover ? 'account-cover--has-image' : ''}" id="account-cover-preview" ${cover ? `style="background-image:url('${escapeHtml(cover)}')"` : ''}>
            <label class="account-cover-btn">
              <input type="file" id="account-cover-input" accept="image/*" hidden />
              Change cover
            </label>
          </div>
          <div class="account-hero-body">
            <label class="account-avatar-wrap">
              <input type="file" id="account-avatar-input" accept="image/*" hidden />
              <span class="account-avatar ${pic ? 'account-avatar--photo' : ''}" id="account-avatar-preview">
                ${pic ? `<img src="${escapeHtml(pic)}" alt="" />` : escapeHtml(userInitials(user))}
              </span>
              <span class="account-avatar-edit">Edit photo</span>
            </label>
            <div class="account-hero-copy">
              <span class="${roleChipClass}">${escapeHtml(roleChipText)}</span>
              <h2>${escapeHtml(user.name || user.username || 'Dreamlander')}</h2>
              <p class="account-hero-meta">@${escapeHtml(user.username || 'user')}<br>${escapeHtml(user.email || '')}</p>
            </div>
          </div>
        </div>

        <form id="account-form" class="account-section account-form" novalidate>
          <div id="account-errors" class="auth-errors hidden" role="alert"></div>
          <h3>Profile details</h3>
          <p class="muted account-form-lede">Update how you appear across Dreamland feeds, search, and studio.</p>

          <label class="account-field">
            <span>Display name</span>
            <input type="text" name="name" maxlength="50" value="${escapeHtml(user.name || '')}" autocomplete="name" />
          </label>
          <label class="account-field">
            <span>Username</span>
            <input type="text" name="username" maxlength="30" value="${escapeHtml(user.username || '')}" autocomplete="username" />
          </label>
          <label class="account-field">
            <span>Bio</span>
            <textarea name="bio" rows="2" maxlength="280" placeholder="Short intro for your profile">${escapeHtml(user.bio || '')}</textarea>
          </label>
          <label class="account-field">
            <span>About</span>
            <textarea name="description" rows="3" maxlength="500" placeholder="Tell viewers more about you">${escapeHtml(user.description || '')}</textarea>
          </label>

          <div class="account-field-row">
            <label class="account-field">
              <span>Phone</span>
              <input type="tel" name="phone" value="${escapeHtml(user.phone || '')}" autocomplete="tel" />
            </label>
            <label class="account-field">
              <span>City</span>
              <input type="text" name="city" value="${escapeHtml(user.city || '')}" autocomplete="address-level2" />
            </label>
          </div>
          <label class="account-field">
            <span>Country</span>
            <input type="text" name="country" value="${escapeHtml(user.country || '')}" autocomplete="country-name" />
          </label>
          <label class="account-field">
            <span>Website</span>
            <input type="url" name="website" value="${escapeHtml(user.website || '')}" placeholder="https://" />
          </label>
          <div class="account-field-row">
            <label class="account-field">
              <span>Gender</span>
              <select name="sex">
                <option value="">Prefer not to say</option>
                <option value="1" ${Number(user.sex) === 1 ? 'selected' : ''}>Female</option>
                <option value="2" ${Number(user.sex) === 2 ? 'selected' : ''}>Male</option>
                <option value="3" ${Number(user.sex) === 3 ? 'selected' : ''}>Other</option>
              </select>
            </label>
            <label class="account-field">
              <span>Birthday</span>
              <input type="date" name="dob" value="${escapeHtml((user.dob || '').slice(0, 10))}" />
            </label>
          </div>

          ${isCreator(user) ? `
          <label class="account-field">
            <span>Creator genre</span>
            <select name="profile_category_type">
              <option value="">Select a genre</option>
              ${cats.map((c) => `<option value="${c.id}" ${Number(c.id) === selectedCategory ? 'selected' : ''}>${escapeHtml(c.name)}</option>`).join('')}
            </select>
          </label>` : ''}

          <button type="submit" class="btn-primary full account-save-btn" id="account-save">Save profile</button>
        </form>

        <section class="account-section">
          <h3>Password</h3>
          <p class="muted account-form-lede">Choose a strong password you do not use elsewhere.</p>
          <form id="account-password-form" novalidate>
            <div id="account-password-errors" class="auth-errors hidden" role="alert"></div>
            <label class="account-field">
              <span>Current password</span>
              <input type="password" name="old_password" autocomplete="current-password" />
            </label>
            <label class="account-field">
              <span>New password</span>
              <input type="password" name="password" minlength="6" autocomplete="new-password" />
            </label>
            <button type="submit" class="btn-ghost full">Update password</button>
          </form>
        </section>

        <div class="account-danger">
          <h3>Session</h3>
          <p class="muted">Sign out on this device. You can sign back in anytime.</p>
          <button type="button" class="btn-ghost full account-signout-btn" id="account-signout">Sign out</button>
        </div>
      </div>`;

    root.querySelector('#account-back')?.addEventListener('click', () => {
      switchView(accountHomeView?.() || 'feed-view');
    });
    root.querySelector('#account-signout')?.addEventListener('click', () => signOut());
    root.querySelector('#account-signout-top')?.addEventListener('click', () => signOut());

    root.querySelector('#account-avatar-input')?.addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      try {
        await uploadProfileImage(file);
        await renderAccount();
      } catch (err) {
        showErrors(root, [flattenErrors(err.payload) || err.message || 'Could not upload photo']);
      }
      e.target.value = '';
    });

    root.querySelector('#account-cover-input')?.addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      try {
        await uploadCoverImage(file);
        await renderAccount();
      } catch (err) {
        showErrors(root, [flattenErrors(err.payload) || err.message || 'Could not upload cover']);
      }
      e.target.value = '';
    });

    root.querySelector('#account-form')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = new FormData(e.target);
      const saveBtn = root.querySelector('#account-save');
      saveBtn.disabled = true;
      showErrors(root, []);
      try {
        await saveProfile(form);
      } catch (err) {
        showErrors(root, [flattenErrors(err.payload) || err.message || 'Could not save profile']);
      } finally {
        saveBtn.disabled = false;
      }
    });

    root.querySelector('#account-password-form')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = new FormData(e.target);
      const box = root.querySelector('#account-password-errors');
      const oldPw = form.get('old_password');
      const pw = form.get('password');
      const msgs = [];
      if (!oldPw) msgs.push('Enter your current password.');
      if (!pw || String(pw).length < 6) msgs.push('New password must be at least 6 characters.');
      if (msgs.length) {
        box.classList.remove('hidden');
        box.innerHTML = msgs.map((m) => `<p>${escapeHtml(m)}</p>`).join('');
        return;
      }
      box.classList.add('hidden');
      try {
        await changePassword(oldPw, pw);
        e.target.reset();
      } catch (err) {
        box.classList.remove('hidden');
        box.innerHTML = `<p>${escapeHtml(flattenErrors(err.payload) || err.message || 'Could not update password')}</p>`;
      }
    });
  }

  async function openAccount() {
    if (!state.token) {
      openAuthModal?.('signin');
      return;
    }
    switchView('account-view');
    await renderAccount();
  }

  return { openAccount, signOut, renderAccount, avatarUrl };
}
