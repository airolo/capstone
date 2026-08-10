<?php
// CSRF protection helpers.

// Returns (creating if needed) the session CSRF token.
function csrfToken() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

// Renders a hidden CSRF <input> for use inside <form method="POST">.
function csrfField() {
  return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

// Validates the submitted CSRF token for a standard POST form.
// Redirects to $loginPath on failure. For JSON endpoints, pass
// $loginPath = null and check the return value instead.
function csrfValid() {
  $token = $_POST['csrf_token'] ?? '';
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfCheck($loginPath = 'login.php') {
  if (!csrfValid()) {
    $_SESSION['login_error'] = "Your session expired. Please try again.";
    header("Location: " . $loginPath);
    exit();
  }
}