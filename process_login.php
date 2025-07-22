<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check for user
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");


    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
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
