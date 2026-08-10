<?php
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

requireRole('student', '../login.php');
touchActivity(600, '../login.php');

$user_id = $_SESSION['user_id'];
$date_today = date('Y-m-d');

// Fetch attendance for today
$stmt = $pdo->prepare("SELECT morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out FROM attendance_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $date_today]);
$attendance = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Time In / Time Out - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="clock" class="w-6 h-6"></i>
    <span class="text-lg font-bold">MySchedMate</span>
  </div>
  <div class="flex space-x-4 items-center">
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- Main -->
<main class="max-w-3xl mx-auto p-6 space-y-6">
  <h2 class="text-2xl font-semibold">📸 Time In / Time Out</h2>

  <div>
    <p class="mb-2">Status Today:</p>
    <ul class="list-disc ml-6 text-sm space-y-1">
      <li><strong>AM Time In:</strong> <?= $attendance && $attendance['morning_time_in'] ? date('h:i A', strtotime($attendance['morning_time_in'])) : 'Not yet' ?></li>
      <li><strong>AM Time Out:</strong> <?= $attendance && $attendance['morning_time_out'] ? date('h:i A', strtotime($attendance['morning_time_out'])) : 'Not yet' ?></li>
      <li><strong>PM Time In:</strong> <?= $attendance && $attendance['afternoon_time_in'] ? date('h:i A', strtotime($attendance['afternoon_time_in'])) : 'Not yet' ?></li>
      <li><strong>PM Time Out:</strong> <?= $attendance && $attendance['afternoon_time_out'] ? date('h:i A', strtotime($attendance['afternoon_time_out'])) : 'Not yet' ?></li>
    </ul>
  </div>

  <div id="reader" class="w-full max-w-md bg-white dark:bg-gray-800 p-4 rounded-xl shadow"></div>

  <div id="qr-status" class="text-center text-sm mt-4 font-medium"></div>
</main>

<script>
const CSRF_TOKEN = '<?= csrfToken() ?>';
const reader = new Html5Qrcode("reader");
reader.start(
  { facingMode: "environment" },
  {
    fps: 10,
    qrbox: 250
  },
  qrCodeMessage => {
    // Send scanned code to server
    fetch("validate_qr.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "code=" + encodeURIComponent(qrCodeMessage) + "&csrf_token=" + encodeURIComponent(CSRF_TOKEN)
    })
    .then(res => res.json())
    .then(data => {
      document.getElementById('qr-status').textContent = data.message;
      document.getElementById('qr-status').className = data.success 
        ? "text-green-600 font-bold text-center mt-4" 
        : "text-red-600 font-bold text-center mt-4";
      if (data.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => {
      document.getElementById('qr-status').textContent = "Error connecting to server.";
    });
  },
  error => { /* ignore */ }
);

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
