<?php
session_start();
require_once '../includes/db.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch admin info
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT fullname, office FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$office = $admin['office'];

// Approve/Deny logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
  $request_id = $_POST['request_id'];
  $action = $_POST['action'];
  $comment = $_POST['admin_comment'] ?? '';

  $status = ($action === 'approve') ? 'Approved' : 'Denied';
  $update = $pdo->prepare("UPDATE make_up_requests SET status = ?, admin_comment = ?, decision_date = NOW() WHERE id = ?");
  $update->execute([$status, $comment, $request_id]);

  // Send notification
  $getInfo = $pdo->prepare("SELECT user_id, request_date FROM make_up_requests WHERE id = ?");
  $getInfo->execute([$request_id]);
  $info = $getInfo->fetch();
  if ($info) {
    $message = "Your make-up request for " . $info['request_date'] . " has been $status.";
    $notify = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notify->execute([$info['user_id'], $message]);
  }

  header("Location: manage_requests.php?updated=1");
  exit();
}

// Filters
$search = $_GET['search'] ?? '';
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

$query = "
  SELECT r.*, u.fullname FROM make_up_requests r
  JOIN users u ON r.user_id = u.id
  WHERE u.office = :office
";
$params = [':office' => $office];

if ($search) {
  $query .= " AND u.fullname LIKE :search";
  $params[':search'] = "%$search%";
}
if ($start && $end) {
  $query .= " AND r.request_date BETWEEN :start AND :end";
  $params[':start'] = $start;
  $params[':end'] = $end;
}

$query .= " ORDER BY r.request_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Make-Up Requests - MySchedMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>

<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar (copied from dashboard) -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-4 sm:px-6 py-3 flex items-center justify-between shadow relative">
  <div class="flex items-center space-x-2">
    <i data-lucide="user-cog" class="w-6 h-6"></i>
    <span class="text-lg font-bold">Admin Panel</span>
  </div>

  <div class="hidden md:flex items-center space-x-4">
    <span class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="dashboard.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
    </a>
    <a href="../logout.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="log-out" class="w-4 h-4"></i> Logout
    </a>
    <button id="theme-toggle" aria-label="Toggle theme"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>

  <!-- Mobile -->
  <div class="md:hidden flex items-center gap-2">
    <button id="theme-toggle-mobile" class="mr-2"><i data-lucide="moon" class="w-5 h-5"></i></button>
    <button id="mobile-menu-btn"><i data-lucide="menu" class="w-6 h-6"></i></button>
  </div>

  <div id="mobile-menu" class="hidden absolute right-4 top-full mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-20">
    <ul class="flex flex-col text-sm text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">
      <li><a href="dashboard.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Dashboard</a></li>
      <li><a href="account_approvals.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Account Approvals</a></li>
      <li><a href="manage_requests.php" class="block px-4 py-2 bg-blue-100 dark:bg-gray-700 font-medium">Make-Up Requests</a></li>
      <li><a href="reports.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Reports</a></li>
      <li><a href="../logout.php" class="block px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<main class="p-4 sm:p-6 max-w-6xl mx-auto space-y-8">

  <h2 class="text-2xl font-semibold flex items-center gap-2"><i data-lucide="check-circle" class="w-6 h-6 text-blue-600"></i> Manage Make-Up Requests</h2>

  <?php if (isset($_GET['updated'])): ?>
    <div class="bg-green-100 text-green-800 p-3 mb-4 rounded">✅ Request updated successfully.</div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="GET" class="flex flex-wrap gap-3 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
    <input type="text" name="search" placeholder="Search student..." value="<?= htmlspecialchars($search) ?>" class="flex-1 px-3 py-2 border rounded text-sm">
    <input type="date" name="start_date" value="<?= $start ?>" class="px-3 py-2 border rounded text-sm">
    <input type="date" name="end_date" value="<?= $end ?>" class="px-3 py-2 border rounded text-sm">
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">🔍 Filter</button>
  </form>

  <!-- Table -->
  <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow mt-6">
    <table class="min-w-full text-sm">
      <thead class="bg-blue-100 dark:bg-gray-700">
        <tr>
          <th class="p-3 text-left">Student</th>
          <th class="p-3 text-left">Requested Date</th>
          <th class="p-3 text-left">Reason</th>
          <th class="p-3 text-left">Status</th>
          <th class="p-3 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($requests): foreach ($requests as $r): ?>
          <tr class="border-t hover:bg-blue-50 dark:hover:bg-gray-700">
            <td class="p-3"><?= htmlspecialchars($r['fullname']) ?></td>
            <td class="p-3"><?= htmlspecialchars($r['request_date']) ?></td>
            <td class="p-3"><?= htmlspecialchars($r['reason']) ?></td>
            <td class="p-3"><?= htmlspecialchars($r['status']) ?></td>
            <td class="p-3">
              <?php if ($r['status'] === 'Pending'): ?>
                <form method="POST" class="flex flex-col sm:flex-row gap-2">
                  <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                  <input type="text" name="admin_comment" placeholder="Optional comment" class="px-2 py-1 border rounded text-xs w-full sm:w-auto">
                  <button name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">Approve</button>
                  <button name="action" value="deny" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Deny</button>
                </form>
              <?php else: ?>
                <span class="text-xs text-gray-500">Decision: <?= htmlspecialchars($r['status']) ?><br><?= htmlspecialchars($r['admin_comment']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="5" class="text-center p-4 text-gray-500">No make-up requests found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>

<footer class="text-center p-4 text-sm text-gray-600 dark:text-gray-400">
  MySchedMate &copy; <?= date('Y') ?>
</footer>

<script>
  // Theme toggle
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

  // Mobile menu
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
