<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$message = "";

// Fetch student’s office for QR validation
$stmt = $pdo->prepare("SELECT office FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$student_office = $user['office'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrCode = trim($_POST['qr_code']);

    // Validate QR for today’s valid time-out type for same office
    $stmt = $pdo->prepare("
        SELECT * FROM qr_codes
        WHERE BINARY TRIM(code) = TRIM(?)
          AND LOWER(office) = LOWER(?)
          AND type IN ('timeout_am', 'timeout_pm')
          AND DATE(date_generated) = CURDATE()
          AND expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$qrCode, $student_office]);
    $qr = $stmt->fetch();

    if ($qr) {
        // Determine session from QR type
        $session = ($qr['type'] === 'timeout_am') ? 'AM' : 'PM';

        // Find the log for this session
        $stmt = $pdo->prepare("
            SELECT id, time_out 
            FROM attendance_logs 
            WHERE user_id = ? AND DATE(time_in) = ? AND session = ?
        ");
        $stmt->execute([$userId, $today, $session]);
        $log = $stmt->fetch();

        if ($log && !$log['time_out']) {
            $stmt = $pdo->prepare("UPDATE attendance_logs SET time_out = NOW() WHERE id = ?");
            $stmt->execute([$log['id']]);
            $message = "✅ Time-Out recorded successfully for Session $session!";
        } elseif ($log && $log['time_out']) {
            $message = "⚠️ You already timed out for Session $session today.";
        } else {
            $message = "⚠️ You must time in first for Session $session before timing out.";
        }
    } else {
        $message = "❌ Invalid or expired QR code.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Time Out - MySchedMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen flex flex-col">

  <!-- Navbar -->
  <nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
    <div class="flex items-center space-x-2">
      <i data-lucide="clock" class="w-6 h-6"></i>
      <span class="text-lg font-bold">MySchedMate</span>
    </div>
    <div class="flex items-center space-x-4">
      <span class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
      <a href="dashboard.php" class="flex items-center gap-1 hover:underline">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
      </a>
      <a href="../logout.php" class="flex items-center gap-1 hover:underline">
        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
      </a>
      <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
    </div>
  </nav>

  <!-- Main -->
  <main class="flex-1 flex items-center justify-center p-6">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow w-full max-w-lg">
      <h2 class="text-2xl font-semibold mb-4 text-center">🕒 Time Out</h2>

      <?php if (!empty($message)): ?>
        <div class="mb-4 p-3 rounded text-center <?= str_contains($message, '✅') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <div id="qr-reader" class="w-full rounded overflow-hidden"></div>

      <form id="qr-form" method="POST" class="hidden">
        <input type="hidden" name="qr_code" id="qr_code">
      </form>
    </div>
  </main>

  <footer class="p-4 text-center text-sm text-gray-600 dark:text-gray-400">
    MySchedMate &copy; <?= date('Y') ?>
  </footer>

  <script>
    // Theme toggle
    const toggleBtn = document.getElementById('theme-toggle');
    const htmlEl = document.documentElement;
    const icon = document.getElementById('theme-icon');
    toggleBtn.addEventListener('click', () => {
      htmlEl.classList.toggle('dark');
      icon.setAttribute('data-lucide', htmlEl.classList.contains('dark') ? 'sun' : 'moon');
      lucide.createIcons();
    });
    lucide.createIcons();

    // QR scanner
    function onScanSuccess(decodedText) {
      document.getElementById('qr_code').value = decodedText;
      document.getElementById('qr-form').submit();
    }
    let scanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 250 });
    scanner.render(onScanSuccess);
  </script>
</body>
</html>
