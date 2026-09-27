<?php
/**
 * apply_job.php - Allow a logged-in seeker to upload a PDF resume and
 * create an application record.
 *
 * Accepts multipart/form-data:
 *   job_id   (int, required)
 *   resume   (file, PDF, required, <= MAX_RESUME_BYTES)
 *
 * Returns JSON.
 */

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$seeker = require_api_auth('seeker');

$jobId = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
if ($jobId <= 0) {
    json_response(['success' => false, 'message' => 'A valid job is required.'], 422);
}

// --- Validate resume upload ------------------------------------------
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {
    json_response(['success' => false, 'message' => 'A resume file is required.'], 422);
}

$file = $_FILES['resume'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $msg = $file['error'] === UPLOAD_ERR_INI_SIZE
        ? 'The file is too large.'
        : 'Upload failed. Please try again.';
    json_response(['success' => false, 'message' => $msg], 422);
}

if ($file['size'] > MAX_RESUME_BYTES) {
    json_response(['success' => false, 'message' => 'Resume must be 3 MB or smaller.'], 422);
}

// Verify real MIME type, not just the browser-supplied one.
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mime !== 'application/pdf') {
    json_response(['success' => false, 'message' => 'Resume must be a PDF file.'], 422);
}

// --- Ensure upload directory exists ----------------------------------
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0775, true);
}

// --- Store file with a safe, unique name -----------------------------
$safeName = 'resume_' . $seeker['id'] . '_' . $jobId . '_' . time() . '.pdf';
$dest     = UPLOAD_DIR . '/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['success' => false, 'message' => 'Could not save resume.'], 500);
}

$storedPath = 'uploads/' . $safeName;

// --- Insert application ----------------------------------------------
try {
    // Confirm the job exists.
    $stmt = db()->prepare('SELECT id FROM jobs WHERE id = ? LIMIT 1');
    $stmt->execute([$jobId]);
    if (!$stmt->fetch()) {
        @unlink($dest);
        json_response(['success' => false, 'message' => 'That job no longer exists.'], 404);
    }

    $stmt = db()->prepare(
        'INSERT INTO applications (job_id, seeker_id, resume_path)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$jobId, $seeker['id'], $storedPath]);

    json_response([
        'success' => true,
        'message' => 'Application submitted.',
        'application' => ['job_id' => $jobId, 'resume_path' => $storedPath],
    ], 201);
} catch (PDOException $e) {
    // Duplicate (job_id, seeker_id) -> already applied.
    if ($e->getCode() === '23000') {
        @unlink($dest);
        json_response(['success' => false, 'message' => 'You have already applied to this job.'], 409);
    }
    @unlink($dest);
    json_response(['success' => false, 'message' => 'Could not submit application.'], 500);
}
