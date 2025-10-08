<?php
session_start();
require_once '../includes/db.php';

// Handle notification dismissal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $notifId = intval($_POST['id']);
  $userId = $_SESSION['user_id'];
  $stmt = $pdo->prepare("DELETE FROM make_up_requests WHERE id = ? AND user_id = ?");
  echo $stmt->execute([$notifId, $userId]) ? 'success' : 'fail';
  exit;
}

// Fetch student data
$stmt = $pdo->prepare("SELECT id, fullname, office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$userId = $user['id'];

// Today's attendance logs
$today = date('Y-m-d');
$logStmt = $pdo->prepare("SELECT time_in, time_out FROM attendance_logs WHERE user_id = ? AND DATE(time_in) = ?");
$logStmt->execute([$userId, $today]);
$log = $logStmt->fetch();

$timeInStatus = $log && $log['time_in'] ? '✅ ' . date("g:i A", strtotime($log['time_in'])) : '❌ Not yet timed in';
$timeOutStatus = $log && $log['time_out'] ? '✅ ' . date("g:i A", strtotime($log['time_out'])) : '❌ Not yet timed out';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard - MySchedMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
</head>

<body class="bg-blue-50 dark:bg-gray-900 text-gray-900 dark:text-white min-h-screen flex flex-col">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white shadow-md px-4 py-3 flex justify-between items-center relative">
  <div class="flex items-center space-x-2">
    <i data-lucide="calendar-clock" class="w-6 h-6"></i>
    <span class="font-bold text-lg">MySchedMate</span>
  </div>

  <!-- Desktop Nav -->
  <div class="hidden md:flex items-center space-x-5">
    <span class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="dashboard.php" class="hover:underline flex items-center gap-1"><i data-lucide="layout-dashboard" class="w-4 h-4"></i>Dashboard</a>
    <a href="../logout.php" class="hover:underline flex items-center gap-1"><i data-lucide="log-out" class="w-4 h-4"></i>Logout</a>
    <button id="theme-toggle" class="hover:scale-110 transition"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>

  <!-- Mobile Menu Button -->
  <button id="menu-toggle" class="md:hidden focus:outline-none">
    <i data-lucide="menu" class="w-6 h-6"></i>
  </button>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden absolute top-14 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg w-48 z-20">
    <ul class="flex flex-col text-sm text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">
      <li><a href="dashboard.php" class="block px-4 py-2 hover:bg-blue-100 dark:hover:bg-gray-700">Dashboard</a></li>
      <li><a href="../logout.php" class="block px-4 py-2 hover:bg-blue-100 dark:hover:bg-gray-700">Logout</a></li>
      <li><button id="theme-toggle-mobile" class="block w-full text-left px-4 py-2 hover:bg-blue-100 dark:hover:bg-gray-700">Toggle Theme</button></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<main class="flex-grow px-4 sm:px-6 py-6 max-w-5xl mx-auto w-full space-y-6">
  <h2 class="text-2xl font-semibold">🎓 Student Dashboard</h2>
  <p class="text-sm">Name: <strong><?= htmlspecialchars($user['fullname']) ?></strong></p>
  <p class="text-sm mb-4">Office: <strong><?= htmlspecialchars($user['office']) ?></strong></p>

  <!-- Dashboard Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php
    $cards = [
      ["view_schedule.php", "calendar-days", "View Schedule"],
      ["upload_schedule.php", "upload-cloud", "Upload Class Schedule"],
      ["time_in.php", "log-in", "Time In"],
      ["time_out.php", "log-out", "Time Out"],
      ["progress.php", "bar-chart", "Progress Report"],
      ["make_up_hours.php", "file-clock", "Make-Up Requests"],
    ];
    foreach ($cards as [$link, $icon, $label]): ?>
      <a href="<?= $link ?>" class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow hover:scale-105 transition flex items-center gap-3">
        <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span><?= $label ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Today's Attendance -->
<section>
  <h3 class="text-lg font-semibold mb-2">🕒 Today's Session (<?= date('F j, Y') ?>)</h3>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 space-y-3 text-sm">
    <?php
    $todayLogsStmt = $pdo->prepare("
        SELECT session, time_in, time_out 
        FROM attendance_logs 
        WHERE user_id = ? AND DATE(time_in) = ? 
        ORDER BY FIELD(session, 'AM', 'PM')
    ");
    $todayLogsStmt->execute([$userId, date('Y-m-d')]);
    $todayLogs = $todayLogsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$todayLogs): ?>
      <p class="text-gray-500">No sessions recorded yet today.</p>
    <?php else:
        foreach ($todayLogs as $log):
            $session_label = ($log['session'] === 'AM') ? 'Session 1 (AM)' : 'Session 2 (PM)'; ?>
            <div class="border-b border-gray-200 dark:border-gray-700 pb-2">
                <strong><?= $session_label ?>:</strong>
                <div class="ml-3">
                  🟢 Time In: <?= $log['time_in'] ? date('h:i A', strtotime($log['time_in'])) : '—' ?><br>
                  🔴 Time Out: <?= $log['time_out'] ? date('h:i A', strtotime($log['time_out'])) : '—' ?>
                </div>
                <?php if ($log['time_in'] && $log['time_out']):
                    $duration = strtotime($log['time_out']) - strtotime($log['time_in']);
                    $hours = floor($duration / 3600);
                    $minutes = floor(($duration % 3600) / 60); ?>
                    <span class="block ml-3 text-gray-500 text-xs">🕓 Duration: <?= $hours ?>h <?= $minutes ?>m</span>
                <?php endif; ?>
            </div>
    <?php endforeach; endif; ?>
  </div>
</section>

  <!-- Notifications -->
  <section class="mt-8">
    <h3 class="text-xl font-semibold mb-2">🔔 Notifications</h3>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 space-y-3 text-sm">
      <?php
      $stmt = $pdo->prepare("SELECT id, request_date, status, admin_comment FROM make_up_requests WHERE user_id = ? AND is_dismissed = 0 ORDER BY request_date DESC LIMIT 5");
      $stmt->execute([$userId]);
      $notifications = $stmt->fetchAll();

      if (!$notifications): ?>
        <p class="text-gray-500">No recent notifications.</p>
      <?php else:
        foreach ($notifications as $n): ?>
          <div class="relative border-b border-gray-200 dark:border-gray-700 pb-2 pr-6">
            <?= $n['status'] === 'approved' ? '✅' : ($n['status'] === 'denied' ? '❌' : '⏳') ?>
            Request on <?= htmlspecialchars($n['request_date']) ?>:
            <strong><?= htmlspecialchars($n['status']) ?></strong>
            <?php if ($n['admin_comment']): ?>
              <br><em>“<?= htmlspecialchars($n['admin_comment']) ?>”</em>
            <?php endif; ?>
            <button onclick="dismissNotification(<?= $n['id'] ?>)" class="absolute right-0 top-0 text-gray-500 hover:text-red-600">✖</button>
          </div>
        <?php endforeach; endif; ?>
    </div>
  </section>
</main>

<!-- Footer -->
<footer class="text-center py-4 text-xs text-gray-500 dark:text-gray-400 bg-blue-100 dark:bg-gray-800">
  MySchedMate © <?= date('Y') ?>
</footer>

<script>
  // Dark mode toggle
  const htmlEl = document.documentElement;
  function toggleTheme() {
    htmlEl.classList.toggle('dark');
    const icon = document.getElementById('theme-icon');
    icon.setAttribute('data-lucide', htmlEl.classList.contains('dark') ? 'sun' : 'moon');
    lucide.createIcons();
  }
  document.getElementById('theme-toggle').onclick = toggleTheme;
  document.getElementById('theme-toggle-mobile').onclick = toggleTheme;

  // Mobile menu toggle
  document.getElementById('menu-toggle').onclick = () => {
    document.getElementById('mobile-menu').classList.toggle('hidden');
  };

  // Dismiss notification
  function dismissNotification(id) {
    fetch('dashboard.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'id=' + id
    }).then(r => r.text()).then(res => {
      if (res.trim() === 'success') {
        document.querySelector(`#notif-${id}`)?.remove();
      }
    });
  }

  lucide.createIcons();
</script>

</body>
</html>
