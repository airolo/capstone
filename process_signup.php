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
