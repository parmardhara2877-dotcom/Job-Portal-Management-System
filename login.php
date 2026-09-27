<?php
/**
 * login.php - Authenticate a user and start a secure session.
 * Accepts JSON: { email, password }
 */

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    json_response(['success' => false, 'message' => 'Invalid request body.'], 400);
}

$email    = trim((string)($data['email']    ?? ''));
$password = (string)($data['password'] ?? '');
$requestedRole = (string)($data['role'] ?? '');

if ($email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Email and password are required.'], 422);
}

// --- Fetch user by email ---------------------------------------------
try {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Server error. Please try again.'], 500);
}

// --- Verify credentials ----------------------------------------------
if (!$user || !password_verify($password, $user['password_hash'])) {
    // Generic message to avoid leaking which part was wrong.
    json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
}

if (in_array($requestedRole, ['seeker', 'employer'], true) && $user['role'] !== $requestedRole) {
    json_response(['success' => false, 'message' => 'This account is registered as an ' . $user['role'] . '.'], 403);
}

// --- Regenerate session id to prevent fixation -----------------------
session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];
$_SESSION['name']    = $user['name'];

json_response([
    'success' => true,
    'message' => 'Logged in.',
    'user'    => [
        'id'    => (int) $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ],
]);
