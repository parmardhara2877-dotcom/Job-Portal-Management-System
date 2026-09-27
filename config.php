<?php
/**
 * Global configuration for the Job Portal (environment-first).
 *
 * This file uses environment variables when available and falls back to
 * sensible defaults for local development. For machine-specific secrets,
 * create a `config.local.php` (gitignored) next to this file and set
 * overrides there (example: `config.local.php.example`).
 */

// If a local override file exists, load it first. It may define constants or
// provide an env() implementation. Keep this load minimal and safe.
$local = __DIR__ . '/config.local.php';
if (file_exists($local)) {
    require $local;
}

// Simple env helper that checks getenv() and $_ENV, with a default.
if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        return $default;
    }
}

// --- Database -----------------------------------------------------------
// Prefer environment variables. Use conservative local defaults.
if (!defined('DB_HOST'))    define('DB_HOST', env('DB_HOST', '127.0.0.1'));
if (!defined('DB_PORT'))    define('DB_PORT', (int) env('DB_PORT', 3306));
if (!defined('DB_NAME'))    define('DB_NAME', env('DB_NAME', 'job_portal'));
if (!defined('DB_USER'))    define('DB_USER', env('DB_USER', 'root'));
if (!defined('DB_PASS'))    define('DB_PASS', env('DB_PASS', ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// --- App ----------------------------------------------------------------
if (!defined('APP_NAME'))    define('APP_NAME', env('APP_NAME', 'JobPortal'));
if (!defined('BASE_URL'))    define('BASE_URL', env('BASE_URL', 'http://localhost:8000'));
// The uploads directory defaults to ./uploads (root-level) — fixes repo layout mismatch
if (!defined('UPLOAD_DIR'))  define('UPLOAD_DIR', env('UPLOAD_DIR', __DIR__ . '/uploads'));
if (!defined('MAX_RESUME_BYTES')) define('MAX_RESUME_BYTES', (int) env('MAX_RESUME_BYTES', 3 * 1024 * 1024)); // 3 MB

// Ensure uploads directory exists and is writable in local/dev setups.
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

// Try to make it writable, but don't error the request here — endpoints should
// still check and return user-friendly errors when uploads fail.
if (!is_writable(UPLOAD_DIR)) {
    @chmod(UPLOAD_DIR, 0755);
}

// --- Session ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Use secure defaults suitable for local development; production
    // deployments should set these via PHP ini or a reverse proxy.
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
if (!function_exists('json_response')) {
    function json_response(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/**
 * Require a logged-in user; optionally require a specific role.
 * Used by protected PHP pages (dashboards). Redirects on failure.
 */
if (!function_exists('require_auth')) {
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
}

/**
 * Require a logged-in user for an API endpoint; optionally a role.
 * Returns 401/403 JSON instead of redirecting.
 */
if (!function_exists('require_api_auth')) {
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
}
