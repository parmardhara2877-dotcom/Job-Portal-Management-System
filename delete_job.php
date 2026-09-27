<?php
/**
 * delete_job.php - Delete a job owned by the logged-in employer.
 * Accepts JSON: { id }
 */

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$employer = require_api_auth('employer');
$data = json_decode(file_get_contents('php://input'), true);
$id = is_array($data) ? (int) ($data['id'] ?? 0) : 0;
if ($id <= 0) json_response(['success' => false, 'message' => 'A valid job is required.'], 422);

try {
    $stmt = db()->prepare('DELETE FROM jobs WHERE id = ? AND employer_id = ?');
    $stmt->execute([$id, $employer['id']]);
    if ($stmt->rowCount() !== 1) json_response(['success' => false, 'message' => 'Job not found.'], 404);
    json_response(['success' => true, 'message' => 'Job deleted.']);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Could not delete job.'], 500);
}
