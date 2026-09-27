/* register.js - submit registration form via fetch */

(function () {
  'use strict';

  const form = document.getElementById('register-form');
  if (!form) return;

  const messageEl = document.getElementById('register-message');
  const btn = document.getElementById('register-btn');
  const requestedRole = new URLSearchParams(window.location.search).get('role');
  if (requestedRole === 'seeker' || requestedRole === 'employer') {
    const roleInput = form.querySelector('input[name="role"][value="' + requestedRole + '"]');
    if (roleInput) roleInput.checked = true;
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const role = form.querySelector('input[name="role"]:checked')
      ? form.querySelector('input[name="role"]:checked').value
      : 'seeker';

    setMessage('', '');
    btn.disabled = true;
    btn.textContent = 'Creating…';

    try {
      const res = await fetch('register.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password, role }),
      });
      const { ok, data } = await window.JobPortal.readJson(res);

      if (ok && data && data.success) {
        window.location.href = role === 'employer'
          ? 'employer_dashboard.php'
          : 'seeker_dashboard.php';
        return;
      }

      setMessage(data && data.message ? data.message : 'Registration failed.', 'error');
    } catch (_) {
      setMessage('Network error. Please try again.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Create account';
    }
  });

  function setMessage(msg, type) {
    if (!messageEl) return;
    messageEl.textContent = msg;
    messageEl.className = 'text-sm ' + (type === 'error' ? 'text-rose-600' : 'text-emerald-600');
  }
})();
