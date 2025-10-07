<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/phpqrcode/qrlib.php';

date_default_timezone_set('Asia/Manila');

// ✅ Define scheduled QR times and expiration
$qrSchedules = [
    'timein_am'  => ['generate_time' => '07:30:00', 'expire_after' => '+30 minutes'],
    'timeout_am' => ['generate_time' => '11:00:00', 'expire_after' => '+30 minutes'],
    'timein_pm'  => ['generate_time' => '13:00:00', 'expire_after' => '+30 minutes'],
    'timeout_pm' => ['generate_time' => '17:30:00', 'expire_after' => '+30 minutes'],
];

function generateQR($type, $office, $pdo, $schedule) {
    $folderPath = __DIR__ . '/assets/qrcodes/';
    if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);

    $fileName = "{$office}_" . date('Ymd_His') . "_{$type}.png";
    $filePath = $folderPath . $fileName;

    $codeValue = uniqid("QR_{$type}_", true);
    QRcode::png($codeValue, $filePath);

    $createdAt = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . $schedule['generate_time']));
    $expiresAt = date('Y-m-d H:i:s', strtotime($schedule['expire_after'], strtotime($createdAt)));

    // ✅ Store QR code entry
    $stmt = $pdo->prepare("
        INSERT INTO qr_codes (office, date_generated, type, qr_path, code, created_at, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$office, date('Y-m-d'), $type, 'assets/qrcodes/' . $fileName, $codeValue, $createdAt, $expiresAt]);

    // ✅ Notification message
    $notifMsg = match($type) {
        'timein_am'  => '📌 Morning Time-In QR generated (7:30 AM)',
        'timeout_am' => '📌 Morning Time-Out QR generated (11:00 AM)',
        'timein_pm'  => '📌 Afternoon Time-In QR generated (1:00 PM)',
        'timeout_pm' => '📌 Afternoon Time-Out QR generated (5:30 PM)',
        default      => '📌 A new QR has been generated.'
    };

    // ✅ Fix: use `qr_code_path` (to match dashboard column name)
    $stmtNotif = $pdo->prepare("
        INSERT INTO notifications (user_id, message, qr_code_path, created_at)
        SELECT id, ?, ?, NOW()
        FROM users
        WHERE role = 'student' AND office = ?
    ");
    $stmtNotif->execute([$notifMsg, 'assets/qrcodes/' . $fileName, $office]);
}


$offices = $pdo->query("SELECT DISTINCT office FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);

foreach ($offices as $office) {
    foreach ($qrSchedules as $type => $schedule) {
        generateQR($type, $office, $pdo, $schedule);
    }
}

echo "✅ All QR codes generated with proper expiry times on " . date('F j, Y h:i A');
?>
