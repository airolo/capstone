<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch schedules
$stmt = $pdo->prepare("SELECT * FROM class_schedules WHERE user_id = ?");
$stmt->execute([$user_id]);
$class_schedules = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM work_schedules WHERE user_id = ?");
$stmt->execute([$user_id]);
$work_schedules = $stmt->fetchAll();

$events = [];

// Class schedules (skip Saturday = day 6)
foreach ($class_schedules as $c) {
  $dayNum = date('w', strtotime($c['day'])); // 0=Sun, 6=Sat
  if ($dayNum == 6) continue; 
  $events[] = [
    'title' => $c['subject'],
    'startTime' => $c['start_time'],
    'endTime' => $c['end_time'],
    'daysOfWeek' => [$dayNum],
    'color' => '#FDE68A', // Yellow
    'textColor' => '#000000'
  ];
}

// Work schedules (always allowed)
foreach ($work_schedules as $w) {
  $dayNum = date('w', strtotime($w['day']));
  $events[] = [
    'title' => $w['task'] ?? 'Office Task',
    'startTime' => $w['start_time'],
    'endTime' => $w['end_time'],
    'daysOfWeek' => [$dayNum],
    'color' => '#93C5FD', // Blue
    'textColor' => '#000000'
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Weekly Calendar View - MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>tailwind.config = { darkMode: 'class' }</script>
  <style>
    #calendar { max-height: 800px; }

    /* Saturday column shading */
    .fc-day-sat {
      background-color: #f9fafb !important; /* Tailwind gray-50 */
    }
    .dark .fc-day-sat {
      background-color: #1f2937 !important; /* Tailwind gray-800 */
    }

    /* ✅ Day header (Monday, Tuesday...) */
    .fc-col-header-cell-cushion {
      text-align: center !important;
      font-weight: bold;
      font-size: 15px;
      color: black; 
    }

    /* ✅ Time labels (07:00, 07:30...) */
    .fc-timegrid-slot-label-cushion {
      text-align: center !important;
      font-weight: bold;
      font-size: 13px;
      color: black; 
    }

    /* ✅ Center event text */
    .fc-event-title,
    .fc-event-time {
      text-align: center !important;
      display: block;
      width: 100%;
    }

    .fc-event {
      font-size: 13px;
      font-weight: 500;
      justify-content: center;
      align-items: center;
      text-align: center;
    }
  </style>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">

<nav class="bg-blue-700 dark:bg-gray-800 text-white px-6 py-3 flex justify-between items-center shadow">
  <div class="flex items-center space-x-2">
    <i data-lucide="calendar" class="w-6 h-6"></i>
    <span class="font-bold text-lg">MySchedMate</span>
  </div>
  <div class="flex items-center space-x-4">
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

<main class="max-w-6xl mx-auto p-6">
  <h2 class="text-2xl font-bold mb-4">📅 Weekly Schedule (Calendar View)</h2>
  <button onclick="window.print()" class="mb-4 bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
    🖨️ Print Schedule
  </button>

  <div id="calendar" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow"></div>

  <div class="mt-6">
    <a href="upload_schedule.php" class="text-blue-600 hover:underline text-sm">↻ Recalculate Work Schedule</a>
  </div>

  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="mt-4 p-3 bg-green-100 text-green-800 rounded">✅ Work schedule generated successfully!</div>
  <?php endif; ?>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
      initialView: 'timeGridWeek',
      slotMinTime: "07:00:00",
      slotMaxTime: "17:30:00",
      allDaySlot: false,
      hiddenDays: [0], // hide Sunday
      slotDuration: "00:30:00",
      slotLabelInterval: "00:30:00",
      slotLabelFormat: { hour: 'numeric', minute: '2-digit', hour12: false },
      expandRows: true,
      contentHeight: "auto",
      headerToolbar: false,

      // Only show day names
      dayHeaderFormat: { weekday: 'long' },

      events: <?php echo json_encode($events); ?>
    });

    calendar.render();
  });
</script>
</body>
</html>
