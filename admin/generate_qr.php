<?php
require_once '../includes/auth.php';
require_once '../includes/generate_qr_codes.php';

// Session and access control
requireRole('admin', '../login.php');
touchActivity(600, '../login.php');

$adminId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Students assigned to the admin's office
$officeStmt = $pdo->prepare("SELECT office FROM users WHERE id = ?");
$officeStmt->execute([$adminId]);
$office = $officeStmt->fetchColumn();

$studentStmt = $pdo->prepare("SELECT id, fullname FROM users WHERE role = 'student' AND office = ? ORDER BY fullname");
$studentStmt->execute([$office]);
$students = $studentStmt->fetchAll();

// Fetch today's QR info per student
$qrByStudent = [];
foreach ($students as $s) {
  $qrByStudent[$s['id']] = getDailyQrCodes($today, $s['id'])[$s['id']] ?? ['name' => $s['fullname'], 'qrs' => []];
}
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
<main class="max-w-5xl mx-auto p-6 space-y-6">
  <h1 class="text-2xl font-bold text-center">
    Today's QR Codes - <?= $today ?> (<?= htmlspecialchars($office) ?> Office)
  </h1>

  <?php if (empty($students)): ?>
    <p class="text-center text-gray-500">No student assistants assigned to your office.</p>
  <?php endif; ?>

  <?php foreach ($qrByStudent as $studentId => $student): ?>
    <section>
      <h2 class="text-lg font-semibold mb-3">🎓 <?= htmlspecialchars($student['name']) ?></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach (QR_SESSIONS as $type => $meta): ?>
          <?php $qr = $student['qrs'][$type] ?? null; ?>
          <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow text-center space-y-3">
            <p class="font-semibold text-blue-700 dark:text-yellow-400"><?= $meta['label'] ?></p>
            <?php if ($qr && $qr['code'] && file_exists($qr['file'])): ?>
              <img src="<?= $qr['url'] ?>?<?= time() ?>" alt="<?= $meta['label'] ?>" class="w-36 h-36 mx-auto">
              <p class="text-sm text-gray-600 dark:text-gray-300">Valid: <?= $meta['start'] ?> - <?= $meta['end'] ?></p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Expires: <?= date('h:i A', strtotime($qr['expires_at'])) ?></p>
            <?php else: ?>
              <p class="text-red-500 text-sm py-10">❗ Not generated yet.</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <p class="text-center text-xs text-gray-500">Each QR code is valid only for the student it was generated for and appears automatically at its scheduled time (07:30, 12:00, 13:00, 17:00).</p>
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
