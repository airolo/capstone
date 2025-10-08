<?php
session_start();
require_once '../includes/db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch admin info
$stmt = $pdo->prepare("SELECT fullname, office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$admin_office = $user['office'];

// Fetch student assistants in the same office
$stmt = $pdo->prepare("SELECT id, fullname, username, email, status, created_at FROM users WHERE role = 'student' AND office = ?");
$stmt->execute([$admin_office]);
$students = $stmt->fetchAll();

// Fetch today's QR codes (4 types)
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

// Fetch pending student count for notification badge
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND office = ? AND status = 'pending'");
$stmt->execute([$admin_office]);
$pending_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - MySchedMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="refresh" content="60"> <!-- Auto-refresh every minute -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>

<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-4 sm:px-6 py-3 flex items-center justify-between shadow relative">
  <div class="flex items-center space-x-2">
    <i data-lucide="user-cog" class="w-6 h-6"></i>
    <span class="text-lg font-bold">Admin Panel</span>
  </div>

  <!-- Right side (desktop) -->
  <div class="hidden md:flex items-center space-x-4">
    <span class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="dashboard.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
    </a>
    <a href="../logout.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="log-out" class="w-4 h-4"></i> Logout
    </a>
    <button id="theme-toggle" aria-label="Toggle theme" class="ml-2"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>

  <!-- Mobile buttons -->
  <div class="md:hidden flex items-center gap-2">
    <button id="theme-toggle-mobile" aria-label="Toggle theme mobile" class="mr-2"><i data-lucide="moon" class="w-5 h-5"></i></button>
    <button id="mobile-menu-btn" aria-label="Open menu" class="focus:outline-none">
      <i data-lucide="menu" class="w-6 h-6"></i>
    </button>
  </div>

  <!-- Mobile menu (hidden by default) -->
  <div id="mobile-menu" class="hidden absolute right-4 top-full mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-20">
    <ul class="flex flex-col text-sm text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">
      <li><a href="dashboard.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Dashboard</a></li>
      <li><a href="account_approvals.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Account Approvals <?php if ($pending_count > 0): ?><span class="ml-2 inline-block bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= htmlspecialchars($pending_count) ?></span><?php endif; ?></a></li>
      <li><a href="manage_requests.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Make-Up Requests</a></li>
      <li><a href="reports.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Reports</a></li>
      <li><a href="../logout.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<main class="p-4 sm:p-6 max-w-6xl mx-auto space-y-8">

  <h2 class="text-2xl font-semibold">👥 Admin Dashboard</h2>
  <p class="text-sm">Name: <strong><?= htmlspecialchars($user['fullname']) ?></strong></p>
  <p class="text-sm mb-4">Office: <strong><?= htmlspecialchars($admin_office) ?></strong></p>

  <!-- Dashboard Buttons -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    <!-- Make-Up Requests -->
    <a href="manage_requests.php" 
      class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex flex-col items-center justify-center text-center space-y-2 relative">
      <i data-lucide="check-circle" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
      <span class="font-medium">Make-Up Requests</span>
    </a>

    <!-- Reports -->
    <a href="reports.php" 
      class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex flex-col items-center justify-center text-center space-y-2 relative">
      <i data-lucide="bar-chart-3" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
      <span class="font-medium">Reports</span>
    </a>

    <!-- Account Approvals with Badge -->
    <a href="account_approvals.php" 
      class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition flex flex-col items-center justify-center text-center space-y-2 relative">
      <i data-lucide="user-check" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
      <span class="font-medium">Account Approvals</span>
      <?php if ($pending_count > 0): ?>
        <span class="absolute top-3 right-4 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">
          <?= htmlspecialchars($pending_count) ?>
        </span>
      <?php endif; ?>
    </a>
  </div>

  <!-- Auto-generated QR Section -->
  <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow space-y-6">
    <h3 class="text-xl font-bold text-blue-700 dark:text-blue-400 flex items-center gap-2">
      <i data-lucide="clock" class="w-5 h-5"></i> Today’s Attendance QR Codes
    </h3>
    <p class="text-sm text-gray-600 dark:text-gray-400">
      QR codes are auto-generated daily for each shift:
      <br class="hidden sm:inline">🕢 <strong>7:30 AM</strong> (Time-In AM) | 🕚 <strong>11:00 AM</strong> (Time-Out AM)
      <br class="hidden sm:inline">🕐 <strong>1:00 PM</strong> (Time-In PM) | 🕔 <strong>5:30 PM</strong> (Time-Out PM)
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
      <?php
      $qr_sections = [
        'Time-In (7:30 AM)' => $qr_timein_am,
        'Time-Out (11:00 AM)' => $qr_timeout_am,
        'Time-In (1:00 PM)' => $qr_timein_pm,
        'Time-Out (5:30 PM)' => $qr_timeout_pm
      ];
      foreach ($qr_sections as $label => $path):
      ?>
      <div class="p-4 border rounded-lg dark:border-gray-700">
        <h4 class="font-semibold mb-2"><?= $label ?></h4>
        <?php if ($path): ?>
          <img src="../<?= htmlspecialchars($path) ?>?<?= time() ?>" alt="<?= htmlspecialchars($label) ?> QR" class="w-32 h-32 sm:w-40 sm:h-40 mx-auto rounded shadow object-contain">
          <p class="mt-2 text-green-600 text-sm">✅ Generated</p>
        <?php else: ?>
          <p class="text-red-500 text-sm">❌ Not yet generated</p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Student List -->
  <section class="mt-8">
    <h3 class="text-xl font-semibold mb-4">Student Assistants – <?= htmlspecialchars($admin_office) ?> Office</h3>
    <?php if (empty($students)): ?>
      <p class="text-gray-600 dark:text-gray-400">No student assistants found for your office.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full bg-white dark:bg-gray-800 border rounded table-auto">
          <thead class="bg-blue-100 dark:bg-gray-700">
            <tr>
              <th class="text-left p-3">Full Name</th>
              <th class="text-left p-3 hidden sm:table-cell">Username</th>
              <th class="text-left p-3 hidden sm:table-cell">Email</th>
              <th class="text-left p-3">Status</th>
              <th class="text-left p-3 hidden md:table-cell">Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student): ?>
              <tr class="border-t hover:bg-blue-50 dark:hover:bg-gray-700">
                <td class="p-3"><?= htmlspecialchars($student['fullname']) ?></td>
                <td class="p-3 hidden sm:table-cell"><?= htmlspecialchars($student['username']) ?></td>
                <td class="p-3 hidden sm:table-cell"><?= htmlspecialchars($student['email']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['status']) ?></td>
                <td class="p-3 text-sm text-gray-500 hidden md:table-cell"><?= htmlspecialchars(date('M d, Y', strtotime($student['created_at']))) ?></td>
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
  // Theme toggle (desktop)
  const toggleBtn = document.getElementById('theme-toggle');
  const toggleBtnMobile = document.getElementById('theme-toggle-mobile');
  const htmlEl = document.documentElement;
  const icon = document.getElementById('theme-icon');

  function toggleTheme() {
    htmlEl.classList.toggle('dark');
    // update desktop icon if exists
    if (icon) icon.setAttribute('data-lucide', htmlEl.classList.contains('dark') ? 'sun' : 'moon');
    lucide.createIcons();
  }

  if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);
  if (toggleBtnMobile) toggleBtnMobile.addEventListener('click', toggleTheme);

  // Mobile menu toggle
  const mobileBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileMenu.classList.toggle('hidden');
    });

    // close when clicking outside
    document.addEventListener('click', (e) => {
      if (!mobileMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
        mobileMenu.classList.add('hidden');
      }
    });
  }

  lucide.createIcons();
</script>

</body>
</html>
