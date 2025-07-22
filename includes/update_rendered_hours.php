<?php
require_once 'db.php';

function updateRenderedHours($user_id) {
  global $pdo;

  // Calculate total rendered minutes
  $stmt = $pdo->prepare("
    SELECT 
      SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out)) AS total_minutes
    FROM attendance_logs
    WHERE user_id = ? AND time_in IS NOT NULL AND time_out IS NOT NULL
  ");
  $stmt->execute([$user_id]);
  $total_minutes = $stmt->fetchColumn();

  // Convert to hours and update users table
  $rendered_hours = round($total_minutes / 60, 2);

  $update = $pdo->prepare("UPDATE users SET rendered_hours = ? WHERE id = ?");
  $update->execute([$rendered_hours, $user_id]);

  return $rendered_hours;
}
