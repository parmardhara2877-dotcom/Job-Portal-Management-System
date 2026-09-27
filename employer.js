/* employer.js - post a job + update application status from the dashboard */

(function () {
  'use strict';

  const form = document.getElementById('post-job-form');
  if (form) {
    const messageEl = document.getElementById('post-job-message');
    const btn = document.getElementById('post-job-btn');
    const jobIdInput = document.getElementById('job-id');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');

    function resetJobForm() {
      form.reset();
      jobIdInput.value = '';
      btn.textContent = 'Publish job';
      cancelEditBtn.classList.add('hidden');
    }

    cancelEditBtn.addEventListener('click', resetJobForm);

    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      const payload = {
        id: jobIdInput.value ? parseInt(jobIdInput.value, 10) : 0,
        title: document.getElementById('title').value.trim(),
        company: document.getElementById('company').value.trim(),
        location: document.getElementById('location').value.trim(),
        type: document.getElementById('type').value,
        salary: document.getElementById('salary').value.trim(),
        description: document.getElementById('description').value.trim(),
      };

      setMessage('', '');
      btn.disabled = true;
      btn.textContent = 'Publishing…';

      try {
        const editing = Boolean(payload.id);
        const res = await fetch(editing ? 'update_job.php' : 'post_job.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const { ok, data } = await window.JobPortal.readJson(res);

        if (ok && data && data.success) {
          setMessage(editing ? 'Job updated! Reloading…' : 'Job published! Reloading…', 'success');
          setTimeout(function () { window.location.reload(); }, 1000);
        } else {
          setMessage(data && data.message ? data.message : 'Could not post job.', 'error');
          btn.disabled = false;
          btn.textContent = editing ? 'Save changes' : 'Publish job';
        }
      } catch (_) {
        setMessage('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.textContent = 'Publish job';
      }
    });

    function setMessage(msg, type) {
      if (!messageEl) return;
      messageEl.textContent = msg;
      messageEl.className = 'text-sm ' + (
        type === 'error'   ? 'text-rose-600' :
        type === 'success' ? 'text-emerald-600' :
        ''
      );
    }
  }

  document.querySelectorAll('.edit-job-btn').forEach(function (button) {
    button.addEventListener('click', function () {
      const job = JSON.parse(button.getAttribute('data-job'));
      document.getElementById('job-id').value = job.id;
      document.getElementById('title').value = job.title;
      document.getElementById('company').value = job.company;
      document.getElementById('location').value = job.location;
      document.getElementById('type').value = job.type;
      document.getElementById('salary').value = job.salary || '';
      document.getElementById('description').value = job.description;
      document.getElementById('post-job-btn').textContent = 'Save changes';
      document.getElementById('cancel-edit-btn').classList.remove('hidden');
      document.getElementById('post-job-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  document.querySelectorAll('.delete-job-btn').forEach(function (button) {
    button.addEventListener('click', async function () {
      if (!window.confirm('Delete this job and its applications?')) return;
      try {
        const res = await fetch('delete_job.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: parseInt(button.getAttribute('data-job-id'), 10) }),
        });
        const { ok, data } = await window.JobPortal.readJson(res);
        if (ok && data && data.success) window.location.reload();
        else alert(data && data.message ? data.message : 'Could not delete job.');
      } catch (_) {
        alert('Network error. Please try again.');
      }
    });
  });

  // Status update selects
  document.querySelectorAll('.status-select').forEach(function (sel) {
    sel.addEventListener('change', async function () {
      const appId = sel.getAttribute('data-application-id');
      const status = sel.value;

      try {
        const res = await fetch('update_application.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ application_id: parseInt(appId, 10), status: status }),
        });
        const { ok, data } = await window.JobPortal.readJson(res);

        if (!(ok && data && data.success)) {
          alert(data && data.message ? data.message : 'Could not update status.');
        }
      } catch (_) {
        alert('Network error. Please try again.');
      }
    });
  });
})();
