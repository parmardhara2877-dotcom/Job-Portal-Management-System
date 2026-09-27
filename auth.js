/* auth.js - session-aware navbar + shared helpers */

(function () {
  'use strict';

  /**
   * Read a JSON response, guarding against non-JSON (e.g. HTML error pages).
   */
  async function readJson(res) {
    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (_) { data = null; }
    return { ok: res.ok, status: res.status, data };
  }

  /**
   * Check current session by asking the server. PHP exposes session state
   * only to its own pages, so we probe a lightweight endpoint.
   */
  async function currentUser() {
    try {
      const res = await fetch('session.php', { credentials: 'same-origin' });
      const { ok, data } = await readJson(res);
      if (ok && data && data.success && data.user) return data.user;
    } catch (_) {}
    return null;
  }

  function renderNav(user) {
    const nav = document.getElementById('nav-links');
    if (!nav) return;

    if (user) {
      const dashboard =
        user.role === 'employer' ? 'employer_dashboard.php' : 'seeker_dashboard.php';
      const dashboardLabel = user.role === 'employer' ? 'Dashboard' : 'My Applications';

      nav.innerHTML = `
        <a href="${dashboard}" class="px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 transition">${dashboardLabel}</a>
        <span class="px-3 py-2 text-slate-500 text-xs hidden sm:inline">Hi, ${escapeHtml(user.name)}</span>
        <button id="logout-btn" class="px-3 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">Log out</button>
      `;

      const logoutBtn = document.getElementById('logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
          try {
            await fetch('logout.php', {
              method: 'POST',
              credentials: 'same-origin',
              headers: { 'Accept': 'application/json' },
            });
          } catch (_) {}
          window.location.href = 'index.html';
        });
      }
    } else {
      nav.innerHTML = `
        <a href="login.html" class="px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 transition">Log in</a>
        <a href="register.html" class="px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">Sign up</a>
      `;
    }
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // Expose helpers globally
  window.JobPortal = {
    readJson,
    currentUser,
    escapeHtml,
  };

  document.addEventListener('DOMContentLoaded', function () {
    currentUser().then(renderNav);
  });
})();
