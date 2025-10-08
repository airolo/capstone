<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $office = trim($_POST['office']);
    $role = $_POST['role'];

    // ✅ CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['signup_error'] = "Invalid form submission. Please try again.";
        header("Location: signup.php");
        exit();
    }

    // ✅ Validate school email format (must start with 8 digits and end with @dwc-legazpi.edu)
    if (!preg_match('/^[0-9]{8}@dwc-legazpi\.edu$/i', $email)) {
        $_SESSION['signup_error'] = "Only valid school emails (8 digits + @dwc-legazpi.edu) are allowed.";
        header("Location: signup.php");
        exit();
    }

    // ✅ Check for existing username or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $_SESSION['signup_error'] = "Username or email already exists.";
        header("Location: signup.php");
        exit();
    }

    // ✅ Validate password
    if (strlen($password) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/', $password)) {
        $_SESSION['signup_error'] = "Password must be at least 8 characters long and contain both letters and numbers.";
        header("Location: signup.php");
        exit();
    }

    // ✅ Hash password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // ✅ New accounts: 'pending' until approved by admin
    $status = ($role === 'student') ? 'pending' : 'active';

    $stmt = $pdo->prepare("
        INSERT INTO users (fullname, username, email, password_hash, role, office, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$fullname, $username, $email, $hashedPassword, $role, $office, $status]);

    $_SESSION['signup_success'] = "Signup successful! Your account is pending admin approval.";
    header("Location: login.php");
    exit();

} else {
    header("Location: signup.php");
    exit();
}
