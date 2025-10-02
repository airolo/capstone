<?php
// validate_qr.php
require_once __DIR__ . "/includes/db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = $_POST['qr_data'] ?? '';

    // Extract last segment (the unique code)
    $parts = explode('|', $raw);
    $code = end($parts);  

    $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $qr = $stmt->fetch();

    if ($qr) {
        if (strtotime($qr['expires_at']) >= time()) {
            echo json_encode([
                "status" => "success",
                "message" => "✅ QR Valid",
                "type" => $qr['type']  // "time_in" or "time_out"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "⏳ QR Expired"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "❌ Invalid QR"
        ]);
    }
}
?>
