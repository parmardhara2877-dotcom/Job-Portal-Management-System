/* login.js - submit login form via fetch */

(function () {
  'use strict';

  const form = document.getElementById('login-form');
  if (!form) return;

  const messageEl = document.getElementById('login-message');
  const btn = document.getElementById('login-btn');
  const requestedRole = new URLSearchParams(window.location.search).get('role');
  if (requestedRole === 'seeker' || requestedRole === 'employer') {
    const roleInput = form.querySelector('input[name="role"][value="' + requestedRole + '"]');
    if (roleInput) roleInput.checked = true;
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const role = form.querySelector('input[name="role"]:checked').value;

    setMessage('', '');
    btn.disabled = true;
    btn.textContent = 'Logging in…';

    try {
      const res = await fetch('login.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, role }),
      });
      const { ok, data } = await window.JobPortal.readJson(res);

      if (ok && data && data.success) {
        const role = data.user && data.user.role;
        window.location.href = role === 'employer'
          ? 'employer_dashboard.php'
          : 'seeker_dashboard.php';
        return;
      }

      setMessage(data && data.message ? data.message : 'Login failed.', 'error');
    } catch (_) {
      setMessage('Network error. Please try again.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Log in';
    }
  });

  function setMessage(msg, type) {
    if (!messageEl) return;
    messageEl.textContent = msg;
    messageEl.className = 'text-sm ' + (type === 'error' ? 'text-rose-600' : 'text-emerald-600');
  }
})();
