<?php
// === FILE: edit_profile.php ===
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'assistant') {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Optional: basic validations
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!empty($newPassword) && ($newPassword !== $confirmPassword)) {
        $error = "Passwords do not match.";
    } else {
        $query = "UPDATE users SET fullname = ?, email = ?";
        $params = [$fullname, $email];

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $newPassword)) {
                $error = "Password must be at least 8 characters and include letters and numbers.";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $query .= ", password_hash = ?";
                $params[] = $hashedPassword;
            }
        }

        $query .= " WHERE id = ?";
        $params[] = $userId;

        if (!isset($error)) {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $success = "Profile updated successfully.";
            // Refresh user info
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - MySchedMate</title>
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
    <i data-lucide="user" class="w-6 h-6"></i>
    <span class="font-bold text-lg">MySchedMate - Assistant</span>
  </div>
  <div class="flex items-center space-x-4">
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- Main Content -->
<main class="max-w-xl mx-auto p-6 mt-8 bg-white dark:bg-gray-800 rounded-lg shadow">
  <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
    <i data-lucide="settings" class="w-5 h-5"></i>
    <span>Edit Profile</span>
  </h2>

  <?php if (isset($error)): ?>
    <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
  <?php elseif (isset($success)): ?>
    <div class="bg-green-100 text-green-700 p-2 rounded mb-4"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <div>
      <label class="block text-sm font-medium">Full Name</label>
      <input type="text" name="fullname" required class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600" value="<?= htmlspecialchars($user['fullname']) ?>">
    </div>

    <div>
      <label class="block text-sm font-medium">Email</label>
      <input type="email" name="email" required class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600" value="<?= htmlspecialchars($user['email']) ?>">
    </div>

    <div>
      <label class="block text-sm font-medium">New Password <small class="text-gray-500">(leave blank to keep current)</small></label>
      <input type="password" name="new_password" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600">
    </div>

    <div>
      <label class="block text-sm font-medium">Confirm New Password</label>
      <input type="password" name="confirm_password" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600">
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
