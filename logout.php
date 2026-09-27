<?php
/**
 * logout.php - Destroy the session safely.
 */

require_once __DIR__ . '/config/db.php';

// Unset all session variables.
$_SESSION = [];

// Delete the session cookie.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path']   ?? '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to landing page if accessed directly in a browser.
if (
    isset($_SERVER['HTTP_ACCEPT'])
    && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
) {
    json_response(['success' => true, 'message' => 'Logged out.']);
}

header('Location: index.html');
exit;
