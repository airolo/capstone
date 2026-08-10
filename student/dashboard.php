<?php
require_once '../includes/auth.php';

// Only allow logged-in student + unified idle timeout
requireRole('student', '../login.php');
touchActivity(600, '../login.php');

// Get student data
$stmt = $pdo->prepare("SELECT id, fullname, office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
  session_unset();
  session_destroy();
  header("Location: ../login.php");
  exit();
}
$userId = $user['id'];

// Fetch today's time logs
$today = date('Y-m-d');
$logStmt = $pdo->prepare("SELECT morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out FROM attendance_logs WHERE user_id = ? AND log_date = ?");
$logStmt->execute([$userId, $today]);
$log = $logStmt->fetch();

// Fetch yesterday's log
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yesterdayStmt = $pdo->prepare("SELECT morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out FROM attendance_logs WHERE user_id = ? AND log_date = ?");
$yesterdayStmt->execute([$userId, $yesterday]);
$yesterdayLog = $yesterdayStmt->fetch();

$missedTimeout = $yesterdayLog
  && (($yesterdayLog['morning_time_in'] && !$yesterdayLog['morning_time_out'])
      || ($yesterdayLog['afternoon_time_in'] && !$yesterdayLog['afternoon_time_out']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard - MySchedMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    tailwind.config = { darkMode: 'class' };
  </script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

  <!-- Navbar -->
  <nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex items-center justify-between shadow">
    <div class="flex items-center space-x-2">
      <i data-lucide="calendar-clock" class="w-6 h-6"></i>
      <span class="text-lg font-bold">MySchedMate</span>
    </div>
    <div class="flex items-center space-x-4">
      <span class="text-sm hidden sm:inline">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
      <a href="dashboard.php" class="flex items-center gap-1 hover:underline">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
      </a>
      <a href="../logout.php" class="flex items-center gap-1 hover:underline">
        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
      </a>
      <button id="theme-toggle">
        <i id="theme-icon" data-lucide="moon" class="w-5 h-5 hover:scale-110 transition duration-300"></i>
      </button>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="p-6 max-w-5xl mx-auto space-y-6">
    <h2 class="text-2xl font-semibold">🎓 Student Dashboard</h2>
    <p class="text-sm">Name: <strong><?= htmlspecialchars($user['fullname']) ?></strong></p>
    <p class="text-sm mb-4">Office: <strong><?= htmlspecialchars($user['office']) ?></strong></p>

    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      <a href="view_schedule.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="calendar-days" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>View Schedule</span>
      </a>
      <a href="upload_schedule.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="upload-cloud" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>Upload Class Schedule</span>
      </a>
      <a href="time_log.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="clock" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>Time In / Out</span>
      </a>
      <a href="attendance_history.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="history" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>Attendance History</span>
      </a>
      <a href="make_up_hours.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="file-clock" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>Make-Up Requests</span>
      </a>
      <a href="contact_support.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="headphones" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>Contact Support</span>
      </a>
    </div>

    <!-- ✅ Real-Time Time Log Status -->
<section>
  <h3 class="text-lg font-semibold mb-2">🕒 Today's Time Log (<?= date('F j, Y') ?>)</h3>
  <div class="mt-6 bg-white dark:bg-gray-800 p-5 rounded-xl shadow space-y-2">
    <p><strong>AM Time-In:</strong> 
      <?= $log && $log['morning_time_in'] 
        ? '<span class="text-green-600 font-medium">✅ ' . date("g:i A", strtotime($log['morning_time_in'])) . '</span>' 
        : '<span class="text-red-600 font-medium">❌ Not yet timed in</span>' ?>
    </p>

    <p><strong>AM Time-Out:</strong> 
      <?= $log && $log['morning_time_out'] 
        ? '<span class="text-green-600 font-medium">✅ ' . date("g:i A", strtotime($log['morning_time_out'])) . '</span>' 
        : '<span class="text-red-600 font-medium">❌ Not yet timed out</span>' ?>
    </p>

    <p><strong>PM Time-In:</strong> 
      <?= $log && $log['afternoon_time_in'] 
        ? '<span class="text-green-600 font-medium">✅ ' . date("g:i A", strtotime($log['afternoon_time_in'])) . '</span>' 
        : '<span class="text-red-600 font-medium">❌ Not yet timed in</span>' ?>
    </p>

    <p><strong>PM Time-Out:</strong> 
      <?= $log && $log['afternoon_time_out'] 
        ? '<span class="text-green-600 font-medium">✅ ' . date("g:i A", strtotime($log['afternoon_time_out'])) . '</span>' 
        : '<span class="text-red-600 font-medium">❌ Not yet timed out</span>' ?>
    </p>

    <?php if ($missedTimeout): ?>
      <p class="text-red-500 mt-2">⚠️ You forgot to time out yesterday (<?= date("F j", strtotime($yesterday)) ?>).</p>
    <?php endif; ?>
  </div>
</section>

<!-- Auto-refresh every 30 seconds -->
<script>
  setTimeout(() => {
    location.reload();
  }, 30000);
</script>


    <!-- Notifications Panel -->
    <section class="mt-8">
      <h2 class="text-xl font-semibold mb-2">🔔 Notifications</h2>
      <div class="bg-white dark:bg-gray-800 p-4 rounded shadow space-y-2 text-sm">
        <?php
        $stmt = $pdo->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll();

        if ($notifications):
          foreach ($notifications as $n):
            $icon = strpos($n['message'], 'Approved') !== false ? '✅' : (strpos($n['message'], 'Denied') !== false ? '❌' : '⏳');
            echo "<div class='border-b border-gray-300 pb-2'>$icon " . htmlspecialchars($n['message']) . " <span class='text-xs text-gray-500'>(" . date('M j, g:i A', strtotime($n['created_at'])) . ")</span></div>";
          endforeach;
        else:
          echo "<p class='text-gray-500'>No recent notifications.</p>";
        endif;
        ?>
      </div>
    </section>
  </main>

  <footer class="text-center p-4 text-sm text-gray-600 dark:text-gray-400">
    MySchedMate &copy; <?= date('Y') ?>
  </footer>

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
