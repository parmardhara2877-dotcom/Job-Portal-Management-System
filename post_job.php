<?php
/**
 * post_job.php - Allow a logged-in employer to insert a new job.
 * Accepts JSON: { title, company, location, type, description, salary }
 *
 * Uses prepared statements throughout to prevent SQL injection.
 */

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$employer = require_api_auth('employer');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    json_response(['success' => false, 'message' => 'Invalid request body.'], 400);
}

// --- Validate input ---------------------------------------------------
$title       = trim((string)($data['title']       ?? ''));
$company     = trim((string)($data['company']     ?? ''));
$location    = trim((string)($data['location']    ?? ''));
$type        = (string)($data['type']        ?? '');
$description = trim((string)($data['description'] ?? ''));
$salary      = trim((string)($data['salary']      ?? ''));

$errors = [];

if ($title === '')       $errors[] = 'Job title is required.';
elseif (mb_strlen($title) > 150)    $errors[] = 'Title must be 150 characters or fewer.';

if ($company === '')    $errors[] = 'Company name is required.';
elseif (mb_strlen($company) > 150)  $errors[] = 'Company must be 150 characters or fewer.';

if ($location === '')   $errors[] = 'Location is required.';
elseif (mb_strlen($location) > 150) $errors[] = 'Location must be 150 characters or fewer.';

if (!in_array($type, ['Full-time', 'Part-time', 'Internship'], true)) {
    $errors[] = 'Job type must be Full-time, Part-time, or Internship.';
}

if ($description === '') $errors[] = 'Description is required.';

if ($salary !== '' && mb_strlen($salary) > 100) {
    $errors[] = 'Salary must be 100 characters or fewer.';
}

if ($errors) {
    json_response(['success' => false, 'message' => implode(' ', $errors)], 422);
}

// --- Insert job -------------------------------------------------------
try {
    $stmt = db()->prepare(
        'INSERT INTO jobs (employer_id, title, company, location, type, description, salary)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $employer['id'],
        $title,
        $company,
        $location,
        $type,
        $description,
        $salary !== '' ? $salary : null,
    ]);

    $jobId = (int) db()->lastInsertId();

    json_response([
        'success' => true,
        'message' => 'Job posted.',
        'job'     => ['id' => $jobId],
    ], 201);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Could not post job.'], 500);
}
