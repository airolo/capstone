<?php
session_start();
require_once '../includes/db.php';

// ✅ Access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ Fetch admin info
$stmt = $pdo->prepare("SELECT fullname, office FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$admin_office = $user['office'];

// ✅ Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $status = 'active';
        $message = 'approved';
    } else {
        $status = 'rejected';
        $message = 'rejected';
    }

    // Update user status
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $student_id]);

    // ✅ Fetch user email to send notification
    $stmt = $pdo->prepare("SELECT email, fullname FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if ($student) {
        $to = $student['email'];
        $subject = "MySchedMate Account $message";
        $body = "Hello " . htmlspecialchars($student['fullname']) . ",\n\n".
                "Your MySchedMate account has been $message by the admin.\n\n".
                "You can now log in at: https://yoursite.com/login.php\n\n".
                "Regards,\nMySchedMate Team";
        $headers = "From: myschedmate@dwc-legazpi.edu";

        @mail($to, $subject, $body, $headers);
    }

    $_SESSION['approval_message'] = "Account has been $message successfully.";
    header("Location: account_approvals.php");
    exit();
}

// ✅ Fetch pending student assistants for approval
$stmt_pending = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND status = 'inactive'");
$stmt_pending->execute();
$pending_accounts = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account Approvals - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
</head>

<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- ✅ Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex items-center justify-between shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="user-cog" class="w-6 h-6"></i>
    <span class="text-lg font-bold">Admin Panel</span>
  </div>
  <div class="space-x-4 flex items-center">
    <span class="text-sm hidden sm:inline">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="dashboard.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
    </a>
    <a href="../logout.php" class="flex items-center gap-1 hover:underline">
      <i data-lucide="log-out" class="w-4 h-4"></i> Logout
    </a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- ✅ Main Content -->
<main class="p-6 max-w-6xl mx-auto space-y-8">
  <h2 class="text-2xl font-semibold flex items-center gap-2">
    <i data-lucide="user-check" class="w-6 h-6 text-blue-600"></i> Account Approvals
  </h2>
  <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
    Review and approve pending student assistant accounts under the <strong><?= htmlspecialchars($admin_office) ?></strong> office.
  </p>

  <?php if (isset($_SESSION['approval_message'])): ?>
    <div class="bg-green-100 border-l-4 border-green-600 text-green-800 p-3 rounded mb-4">
      <?= htmlspecialchars($_SESSION['approval_message']); unset($_SESSION['approval_message']); ?>
    </div>
  <?php endif; ?>

  <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow space-y-4">
    <h3 class="text-lg font-semibold mb-2 text-blue-700 dark:text-blue-400">Pending Accounts</h3>

    <?php if (empty($pending_students)): ?>
      <div class="bg-blue-50 dark:bg-gray-700 text-center p-4 rounded text-gray-700 dark:text-gray-300">
        No pending student accounts at the moment.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full border rounded-lg overflow-hidden">
          <thead class="bg-blue-100 dark:bg-gray-700 text-left">
            <tr>
              <th class="p-3">Full Name</th>
              <th class="p-3">Username</th>
              <th class="p-3">Email</th>
              <th class="p-3">Created</th>
              <th class="p-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-gray-600">
            <?php foreach ($pending_students as $student): ?>
              <tr class="hover:bg-blue-50 dark:hover:bg-gray-700 transition">
                <td class="p-3"><?= htmlspecialchars($student['fullname']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['username']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['email']) ?></td>
                <td class="p-3 text-sm text-gray-500"><?= htmlspecialchars(date('M d, Y', strtotime($student['created_at']))) ?></td>
                <td class="p-3 text-center space-x-2">
                  <form method="POST" class="inline">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['id']) ?>">
                    <button type="submit" name="action" value="approve" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 text-sm rounded">Approve</button>
                  </form>
                  <form method="POST" class="inline">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['id']) ?>">
                    <button type="submit" name="action" value="reject" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 text-sm rounded">Reject</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</main>

<footer class="text-center p-4 text-sm text-gray-600 dark:text-gray-400">
  MySchedMate &copy; <?= date('Y') ?>
</footer>

<script>
  const toggleBtn = document.getElementById('theme-toggle');
  const htmlEl = document.documentElement;
  const icon = document.getElementById('theme-icon');
  toggleBtn.addEventListener('click', () => {
    htmlEl.classList.toggle('dark');
    icon.setAttribute('data-lucide', htmlEl.classList.contains('dark') ? 'sun' : 'moon');
    lucide.createIcons();
  });
  lucide.createIcons();
</script>

</body>
</html>
