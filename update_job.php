<?php
/**
 * update_job.php - Update a job owned by the logged-in employer.
 * Accepts JSON: { id, title, company, location, type, description, salary }
 */

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$employer = require_api_auth('employer');
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    json_response(['success' => false, 'message' => 'Invalid request body.'], 400);
}

$id = (int) ($data['id'] ?? 0);
$title = trim((string) ($data['title'] ?? ''));
$company = trim((string) ($data['company'] ?? ''));
$location = trim((string) ($data['location'] ?? ''));
$type = (string) ($data['type'] ?? '');
$description = trim((string) ($data['description'] ?? ''));
$salary = trim((string) ($data['salary'] ?? ''));

$errors = [];
if ($id <= 0) $errors[] = 'A valid job is required.';
if ($title === '' || mb_strlen($title) > 150) $errors[] = 'Job title is required and must be 150 characters or fewer.';
if ($company === '' || mb_strlen($company) > 150) $errors[] = 'Company is required and must be 150 characters or fewer.';
if ($location === '' || mb_strlen($location) > 150) $errors[] = 'Location is required and must be 150 characters or fewer.';
if (!in_array($type, ['Full-time', 'Part-time', 'Internship'], true)) $errors[] = 'Invalid job type.';
if ($description === '') $errors[] = 'Description is required.';
if (mb_strlen($salary) > 100) $errors[] = 'Salary must be 100 characters or fewer.';
if ($errors) json_response(['success' => false, 'message' => implode(' ', $errors)], 422);

try {
    $stmt = db()->prepare(
        'UPDATE jobs SET title = ?, company = ?, location = ?, type = ?, description = ?, salary = ?
         WHERE id = ? AND employer_id = ?'
    );
    $stmt->execute([$title, $company, $location, $type, $description, $salary !== '' ? $salary : null, $id, $employer['id']]);
    if ($stmt->rowCount() === 0) {
        $check = db()->prepare('SELECT id FROM jobs WHERE id = ? AND employer_id = ?');
        $check->execute([$id, $employer['id']]);
        if (!$check->fetch()) json_response(['success' => false, 'message' => 'Job not found.'], 404);
    }
    json_response(['success' => true, 'message' => 'Job updated.']);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Could not update job.'], 500);
}
