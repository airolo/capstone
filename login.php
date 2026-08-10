<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
// Optional: Redirect if already logged in
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
    <a href="index.php" class="inline-flex items-center gap-1 text-blue-600 hover:underline text-sm mb-4">← Back to Home</a>
    <h2 class="text-2xl font-bold text-center text-blue-700 mb-6">Login to MySchedMate</h2>
    <?php if (isset($_GET['timeout'])): ?>
  <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center">
    Session expired. Please log in again.
  </div>
<?php endif; ?>
    <?php if (isset($_GET['logged_out'])): ?>
  <div class="bg-blue-100 text-blue-700 p-3 rounded mb-4 text-center">
    You have been logged out.
  </div>
<?php endif; ?>
    <?php if (isset($_SESSION['login_error'])): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        <?= htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
      </div>
    <?php endif; ?>

    <form action="process_login.php" method="POST" class="space-y-4">
      <div>
        <label class="block font-semibold text-sm mb-1">Username</label>
        <input type="text" name="username" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500" />
      </div>
      <div>
        <label class="block font-semibold text-sm mb-1">Password</label>
        <input type="password" name="password" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500" />
      </div>
      <?= csrfField() ?>
      <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700 transition">Login</button>
    </form>

    <p class="text-center text-sm mt-6">
      Don't have an account?
      <a href="signup.php" class="text-blue-600 hover:underline">Sign up here</a>
    </p>
  </div>

</body>
</html>
