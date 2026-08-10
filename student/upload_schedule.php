<?php
require_once '../includes/auth.php';

// Check user is a logged-in student
requireRole('student', '../login.php');
touchActivity(600, '../login.php');

$user_id = $_SESSION['user_id'];
$upload_success = false;
$error_message = '';

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
  $file = $_FILES['csv_file']['tmp_name'];

  if (($handle = fopen($file, 'r')) !== false) {
    // Optional: Clear old class schedule
    $pdo->prepare("DELETE FROM class_schedules WHERE user_id = ?")->execute([$user_id]);

    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
      if (count($data) < 4) continue; // Skip invalid rows

      list($day, $start, $end, $subject) = $data;
      $stmt = $pdo->prepare("INSERT INTO class_schedules (user_id, day, start_time, end_time, subject) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$user_id, $day, $start, $end, $subject]);
    }
    fclose($handle);

    // ✅ Auto-generate work schedule
    require_once 'generate_work_schedule.php';

    header("Location: view_schedule.php?success=1");
exit();

  } else {
    $error_message = "Failed to open uploaded file.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Class Schedule</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script> tailwind.config = { darkMode: 'class' } </script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<!-- Navbar -->
<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="calendar-plus" class="w-6 h-6"></i>
    <span class="font-bold text-lg">Upload Schedule</span>
  </div>
  <div class="flex space-x-4 items-center">
    <a href="dashboard.php" class="hover:underline">Dashboard</a>
    <a href="../logout.php" class="hover:underline">Logout</a>
    <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
  </div>
</nav>

<!-- Content -->
<main class="max-w-xl mx-auto p-6 space-y-4">
  <h1 class="text-xl font-semibold">📥 Upload Class Schedule (.csv)</h1>

  <?php if ($upload_success): ?>
    <div class="bg-green-100 text-green-800 p-3 rounded">Class schedule uploaded and work schedule auto-generated!</div>
  <?php elseif ($error_message): ?>
    <div class="bg-red-100 text-red-800 p-3 rounded"><?= htmlspecialchars($error_message) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow space-y-4">
    <input type="file" name="csv_file" accept=".csv" required class="block w-full p-2 border rounded text-sm">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
      Upload & Generate
    </button>
  </form>

  <p class="text-sm text-gray-500 dark:text-gray-400">CSV format: <code>Day,Start Time,End Time,Subject</code> <br>Example: <code>Monday,08:00,10:00,Math 101</code></p>
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
