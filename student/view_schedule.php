<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch class and work schedules
$stmt = $pdo->prepare("SELECT * FROM class_schedules WHERE user_id = ?");
$stmt->execute([$user_id]);
$class_schedules = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM work_schedules WHERE user_id = ?");
$stmt->execute([$user_id]);
$work_schedules = $stmt->fetchAll();

// Group all entries by day
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
function scheduleFor($schedules, $day) {
  return array_filter($schedules, fn($s) => $s['day'] === $day);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Weekly Calendar View - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script> tailwind.config = { darkMode: 'class' } </script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">
  <nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
    <div class="flex items-center space-x-2">
      <i data-lucide="calendar" class="w-6 h-6"></i>
      <span class="font-bold text-lg">MySchedMate</span>
    </div>
    <div class="flex items-center space-x-4">
      <a href="dashboard.php" class="hover:underline">Dashboard</a>
      <a href="../logout.php" class="hover:underline">Logout</a>
      <button id="theme-toggle"><i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i></button>
    </div>
  </nav>

  <main class="max-w-6xl mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">📅 Weekly Schedule (Calendar View)</h2>
    <button onclick="window.print()" class="mb-4 bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
     🖨️ Print Schedule
    </button>

    <div class="overflow-x-auto">
      <table class="w-full border border-gray-300 dark:border-gray-700 text-sm">
        <thead>
          <tr class="bg-gray-100 dark:bg-gray-800">
            <th class="p-3 w-32">Time</th>
            <?php foreach ($days as $day): ?>
              <th class="p-3 text-center"><?= $day ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          for ($hour = 7; $hour <= 18; $hour++):
            $time_label = sprintf("%02d:00", $hour);
          ?>
            <tr class="border-t border-gray-200 dark:border-gray-700">
              <td class="p-2 font-medium bg-gray-50 dark:bg-gray-900"><?= $time_label ?></td>
              <?php foreach ($days as $day): ?>
                <td class="p-2 align-top">
                  <?php
                  $entries = array_merge(scheduleFor($class_schedules, $day), scheduleFor($work_schedules, $day));
                  foreach ($entries as $e):
                    $start_hour = (int)substr($e['start_time'], 0, 2);
                    if ($start_hour == $hour):
                      $label = isset($e['subject']) ? $e['subject'] : ($e['task'] ?? 'Office Task');
                      $type = isset($e['subject']) ? 'bg-yellow-200 text-yellow-800' : 'bg-blue-200 text-blue-800';
                      echo "<div class='p-1 rounded-md mb-1 $type text-xs font-semibold'>" . htmlspecialchars($label) . "<br><span class='text-[10px] font-normal'>" . date('h:i A', strtotime($e['start_time'])) . " - " . date('h:i A', strtotime($e['end_time'])) . "</span></div>";
                    endif;
                  endforeach;
                  ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <!-- Manual regenerate link -->
    <div class="mt-6">
      <a href="generate_work_schedule.php" class="text-blue-600 hover:underline text-sm">↻ Recalculate Work Schedule</a>
    </div>

    <!-- Success alert if redirected from upload -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
      <div class="mt-4 p-3 bg-green-100 text-green-800 rounded">✅ Work schedule generated successfully!</div>
    <?php endif; ?>

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