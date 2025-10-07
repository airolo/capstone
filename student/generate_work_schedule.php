<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

date_default_timezone_set('Asia/Manila');

// Define working time boundaries
$start_day = strtotime("07:30:00");
$lunch_start = strtotime("12:00:00");
$lunch_end = strtotime("13:00:00");
$end_day = strtotime("18:00:00");
$required_work_hours = 4 * 3600; // 4 hours in seconds

// Fetch class schedules
$stmt = $pdo->prepare("SELECT day, start_time, end_time FROM class_schedules WHERE user_id = ? ORDER BY day, start_time");
$stmt->execute([$user_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize by day
$weekly_classes = [];
foreach ($classes as $class) {
    $day = $class['day'];
    if (!isset($weekly_classes[$day])) $weekly_classes[$day] = [];
    $weekly_classes[$day][] = [
        'start' => strtotime($class['start_time']),
        'end' => strtotime($class['end_time'])
    ];
}

// Clear old work schedules
$pdo->prepare("DELETE FROM work_schedules WHERE user_id = ?")->execute([$user_id]);

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

foreach ($days as $day) {
    $blocks = $weekly_classes[$day] ?? [];
    usort($blocks, fn($a, $b) => $a['start'] <=> $b['start']);

    $available_slots = [];

    // Add morning slot before first class
    $current = $start_day;
    foreach ($blocks as $block) {
        if ($block['start'] > $current) {
            $available_slots[] = ['start' => $current, 'end' => $block['start']];
        }
        $current = $block['end'];
    }

    // Add slot after last class
    if ($current < $end_day) {
        $available_slots[] = ['start' => $current, 'end' => $end_day];
    }

    // Remove lunch break overlap
    $adjusted_slots = [];
    foreach ($available_slots as $slot) {
        if ($slot['end'] <= $lunch_start || $slot['start'] >= $lunch_end) {
            $adjusted_slots[] = $slot;
        } else {
            if ($slot['start'] < $lunch_start) {
                $adjusted_slots[] = ['start' => $slot['start'], 'end' => $lunch_start];
            }
            if ($slot['end'] > $lunch_end) {
                $adjusted_slots[] = ['start' => $lunch_end, 'end' => $slot['end']];
            }
        }
    }

    // Allocate up to 4 work hours
    $total_allocated = 0;
    foreach ($adjusted_slots as $slot) {
        if ($total_allocated >= $required_work_hours) break;

        $available = $slot['end'] - $slot['start'];
        $needed = min($available, $required_work_hours - $total_allocated);

        if ($needed >= 1800) { // at least 30 minutes
            $start = $slot['start'];
            $end = $slot['start'] + $needed;
            $pdo->prepare("INSERT INTO work_schedules (user_id, day, start_time, end_time, task) VALUES (?, ?, ?, ?, ?)")
                ->execute([$user_id, $day, date('H:i:s', $start), date('H:i:s', $end), 'Office Task']);
            $total_allocated += $needed;
        }
    }
}

$_SESSION['schedule_generated'] = "✅ Work schedule auto-generated successfully (4 hours/day, excluding lunch).";
header("Location: dashboard.php");
exit();
?>
