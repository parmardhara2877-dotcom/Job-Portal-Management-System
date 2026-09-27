<?php
/**
 * register.php - Create a new user account.
 * Accepts JSON: { name, email, password, role }
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

// --- Server-side validation -------------------------------------------
$name     = trim((string)($data['name']     ?? ''));
$email    = trim((string)($data['email']    ?? ''));
$password = (string)($data['password'] ?? '');
$role     = (string)($data['role']     ?? 'seeker');

$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Name must be 100 characters or fewer.';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email address is not valid.';
} elseif (mb_strlen($email) > 190) {
    $errors[] = 'Email must be 190 characters or fewer.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
} elseif (strlen($password) > 72) {
    $errors[] = 'Password must be 72 characters or fewer.';
}

if (!in_array($role, ['seeker', 'employer'], true)) {
    $role = 'seeker';
}

if ($errors) {
    json_response(['success' => false, 'message' => implode(' ', $errors)], 422);
}

// --- Duplicate email check --------------------------------------------
try {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(['success' => false, 'message' => 'An account with that email already exists.'], 409);
    }
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Server error. Please try again.'], 500);
}

// --- Insert user ------------------------------------------------------
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = db()->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $hash, $role]);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Could not create account.'], 500);
}

// --- Auto-login the new user -----------------------------------------
$userId = (int) db()->lastInsertId();
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['email']   = $email;
$_SESSION['role']    = $role;
$_SESSION['name']    = $name;

json_response([
    'success' => true,
    'message' => 'Account created.',
    'user'    => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role],
], 201);
