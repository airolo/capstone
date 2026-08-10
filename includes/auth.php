<?php
// Unified session bootstrap + role guards.
// Every protected page replaces its inline guard block with:
//   require_once '../includes/auth.php';
//   requireRole('admin', '../login.php');
//   touchActivity(600, '../login.php');

session_set_cookie_params([
  'httponly' => true,
  'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/db.php';

// Require a logged-in user with exactly $role (user_id + user_role keys).
function requireRole($role, $loginPath = 'login.php') {
  if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== $role) {
    header("Location: " . $loginPath);
    exit();
  }
}

// Idle timeout: destroys the session after $timeout seconds of inactivity.
function touchActivity($timeout = 600, $loginPath = 'login.php') {
  if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > $timeout) {
    session_unset();
    session_destroy();
    header("Location: " . $loginPath . "?timeout=1");
    exit();
  }
  $_SESSION['LAST_ACTIVITY'] = time();
}