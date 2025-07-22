<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
  header("Location: ../login.php");
  exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname']);
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $office = trim($_POST['office']);

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
  $stmt->execute([$username, $email]);
  if ($stmt->fetchColumn() > 0) {
    $error = "Username or email already exists.";
  } else {
    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password, office, role, is_active) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
    if ($stmt->execute([$fullname, $username, $email, $password, $office])) {
      $success = "✅ Admin successfully registered.";
    } else {
      $error = "Something went wrong. Please try again.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Admin - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    tailwind.config = { darkMode: 'class' };
  </script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="shield-plus" class="w-6 h-6"></i>
    <span class="font-bold text-lg">Super Admin Panel</span>
  </div>
  <div class="flex items-center space-x-4">
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle">
      <i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i>
    </button>
  </div>
</nav>

<!-- Main Content -->
<main class="max-w-xl mx-auto mt-10 bg-white dark:bg-gray-800 p-6 rounded-xl shadow space-y-6">
  <h2 class="text-2xl font-bold text-center flex items-center justify-center space-x-2">
    <i data-lucide="user-plus" class="w-6 h-6"></i>
    <span>Register New Admin</span>
  </h2>

  <?php if ($success): ?>
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <input type="text" name="fullname" required placeholder="Full Name" class="w-full px-3 py-2 border rounded dark:bg-gray-900 dark:border-gray-700">
    <input type="text" name="username" required placeholder="Username" class="w-full px-3 py-2 border rounded dark:bg-gray-900 dark:border-gray-700">
    <input type="email" name="email" required placeholder="Email" class="w-full px-3 py-2 border rounded dark:bg-gray-900 dark:border-gray-700">
    <input type="password" name="password" required placeholder="Password" class="w-full px-3 py-2 border rounded dark:bg-gray-900 dark:border-gray-700">
    <input type="text" name="office" required placeholder="Office" class="w-full px-3 py-2 border rounded dark:bg-gray-900 dark:border-gray-700">

    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Register Admin</button>
  </form>
</main>

<!-- Dark Mode Script -->
<script>
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
