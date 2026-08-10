<?php
require_once '../includes/auth.php';
require_once '../includes/csrf.php';

requireRole('student', '../login.php');
touchActivity(600, '../login.php');

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrfCheck('../login.php');

  $subject = trim($_POST['subject']);
  $message = trim($_POST['message']);

  if ($subject !== '' && $message !== '') {
    $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $subject, $message]);

    // Notify every superadmin so the ticket is actually seen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'superadmin'");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    $notify = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    foreach ($admins as $admin) {
      $notify->execute([$admin['id'], "New support ticket from a student: $subject"]);
    }

    $success = true;
  } else {
    $error = "Please fill in both subject and message.";
  }
}

// Fetch the student's own tickets
$stmt = $pdo->prepare("SELECT id, subject, message, status, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Support - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

  <!-- Navbar -->
  <nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex items-center justify-between shadow">
    <div class="flex items-center space-x-2">
      <i data-lucide="headphones" class="w-6 h-6"></i>
      <span class="text-lg font-bold">MySchedMate</span>
    </div>
    <div class="flex items-center space-x-4">
      <a href="dashboard.php" class="hover:underline">Dashboard</a>
      <a href="../logout.php" class="hover:underline">Logout</a>
      <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
    </div>
  </nav>

  <!-- Main -->
  <main class="max-w-3xl mx-auto p-6 space-y-6">
    <h2 class="text-2xl font-semibold">🎧 Contact Support</h2>
    <p class="text-sm text-gray-600 dark:text-gray-300">
      Experiencing an issue with the system? Send a message and the office administrator (super admin) will look into it.
    </p>

    <?php if (isset($success)): ?>
      <div class="p-3 bg-green-100 text-green-800 rounded">✅ Ticket submitted successfully! The super admin has been notified.</div>
    <?php elseif (isset($error)): ?>
      <div class="p-3 bg-red-100 text-red-800 rounded">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white dark:bg-gray-800 p-4 rounded shadow space-y-4">
      <?= csrfField() ?>
      <div>
        <label class="block text-sm mb-1">Subject</label>
        <input type="text" name="subject" placeholder="e.g. QR code not scanning" required class="w-full p-2 border rounded dark:bg-gray-900 dark:border-gray-700">
      </div>
      <div>
        <label class="block text-sm mb-1">Message</label>
        <textarea name="message" rows="5" placeholder="Describe the issue..." required class="w-full p-2 border rounded dark:bg-gray-900 dark:border-gray-700"></textarea>
      </div>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Send Message</button>
    </form>

    <h3 class="text-lg font-bold mt-10">📩 Your Tickets</h3>
    <div class="space-y-3">
      <?php if ($tickets): foreach ($tickets as $t): ?>
        <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
          <div class="flex items-center justify-between mb-1">
            <p class="font-semibold"><?= htmlspecialchars($t['subject']) ?></p>
            <span class="text-xs px-2 py-1 rounded <?= $t['status'] === 'Resolved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
              <?= htmlspecialchars($t['status']) ?>
            </span>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-300"><?= nl2br(htmlspecialchars($t['message'])) ?></p>
          <p class="text-xs text-gray-500 mt-2"><?= date('M j, Y g:i A', strtotime($t['created_at'])) ?></p>
        </div>
      <?php endforeach; else: ?>
        <p class="text-sm text-gray-500">No support tickets yet.</p>
      <?php endif; ?>
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