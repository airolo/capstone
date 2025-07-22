<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

$stmt = $pdo->prepare("SELECT office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$office = $stmt->fetchColumn();

$search = $_GET['search'] ?? '';
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

// Build filter query
$query = "
  SELECT u.fullname, a.log_date, a.time_in, a.time_out
  FROM attendance_logs a
  JOIN users u ON a.user_id = u.id
  WHERE u.office = :office
";

$params = [':office' => $office];

if ($search) {
  $query .= " AND u.fullname LIKE :search";
  $params[':search'] = "%$search%";
}
if ($start && $end) {
  $query .= " AND a.log_date BETWEEN :start AND :end";
  $params[':start'] = $start;
  $params[':end'] = $end;
}

$query .= " ORDER BY a.log_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance Reports - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script> tailwind.config = { darkMode: 'class' } </script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="file-bar-chart" class="w-6 h-6"></i>
    <span class="font-bold text-lg">Admin Panel - Reports</span>
  </div>
  <div class="flex items-center space-x-4">
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="generate_qr.php" class="hover:underline">QR Generator</a>
    <a href="reports.php" class="hover:underline font-semibold">Reports</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- Main Content -->
<main class="max-w-6xl mx-auto p-6">
  <h1 class="text-xl font-bold mb-4">📅 Filter Attendance Logs</h1>

  <form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
    <input type="text" name="search" placeholder="Search name..." class="px-3 py-2 border rounded" value="<?= htmlspecialchars($search) ?>">
    <input type="date" name="start_date" value="<?= $start ?>" class="px-3 py-2 border rounded">
    <input type="date" name="end_date" value="<?= $end ?>" class="px-3 py-2 border rounded">
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">🔍 Filter</button>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full bg-white dark:bg-gray-800 text-sm rounded shadow">
        
     <thead>
  <tr class="bg-blue-100 dark:bg-gray-700 text-left">
    <th class="p-3">Student</th>
    <th class="p-3">Date</th>
    <th class="p-3">Time In</th>
    <th class="p-3">Time Out</th>
    <th class="p-3">Rendered</th>
  </tr>
</thead>

      <tbody>
  <?php if ($logs): foreach ($logs as $log): ?>
    <tr class="border-t border-gray-200 dark:border-gray-700">
      <td class="p-3"><?= htmlspecialchars($log['fullname']) ?></td>
      <td class="p-3"><?= $log['log_date'] ?></td>
      <td class="p-3"><?= $log['time_in'] ?? '—' ?></td>
      <td class="p-3"><?= $log['time_out'] ?? '—' ?></td>
      <td class="p-3">
        <?php
          if ($log['time_in'] && $log['time_out']) {
            $hours = floor($log['minutes_rendered'] / 60);
            $minutes = $log['minutes_rendered'] % 60;
            echo "{$hours}h {$minutes}m";
          } else {
            echo "—";
          }
        ?>
      </td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td colspan="5" class="p-4 text-center text-gray-500">No logs found.</td></tr>
  <?php endif; ?>
  
</tbody>

    </table>
  </div>
</main>

<!-- Theme Toggle Script -->
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
