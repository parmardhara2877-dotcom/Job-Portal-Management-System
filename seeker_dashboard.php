<?php
/**
 * seeker_dashboard.php - Protected page for logged-in seekers.
 * Shows the jobs they have applied to and the current status of each.
 */
require_once __DIR__ . '/config/db.php';
require_auth('seeker');

$seekerId = (int) $_SESSION['user_id'];
$seekerName = $_SESSION['name'] ?? 'Seeker';

try {
    $stmt = db()->prepare(
        'SELECT
            a.id AS application_id,
            a.status,
            a.applied_at,
            a.resume_path,
            j.id AS job_id,
            j.title,
            j.company,
            j.location,
            j.type,
            j.salary
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE a.seeker_id = ?
         ORDER BY a.applied_at DESC'
    );
    $stmt->execute([$seekerId]);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
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
  <title>Seeker Dashboard - JobPortal</title>
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Hello, <?= htmlspecialchars($seekerName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-slate-500 text-sm mt-1">Track your applications and their status.</p>
      </div>
      <a href="index.html" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-white font-semibold hover:bg-emerald-700 transition">
        Browse more jobs
      </a>
    </div>

    <?php if (!$applications): ?>
      <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
        <p class="text-slate-500">You haven't applied to any jobs yet.</p>
        <a href="index.html" class="mt-4 inline-block text-emerald-700 font-semibold hover:underline">Find your first opportunity →</a>
      </div>
    <?php else: ?>
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3 text-left font-semibold">Job</th>
              <th class="px-6 py-3 text-left font-semibold">Company</th>
              <th class="px-6 py-3 text-left font-semibold">Location</th>
              <th class="px-6 py-3 text-left font-semibold">Type</th>
              <th class="px-6 py-3 text-left font-semibold">Applied</th>
              <th class="px-6 py-3 text-left font-semibold">Status</th>
              <th class="px-6 py-3 text-left font-semibold">Resume</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($applications as $app): ?>
              <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($app['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($app['company'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($app['location'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($app['type'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-6 py-4 text-slate-500"><?= date('M j, Y', strtotime($app['applied_at'])) ?></td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusColors[$app['status']] ?? 'bg-slate-100 text-slate-600' ?>">
                    <?= htmlspecialchars(ucfirst($app['status']), ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <a href="<?= htmlspecialchars($app['resume_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-emerald-700 font-medium hover:underline">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>

  <script src="assets/auth.js"></script>
</body>
</html>
