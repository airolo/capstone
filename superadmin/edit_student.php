<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
  header("Location: ../login.php");
  exit();
}

// Validate student ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: dashboard.php");
  exit();
}

$student_id = intval($_GET['id']);

// Fetch current student data
$stmt = $pdo->prepare("SELECT id, fullname, username, email, office FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
  header("Location: dashboard.php");
  exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = trim($_POST['fullname']);
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $office = trim($_POST['office']);
  $new_password = trim($_POST['new_password']);

  if (!empty($new_password)) {
    // Hash the new password
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, office = ?, password = ? WHERE id = ?");
    $stmt->execute([$fullname, $username, $email, $office, $hashedPassword, $student_id]);
  } else {
    // Update without changing password
    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ?, office = ? WHERE id = ?");
    $stmt->execute([$fullname, $username, $email, $office, $student_id]);
  }

  header("Location: dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Student Assistant</title>
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

<!-- Edit Form Card -->
<div class="flex items-center justify-center py-12 px-4">
  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 w-full max-w-lg">
    <h2 class="text-xl font-semibold mb-6 text-center">Edit Student Assistant</h2>

    <form method="POST" class="space-y-4">
      <!-- Full Name -->
      <div>
        <label class="block mb-1 font-medium">Full Name</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($student['fullname']) ?>" 
               class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200" required>
      </div>

      <!-- Username -->
      <div>
        <label class="block mb-1 font-medium">Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($student['username']) ?>" 
               disabled class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200" required>
      </div>

      <!-- Email -->
      <div>
        <label class="block mb-1 font-medium">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" 
               disabled class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200" required>
      </div>

      <!-- Office -->
      <div>
        <label class="block mb-1 font-medium">Office</label>
        <select name="office" class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200" required>
          <option value="Registrar" <?= $student['office'] === 'Registrar' ? 'selected' : '' ?>>Registrar</option>
          <option value="Library" <?= $student['office'] === 'Library' ? 'selected' : '' ?>>Library</option>
          <option value="Accounting" <?= $student['office'] === 'Accounting' ? 'selected' : '' ?>>Accounting</option>
          <option value="IT" <?= $student['office'] === 'IT' ? 'selected' : '' ?>>IT</option>
        </select>
      </div>

      <!-- Reset Password -->
      <div>
        <label class="block mb-1 font-medium">New Password (optional)</label>
        <input type="password" name="new_password" placeholder="Leave blank to keep current password"
               class="w-full px-3 py-2 border rounded focus:ring focus:ring-blue-200">
      </div>

      <!-- Buttons -->
      <div class="flex justify-end space-x-3 pt-4">
        <a href="dashboard.php" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded">Cancel</a>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Save Changes</button>
      </div>
    </form>
  </div>
</div>

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
