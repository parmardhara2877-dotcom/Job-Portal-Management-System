<?php
/**
 * update_application.php - Allow an employer to update the status of an
 * application for a job they own.
 * Accepts JSON: { application_id, status }
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

$appId = isset($data['application_id']) ? (int) $data['application_id'] : 0;
$status = (string)($data['status'] ?? '');

if ($appId <= 0) {
    json_response(['success' => false, 'message' => 'A valid application is required.'], 422);
}

if (!in_array($status, ['pending', 'reviewed', 'accepted', 'rejected'], true)) {
    json_response(['success' => false, 'message' => 'Invalid status.'], 422);
}

try {
    // Ensure the application belongs to a job owned by this employer.
    $stmt = db()->prepare(
        'SELECT a.id
         FROM applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE a.id = ? AND j.employer_id = ?
         LIMIT 1'
    );
    $stmt->execute([$appId, $employer['id']]);
    if (!$stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Application not found.'], 404);
    }

    $stmt = db()->prepare('UPDATE applications SET status = ? WHERE id = ?');
    $stmt->execute([$status, $appId]);

    json_response(['success' => true, 'message' => 'Status updated.']);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Could not update status.'], 500);
}
