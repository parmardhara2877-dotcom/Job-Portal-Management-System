<?php
/**
 * Global configuration for the Job Portal.
 *
 * Adjust the DB_* constants to match your MySQL server, then copy this
 * file's values into a non-committed local override if you prefer.
 */

// --- Database -----------------------------------------------------------
const DB_HOST    = 'jobportalweb.infinityfreeapp.com';   //'127.0.0.1';
const DB_PORT    = 3306;
const DB_NAME    = 'if0_42747639_jobportal';
const DB_USER    = 'if0_42747639';
const DB_PASS    = 'myportal0128';
const DB_CHARSET = 'utf8mb4';

// --- App ----------------------------------------------------------------
const APP_NAME    = 'JobPortal';
const BASE_URL    = 'http://jobportalweb.infinityfreeapp.com';
const UPLOAD_DIR  = __DIR__ . '/../uploads';
const MAX_RESUME_BYTES = 3 * 1024 * 1024; // 3 MB

// --- Session ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Send a JSON response and stop execution.
 */
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Require a logged-in user; optionally require a specific role.
 * Used by protected PHP pages (dashboards). Redirects on failure.
 */
function require_auth(string $role = null): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }

    if ($role !== null && ($_SESSION['role'] ?? null) !== $role) {
        // Logged in but wrong role -> send to their own dashboard.
        $own = ($_SESSION['role'] ?? '') === 'employer'
            ? 'employer_dashboard.php'
            : 'seeker_dashboard.php';
        header('Location: ' . $own);
        exit;
    }
}

/**
 * Require a logged-in user for an API endpoint; optionally a role.
 * Returns 401/403 JSON instead of redirecting.
 */
function require_api_auth(string $role = null): array
{
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Authentication required.'], 401);
    }

    if ($role !== null && ($_SESSION['role'] ?? null) !== $role) {
        json_response(['success' => false, 'message' => 'Permission denied.'], 403);
    }

    return [
        'id'    => (int) $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'role'  => $_SESSION['role'] ?? '',
        'name'  => $_SESSION['name'] ?? '',
    ];
}
