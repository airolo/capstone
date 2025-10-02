<?php
// === FILE: superadmin_register_admin.php ===

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $office = trim($_POST['office']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = "Username or email already exists.";
    } elseif (strlen($password) < 8 || !preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/', $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters with letters and numbers.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password_hash, role, office) VALUES (?, ?, ?, ?, 'admin', ?)");
        $stmt->execute([$fullname, $username, $email, $hashedPassword, $office]);
        $_SESSION['success'] = "Admin registered successfully.";
        header("Location: superadmin_register_admin.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Admin - Super Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 min-h-screen">
  <div class="max-w-xl mx-auto mt-10 p-6 bg-white shadow rounded">
    <h2 class="text-2xl font-bold mb-4">Register New Admin</h2>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php elseif (isset($_SESSION['success'])): ?>
      <div class="bg-green-100 text-green-700 p-2 rounded mb-4"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <input name="fullname" required placeholder="Full Name" class="w-full border p-2 rounded">
      <input name="username" required placeholder="Username" class="w-full border p-2 rounded">
      <input type="email" name="email" required placeholder="Email" class="w-full border p-2 rounded">
      <input type="password" name="password" required placeholder="Password (letters and numbers)" class="w-full border p-2 rounded">
      <select name="office" required class="w-full border p-2 rounded">
        <option value="">-- Select Office --</option>
        <option value="Registrar">Registrar</option>
        <option value="Clinic">Clinic</option>
        <option value="Library">Library</option>
        <option value="Guidance">Guidance</option>
        <option value="Accounting">Accounting</option>
        <option value="ICTC Office">ICTC Office</option>
          <option value="VPAA Office">VPAA Office</option>
          <option value="AGTC">AGTC</option>
          <option value="SAO">SAO</option>
          <option value="SAO">SOECS Department</option>
          <option value="SEAS Department">SEAS Department</option>
          <option value="SHOM Department">SHOM Department</option>
          <option value="SON Department">SON Department</option>
      </select>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Register Admin</button>
    </form>
  </div>
</body>
</html>
