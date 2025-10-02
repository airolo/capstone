<?php
session_start();

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $notifId = intval($_POST['id']);
    $userId = $_SESSION['user_id'];

    // Soft delete from view (or hard delete if preferred)
    $stmt = $pdo->prepare("DELETE FROM make_up_requests WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$notifId, $userId])) {
        echo 'success';
    } else {
        echo 'fail';
    }
}

// Get student data
$stmt = $pdo->prepare("SELECT id, fullname, office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$userId = $user['id'];

// Fetch today's time logs
$today = date('Y-m-d');
$logStmt = $pdo->prepare("SELECT time_in, time_out FROM attendance_logs WHERE user_id = ? AND DATE(time_in) = ?");
$logStmt->execute([$userId, $today]);
$log = $logStmt->fetch();

$timeInStatus = $log && $log['time_in'] ? '✅ ' . date("g:i A", strtotime($log['time_in'])) : '❌ Not yet timed in';
$timeOutStatus = $log && $log['time_out'] ? '✅ ' . date("g:i A", strtotime($log['time_out'])) : '❌ Not yet timed out';

// Fetch yesterday’s log
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yesterdayStmt = $pdo->prepare("SELECT time_in, time_out FROM attendance_logs WHERE user_id = ? AND DATE(time_in) = ?");
$yesterdayStmt->execute([$userId, $yesterday]);
$yesterdayLog = $yesterdayStmt->fetch();

$missedTimeout = $yesterdayLog && $yesterdayLog['time_in'] && !$yesterdayLog['time_out'];


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
<p class="text-sm mb-2">Office: <strong><?= htmlspecialchars($user['office']) ?></strong></p>
<!-- <a href="edit_profile.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">Edit Profile</a> -->


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
       <!-- <a href="generate_work_schedule.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3"> -->
        <!-- <i data-lucide="cog" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i> -->
        <!-- <span>Generate Work Schedule</span> -->
      <!-- </a> -->
      <a href="time_in.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
  <i data-lucide="log-in" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
  <span>Time In</span>
</a>

<a href="time_out.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
  <i data-lucide="log-out" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
  <span>Time Out</span>
</a>

      <a href="progress.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="bar-chart" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>Progress Report</span>
      </a>
      <a href="make_up_hours.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
        <i data-lucide="file-clock" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
        <span>Make-Up Requests</span>
      </a>
    </div>

    <!-- ✅ Real-Time Time Log Status -->
<section>
  <h3 class="text-lg font-semibold mb-2">🕒 Today's Time Log (<?= date('F j, Y') ?>)</h3>
  <div class="mt-6 bg-white dark:bg-gray-800 p-5 rounded-xl shadow space-y-2">
    <p><strong>Time-In:</strong> 
      <?= $log && $log['time_in'] 
        ? '<span class="text-green-600 font-medium">✅ ' . date("g:i A", strtotime($log['time_in'])) . '</span>' 
        : '<span class="text-red-600 font-medium">❌ Not yet timed in</span>' ?>
    </p>

    <p><strong>Time-Out:</strong> 
      <?= $log && $log['time_out'] 
        ? '<span class="text-green-600 font-medium">✅ ' . date("g:i A", strtotime($log['time_out'])) . '</span>' 
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
  <div class="bg-white dark:bg-gray-800 p-4 rounded shadow space-y-4 text-sm" id="notification-container">
    <?php
    // 1. Fetch Make-up Request Notifications
    $stmt = $pdo->prepare("SELECT id, request_date, status, admin_comment 
                           FROM make_up_requests 
                           WHERE user_id = ? AND is_dismissed = 0
                           ORDER BY request_date DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $makeup_notifications = $stmt->fetchAll();

    // 2. Fetch QR Notifications (Time In & Time Out separately)
    $stmt2 = $pdo->prepare("SELECT id, message, qr_code_path, created_at 
                            FROM notifications 
                            WHERE user_id = ? AND is_dismissed = 0
                            ORDER BY created_at DESC");
    $stmt2->execute([$_SESSION['user_id']]);
    $qr_notifications = $stmt2->fetchAll();

    $timeInNotifs = [];
    $timeOutNotifs = [];
    foreach ($qr_notifications as $n) {
      if (stripos($n['message'], 'TIMEIN') !== false) {
        $timeInNotifs[] = $n;
      } elseif (stripos($n['message'], 'TIMEOUT') !== false) {
        $timeOutNotifs[] = $n;
      }
    }

    if ($makeup_notifications || $timeInNotifs || $timeOutNotifs):
    ?>
      <!-- ✅ Make-up Notifications -->
      <?php foreach ($makeup_notifications as $n): 
        $icon = $n['status'] === 'approved' ? '✅' : ($n['status'] === 'denied' ? '❌' : '⏳');
        $message = $n['status'] === 'pending'
          ? "Your request on {$n['request_date']} is still pending."
          : "Your request on {$n['request_date']} was <strong>{$n['status']}</strong>. " . ($n['admin_comment'] ? "Comment: <em>{$n['admin_comment']}</em>" : '');
      ?>
        <div id="notif-makeup-<?= $n['id'] ?>" class="relative border-b border-gray-300 pb-2 pr-6">
          <?= $icon ?> <?= $message ?>
          <button class="absolute right-0 top-0 text-gray-500 hover:text-red-600" 
                  onclick="dismissNotification(<?= $n['id'] ?>, 'makeup')">✖</button>
        </div>
      <?php endforeach; ?>

      <!-- ✅ Time In QR Notifications -->
      <?php if ($timeInNotifs): ?>
        <h3 class="text-md font-semibold mt-4">⏱ Time In QR</h3>
        <?php foreach ($timeInNotifs as $n): ?>
          <div id="notif-qr-<?= $n['id'] ?>" class="relative border-b border-gray-300 pb-2 pr-6">
            <?= htmlspecialchars($n['message']) ?>
            <?php if (!empty($n['qr_code_path'])): ?>
              <div class="mt-2 flex items-center space-x-4">
                <img src="../<?= htmlspecialchars($n['qr_code_path']) ?>" alt="QR Code" class="w-20 h-20 border rounded">
                <button onclick="showQrModal('../<?= htmlspecialchars($n['qr_code_path']) ?>')" 
                  class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs">View Full QR</button>
              </div>
            <?php endif; ?>
            <p class="text-xs text-gray-500 mt-1">
              Generated at: <?= date("F j, Y g:i A", strtotime($n['created_at'])) ?>
            </p>
            <button class="absolute right-0 top-0 text-gray-500 hover:text-red-600"
                    onclick="dismissNotification(<?= $n['id'] ?>, 'qr')">✖</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- ✅ Time Out QR Notifications -->
      <?php if ($timeOutNotifs): ?>
        <h3 class="text-md font-semibold mt-4">⏰ Time Out QR</h3>
        <?php foreach ($timeOutNotifs as $n): ?>
          <div id="notif-qr-<?= $n['id'] ?>" class="relative border-b border-gray-300 pb-2 pr-6">
            <?= htmlspecialchars($n['message']) ?>
            <?php if (!empty($n['qr_code_path'])): ?>
              <div class="mt-2 flex items-center space-x-4">
                <img src="../<?= htmlspecialchars($n['qr_code_path']) ?>" alt="QR Code" class="w-20 h-20 border rounded">
                <button onclick="showQrModal('../<?= htmlspecialchars($n['qr_code_path']) ?>')" 
                  class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs">View Full QR</button>
              </div>
            <?php endif; ?>
            <p class="text-xs text-gray-500 mt-1">
              Generated at: <?= date("F j, Y g:i A", strtotime($n['created_at'])) ?>
            </p>
            <button class="absolute right-0 top-0 text-gray-500 hover:text-red-600"
                    onclick="dismissNotification(<?= $n['id'] ?>, 'qr')">✖</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    <?php else: ?>
      <p class='text-gray-500'>No recent notifications.</p>
    <?php endif; ?>
  </div>
</section>



<!-- Modal -->
<div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 p-6 rounded shadow-lg relative">
    <button onclick="closeQrModal()" class="absolute top-2 right-2 text-gray-600 hover:text-red-600">✖</button>
    <img id="qrModalImage" src="" alt="Full QR" class="w-80 h-80 mx-auto">
  </div>
</div>

<script>
  function showQrModal(src) {
    document.getElementById("qrModalImage").src = src;
    document.getElementById("qrModal").classList.remove("hidden");
  }
  function closeQrModal() {
    document.getElementById("qrModal").classList.add("hidden");
  }

  // 🔥 Dismiss notification
  function dismissNotification(id, type) {
    fetch("../student/dismiss_notification.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${id}&type=${type}`
    })
    .then(response => response.text())
    .then(data => {
      if (data.trim() === "OK") {
        document.getElementById(`notif-${type}-${id}`).remove();
      } else {
        alert("Failed to remove notification: " + data);
      }
    })
    .catch(err => alert("Error: " + err));
  }
</script>


  </main>

  <footer class="text-center p-4 text-sm text-gray-600 dark:text-gray-400">
    MySchedMate &copy; <?= date('Y') ?>
  </footer>

  <script>

  
  function dismissNotification(id) {
    fetch('dismiss_notification.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id=' + encodeURIComponent(id)
    })
    .then(response => response.text())
    .then(result => {
      if (result === 'success') {
        const el = document.getElementById('notif-' + id);
        if (el) {
  el.remove();

  // Check if there are any notifications left
  const container = document.getElementById('notification-container');
  const remaining = container.querySelectorAll('[id^="notif-"]');
  if (remaining.length === 0) {
    const noNotif = document.createElement('p');
    noNotif.className = 'text-gray-500';
    noNotif.textContent = 'No new notifications.';
    container.appendChild(noNotif);
  }
}

      } else {
        alert('Failed to remove notification.');
      }
    });
  }
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
