<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? '';

    if ($id && $type === 'makeup') {
        $stmt = $pdo->prepare("UPDATE make_up_requests SET is_dismissed = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        echo "OK";
    } elseif ($id && $type === 'qr') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_dismissed = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        echo "OK";
    } else {
        echo "ERROR";
    }
}
