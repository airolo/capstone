<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = strtolower(trim($_POST['username']));
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $office = trim($_POST['office']); // ✅ Capture office
    $role = 'admin';

    // ✅ Check for existing username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['signup_error'] = "Username already exists. Please choose another.";
        header("Location: superadmin_register_admin.php");
        exit();
    }

    // ✅ Check for existing email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['signup_error'] = "Email already exists. Please use another.";
        header("Location: superadmin_register_admin.php");
        exit();
    }

    // ✅ Validate password
    if (strlen($password) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $password)) {
        $_SESSION['signup_error'] = "Password must be at least 8 characters and include letters and numbers.";
        header("Location: superadmin_register_admin.php");
        exit();
    }

    // ✅ Hash and insert
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password_hash, role, office) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullname, $username, $email, $hashedPassword, $role, $office]);

    $_SESSION['signup_success'] = "✅ Admin registered successfully!";
    header("Location: superadmin_register_admin.php");
    exit();
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
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- Main -->
<main class="max-w-xl mx-auto p-6 mt-8 bg-white dark:bg-gray-800 rounded-lg shadow">
  <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
    <i data-lucide="user-plus" class="w-5 h-5"></i>
    <span>Register New Admin</span>
  </h2>

  <?php if (isset($_SESSION['signup_error'])): ?>
  <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
    <?= htmlspecialchars($_SESSION['signup_error']); unset($_SESSION['signup_error']); ?>
  </div>
<?php elseif (isset($_SESSION['signup_success'])): ?>
  <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
    <?= htmlspecialchars($_SESSION['signup_success']); unset($_SESSION['signup_success']); ?>
  </div>
<?php endif; ?>


  <form method="POST" class="space-y-4">
    <div>
      <label class="block text-sm font-medium">Full Name</label>
      <input type="text" name="fullname" required class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600">
    </div>

    <div>
      <label class="block text-sm font-medium">Username</label>
      <input type="text" name="username" required class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600">
    </div>

    <div>
      <label class="block text-sm font-medium">Email</label>
      <input type="email" name="email" required class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600">
    </div>

    <div>
      <label class="block text-sm font-medium">Password</label>
      <input type="password" name="password" required placeholder="At least 8 characters with letters and numbers" class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:border-gray-600">
    </div>

    <div>
  <label class="block font-semibold text-sm mb-1">Assigned Office</label>
  <select name="office" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
    <option value="" disabled selected>Select office</option>
    <option value="Library">Library</option>
    <option value="Registrar">Registrar</option>
    <option value="Guidance">Guidance</option>
    <option value="IT Office">ICTC Office</option>
    <option value="IT Office">VPAA Office</option>
    <option value="IT Office">AGTC</option>
    <option value="IT Office">SAO</option>
    <option value="IT Office">SOECS Department</option>
    <option value="IT Office">SEAS Department</option>
    <option value="IT Office">SHOM Department</option>
    <option value="IT Office">SON Department</option>
  </select>
</div>


    <div class="flex justify-end space-x-4 mt-6">
      <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</a>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Register</button>
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
