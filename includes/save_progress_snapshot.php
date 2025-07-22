<?php
require_once 'db.php';

function saveProgressSnapshot($user_id) {
  global $pdo;

  $stmt = $pdo->prepare("SELECT rendered_hours FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $rendered = $stmt->fetchColumn();

  // Only one snapshot per day
  $check = $pdo->prepare("SELECT 1 FROM progress_history WHERE user_id = ? AND date_recorded = CURDATE()");
  $check->execute([$user_id]);
  if (!$check->fetch()) {
    $insert = $pdo->prepare("INSERT INTO progress_history (user_id, rendered_hours) VALUES (?, ?)");
    $insert->execute([$user_id, $rendered]);
  }
}
