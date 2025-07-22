<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$fullname = $_SESSION['fullname'];

// Optional: session timeout
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
} elseif (time() - $_SESSION['last_activity'] > 600) { // 10 minutes
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();
?>
