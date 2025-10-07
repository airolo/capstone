<?php
session_start();
require_once '../includes/db.php';

// ✅ Access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ Fetch admin info
$stmt = $pdo->prepare("SELECT fullname, office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$admin_office = $user['office'];

// ✅ Fetch student assistants in the same office
$stmt = $pdo->prepare("SELECT id, fullname, username, email, status, created_at FROM users WHERE role = 'student' AND office = ?");
$stmt->execute([$admin_office]);
$students = $stmt->fetchAll();

// ✅ Fetch today's QR codes (4 types)
$date_today = date('Y-m-d');
$stmtQR = $pdo->prepare("
    SELECT type, qr_path, created_at 
    FROM qr_codes 
    WHERE office = ? AND date_generated = ?
");
$stmtQR->execute([$admin_office, $date_today]);
$qrs = $stmtQR->fetchAll(PDO::FETCH_UNIQUE);

$qr_timein_am  = $qrs['timein_am']['qr_path'] ?? null;
$qr_timeout_am = $qrs['timeout_am']['qr_path'] ?? null;
$qr_timein_pm  = $qrs['timein_pm']['qr_path'] ?? null;
$qr_timeout_pm = $qrs['timeout_pm']['qr_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - MySchedMate</title>
  <meta http-equiv="refresh" content="60"> <!-- Auto-refresh every minute -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>

<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- ✅ Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex items-center justify-between shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="user-cog" class="w-6 h-6"></i>
    <span class="text-lg font-bold">Admin Panel</span>
  </div>
  <div class="space-x-4 flex items-center">
    <span class="text-sm hidden sm:inline">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="dashboard.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
    </a>
    <a href="../logout.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="log-out" class="w-4 h-4"></i> Logout
    </a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- ✅ Main Content -->
<main class="p-6 max-w-6xl mx-auto space-y-8">
  <h2 class="text-2xl font-semibold">👥 Admin Dashboard</h2>
  <p class="text-sm">Name: <strong><?= htmlspecialchars($user['fullname']) ?></strong></p>
  <p class="text-sm mb-4">Office: <strong><?= htmlspecialchars($admin_office) ?></strong></p>

  <!-- ✅ Dashboard Buttons -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-6">
    <a href="manage_requests.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
      <i data-lucide="check-circle" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
      <span>Make-Up Requests</span>
    </a>
    <a href="reports.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex items-center space-x-3">
      <i data-lucide="bar-chart-3" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
      <span>Reports</span>
    </a>
  </div>

  <!-- ✅ Auto-generated QR Section -->
  <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow space-y-6">
    <h3 class="text-xl font-bold text-blue-700 dark:text-blue-400 flex items-center gap-2">
      <i data-lucide="clock" class="w-5 h-5"></i> Today’s Attendance QR Codes
    </h3>
    <p class="text-sm text-gray-600 dark:text-gray-400">
      QR codes are auto-generated daily for each shift:
      <br>🕢 <strong>7:30 AM</strong> (Time-In AM) | 🕚 <strong>11:00 AM</strong> (Time-Out AM)
      <br>🕐 <strong>1:00 PM</strong> (Time-In PM) | 🕔 <strong>5:30 PM</strong> (Time-Out PM)
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
      <!-- Time-In AM -->
      <div class="p-4 border rounded-lg dark:border-gray-700">
        <h4 class="font-semibold mb-2">Time-In (7:30 AM)</h4>
        <?php if ($qr_timein_am): ?>
          <img src="../<?= htmlspecialchars($qr_timein_am) ?>?<?= time() ?>" alt="Time-In AM QR" class="w-40 h-40 mx-auto rounded shadow">
          <p class="mt-2 text-green-600 text-sm">✅ Generated</p>
        <?php else: ?>
          <p class="text-red-500 text-sm">❌ Not yet generated</p>
        <?php endif; ?>
      </div>

      <!-- Time-Out AM -->
      <div class="p-4 border rounded-lg dark:border-gray-700">
        <h4 class="font-semibold mb-2">Time-Out (11:00 AM)</h4>
        <?php if ($qr_timeout_am): ?>
          <img src="../<?= htmlspecialchars($qr_timeout_am) ?>?<?= time() ?>" alt="Time-Out AM QR" class="w-40 h-40 mx-auto rounded shadow">
          <p class="mt-2 text-green-600 text-sm">✅ Generated</p>
        <?php else: ?>
          <p class="text-red-500 text-sm">❌ Not yet generated</p>
        <?php endif; ?>
      </div>

      <!-- Time-In PM -->
      <div class="p-4 border rounded-lg dark:border-gray-700">
        <h4 class="font-semibold mb-2">Time-In (1:00 PM)</h4>
        <?php if ($qr_timein_pm): ?>
          <img src="../<?= htmlspecialchars($qr_timein_pm) ?>?<?= time() ?>" alt="Time-In PM QR" class="w-40 h-40 mx-auto rounded shadow">
          <p class="mt-2 text-green-600 text-sm">✅ Generated</p>
        <?php else: ?>
          <p class="text-red-500 text-sm">❌ Not yet generated</p>
        <?php endif; ?>
      </div>

      <!-- Time-Out PM -->
      <div class="p-4 border rounded-lg dark:border-gray-700">
        <h4 class="font-semibold mb-2">Time-Out (5:30 PM)</h4>
        <?php if ($qr_timeout_pm): ?>
          <img src="../<?= htmlspecialchars($qr_timeout_pm) ?>?<?= time() ?>" alt="Time-Out PM QR" class="w-40 h-40 mx-auto rounded shadow">
          <p class="mt-2 text-green-600 text-sm">✅ Generated</p>
        <?php else: ?>
          <p class="text-red-500 text-sm">❌ Not yet generated</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ✅ Student List -->
  <section class="mt-8">
    <h3 class="text-xl font-semibold mb-4">Student Assistants – <?= htmlspecialchars($admin_office) ?> Office</h3>
    <?php if (empty($students)): ?>
      <p class="text-gray-600 dark:text-gray-400">No student assistants found for your office.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full bg-white dark:bg-gray-800 border rounded">
          <thead class="bg-blue-100 dark:bg-gray-700">
            <tr>
              <th class="text-left p-3">Full Name</th>
              <th class="text-left p-3">Username</th>
              <th class="text-left p-3">Email</th>
              <th class="text-left p-3">Status</th>
              <th class="text-left p-3">Created</th>
              <th class="text-left p-3">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student): ?>
              <tr class="border-t hover:bg-blue-50 dark:hover:bg-gray-700">
                <td class="p-3"><?= htmlspecialchars($student['fullname']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['username']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['email']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['status']) ?></td>
                <td class="p-3 text-sm text-gray-500"><?= htmlspecialchars(date('M d, Y', strtotime($student['created_at']))) ?></td>
                <td class="p-3">
                  <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($student['id']) ?>">
                    <button type="submit" name="delete_student" class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
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
