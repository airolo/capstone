<?php
require_once 'includes/auth.php';
require_once 'includes/csrf.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
    <a href="index.php" class="inline-flex items-center gap-1 text-blue-600 hover:underline text-sm mb-4">← Back to Home</a>
    <h2 class="text-2xl font-bold text-center text-blue-700 mb-6">Create an Account</h2>

    <?php if (isset($_SESSION['signup_error'])): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        <?= htmlspecialchars($_SESSION['signup_error']); unset($_SESSION['signup_error']); ?>
      </div>
    <?php endif; ?>

    <form action="process_signup.php" method="POST" class="space-y-4">
      <div>
        <label class="block font-semibold text-sm mb-1">Full Name</label>
        <input type="text" name="fullname" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500" />
      </div>
      <div>
        <label class="block font-semibold text-sm mb-1">Username</label>
        <input type="text" name="username" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500" />
      </div>
      <div>
        <label class="block font-semibold text-sm mb-1">Email</label>
        <input type="email" name="email" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500" />
      </div>
     <div>
  <label class="block font-semibold text-sm mb-1">Password</label>
  <input type="password" name="password"
         pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$"
         title="Password must be at least 8 characters long and contain both letters and numbers"
         required
         class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500" />
  <small class="text-sm text-gray-500">
    Must be at least 8 characters, include letters and numbers.
  </small>
</div>


      <div>
        <label class="block font-semibold text-sm mb-1">Office</label>
        <select name="office" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
          <option value="" disabled selected>Select your assigned office</option>
          <option value="Library">Library</option>
          <option value="Registrar">Registrar</option>
          <option value="Guidance">Guidance</option>
          <option value="IT Office">IT Office</option>
        </select>
      </div>
      <?= csrfField() ?>

      <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700 transition">Sign Up</button>
    </form>

    <p class="text-center text-sm mt-6">
      Already have an account?
      <a href="login.php" class="text-blue-600 hover:underline">Log in here</a>
    </p>
  </div>

</body>
</html>
