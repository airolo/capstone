# AGENTS.md

Guidance for AI agents working in this repository.

## Project Overview

**MySchedMate** — a capstone project: a web app for student assistants to manage class/work schedules, track rendered hours, submit make-up requests, and log attendance via QR codes. Three roles:

- **student** — upload class schedule (CSV), view auto-generated work schedule, time log via QR scan, view attendance history, submit make-up hour requests, report system issues to the superadmin
- **admin** — office manager: view students in their office, view the daily QR codes, approve/deny make-up requests, view attendance reports
- **superadmin** — manage admin accounts (create, edit, activate/deactivate, delete), manage student accounts, view all students

## Tech Stack

| Layer | Technology |
|---|---|
| Language | Plain procedural PHP (7.4+; uses arrow functions, `??`, `<=>`, `random_bytes`). No framework, no namespaces, no composer.json |
| Database | MySQL via PDO — DSN in `includes/db.php` (`myschedmate_db`, user `root`, empty password, utf8mb4, `ERRMODE_EXCEPTION`), global `$pdo` |
| QR generation | Vendored **phpqrcode 1.1.4** at `includes/phpqrcode/` (`QRcode::png($code, $file)`), wrapped by `includes/generate_qr_codes.php` |
| Frontend | Tailwind Play CDN + Lucide icons on every page; inline `tailwind.config` with `darkMode: 'class'`; dark-mode toggle JS copy-pasted per page |
| QR scanning | `html5-qrcode` CDN (camera scanner in `student/time_log.php`) |
| Build tooling | **None** — no composer.json, package.json; `.gitignore` ignores generated artifacts |

All styling is inline Tailwind classes (no CSS build step).

## Running the App

1. Serve the repo root with the PHP built-in server (`php -S localhost:8000`) or XAMPP/Apache with PHP 7.4+.
2. Import `schema.sql` into MySQL (`mysql -u root myschedmate_db < schema.sql` or paste into phpMyAdmin). It seeds a default superadmin (`superadmin` / `admin123` — change after first login).
3. Open `index.php` (landing) or `login.php`. Register via `signup.php` (always creates a `student`).
4. QR codes are generated automatically by the cron script `cron/generate_daily_qr.php` (run every minute; see the header comment for scheduling). Without it, no QR codes exist for students to scan.

No lint, test, or build commands exist (use `php -l <file>` to syntax check).

## Directory Map

### Root
| File | Purpose |
|---|---|
| `index.php` | Public landing page (hero, features, SDG 4/8 sections), no auth |
| `login.php` | Login form; pre-redirects if already logged in; CSRF field; `?timeout=1` / `?logged_out=1` messages |
| `process_login.php` | POST handler; CSRF check; rejects `is_active = 0` accounts; `session_regenerate_id`; role-based redirect |
| `signup.php` | Public registration form (fullname, username, email, password, office) |
| `process_signup.php` | POST handler; CSRF check; duplicate checks; always inserts `role = 'student'` |
| `logout.php` | Destroys session → `login.php?logged_out=1` |
| `schema.sql` | Canonical MySQL schema (`myschedmate_db`) + default superadmin seed |

### `includes/`
| File | Purpose |
|---|---|
| `auth.php` | **Unified session bootstrap**: starts session with httponly/Lax cookies; `requireRole($role, $loginPath)` guard; `touchActivity(600, $loginPath)` 10-min idle timeout. Every protected page uses these |
| `csrf.php` | CSRF helpers: `csrfToken()`, `csrfField()`, `csrfValid()`, `csrfCheck($loginPath)` |
| `db.php` | Global `$pdo` connection (loaded via `auth.php`) |
| `generate_qr_codes.php` | Daily QR engine: per-student, per-session QR codes. Constants `QR_SESSIONS` (4 sessions with scan windows) and `QR_COLUMN_MAP`; functions `generateDailyQrCodes()`, `generateDueQrCodes()` (cron, time-aware), `getDailyQrCodes()` |
| `update_rendered_hours.php` | `updateRenderedHours($user_id)` — sums AM + PM `TIMESTAMPDIFF` minutes → `users.rendered_hours` |
| `save_progress_snapshot.php` | `saveProgressSnapshot($user_id)` — archives one `progress_history` row/day |
| `phpqrcode/` | Vendored phpqrcode 1.1.4 library (cache/ + bindings/ are gitignored) |

### `admin/` (office manager panel)
| File | Purpose |
|---|---|
| `dashboard.php` | `requireRole('admin')`; admin info + office's students; nav cards |
| `generate_qr.php` | Displays today's 4 QR codes per student in the admin's office (cron generates them; no manual regenerate) |
| `manage_requests.php` | Approve/deny make-up requests (CSRF-validated POST); search + date filters; inserts `notifications` row on decision |
| `reports.php` | Attendance report JOINing `attendance_logs`/`users`, scoped to office; AM/PM columns + computed `minutes_rendered` |
| `student_list.php` | Lists office students (office taken from the admin's own `users.office`) with required/rendered/missed hours |

### `student/`
| File | Purpose |
|---|---|
| `dashboard.php` | Today's AM/PM time-in/out status, missed-timeout warning, auto-reload 30s, latest 5 `notifications` |
| `contact_support.php` | Submit support tickets (CSRF-validated POST) inserted into `support_tickets` + a `notifications` row for every superadmin; lists the student's tickets |
| `view_schedule.php` | Weekly calendar (07:00–18:00 × Mon–Fri) merging class + work schedules; print |
| `upload_schedule.php` | CSV upload (`fgetcsv`); deletes then inserts `class_schedules`; auto-runs work-schedule generator |
| `generate_work_schedule.php` | Rebuilds `work_schedules`: fills class gaps ≥1h inside 08:00–18:00 with max 2h 'Office Task' shifts |
| `time_log.php` | QR camera scanner UI (html5-qrcode) → POSTs `code` + `csrf_token` to `validate_qr.php`, expects JSON `{success, message}` |
| `validate_qr.php` | **POST JSON API: the attendance logger.** Validates CSRF, looks up the scanned `code` in `qr_codes`, checks it belongs to the session user, is not expired, and is within the session's scan window, then writes the matching AM/PM time column via `ON DUPLICATE KEY UPDATE` and recomputes rendered hours. Also renders a scanner page identical to `time_log.php` on GET |
| `progress.php` | Calls `updateRenderedHours` + `saveProgressSnapshot`; shows required/rendered/missed hours + history (no longer linked from the dashboard since Contact Support replaced the Progress Report card; kept for the hour-tracking helpers) |
| `make_up_hours.php` | Submit make-up requests (CSRF-validated POST); lists user's requests with status |
| `attendance_history.php` | Lists all user's log dates with AM/PM in/out and per-day rendered minutes + running total |

### `superadmin/`
| File | Purpose |
|---|---|
| `dashboard.php` | List/filter admins + students; toggle `is_active`; delete; CSRF-validated POSTs; Edit links to `edit_admin.php` / `edit_student.php`; Support Tickets section (view + mark resolved) |
| `superadmin_register_admin.php` | Register admin (writes `password_hash`; CSRF-validated) |
| `edit_admin.php` | Edit admin fullname/office via `?id=`; CSRF-validated POST; hard-coded office dropdown |
| `edit_student.php` | Edit student fullname/office via `?id=`; CSRF-validated POST; student office dropdown |

### Other
- `assets/` — landing images, `qrcodes/` (generated QR PNGs, gitignored)
- `templates/sample_schedule.csv` — sample CSV: `Monday,08:00,10:00,Math 101`
- `uploads/` — legacy QR artifacts (gitignored)
- `cron/generate_daily_qr.php` — **the QR cron job** (run every minute; generates each student's 4 QR codes at 07:30/12:00/13:00/17:00)

## Code Conventions

- Procedural PHP, snake_case filenames, camelCase function names, snake_case DB columns, 2-space indentation
- `<?php` opens, `<?= ?>` short echo, no closing `?>` tag, `exit()` after `header("Location: ...")`
- Protected pages (root): `require_once 'includes/auth.php';` then `requireRole('student', 'login.php');` / `touchActivity(600, 'login.php');`
- Subdirectory pages: `require_once '../includes/auth.php';` + `requireRole('admin', '../login.php');` + `touchActivity(600, '../login.php');`
- POST forms: add `<?= csrfField() ?>` in the form and call `csrfCheck('../login.php');` at the top of the handler (JSON endpoints use `csrfValid()` and return an error JSON)
- DB access: always prepared statements with positional `?` + `execute([...])`; named params (`:office`, `:search`) only for dynamic WHERE clauses; `fetch()` / `fetchAll()` / `fetchColumn()`
- Flash messages via `$_SESSION` (`login_error`, `signup_error`, `signup_success`, `error`/`success`, `schedule_generated`), unset after display; GET flash via `?success=1`, `?updated=1`, `?timeout=1`, `?logged_out=1`
- `htmlspecialchars()` applied to user data in output
- Session keys set by login: `user_id`, `username`, `user_role` (+ `LAST_ACTIVITY` maintained by `touchActivity`)
- Dark-mode toggle + Lucide `lucide.createIcons()` copy-pasted at the end of each page's `<body>`

## Database Schema

Canonical DDL: **`schema.sql`** (repo root). Summary:

- **users** — `id`, `fullname`, `username` (uniq), `email` (uniq), `password_hash`, `role` ('student'|'admin'|'superadmin'), `office`, `status`, `required_hours`, `rendered_hours`, `missed_hours`, `is_active`, `created_at`
- **attendance_logs** — `id`, `user_id`, `log_date` (one row per user per day), `morning_time_in`, `morning_time_out`, `afternoon_time_in`, `afternoon_time_out` (DATETIME, nullable), `minutes_rendered`
- **class_schedules** — `id`, `user_id`, `day` ('Monday'..'Friday'), `start_time`, `end_time`, `subject`
- **work_schedules** — `id`, `user_id`, `day`, `start_time`, `end_time`, `task` ('Office Task')
- **make_up_requests** — `id`, `user_id`, `date` (the column is named `date` app-wide), `start_time`, `end_time`, `reason`, `status` ('Pending'|'Approved'|'Denied'), `admin_comment`, `decision_date`
- **progress_history** — `id`, `user_id`, `date_recorded` (one per day), `rendered_hours`
- **qr_codes** — PK `(date_generated, qr_type, user_id)` (Idempotent via `REPLACE INTO`), `qr_type` enum (`morning_in`|`morning_out`|`afternoon_in`|`afternoon_out`), `code` (indexed), `expires_at`
- **notifications** — `id`, `user_id`, `message`, `created_at`
- **support_tickets** — `id`, `user_id`, `subject`, `message`, `status` ('Open'|'Resolved'), `created_at`

## Core Workflows

- **Login**: `process_login.php` CSRF-checks, `password_verify`, rejects `is_active = 0`, `session_regenerate_id(true)`, redirects by role: student → `student/dashboard.php`, admin → `admin/dashboard.php`, superadmin → `superadmin/dashboard.php`
- **Daily QR (cron)**: `cron/generate_daily_qr.php` (every minute) → `generateDueQrCodes()` creates each of the 4 per-student codes the moment its `gen_at` time is reached (07:30/12:00/13:00/17:00) if missing → PNG at `assets/qrcodes/YYYYMMDD_<type>_u<id>.png` + `REPLACE INTO qr_codes`
- **QR time logging**: student scans their own code in `time_log.php` → POST to `validate_qr.php` → CSRF + ownership + expiry + scan-window checks → one of the 4 attendance columns gets `NOW()` (first scan in a column wins via `ON DUPLICATE KEY UPDATE ... IF(col IS NULL, NOW(), col)`) → `updateRenderedHours()` refreshes totals
- **Schedule upload**: CSV rows `Day,Start,End,Subject` → replace user's `class_schedules` → auto-run `generate_work_schedule.php` → redirect `view_schedule.php?success=1`
- **Hour tracking**: derived from `attendance_logs` (AM + PM diffs); `updateRenderedHours()` refreshes `users.rendered_hours`; `saveProgressSnapshot()` archives one row/day (both called on `student/progress.php`)
- **Make-up requests**: student submits (CSRF) → admin approves/denies in `manage_requests.php` (CSRF) → `notifications` row → student dashboard shows the last 5 notifications
- **Contact support**: student submits (CSRF) → `support_tickets` row + `notifications` row for every superadmin → super admin dashboard lists tickets and marks them resolved

## Remaining Notes / Caveats

- **QR codes are strictly per-student** — each is bound to one `user_id`; scanning someone else's code is rejected
- **Cron is required** for QR generation in production-device: run `cron/generate_daily_qr.php` once per minute (see file header for Windows/Linux scheduling)
- **`minutes_rendered`** in `attendance_logs` is not maintained on every scan; `updateRenderedHours()` recomputes totals on demand (progress/history pages)
- No test suite, CI, or build tooling — use `php -l` and manual smoke tests