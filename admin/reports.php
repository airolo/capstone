<?php
session_start();
require_once '../includes/db.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch Office
$stmt = $pdo->prepare("SELECT office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$office = $stmt->fetchColumn();

$search = $_GET['search'] ?? '';
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

// Build Query
$query = "
  SELECT u.fullname, DATE(a.time_in) AS log_date, a.time_in, a.time_out
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
  $query .= " AND DATE(a.time_in) BETWEEN :start AND :end";
  $params[':start'] = $start;
  $params[':end'] = $end;
}

$query .= " ORDER BY a.time_in DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reports - MySchedMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>

<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-4 sm:px-6 py-3 flex items-center justify-between shadow relative">
  <div class="flex items-center space-x-2">
    <i data-lucide="file-bar-chart" class="w-6 h-6"></i>
    <span class="text-lg font-bold">Admin Panel</span>
  </div>

  <!-- Desktop Navigation -->
  <div class="hidden md:flex items-center space-x-4">
    <span class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="dashboard.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
    </a>
    <a href="../logout.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="log-out" class="w-4 h-4"></i> Logout
    </a>
    <button id="theme-toggle" class="ml-2" aria-label="Toggle theme">
      <i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i>
    </button>
  </div>

  <!-- Mobile Buttons -->
  <div class="md:hidden flex items-center gap-2">
    <button id="theme-toggle-mobile" class="mr-2" aria-label="Toggle theme mobile">
      <i data-lucide="moon" class="w-5 h-5"></i>
    </button>
    <button id="mobile-menu-btn" class="focus:outline-none" aria-label="Open menu">
      <i data-lucide="menu" class="w-6 h-6"></i>
    </button>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden absolute right-4 top-full mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-20">
    <ul class="flex flex-col text-sm text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">
      <li><a href="dashboard.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Dashboard</a></li>
      <li><a href="account_approvals.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Account Approvals</a></li>
      <li><a href="manage_requests.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Make-Up Requests</a></li>
      <li><a href="reports.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Reports</a></li>
      <li><a href="../logout.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<main class="p-4 sm:p-6 max-w-6xl mx-auto space-y-8">

  <h1 class="text-2xl font-semibold flex items-center gap-2">
    <i data-lucide="calendar-days" class="w-6 h-6 text-blue-600 dark:text-yellow-400"></i>
    Attendance Reports
  </h1>

  <!-- Filter Form -->
  <form method="GET" class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-md flex flex-wrap items-end gap-4">
    <div class="flex flex-col">
      <label class="text-sm font-medium mb-1">Search Name</label>
      <input type="text" name="search" placeholder="Enter name..." class="px-3 py-2 border rounded-md" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="flex flex-col">
      <label class="text-sm font-medium mb-1">Start Date</label>
      <input type="date" name="start_date" value="<?= $start ?>" class="px-3 py-2 border rounded-md">
    </div>
    <div class="flex flex-col">
      <label class="text-sm font-medium mb-1">End Date</label>
      <input type="date" name="end_date" value="<?= $end ?>" class="px-3 py-2 border rounded-md">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mt-2 sm:mt-5">🔍 Filter</button>
  </form>

  <!-- Attendance Logs Table -->
  <div class="overflow-x-auto">
    <table class="w-full bg-white dark:bg-gray-800 text-sm rounded-xl shadow-md">
      <thead class="bg-blue-100 dark:bg-gray-700">
        <tr class="text-left">
          <th class="p-3">Student</th>
          <th class="p-3">Date</th>
          <th class="p-3">Time In</th>
          <th class="p-3">Time Out</th>
          <th class="p-3">Rendered</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($logs): foreach ($logs as $log): ?>
          <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-700">
            <td class="p-3"><?= htmlspecialchars($log['fullname']) ?></td>
            <td class="p-3"><?= htmlspecialchars($log['log_date']) ?></td>
            <td class="p-3"><?= htmlspecialchars($log['time_in'] ?? '—') ?></td>
            <td class="p-3"><?= htmlspecialchars($log['time_out'] ?? '—') ?></td>
            <td class="p-3">
              <?php
              if ($log['time_in'] && $log['time_out']) {
                $start = new DateTime($log['time_in']);
                $end = new DateTime($log['time_out']);
                $diff = $start->diff($end);
                $hours = $diff->h + ($diff->days * 24);
                $minutes = $diff->i;
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

<footer class="text-center p-4 text-sm text-gray-600 dark:text-gray-400">
  MySchedMate &copy; <?= date('Y') ?>
</footer>

<script>
  const toggleBtn = document.getElementById('theme-toggle');
  const toggleBtnMobile = document.getElementById('theme-toggle-mobile');
  const htmlEl = document.documentElement;
  const icon = document.getElementById('theme-icon');

  function toggleTheme() {
    htmlEl.classList.toggle('dark');
    if (icon) icon.setAttribute('data-lucide', htmlEl.classList.contains('dark') ? 'sun' : 'moon');
    lucide.createIcons();
  }

  if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);
  if (toggleBtnMobile) toggleBtnMobile.addEventListener('click', toggleTheme);

  const mobileBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileMenu.classList.toggle('hidden');
    });
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
