<?php
/**
 * session.php - Returns the currently logged-in user (if any) as JSON.
 * Used by the frontend to render a session-aware navbar.
 */

require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    json_response([
        'success' => true,
        'user' => [
            'id'    => (int) $_SESSION['user_id'],
            'name'  => $_SESSION['name']  ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role'  => $_SESSION['role']  ?? '',
        ],
    ]);
}

json_response(['success' => false, 'user' => null]);
