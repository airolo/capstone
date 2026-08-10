<?php
// ============================================================
// MySchedMate — Daily QR Code Cron Script
// ------------------------------------------------------------
// Auto-generates each student's QR codes at their exact
// scheduled times:
//   07:30  -> AM Time In
//   12:00  -> AM Time Out
//   13:00  -> PM Time In
//   17:00  -> PM Time Out
//
// Run this script every minute. It only generates the QR codes
// whose scheduled time has been reached and which do not exist
// yet, so it is a cheap no-op for the rest of the day and also
// recovers if the server was offline at the scheduled minute.
//
// Scheduling:
//
//   Linux/macOS crontab:
//     crontab -e
//     |  * * * * * php /full/path/to/capstone-myschedmate/cron/generate_daily_qr.php >> /var/log/myschedmate_qr.log 2>&1
//
//   Windows Task Scheduler (dev machine):
//     schtasks /Create /TN "MySchedMate Daily QR" /TR "php C:\Users\Bradley\capstone-myschedmate\cron\generate_daily_qr.php" /SC MINUTE /MO 1
//
// NOTE: There is no manual regenerate button — QR codes appear
// automatically at their scheduled times.
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/generate_qr_codes.php';

$date = date('Y-m-d');
$generated = generateDueQrCodes($date);

if (empty($generated)) {
  echo "[" . date('Y-m-d H:i:s') . "] No QR codes due for $date." . PHP_EOL;
  exit();
}

echo "[" . date('Y-m-d H:i:s') . "] QR codes generated for $date:" . PHP_EOL;
foreach ($generated as $studentId => $student) {
  echo "  Student #$studentId ({$student['name']}):" . PHP_EOL;
  foreach ($student['qrs'] as $c) {
    echo "    - {$c['label']} (generated {$c['gen_at']}, valid {$c['start']} - {$c['end']}): {$c['code']}" . PHP_EOL;
  }
}