<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/phpqrcode/qrlib.php';

// Session and access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch admin office
$stmtOffice = $pdo->prepare("SELECT office FROM users WHERE id = ?");
$stmtOffice->execute([$_SESSION['user_id']]);
$admin_office = $stmtOffice->fetchColumn();

// Date & expiration
$date = date('Y-m-d');
$expires_at = date('Y-m-d 23:59:00');

// File paths
$timeInFile = '../assets/qrcodes/' . $admin_office . '_' . date('Ymd') . '_timein.png';
$timeOutFile = '../assets/qrcodes/' . $admin_office . '_' . date('Ymd') . '_timeout.png';

// Generate QR helper
function generateQR($type, $filePath, $date, $expires_at, $admin_office, $pdo) {
  $code = uniqid("QR_{$type}_", true);
  QRcode::png($code, $filePath);

  // Save QR to DB
  $stmt = $pdo->prepare("REPLACE INTO qr_codes (date_generated, code, expires_at, office, type) 
                         VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([$date, $code, $expires_at, $admin_office, $type]);

  // Notify students in same office
  $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, message, qr_code_path, created_at)
                              SELECT id, ?, ?, NOW()
                              FROM users WHERE role = 'student' AND office = ?");
  $stmtNotif->execute([
    "📌 A QR Code has been generated for " . ucfirst($type) . " in $admin_office.",
    'assets/qrcodes/' . basename($filePath),
    $admin_office
  ]);
}

// Handle Regeneration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['gen_timein'])) {
    generateQR("timein", $timeInFile, $date, $expires_at, $admin_office, $pdo);
  }
  if (isset($_POST['gen_timeout'])) {
    generateQR("timeout", $timeOutFile, $date, $expires_at, $admin_office, $pdo);
  }
}

// Fetch existing QR codes
$stmt = $pdo->prepare("SELECT type, code, expires_at FROM qr_codes WHERE date_generated = ? AND office = ?");
$stmt->execute([$date, $admin_office]);
$rows = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

$timeInQR = $rows['timein']['code'] ?? '';
$timeOutQR = $rows['timeout']['code'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>QR Code Generator - Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
  <div class="flex items-center space-x-2">
   <i data-lucide="qr-code" class="w-6 h-6"></i>
    <span class="font-bold text-lg">Admin Panel - QR Generator</span>
  </div>
  <div class="flex items-center space-x-4">
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- Main Content -->
<main class="max-w-3xl mx-auto p-6 space-y-8">

  <!-- TIME IN QR -->
  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
    <h2 class="text-xl font-bold flex items-center gap-2 text-blue-700 dark:text-blue-400">
      <i data-lucide="log-in" class="w-5 h-5"></i> Time In QR (<?= htmlspecialchars($admin_office) ?>)
    </h2>
    <?php if ($timeInQR && file_exists($timeInFile)): ?>
      <img src="<?= $timeInFile ?>?<?= time() ?>" alt="QR Code Time In" class="w-40 h-40 mx-auto">
    <?php else: ?>
      <p class="text-red-500">❗ No QR code generated yet.</p>
    <?php endif; ?>
    <form method="POST" class="text-center">
      <button name="gen_timein" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center space-x-2 mx-auto">
        <i data-lucide="rotate-cw" class="w-4 h-4"></i><span>Generate / Regenerate Time In QR</span>
      </button>
    </form>
  </div>

  <!-- TIME OUT QR -->
  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
    <h2 class="text-xl font-bold flex items-center gap-2 text-blue-700 dark:text-blue-400">
      <i data-lucide="log-out" class="w-5 h-5"></i> Time Out QR (<?= htmlspecialchars($admin_office) ?>)
    </h2>
    <?php if ($timeOutQR && file_exists($timeOutFile)): ?>
      <img src="<?= $timeOutFile ?>?<?= time() ?>" alt="QR Code Time Out" class="w-40 h-40 mx-auto">
    <?php else: ?>
      <p class="text-red-500">❗ No QR code generated yet.</p>
    <?php endif; ?>
    <form method="POST" class="text-center">
      <button name="gen_timeout" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center space-x-2 mx-auto">
        <i data-lucide="rotate-cw" class="w-4 h-4"></i><span>Generate / Regenerate Time Out QR</span>
      </button>
    </form>
  </div>

</main>

<!-- Dark Mode Toggle -->
<script>
  const toggleBtn = document.getElementById('theme-toggle');
  const htmlEl = document.documentElement;
  const icon = document.getElementById('theme-icon');

  toggleBtn.addEventListener('click', () => {
    htmlEl.classList.toggle('dark');
    icon.setAttribute('data-lucide', htmlEl.classList.contains('dark') ? 'sun' : 'moon');
    lucide.createIcons();
  });

  lucide.createIcons();
</script>
</body>
</html>
