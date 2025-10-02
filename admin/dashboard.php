<?php
session_start();
require_once '../includes/db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT fullname, office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch student assistants only in admin's office
$stmt = $pdo->prepare("SELECT fullname, username, email, status, created_at FROM users WHERE role = 'student' AND office = ?");
$stmt->execute([$user['office']]);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - MySchedMate</title>
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
    <i data-lucide="user-cog" class="w-6 h-6"></i>
    <span class="text-lg font-bold">Admin Panel</span>
  </div>
  <div class="space-x-4 flex items-center">
    <span class="text-sm hidden sm:inline">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle">
      <i id="theme-icon" data-lucide="moon" class="w-5 h-5 hover:scale-110 transition duration-300"></i>
    </button>
  </div>
</nav>

<!-- Main Content -->
<main class="p-6 max-w-6xl mx-auto space-y-6">
  <h2 class="text-2xl font-semibold"> 👥 Admin Dashboard</h2>
  <p class="text-sm">Name: <strong><?= htmlspecialchars($user['fullname']) ?></strong></p>
  <p class="text-sm mb-4">Office: <strong><?= htmlspecialchars($user['office']) ?></strong></p>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
   <a href="generate_qr.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition duration-300 flex items-center space-x-3">
  <i data-lucide="qr-code" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
  <span>QR Code</span>
</a>


    <a href="manage_requests.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition duration-300 flex items-center space-x-3">
      <i data-lucide="check-circle" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
      <span>Make-Up Requests</span>
    </a>

    <a href="reports.php" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-md hover:scale-105 transition duration-300 flex items-center space-x-3">
      <i data-lucide="bar-chart-3" class="w-5 h-5 text-blue-600 dark:text-yellow-400"></i>
      <span>Reports</span>
    </a>
  </div>

  <div class="mt-8">
    <h3 class="text-xl font-semibold mb-4">Student Assistants - <?= htmlspecialchars($user['office']) ?> Office</h3>
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
                  <!-- Delete -->
                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                  <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                  <button type="submit" name="delete_student" class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">Delete</button>
                </form>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php endif ?>
  </div>
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
