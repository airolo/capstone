<?php
session_start();
require_once '../includes/db.php';

// Super Admin access only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
  header("Location: ../login.php");
  exit();
}

// Get admin ID
$admin_id = $_GET['id'] ?? null;
if (!$admin_id) {
  header("Location: dashboard.php");
  exit();
}

// Fetch admin data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
  echo "Admin not found.";
  exit();
}

// Handle form update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname']);
  $office = trim($_POST['office']);

  $stmt = $pdo->prepare("UPDATE users SET fullname = ?, office = ? WHERE id = ?");
  $stmt->execute([$fullname, $office, $admin_id]);

  header("Location: dashboard.php?updated=1");
  exit();
}

// Define dropdown office list
$offices = ['Registrar', 'Library', 'OSA', 'Clinic', 'Accounting', 'Research Office', 'MIS', 'Guidance'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Admin - MySchedMate</title>
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
    <i data-lucide="shield" class="w-6 h-6"></i>
    <span class="font-bold text-lg">Super Admin Panel</span>
  </div>
  <div class="flex items-center space-x-4">
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- Main -->
<main class="max-w-xl mx-auto p-6 mt-8 bg-white dark:bg-gray-800 rounded-lg shadow">
  <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
    <i data-lucide="user-edit" class="w-5 h-5"></i>
    <span>Edit Admin Account</span>
  </h2>

  <form method="POST" class="space-y-4">
    <div>
      <label class="block text-sm font-medium">Full Name</label>
      <input type="text" name="fullname" required class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600" value="<?= htmlspecialchars($admin['fullname']) ?>">
    </div>

    <div>
      <label class="block text-sm font-medium">Username</label>
      <input type="text" name="username" disabled class="w-full px-4 py-2 border rounded bg-gray-100 dark:bg-gray-700 dark:border-gray-600" value="<?= htmlspecialchars($admin['username']) ?>">
    </div>

    <div>
      <label class="block text-sm font-medium">Email</label>
      <input type="email" name="email" disabled class="w-full px-4 py-2 border rounded bg-gray-100 dark:bg-gray-700 dark:border-gray-600" value="<?= htmlspecialchars($admin['email']) ?>">
    </div>

    <div>
      <label class="block text-sm font-medium">Office</label>
      <select name="office" required class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600">
        <option value="" disabled>Select office</option>
        <?php foreach ($offices as $opt): ?>
          <option value="<?= $opt ?>" <?= ($admin['office'] === $opt) ? 'selected' : '' ?>>
            <?= $opt ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="flex justify-end space-x-4 mt-6">
      <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</a>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Changes</button>
    </div>
  </form>
</main>

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
