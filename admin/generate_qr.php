<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/phpqrcode/qrlib.php';

// Session and access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Paths and filenames
$date = date('Y-m-d');
$todayCodeFile = '../assets/qrcodes/' . date('Ymd') . '.png';
$expires_at = date('Y-m-d 23:59:00');

// Handle QR Regeneration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate'])) {
  $code = uniqid("QR_", true);

  if (!file_exists('../assets/qrcodes')) {
    mkdir('../assets/qrcodes', 0777, true);
  }

  QRcode::png($code, $todayCodeFile);

  // Save to DB
  $stmt = $pdo->prepare("REPLACE INTO qr_codes (date_generated, code, expires_at) VALUES (?, ?, ?)");
  $stmt->execute([$date, $code, $expires_at]);
}

// Fetch today's QR info
$stmt = $pdo->prepare("SELECT code, expires_at FROM qr_codes WHERE date_generated = ?");
$stmt->execute([$date]);
$row = $stmt->fetch();
$code = $row['code'] ?? '';
$expires_at = $row['expires_at'] ?? $expires_at;
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
<main class="max-w-2xl mx-auto p-6 text-center space-y-6">
  <h1 class="text-2xl font-bold flex items-center justify-center gap-2">
  <i data-lucide="qr-code" class="w-6 h-6"></i>
  <span>Today's QR Code</span>
</h1>


  <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
    <?php if ($code && file_exists($todayCodeFile)): ?>
      <img src="<?= $todayCodeFile ?>?<?= time() ?>" alt="QR Code" class="w-40 h-40 mx-auto">
    <?php else: ?>
      <p class="text-red-500">❗ No QR code generated yet.</p>
    <?php endif; ?>

    <div class="flex justify-center space-x-6 text-sm text-gray-700 dark:text-gray-300">
      <div><i data-lucide="calendar-days" class="inline w-4 h-4 mr-1"></i> Date: <strong><?= $date ?></strong></div>
      <div><i data-lucide="hourglass" class="inline w-4 h-4 mr-1"></i> Expires at: <strong><?= date("h:i A", strtotime($expires_at)) ?></strong></div>
    </div>

    <form method="POST">
      <button name="regenerate" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center space-x-2 mx-auto">
        <i data-lucide="rotate-cw" class="w-4 h-4"></i>
        <span>Regenerate QR</span>
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
