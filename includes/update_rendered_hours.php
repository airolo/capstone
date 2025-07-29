<?php
require_once 'db.php';

// update_rendered_hours.php
function updateRenderedHours($user_id) {
  global $pdo;

  // Fetch all attendance logs with time_in and time_out
  $stmt = $pdo->prepare("SELECT time_in, time_out FROM attendance_logs WHERE user_id = ? AND time_out IS NOT NULL");
  $stmt->execute([$user_id]);
  $logs = $stmt->fetchAll();

  $totalMinutes = 0;
  foreach ($logs as $log) {
    $timeIn = strtotime($log['time_in']);
    $timeOut = strtotime($log['time_out']);
    $diff = ($timeOut - $timeIn) / 60; // minutes
    $totalMinutes += max(0, $diff);
  }

  $rendered_hours = round($totalMinutes / 60, 2); // Convert to hours

  // Count number of unique class dates
 $daysStmt = $pdo->prepare("SELECT COUNT(DISTINCT day) AS class_days FROM class_schedules WHERE user_id = ?");

  $daysStmt->execute([$user_id]);
  $class_days = $daysStmt->fetchColumn();

  $required_hours = $class_days * 4; // 4 hours per class day

  // Update users table
  $update = $pdo->prepare("UPDATE users SET rendered_hours = ?, required_hours = ? WHERE id = ?");
  $update->execute([$rendered_hours, $required_hours, $user_id]);
}

