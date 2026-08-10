# MySchedMate

A Capstone Project — a web app that helps student assistants manage their class and work schedules, track rendered hours, submit make-up hour requests, and log attendance via QR code scanning.

## Features

### Student
- Upload class schedule via CSV and get an auto-generated work schedule for free time slots
- View weekly calendar (class + work schedules) with print support
- Log attendance by scanning a personal QR code (camera scanner) for AM/PM time-in and time-out
- View attendance history with rendered hours and running totals
- Track required vs rendered vs missed hours with progress history
- Submit make-up hour requests
- Report system issues to the superadmin

### Admin (Office Manager)
- View students assigned to their office
- View and download daily QR codes for their office's students
- Approve or deny make-up requests (with search and date filters)
- View attendance reports (AM/PM columns + computed minutes rendered)

### Superadmin
- Manage admin accounts (create, edit, activate/deactivate, delete)
- Manage student accounts
- View all students and system-wide data
- View and resolve student support tickets

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Plain procedural PHP 7.4+ (no framework, no composer) |
| Database | MySQL via PDO (`includes/db.php`) |
| QR generation | Vendored phpqrcode 1.1.4 (`includes/phpqrcode/`) |
| Frontend | Tailwind Play CDN + Lucide icons, dark-mode toggle |
| QR scanning | html5-qrcode CDN (camera scanner in `student/time_log.php`) |

## Setup

1. **Prerequisites**: PHP 7.4+ with PDO MySQL, MySQL server. XAMPP recommended.
2. **Database**: Import `schema.sql` into MySQL:
   ```
   mysql -u root myschedmate_db < schema.sql
   ```
   (or paste into phpMyAdmin). This creates the `myschedmate_db` database, all tables, and seeds a default superadmin.
3. **Serve** the repo root with the PHP built-in server:
   ```
   php -S localhost:8000
   ```
   or place it under an Apache/ XAMPP `htdocs` directory.
4. **QR cron** (required for attendance logging): run `cron/generate_daily_qr.php` once per minute so daily QR codes are generated on time (07:30 / 12:00 / 13:00 / 17:00). See the header comment in that file for Windows/Linux scheduling instructions.

## First Login

- Default superadmin: `superadmin` / `admin123` — **change the password after first login**
- Register a student account via `signup.php` (always creates a `student` role)
- Admin accounts are created by the superadmin (`superadmin/superadmin_register_admin.php`)

## Sample Data

- `templates/sample_schedule.csv` — sample class schedule: `Day,Start,End,Subject` (e.g. `Monday,08:00,10:00,Math 101`)

## Project Structure

```
├── index.php                  # Public landing page
├── login.php / signup.php     # Auth pages
├── process_login.php / process_signup.php
├── schema.sql                 # Canonical MySQL schema + seed data
├── cron/generate_daily_qr.php # Daily QR generation cron job
├── includes/
│   ├── auth.php               # Session bootstrap + role guards + idle timeout
│   ├── csrf.php               # CSRF token helpers
│   ├── db.php                 # PDO connection
│   ├── generate_qr_codes.php  # Daily QR engine (4 sessions per student)
│   ├── update_rendered_hours.php
│   ├── save_progress_snapshot.php
│   └── phpqrcode/             # Vendored phpqrcode 1.1.4
├── admin/                     # Office manager panel (dashboard, QR view, requests, reports)
├── student/                   # Student panel (dashboard, schedule, time log, make-up, support)
├── superadmin/                # Superadmin panel (account management, tickets)
├── assets/                    # Landing images; generated QR PNGs (gitignored)
└── templates/sample_schedule.csv
```

## Notes

- QR codes are strictly per-student — scanning another student's code is rejected
- `minutes_rendered` in `attendance_logs` is recomputed on demand by `updateRenderedHours()` (e.g. on the progress page)
- No test suite or build tooling — use `php -l <file>` for syntax checks and manual smoke tests