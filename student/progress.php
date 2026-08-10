<?php
require_once '../includes/auth.php';
require_once '../includes/update_rendered_hours.php';
require_once '../includes/save_progress_snapshot.php';

requireRole('student', '../login.php');
touchActivity(600, '../login.php');

$user_id = $_SESSION['user_id'];

// Auto-update rendered hours
updateRenderedHours($user_id);
saveProgressSnapshot($user_id);

// Fetch hour summary
$stmt = $pdo->prepare("SELECT required_hours, rendered_hours, (required_hours - rendered_hours) AS missed_hours FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$data = $stmt->fetch();

$required = $data['required_hours'] ?? 0;
$rendered = $data['rendered_hours'] ?? 0;
$missed = max(0, $data['missed_hours'] ?? 0);
$percent = $required > 0 ? min(100, round(($rendered / $required) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Progress Report - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

  <!-- Navbar -->
  <nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex items-center justify-between shadow">
    <div class="flex items-center space-x-2">
      <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
      <span class="text-lg font-bold">MySchedMate</span>
    </div>
    <div class="flex items-center space-x-4">
      <a href="dashboard.php" class="hover:underline">Dashboard</a>
      <a href="../logout.php" class="hover:underline">Logout</a>
      <button id="theme-toggle">
        <i id="theme-icon" data-lucide="moon" class="w-5 h-5"></i>
      </button>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="max-w-xl mx-auto p-6 space-y-6">
    <h1 class="text-2xl font-bold">📊 Progress Report</h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
      <div class="text-sm text-gray-700 dark:text-gray-300">
        <p>Required Hours: <strong><?= $required ?></strong></p>
        <p>Rendered Hours: <strong><?= $rendered ?></strong></p>
        <p>Missed Hours: <strong><?= $missed ?></strong></p>
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Completion:</label>
        <div class="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-5 mt-2">
          <div class="bg-green-500 text-xs font-semibold text-white text-center p-1 rounded-full h-5" style="width: <?= $percent ?>%;">
            <?= $percent ?>%
          </div>
        </div>
      </div>
      <?php
// Fetch 7 most recent entries
$historyStmt = $pdo->prepare("SELECT date_recorded, rendered_hours FROM progress_history WHERE user_id = ? ORDER BY date_recorded DESC LIMIT 7");
$historyStmt->execute([$user_id]);
$history = $historyStmt->fetchAll();
?>

<?php if ($history): ?>
  <div class="mt-6">
    <h2 class="font-semibold text-lg mb-2">📈 Progress History</h2>
    <table class="w-full text-sm text-left">
      <thead>
        <tr class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
          <th class="p-2">Date</th>
          <th class="p-2">Rendered Hours</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($history as $h): ?>
          <tr class="border-t dark:border-gray-600">
            <td class="p-2"><?= htmlspecialchars($h['date_recorded']) ?></td>
            <td class="p-2"><?= htmlspecialchars($h['rendered_hours']) ?> hrs</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

    </div>
  </main>

  <script>
    const toggleBtn = document.getElementById('theme-toggle');
    const html = document.documentElement;
    const icon = document.getElementById('theme-icon');

    toggleBtn.addEventListener('click', () => {
      html.classList.toggle('dark');
      icon.setAttribute('data-lucide', html.classList.contains('dark') ? 'sun' : 'moon');
      lucide.createIcons();
    });

    lucide.createIcons();
  </script>
</body>
</html>
