<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
  header("Location: ../login.php");
  exit();
}

// =========================
// HANDLE ADMIN ACTIONS
// =========================
if (isset($_POST['toggle_active']) && isset($_POST['admin_id'])) {
  $admin_id = $_POST['admin_id'];

  $stmt = $pdo->prepare("SELECT email, is_active FROM users WHERE id = ?");
  $stmt->execute([$admin_id]);
  $adminData = $stmt->fetch();

  $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
  $stmt->execute([$admin_id]);

  $subject = "MySchedMate Admin Account " . ($adminData['is_active'] ? "Deactivated" : "Reactivated");
  $message = "Hello,\n\nYour account has been " . ($adminData['is_active'] ? "deactivated" : "reactivated") . ".\nPlease contact Super Admin if this was a mistake.";
  @mail($adminData['email'], $subject, $message, "From: myschedmate@domain.com");
}

if (isset($_POST['delete_admin']) && isset($_POST['admin_id'])) {
  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$_POST['admin_id']]);
}

// =========================
// HANDLE STUDENT ACTIONS
// =========================
if (isset($_POST['toggle_student_active']) && isset($_POST['student_id'])) {
  $student_id = $_POST['student_id'];

  $stmt = $pdo->prepare("SELECT email, is_active FROM users WHERE id = ?");
  $stmt->execute([$student_id]);
  $studentData = $stmt->fetch();

  $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
  $stmt->execute([$student_id]);

  $subject = "MySchedMate Student Assistant Account " . ($studentData['is_active'] ? "Deactivated" : "Reactivated");
  $message = "Hello,\n\nYour account has been " . ($studentData['is_active'] ? "deactivated" : "reactivated") . ".\nPlease contact Super Admin if this was a mistake.";
  @mail($studentData['email'], $subject, $message, "From: myschedmate@domain.com");
}

if (isset($_POST['delete_student']) && isset($_POST['student_id'])) {
  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$_POST['student_id']]);
}

// =========================
// FETCH ADMINS
// =========================
$search = $_GET['search'] ?? '';
$query = "SELECT id, fullname, username, email, office, is_active FROM users WHERE role = 'admin'";
$params = [];

if ($search) {
  $query .= " AND (fullname LIKE ? OR office LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$admins = $stmt->fetchAll();

// =========================
// FETCH STUDENTS
// =========================
$search_student = $_GET['search_student'] ?? '';
$query = "SELECT id, fullname, username, email, office, is_active FROM users WHERE role = 'student'";
$params = [];

if ($search_student) {
  $query .= " AND (fullname LIKE ? OR username LIKE ? OR office LIKE ?)";
  $params = ["%$search_student%", "%$search_student%", "%$search_student%"];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Super Admin Dashboard</title>
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
<main class="p-6 max-w-6xl mx-auto space-y-8">
  <h2 class="text-2xl font-bold mb-4">🛡️ Super Admin Dashboard</h2>

  <!-- Register Button -->
  <div>
    <a href="superadmin_register_admin.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
      ➕ Register New Admin
    </a>
  </div>

  <!-- Registered Admins Table -->
  <div class="bg-white dark:bg-gray-800 shadow rounded p-4">
    <h3 class="text-lg font-semibold mb-4">👥 Registered Admins</h3>

    <!-- Search -->
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-3">
      <input type="text" name="search" placeholder="Search by name or office..." 
             value="<?= htmlspecialchars($search) ?>" 
             class="px-3 py-2 border rounded w-64">
      <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded">🔍 Search</button>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-blue-100 dark:bg-gray-700 text-black dark:text-white">
          <tr>
            <th class="p-2">Full Name</th>
            <th class="p-2">Username</th>
            <th class="p-2">Email</th>
            <th class="p-2">Office</th>
            <th class="p-2">Status</th>
            <th class="p-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($admins): foreach ($admins as $admin): ?>
            <tr class="border-b dark:border-gray-600">
              <td class="p-2"><?= htmlspecialchars($admin['fullname']) ?></td>
              <td class="p-2"><?= htmlspecialchars($admin['username']) ?></td>
              <td class="p-2"><?= htmlspecialchars($admin['email']) ?></td>
              <td class="p-2"><?= htmlspecialchars($admin['office']) ?></td>
              <td class="p-2">
                <?= $admin['is_active'] ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Inactive</span>' ?>
              </td>
              <td class="p-2 space-x-1">
                <!-- Toggle Active -->
                <form method="POST" class="inline">
                  <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                  <button type="submit" name="toggle_active" class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded">
                    <?= $admin['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </button>
                </form>
                <!-- Edit -->
                <a href="edit_admin.php?id=<?= $admin['id'] ?>" class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded">Edit</a>
                <!-- Delete -->
                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                  <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                  <button type="submit" name="delete_admin" class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="p-3 text-center text-gray-500">No admins found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Registered Student Assistants Table -->
  <div class="bg-white dark:bg-gray-800 shadow rounded p-4">
    <h3 class="text-lg font-semibold mb-4">🎓 Registered Student Assistants</h3>

    <!-- Search -->
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-3">
      <input type="text" name="search_student" placeholder="Search by name, username, or office..." 
             value="<?= htmlspecialchars($search_student) ?>" 
             class="px-3 py-2 border rounded w-64">
      <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded">🔍 Search</button>
    </form>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-blue-100 dark:bg-gray-700 text-black dark:text-white">
          <tr>
            <th class="p-2">Full Name</th>
            <th class="p-2">Username</th>
            <th class="p-2">Email</th>
            <th class="p-2">Office</th>
            <th class="p-2">Status</th>
            <th class="p-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($students): foreach ($students as $student): ?>
            <tr class="border-b dark:border-gray-600">
              <td class="p-2"><?= htmlspecialchars($student['fullname']) ?></td>
              <td class="p-2"><?= htmlspecialchars($student['username']) ?></td>
              <td class="p-2"><?= htmlspecialchars($student['email']) ?></td>
              <td class="p-2"><?= htmlspecialchars($student['office']) ?></td>
              <td class="p-2">
                <?= $student['is_active'] ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Inactive</span>' ?>
              </td>
              <td class="p-2 space-x-1">
                <!-- Toggle Active -->
                <form method="POST" class="inline">
                  <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                  <button type="submit" name="toggle_student_active" class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded">
                    <?= $student['is_active'] ? 'Deactivate' : 'Activate' ?>
                  </button>
                </form>
                <!-- Edit -->
                <a href="edit_student.php?id=<?= $student['id'] ?>" 
                   class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded">Edit</a>
                <!-- Delete -->
                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this student assistant?');">
                  <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                  <button type="submit" name="delete_student" class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="p-3 text-center text-gray-500">No student assistants found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
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
