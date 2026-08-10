<?php
require_once '../includes/auth.php';
require_once '../includes/update_rendered_hours.php';

requireRole('student', '../login.php');
touchActivity(600, '../login.php');

$user_id = $_SESSION['user_id'];

// Keep rendered_hours in users up to date
updateRenderedHours($user_id);

// Fetch attendance history, most recent first
$stmt = $pdo->prepare("SELECT log_date, morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out FROM attendance_logs WHERE user_id = ? ORDER BY log_date DESC");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll();

// Compute rendered minutes per day + grand total
$totalMinutes = 0;
foreach ($logs as &$log) {
  $minutes = 0;
  if ($log['morning_time_in'] && $log['morning_time_out']) {
    $minutes += (int) round((strtotime($log['morning_time_out']) - strtotime($log['morning_time_in'])) / 60);
  }
  if ($log['afternoon_time_in'] && $log['afternoon_time_out']) {
    $minutes += (int) round((strtotime($log['afternoon_time_out']) - strtotime($log['afternoon_time_in'])) / 60);
  }
  $log['minutes_rendered'] = $minutes;
  $totalMinutes += $minutes;
}
unset($log);

$totalHours = floor($totalMinutes / 60);
$totalRemainderMinutes = $totalMinutes % 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance History - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

  <!-- Navbar -->
  <nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex items-center justify-between shadow">
    <div class="flex items-center space-x-2">
      <i data-lucide="history" class="w-6 h-6"></i>
      <span class="text-lg font-bold">MySchedMate</span>
    </div>
    <div class="flex items-center space-x-4">
      <a href="dashboard.php" class="hover:underline">Dashboard</a>
      <a href="../logout.php" class="hover:underline">Logout</a>
      <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
    </div>
  </nav>

  <!-- Main -->
  <main class="max-w-4xl mx-auto p-6 space-y-6">
    <h1 class="text-2xl font-bold">📅 Attendance History</h1>

    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
      <p class="text-sm">
        Total Rendered: <strong><?= $totalHours ?>h <?= $totalRemainderMinutes ?>m</strong>
        (<?= $totalMinutes ?> minutes across <?= count($logs) ?> day<?= count($logs) === 1 ? '' : 's' ?>)
      </p>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-blue-100 dark:bg-gray-700 text-black dark:text-white">
          <tr>
            <th class="p-3">Date</th>
            <th class="p-3">AM Time In</th>
            <th class="p-3">AM Time Out</th>
            <th class="p-3">PM Time In</th>
            <th class="p-3">PM Time Out</th>
            <th class="p-3">Rendered</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($logs): foreach ($logs as $log): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700">
              <td class="p-3"><?= htmlspecialchars($log['log_date']) ?></td>
              <td class="p-3"><?= $log['morning_time_in'] ? date('h:i A', strtotime($log['morning_time_in'])) : '—' ?></td>
              <td class="p-3"><?= $log['morning_time_out'] ? date('h:i A', strtotime($log['morning_time_out'])) : '—' ?></td>
              <td class="p-3"><?= $log['afternoon_time_in'] ? date('h:i A', strtotime($log['afternoon_time_in'])) : '—' ?></td>
              <td class="p-3"><?= $log['afternoon_time_out'] ? date('h:i A', strtotime($log['afternoon_time_out'])) : '—' ?></td>
              <td class="p-3">
                <?php if ($log['minutes_rendered'] > 0): ?>
                  <?= floor($log['minutes_rendered'] / 60) ?>h <?= $log['minutes_rendered'] % 60 ?>m
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="p-4 text-center text-gray-500">No attendance records yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

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
