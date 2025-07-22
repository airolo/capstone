<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $notifId = intval($_POST['id']);
    $userId = $_SESSION['user_id'];

    // Soft delete: You can change to UPDATE if you want to hide instead of delete
    $stmt = $pdo->prepare("DELETE FROM make_up_requests WHERE id = ? AND user_id = ?");
    echo $stmt->execute([$notifId, $userId]) ? 'success' : 'fail';
}
?>
