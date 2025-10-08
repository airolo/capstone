<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $date = $_POST['date'];
  $start_time = $_POST['start_time'];
  $end_time = $_POST['end_time'];
  $reason = trim($_POST['reason']);

  if ($date && $start_time && $end_time && $reason) {
    $stmt = $pdo->prepare("INSERT INTO make_up_requests (user_id, request_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $date, $start_time, $end_time, $reason]);
    $success = true;
  }
}

// Fetch existing requests
$stmt = $pdo->prepare("SELECT * FROM make_up_requests WHERE user_id = ? ORDER BY request_date DESC");

$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Make-Up Hours - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">
  <!-- Navbar -->
  <nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
    <div class="flex items-center space-x-2">
      <i data-lucide="file-clock" class="w-6 h-6"></i>
      <span class="font-bold text-lg">Make-Up Hours</span>
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
  <main class="max-w-3xl mx-auto p-6 space-y-6">
    <h2 class="text-2xl font-semibold">Request Make-Up Hours</h2>

    <?php if (isset($success)): ?>
      <div class="p-3 bg-green-100 text-green-800 rounded">✅ Request submitted successfully!</div>
    <?php endif; ?>

    <form method="POST" class="bg-white dark:bg-gray-800 p-4 rounded shadow space-y-4">
      <div>
        <label class="block text-sm mb-1">Date</label>
        <input type="date" name="date" class="w-full p-2 border rounded" required>
      </div>
      <div class="flex gap-4">
        <div class="flex-1">
          <label class="block text-sm mb-1">Start Time</label>
          <input type="time" name="start_time" class="w-full p-2 border rounded" required>
        </div>
        <div class="flex-1">
          <label class="block text-sm mb-1">End Time</label>
          <input type="time" name="end_time" class="w-full p-2 border rounded" required>
        </div>
      </div>
      <div>
        <label class="block text-sm mb-1">Reason</label>
        <textarea name="reason" class="w-full p-2 border rounded" required></textarea>
      </div>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Submit Request</button>
    </form>

    <h3 class="text-lg font-bold mt-10">📋 Your Requests</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-sm bg-white dark:bg-gray-800 mt-4">
        <thead class="bg-blue-100 dark:bg-gray-700">
          <tr>
            <th class="p-2">Date</th>
            <th class="p-2">Time</th>
            <th class="p-2">Reason</th>
            <th class="p-2">Status</th>
          </tr>
        </thead>
        <tbody>
  <?php if ($requests): foreach ($requests as $r): ?>
    <tr class="border-t border-gray-200 dark:border-gray-700">
      <td class="p-2"><?= htmlspecialchars($r['request_date']) ?></td>
      <td class="p-2"><?= date('h:i A', strtotime($r['start_time'])) . " - " . date('h:i A', strtotime($r['end_time'])) ?></td>
      <td class="p-2"><?= htmlspecialchars($r['reason']) ?></td>
      <td class="p-2 font-semibold <?= $r['status'] === 'approved' ? 'text-green-600' : ($r['status'] === 'denied' ? 'text-red-600' : 'text-yellow-600') ?>">
        <?= ucfirst($r['status']) ?>
      </td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td colspan="4" class="text-center p-4 text-gray-500">No requests yet.</td></tr>
  <?php endif; ?>
</tbody>

      </table>
    </div>
  </main>

  <script>
    const toggleBtn = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const html = document.documentElement;

    toggleBtn.addEventListener('click', () => {
      html.classList.toggle('dark');
      icon.setAttribute('data-lucide', html.classList.contains('dark') ? 'sun' : 'moon');
      lucide.createIcons();
    });

    lucide.createIcons();
  </script>
</body>
</html>
