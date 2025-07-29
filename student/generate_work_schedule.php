<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Define working hours range (example: 8:00 AM - 6:00 PM)
$start_day = strtotime("07:30:00");

$end_day = strtotime("18:00:00");

// Fetch class schedules
$stmt = $pdo->prepare("SELECT day, start_time, end_time FROM class_schedules WHERE user_id = ? ORDER BY day, start_time");
$stmt->execute([$user_id]);
$classes = $stmt->fetchAll();

// Organize class schedule per day
$weekly_classes = [];
foreach ($classes as $class) {
    $day = $class['day'];
    if (!isset($weekly_classes[$day])) $weekly_classes[$day] = [];
    $weekly_classes[$day][] = [
        'start' => strtotime($class['start_time']),
        'end' => strtotime($class['end_time'])
    ];
}

// Clear previous work schedule
$pdo->prepare("DELETE FROM work_schedules WHERE user_id = ?")->execute([$user_id]);

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday', 'Saturday'];
foreach ($days as $day) {
    $blocks = $weekly_classes[$day] ?? [];
    usort($blocks, function($a, $b) { return $a['start'] <=> $b['start']; });

    $current = $start_day;
    foreach ($blocks as $block) {
        if ($block['start'] - $current >= 3600) {
            $start = $current;
            $end = min($block['start'], $start + 7200); // max 2-hr work shift
            $pdo->prepare("INSERT INTO work_schedules (user_id, day, start_time, end_time, task) VALUES (?, ?, ?, ?, ?)")
                ->execute([$user_id, $day, date('H:i:s', $start), date('H:i:s', $end), 'Office Task']);
        }
        $current = max($current, $block['end']);
    }

    // Check for remaining block
    if ($end_day - $current >= 3600) {
        $end = min($end_day, $current + 7200);
        $pdo->prepare("INSERT INTO work_schedules (user_id, day, start_time, end_time, task) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user_id, $day, date('H:i:s', $current), date('H:i:s', $end), 'Office Task']);
    }
}

$_SESSION['schedule_generated'] = "Work schedule has been auto-generated successfully.";
header("Location: dashboard.php");
exit();
