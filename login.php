<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_role'])) {
  if ($_SESSION['user_role'] === 'admin') {
    header("Location: admin/dashboard.php");
  } elseif ($_SESSION['user_role'] === 'superadmin') {
    header("Location: superadmin/dashboard.php");
  } else {
    header("Location: student/dashboard.php");
  }
  exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-blue-50 flex items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8">

  <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-lg w-full max-w-md transition-all duration-300">
    <h2 class="text-3xl font-bold text-center text-blue-700 mb-6">Login to MySchedMate</h2>

    <!-- Session timeout -->
    <?php if (isset($_GET['timeout'])): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center text-sm">
        Session expired. Please log in again.
      </div>
    <?php endif; ?>

    <!-- Login error -->
    <?php if (isset($_SESSION['login_error'])): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center text-sm">
        <?= htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="process_login.php" method="POST" class="space-y-5">
      <div>
        <label class="block font-semibold text-sm mb-1 text-gray-700">Username</label>
        <input type="text" name="username" required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
      </div>

      <div>
        <label class="block font-semibold text-sm mb-1 text-gray-700">Password</label>
        <input type="password" name="password" required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
      </div>

      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <button type="submit"
        class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-300 transition">
        Login
      </button>
    </form>

    <p class="text-center text-sm mt-6 text-gray-600">
      Don’t have an account?
      <a href="signup.php" class="text-blue-600 font-medium hover:underline">Sign up here</a>
    </p>
  </div>

</body>
</html>
