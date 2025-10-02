<?php
// No session needed for cron
// Use absolute paths so it works outside browser
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/phpqrcode/qrlib.php';

// Date & expiration
$date = date('Y-m-d');
$timeInStart  = "07:30:00";
$timeOutStart = "17:30:00";
$expires_at   = $date . " 23:59:59";

// Ensure output folder exists
$qrFolder = __DIR__ . '/assets/qrcodes/';
if (!file_exists($qrFolder)) {
    mkdir($qrFolder, 0777, true);
}

// Get all offices from users table
$stmtOffices = $pdo->query("SELECT DISTINCT office FROM users WHERE role = 'student'");
$offices = $stmtOffices->fetchAll(PDO::FETCH_COLUMN);

// Helper function to generate QR
function generateQR($office, $type, $time, $date, $expires_at, $pdo) {
    global $qrFolder;

    $code = uniqid("QR_{$type}_", true);
    $payload = "{$office}|{$type}|{$date}|{$code}";

    $fileName = "{$office}_" . date('Ymd') . "_{$type}.png";
    $filePath = $qrFolder . $fileName;

    QRcode::png($payload, $filePath, QR_ECLEVEL_L, 4);

    // Save into DB (unique per office+type+date)
    $stmt = $pdo->prepare("REPLACE INTO qr_codes (date_generated, code, expires_at, office, type) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$date, $code, $expires_at, $office, $type]);

    // Notify all students in that office
    $message = "📌 QR for " . strtoupper($type) . " has been auto-generated at {$time} for {$office}.";
    $relPath = 'assets/qrcodes/' . $fileName;

    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, message, qr_code_path, created_at)
                                SELECT id, ?, ?, NOW() FROM users WHERE role = 'student' AND office = ?");
    $stmtNotif->execute([$message, $relPath, $office]);
}

// Generate for each office
foreach ($offices as $office) {
    generateQR($office, "timein",  $timeInStart,  $date, $expires_at, $pdo);
    generateQR($office, "timeout", $timeOutStart, $date, $expires_at, $pdo);
}

echo "✅ QR codes auto-generated for all offices at 7:30 AM and 5:30 PM.\n";
