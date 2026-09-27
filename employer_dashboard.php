<?php
/**
 * employer_dashboard.php - Protected page for logged-in employers.
 * Features a "Post a Job" form and a list of applicants for their postings.
 */
require_once __DIR__ . '/config/db.php';
require_auth('employer');

$employerId = (int) $_SESSION['user_id'];
$employerName = $_SESSION['name'] ?? 'Employer';

try {
    // Jobs posted by this employer, with applicant counts.
    $stmt = db()->prepare(
        'SELECT
            j.id, j.title, j.company, j.location, j.type, j.salary, j.description, j.created_at,
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
         FROM jobs j
         WHERE j.employer_id = ?
         ORDER BY j.created_at DESC'
    );
    $stmt->execute([$employerId]);
    $jobs = $stmt->fetchAll();

    // Recent applicants across all of this employer's jobs.
    $stmt = db()->prepare(
        'SELECT
            a.id AS application_id,
            a.status,
            a.applied_at,
            a.resume_path,
            j.title AS job_title,
            u.name AS seeker_name,
            u.email AS seeker_email
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.seeker_id
         WHERE j.employer_id = ?
         ORDER BY a.applied_at DESC'
    );
    $stmt->execute([$employerId]);
    $applicants = $stmt->fetchAll();
} catch (PDOException $e) {
    $jobs = [];
    $applicants = [];
}

$statusColors = [
    'pending'   => 'bg-amber-100 text-amber-700',
    'reviewed'  => 'bg-sky-100 text-sky-700',
    'accepted'  => 'bg-emerald-100 text-emerald-700',
    'rejected'  => 'bg-rose-100 text-rose-700',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employer Dashboard - JobPortal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/styles.css" />
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="index.html" class="flex items-center gap-2 text-xl font-bold text-slate-900">
        <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white grid place-items-center font-bold">J</span>
        JobPortal
      </a>
      <nav id="nav-links" class="flex items-center gap-2 sm:gap-4 text-sm font-medium"></nav>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-slate-900">Hello, <?= htmlspecialchars($employerName, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="text-slate-500 text-sm mt-1">Post new roles and review applicants.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Post a Job -->
      <section class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h2 class="text-lg font-bold text-slate-900 mb-4">Post a Job</h2>
          <form id="post-job-form" class="space-y-4">
            <input type="hidden" id="job-id" name="id" value="" />
            <div>
              <label for="title" class="block text-sm font-semibold text-slate-700 mb-1">Job title</label>
              <input id="title" name="title" type="text" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label for="company" class="block text-sm font-semibold text-slate-700 mb-1">Company</label>
              <input id="company" name="company" type="text" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label for="location" class="block text-sm font-semibold text-slate-700 mb-1">Location</label>
              <input id="location" name="location" type="text" required placeholder="City or Remote"
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label for="type" class="block text-sm font-semibold text-slate-700 mb-1">Type</label>
              <select id="type" name="type"
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="Full-time">Full-time</option>
                <option value="Part-time">Part-time</option>
                <option value="Internship">Internship</option>
              </select>
            </div>
            <div>
              <label for="salary" class="block text-sm font-semibold text-slate-700 mb-1">Salary (optional)</label>
              <input id="salary" name="salary" type="text" placeholder="e.g. $60,000/yr"
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label for="description" class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
              <textarea id="description" name="description" rows="4" required
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>
            <div id="post-job-message" class="text-sm"></div>
            <div class="flex gap-2">
              <button type="submit" id="post-job-btn" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2.5 text-white font-semibold hover:bg-emerald-700 transition">
              Publish job
              </button>
              <button type="button" id="cancel-edit-btn" class="hidden rounded-lg border border-slate-300 px-4 py-2.5 text-slate-700 font-semibold hover:bg-slate-50">Cancel</button>
            </div>
          </form>
        </div>
      </section>

      <!-- Jobs + applicants -->
      <section class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h2 class="text-lg font-bold text-slate-900 mb-4">Your job postings</h2>
          <?php if (!$jobs): ?>
            <p class="text-slate-500 text-sm">You haven't posted any jobs yet. Use the form to create your first listing.</p>
          <?php else: ?>
            <div class="space-y-3">
              <?php foreach ($jobs as $job): ?>
                <div class="rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                  <div>
                    <p class="font-semibold text-slate-900"><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-sm text-slate-500"><?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($job['type'], ENT_QUOTES, 'UTF-8') ?></p>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold whitespace-nowrap">
                      <?= (int)$job['applicant_count'] ?> applicant<?= $job['applicant_count'] == 1 ? '' : 's' ?>
                    </span>
                    <button type="button" class="edit-job-btn rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-job='<?= htmlspecialchars(json_encode($job), ENT_QUOTES, 'UTF-8') ?>'>Edit</button>
                    <button type="button" class="delete-job-btn rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" data-job-id="<?= (int)$job['id'] ?>">Delete</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <h2 class="text-lg font-bold text-slate-900 mb-4">Recent applicants</h2>
          <?php if (!$applicants): ?>
            <p class="text-slate-500 text-sm">No applications yet.</p>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold">Applicant</th>
                    <th class="px-4 py-3 text-left font-semibold">Job</th>
                    <th class="px-4 py-3 text-left font-semibold">Applied</th>
                    <th class="px-4 py-3 text-left font-semibold">Resume</th>
                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <?php foreach ($applicants as $app): ?>
                    <tr class="hover:bg-slate-50 transition">
                      <td class="px-4 py-3">
                        <p class="font-semibold text-slate-900"><?= htmlspecialchars($app['seeker_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($app['seeker_email'], ENT_QUOTES, 'UTF-8') ?></p>
                      </td>
                      <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($app['job_title'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="px-4 py-3 text-slate-500"><?= date('M j, Y', strtotime($app['applied_at'])) ?></td>
                      <td class="px-4 py-3">
                        <a href="<?= htmlspecialchars($app['resume_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-emerald-700 font-medium hover:underline">View</a>
                      </td>
                      <td class="px-4 py-3">
                        <select data-application-id="<?= (int)$app['application_id'] ?>" class="status-select rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                          <?php foreach (['pending', 'reviewed', 'accepted', 'rejected'] as $s): ?>
                            <option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>

  <script src="assets/auth.js"></script>
  <script src="assets/employer.js"></script>
</body>
</html>
