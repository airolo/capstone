<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

$admin_id = $_SESSION['user_id'];
$officeStmt = $pdo->prepare("SELECT office FROM users WHERE id = ?");
$officeStmt->execute([$admin_id]);
$office = $officeStmt->fetchColumn();

// Process approve/deny action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
  $request_id = $_POST['request_id'];
  $action = $_POST['action'];
  $comment = $_POST['admin_comment'] ?? '';

  $status = ($action === 'approve') ? 'Approved' : 'Denied';
  $update = $pdo->prepare("UPDATE make_up_requests SET status = ?, admin_comment = ?, decision_date = NOW() WHERE id = ?");
  $update->execute([$status, $comment, $request_id]);

  // Get user_id and date to notify
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
  <title>Manage Make-Up Requests</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navigation -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="user-check" class="w-5 h-5"></i>
    <span class="text-lg font-semibold">Make-Up Request Management</span>
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

<main class="max-w-6xl mx-auto p-6">
  <h2 class="text-2xl font-bold mb-4">📋 Student Make-Up Requests</h2>

  <?php if (isset($_GET['updated'])): ?>
    <div class="bg-green-100 text-green-800 p-3 mb-4 rounded">✅ Request updated successfully.</div>
  <?php endif; ?>

  <!-- Filter Form -->
  <form method="GET" class="flex flex-wrap gap-4 mb-6">
    <input type="text" name="search" placeholder="Search student..." value="<?= htmlspecialchars($search) ?>" class="px-3 py-2 border rounded">
    <input type="date" name="start_date" value="<?= $start ?>" class="px-3 py-2 border rounded">
    <input type="date" name="end_date" value="<?= $end ?>" class="px-3 py-2 border rounded">
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">🔍 Filter</button>
  </form>

  <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-200 dark:bg-gray-700 text-left">
        <tr>
          <th class="p-3">Student</th>
          <th class="p-3">Requested Date</th>
          <th class="p-3">Reason</th>
          <th class="p-3">Status</th>
          <th class="p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($requests): foreach ($requests as $r): ?>
          <tr class="border-t dark:border-gray-700">
            <td class="p-3"><?= htmlspecialchars($r['fullname']) ?></td>
<td class="p-3"><?= htmlspecialchars($r['request_date']) ?></td>
            <td class="p-3"><?= htmlspecialchars($r['reason']) ?></td>
            <td class="p-3"><?= htmlspecialchars($r['status']) ?></td>
            <td class="p-3">
              <?php if ($r['status'] === 'Pending'): ?>
                <form method="POST" class="flex flex-col sm:flex-row gap-2">
                  <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                  <input type="text" name="admin_comment" placeholder="Optional comment" class="px-2 py-1 border rounded text-xs w-full">
                  <button name="action" value="approve" class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700">Approve</button>
                  <button name="action" value="deny" class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700">Deny</button>
                </form>
              <?php else: ?>
                <span class="text-xs text-gray-500">Decision: <?= htmlspecialchars($r['status']) ?><br><?= htmlspecialchars($r['admin_comment']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="5" class="text-center p-4 text-gray-500">No requests found.</td></tr>
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
