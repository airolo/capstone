<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the CSRF token from the signup form
    if (!csrfValid()) {
        $_SESSION['signup_error'] = "Your session expired. Please try again.";
        header("Location: signup.php");
        exit();
    }

    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $office = trim($_POST['office']);

    // Public registration always creates a student account.
    // The role is never taken from user input.
    $role = 'student';

    // Check for existing username or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->fetch()) {
        $_SESSION['signup_error'] = "Username or email already exists.";
        header("Location: signup.php");
        exit();
    }

    if (strlen($password) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/', $password)) {
        $_SESSION['signup_error'] = "Password must be at least 8 characters long and contain letters and numbers.";
        header("Location: signup.php");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password_hash, role, office) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullname, $username, $email, $hashedPassword, $role, $office]);

    $_SESSION['signup_success'] = "Signup successful! Please login.";
    header("Location: login.php");
    exit();
} else {
    header("Location: signup.php");
    exit();
}