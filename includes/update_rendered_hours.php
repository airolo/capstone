<?php
require_once 'db.php';

function updateRenderedHours($user_id) {
  global $pdo;

  // Calculate total rendered minutes (AM + PM sessions)
  $stmt = $pdo->prepare("
    SELECT COALESCE(SUM(
      COALESCE(TIMESTAMPDIFF(MINUTE, morning_time_in, morning_time_out), 0) +
      COALESCE(TIMESTAMPDIFF(MINUTE, afternoon_time_in, afternoon_time_out), 0)
    ), 0) AS total_minutes
    FROM attendance_logs
    WHERE user_id = ?
  ");
  $stmt->execute([$user_id]);
  $total_minutes = $stmt->fetchColumn();

  // Convert to hours and update users table
  $rendered_hours = round($total_minutes / 60, 2);

  $update = $pdo->prepare("UPDATE users SET rendered_hours = ? WHERE id = ?");
  $update->execute([$rendered_hours, $user_id]);

  return $rendered_hours;
}
