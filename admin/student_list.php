<?php
session_start();
require_once '../includes/db.php';

// Security: only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

$admin_office = $_SESSION['admin_office'] ?? '';

// Fetch students only from the same office
$stmt = $pdo->prepare("SELECT fullname, username, email, required_hours, rendered_hours, missed_hours FROM users WHERE role = 'student' AND office = ?");
$stmt->execute([$admin_office]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Assistants - <?= htmlspecialchars($admin_office) ?> Office</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900 text-black dark:text-white min-h-screen">
  <div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Student Assistants – <?= htmlspecialchars($admin_office) ?> Office</h1>
    
    <?php if (empty($students)): ?>
      <p class="text-sm text-gray-500">No student assistants found for your office.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 shadow rounded">
          <thead>
            <tr class="bg-blue-100 dark:bg-gray-700 text-left">
              <th class="p-3">Full Name</th>
              <th class="p-3">Username</th>
              <th class="p-3">Email</th>
              <th class="p-3">Required Hours</th>
              <th class="p-3">Rendered Hours</th>
              <th class="p-3">Missed Hours</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student): ?>
              <tr class="border-t hover:bg-blue-50 dark:hover:bg-gray-700">
                <td class="p-3"><?= htmlspecialchars($student['fullname']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['username']) ?></td>
                <td class="p-3"><?= htmlspecialchars($student['email']) ?></td>
                <td class="p-3"><?= (int)$student['required_hours'] ?></td>
                <td class="p-3"><?= (int)$student['rendered_hours'] ?></td>
                <td class="p-3"><?= (int)$student['missed_hours'] ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php endif ?>
  </div>
</body>
</html>
