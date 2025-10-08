<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // ✅ Frontend Email Validation
    function validateEmail(event) {
      const emailInput = document.querySelector('input[name="email"]');
      const emailError = document.getElementById('emailError');
      const emailValue = emailInput.value.trim().toLowerCase();

      if (!emailValue.endsWith('@dwc-legazpi.edu')) {
        event.preventDefault();
        emailError.textContent = "Please use your school email ending with @dwc-legazpi.edu.";
        emailError.classList.remove('hidden');
        emailInput.classList.add('border-red-500');
        return false;
      }
      emailError.classList.add('hidden');
      emailInput.classList.remove('border-red-500');
      return true;
    }
  </script>
</head>

<body class="bg-blue-50 flex items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8">

  <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-lg w-full max-w-md transition-all duration-300">
    <h2 class="text-3xl font-bold text-center text-blue-700 mb-6">Create an Account</h2>

    <!-- Error Message -->
    <?php if (isset($_SESSION['signup_error'])): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-center text-sm">
        <?= htmlspecialchars($_SESSION['signup_error']); unset($_SESSION['signup_error']); ?>
      </div>
    <?php endif; ?>

    <form action="process_signup.php" method="POST" class="space-y-5" onsubmit="return validateEmail(event)">
      <!-- Full Name -->
      <div>
        <label class="block font-semibold text-sm mb-1 text-gray-700">Full Name</label>
        <input type="text" name="fullname" required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
      </div>

      <!-- Username -->
      <div>
        <label class="block font-semibold text-sm mb-1 text-gray-700">Username</label>
        <input type="text" name="username" required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
      </div>

      <!-- Email -->
      <div>
        <label class="block font-semibold text-sm mb-1 text-gray-700">Email</label>
        <input type="email" name="email" required
          placeholder="06543210@dwc-legazpi.edu"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
        <p id="emailError" class="text-red-600 text-sm mt-1 hidden"></p>
        <small class="text-sm text-gray-500">Use your school email (e.g. 06543210@dwc-legazpi.edu)</small>
      </div>

      <!-- Password -->
      <div>
        <label class="block font-semibold text-sm mb-1 text-gray-700">Password</label>
        <input type="password" name="password"
          pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$"
          title="Password must be at least 8 characters long and contain both letters and numbers"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
        <small class="text-sm text-gray-500">
          Must be at least 8 characters, include letters and numbers.
        </small>
      </div>

      <!-- Office -->
      <div>
        <label class="block font-semibold text-sm mb-1 text-gray-700">Office</label>
        <select name="office" required
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
          <option value="" disabled selected>Select your assigned office</option>
          <option value="Library">Library</option>
          <option value="Registrar">Registrar</option>
          <option value="Guidance">Guidance</option>
          <option value="ICTC Office">ICTC Office</option>
          <option value="VPAA Office">VPAA Office</option>
          <option value="AGTC">AGTC</option>
          <option value="SAO">SAO</option>
          <option value="SOECS Department">SOECS Department</option>
          <option value="SEAS Department">SEAS Department</option>
          <option value="SHOM Department">SHOM Department</option>
          <option value="SON Department">SON Department</option>
        </select>
      </div>

      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="role" value="student">

      <button type="submit"
        class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-300 transition">
        Sign Up
      </button>
    </form>

    <p class="text-center text-sm mt-6 text-gray-600">
      Already have an account?
      <a href="login.php" class="text-blue-600 font-medium hover:underline">Log in here</a>
    </p>
  </div>

</body>
</html>
