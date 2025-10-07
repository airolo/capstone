<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// ✅ Ensure only logged-in student assistants can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Ensure QR code data is provided
if (empty($_POST['qr_data'])) {
    echo json_encode(['status' => 'error', 'message' => 'No QR code detected']);
    exit();
}

$scanned_code = trim($_POST['qr_data']);
$current_time = date('H:i:s');
$current_date = date('Y-m-d');

// ✅ Fetch QR details from database
$stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE qr_data = ? AND DATE(date_generated) = CURDATE()");
$stmt->execute([$scanned_code]);
$qr = $stmt->fetch();

if (!$qr) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired QR code']);
    exit();
}

$type = $qr['type']; // timein_am, timeout_am, timein_pm, timeout_pm

// ✅ Check if user already logged for this shift
$stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = ? AND DATE(time_in_am) = ?");
$stmt->execute([$user_id, $current_date]);
$attendance = $stmt->fetch();

// ✅ Determine the operation
switch ($type) {
    // 🕢 TIME-IN AM (7:30 AM)
    case 'timein_am':
        if ($attendance && $attendance['time_in_am']) {
            echo json_encode(['status' => 'error', 'message' => 'You have already timed in this morning.']);
            exit();
        }
        if (!$attendance) {
            $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, time_in_am, date_logged) VALUES (?, NOW(), ?)");
            $stmt->execute([$user_id, $current_date]);
        } else {
            $stmt = $pdo->prepare("UPDATE attendance_logs SET time_in_am = NOW() WHERE id = ?");
            $stmt->execute([$attendance['id']]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Morning time-in recorded successfully!']);
        break;

    // 🕚 TIME-OUT AM (11:00 AM)
    case 'timeout_am':
        if (!$attendance || !$attendance['time_in_am']) {
            echo json_encode(['status' => 'error', 'message' => 'You must time in first before timing out (AM).']);
            exit();
        }
        if ($attendance['time_out_am']) {
            echo json_encode(['status' => 'error', 'message' => 'You have already timed out this morning.']);
            exit();
        }
        $stmt = $pdo->prepare("UPDATE attendance_logs SET time_out_am = NOW() WHERE id = ?");
        $stmt->execute([$attendance['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Morning time-out recorded successfully!']);
        break;

    // 🕐 TIME-IN PM (1:00 PM)
    case 'timein_pm':
        if ($attendance && $attendance['time_in_pm']) {
            echo json_encode(['status' => 'error', 'message' => 'You have already timed in this afternoon.']);
            exit();
        }
        if (!$attendance) {
            $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, time_in_pm, date_logged) VALUES (?, NOW(), ?)");
            $stmt->execute([$user_id, $current_date]);
        } else {
            $stmt = $pdo->prepare("UPDATE attendance_logs SET time_in_pm = NOW() WHERE id = ?");
            $stmt->execute([$attendance['id']]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Afternoon time-in recorded successfully!']);
        break;

    // 🕔 TIME-OUT PM (5:30 PM)
    case 'timeout_pm':
        if (!$attendance || !$attendance['time_in_pm']) {
            echo json_encode(['status' => 'error', 'message' => 'You must time in first before timing out (PM).']);
            exit();
        }
        if ($attendance['time_out_pm']) {
            echo json_encode(['status' => 'error', 'message' => 'You have already timed out this afternoon.']);
            exit();
        }
        $stmt = $pdo->prepare("UPDATE attendance_logs SET time_out_pm = NOW() WHERE id = ?");
        $stmt->execute([$attendance['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Afternoon time-out recorded successfully!']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unrecognized QR type.']);
        break;
}
?>
