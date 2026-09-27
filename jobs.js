/* jobs.js - fetch and render job cards on the landing page + apply modal */

(function () {
  'use strict';

  const grid = document.getElementById('jobs-grid');
  if (!grid) return;

  const opportunities = document.getElementById('opportunities');
  const roleModal = document.getElementById('role-modal');
  const roleLogin = document.getElementById('role-login');
  const roleRegister = document.getElementById('role-register');

  const loadingEl = document.getElementById('loading');
  const emptyEl = document.getElementById('empty-state');
  const countEl = document.getElementById('job-count');
  const filterForm = document.getElementById('filter-form');

  const modal = document.getElementById('apply-modal');
  const applyForm = document.getElementById('apply-form');
  const applyMessage = document.getElementById('apply-message');
  const applyJobId = document.getElementById('apply-job-id');
  const applyJobTitle = document.getElementById('apply-job-title');
  const applyJobCompany = document.getElementById('apply-job-company');

  const typeColors = {
    'Full-time':  'bg-emerald-100 text-emerald-700',
    'Part-time':  'bg-sky-100 text-sky-700',
    'Internship': 'bg-violet-100 text-violet-700',
  };

  let currentUser = null;
  let allJobs = [];

  document.addEventListener('DOMContentLoaded', function () {
    window.JobPortal.currentUser().then(function (u) {
      currentUser = u;
      if (currentUser && currentUser.role === 'seeker') {
        opportunities.classList.remove('hidden');
        loadJobs();
      }
    });
  });

  document.querySelectorAll('[data-role]').forEach(function (button) {
    button.addEventListener('click', function () {
      const role = button.getAttribute('data-role');
      if (roleModal) roleModal.classList.remove('hidden');
      if (roleLogin) roleLogin.href = 'login.html?role=' + encodeURIComponent(role);
      if (roleRegister) roleRegister.href = 'register.html?role=' + encodeURIComponent(role);
    });
  });

  if (roleModal) {
    roleModal.querySelectorAll('[data-close-role-modal]').forEach(function (element) {
      element.addEventListener('click', function () { roleModal.classList.add('hidden'); });
    });
  }

  filterForm.addEventListener('submit', function (e) {
    e.preventDefault();
    loadJobs();
  });

  async function loadJobs() {
    const params = new URLSearchParams();
    const search = document.getElementById('search').value.trim();
    const type = document.getElementById('type').value;
    const location = document.getElementById('location').value.trim();
    if (search) params.set('search', search);
    if (type) params.set('type', type);
    if (location) params.set('location', location);

    if (loadingEl) loadingEl.classList.remove('hidden');
    if (emptyEl) emptyEl.classList.add('hidden');
    grid.querySelectorAll('.job-card').forEach(function (c) { c.remove(); });

    try {
      const res = await fetch('fetch_jobs.php?' + params.toString(), {
        credentials: 'same-origin',
      });
      const { ok, data } = await window.JobPortal.readJson(res);

      if (ok && data && data.success) {
        allJobs = data.jobs || [];
        renderJobs(allJobs);
      } else {
        showEmpty();
      }
    } catch (_) {
      showEmpty();
    } finally {
      if (loadingEl) loadingEl.classList.add('hidden');
    }
  }

  function renderJobs(jobs) {
    grid.querySelectorAll('.job-card').forEach(function (c) { c.remove(); });

    if (!jobs.length) {
      showEmpty();
      return;
    }
    if (emptyEl) emptyEl.classList.add('hidden');
    if (countEl) countEl.textContent = jobs.length + ' opening' + (jobs.length === 1 ? '' : 's');

    jobs.forEach(function (job) {
      const card = document.createElement('article');
      card.className = 'job-card card-hover fade-in bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col';
      card.innerHTML = `
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="font-bold text-slate-900 text-lg leading-snug">${window.JobPortal.escapeHtml(job.title)}</h3>
            <p class="text-slate-600 text-sm mt-0.5">${window.JobPortal.escapeHtml(job.company)}</p>
          </div>
          <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap ${typeColors[job.type] || 'bg-slate-100 text-slate-600'}">${window.JobPortal.escapeHtml(job.type)}</span>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
          <span class="inline-flex items-center gap-1">${window.JobPortal.escapeHtml(job.location)}</span>
          ${job.salary ? `<span class="inline-flex items-center gap-1">${window.JobPortal.escapeHtml(job.salary)}</span>` : ''}
          <span class="inline-flex items-center gap-1">${(job.application_count || 0)} applicant${(job.application_count || 0) === 1 ? '' : 's'}</span>
        </div>
        <p class="mt-3 text-sm text-slate-600 line-clamp-3 flex-1">${window.JobPortal.escapeHtml(job.description)}</p>
        <div class="mt-4 flex items-center justify-between">
          <span class="text-xs text-slate-400">Posted ${formatDate(job.created_at)}</span>
          <button class="apply-btn inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-white text-sm font-semibold hover:bg-emerald-700 transition" data-job-id="${job.id}">Apply</button>
        </div>
      `;
      grid.appendChild(card);
    });

    grid.querySelectorAll('.apply-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const id = parseInt(btn.getAttribute('data-job-id'), 10);
        openApplyModal(id);
      });
    });
  }

  function showEmpty() {
    grid.querySelectorAll('.job-card').forEach(function (c) { c.remove(); });
    if (emptyEl) emptyEl.classList.remove('hidden');
    if (countEl) countEl.textContent = '';
  }

  function openApplyModal(jobId) {
    const job = allJobs.find(function (j) { return parseInt(j.id, 10) === jobId; });
    if (!job) return;

    if (!currentUser) {
      window.location.href = 'login.html?role=seeker';
      return;
    }
    if (currentUser.role !== 'seeker') {
      alert('Only job seekers can apply. You are signed in as an employer.');
      return;
    }

    applyJobId.value = jobId;
    applyJobTitle.textContent = job.title;
    applyJobCompany.textContent = job.company + ' · ' + job.location;
    if (applyMessage) {
      applyMessage.textContent = '';
      applyMessage.className = 'text-sm';
    }
    applyForm.reset();
    modal.classList.remove('hidden');
  }

  modal.querySelectorAll('[data-close-modal]').forEach(function (el) {
    el.addEventListener('click', function () { modal.classList.add('hidden'); });
  });

  applyForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    const jobId = applyJobId.value;
    const fileInput = document.getElementById('resume');
    if (!fileInput || !fileInput.files.length) {
      setApplyMessage('Please choose a PDF resume.', 'error');
      return;
    }

    const fd = new FormData();
    fd.append('job_id', jobId);
    fd.append('resume', fileInput.files[0]);

    setApplyMessage('Submitting…', 'info');

    try {
      const res = await fetch('apply_job.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      });
      const { ok, data } = await window.JobPortal.readJson(res);

      if (ok && data && data.success) {
        setApplyMessage('Application submitted!', 'success');
        setTimeout(function () { modal.classList.add('hidden'); }, 1200);
      } else {
        setApplyMessage(data && data.message ? data.message : 'Submission failed.', 'error');
      }
    } catch (_) {
      setApplyMessage('Network error. Please try again.', 'error');
    }
  });

  function setApplyMessage(msg, type) {
    if (!applyMessage) return;
    applyMessage.textContent = msg;
    applyMessage.className = 'text-sm ' + (
      type === 'error'   ? 'text-rose-600' :
      type === 'success' ? 'text-emerald-600' :
      'text-slate-500'
    );
  }

  function formatDate(iso) {
    try {
      const d = new Date(iso.replace(' ', 'T'));
      return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    } catch (_) { return ''; }
  }
})();
