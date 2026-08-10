<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the CSRF token from the login form
    if (!csrfValid()) {
        $_SESSION['login_error'] = "Your session expired. Please try again.";
        header("Location: login.php");
        exit();
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check for user (only active accounts can log in)
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role, is_active FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && !$user['is_active']) {
        $_SESSION['login_error'] = "Your account has been deactivated. Please contact the administrator.";
        header("Location: login.php");
        exit();
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'student') {
            header("Location: student/dashboard.php");
        } elseif ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } elseif ($user['role'] === 'superadmin') {
            header("Location: superadmin/dashboard.php");
        }

        exit();
    } else {
        $_SESSION['login_error'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}