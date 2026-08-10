-- ============================================================
-- MySchedMate — MySQL Schema
-- Database: myschedmate_db (utf8mb4)
-- ------------------------------------------------------------
-- Import:
--   mysql -u root -p myschedmate_db < schema.sql
--   (or paste into phpMyAdmin's SQL tab)
--
-- Requires MySQL 8.0.13+ (uses DATE DEFAULT (CURRENT_DATE)).
-- ============================================================

CREATE DATABASE IF NOT EXISTS myschedmate_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE myschedmate_db;

-- ------------------------------------------------------------
-- users — students, admins (office managers), superadmins
-- Login reads `password_hash` and requires is_active = 1.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  fullname       VARCHAR(100)    NOT NULL,
  username       VARCHAR(50)     NOT NULL,
  email          VARCHAR(100)    NOT NULL,
  password_hash  VARCHAR(255)    NOT NULL,
  role           ENUM('student','admin','superadmin') NOT NULL DEFAULT 'student',
  office         VARCHAR(100)    NOT NULL DEFAULT '',
  status         VARCHAR(20)     NOT NULL DEFAULT 'Active',
  required_hours DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
  rendered_hours DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
  missed_hours   DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
  is_active      TINYINT(1)      NOT NULL DEFAULT 1,
  created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email (email),
  KEY idx_role (role),
  KEY idx_office (office)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- attendance_logs — one row per student per day with separate
-- AM and PM sessions (each with its own time-in / time-out).
-- Filled by student/validate_qr.php QR scans.
-- minutes_rendered is recomputed from the four timestamps.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance_logs (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id             INT UNSIGNED NOT NULL,
  log_date            DATE         NOT NULL,
  morning_time_in     DATETIME     NULL,
  morning_time_out    DATETIME     NULL,
  afternoon_time_in   DATETIME     NULL,
  afternoon_time_out  DATETIME     NULL,
  minutes_rendered    INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_date (user_id, log_date),
  CONSTRAINT fk_attendance_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- class_schedules — uploaded from CSV (Day,Start,End,Subject)
-- `day` holds English weekday names: 'Monday'..'Friday'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS class_schedules (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  day        VARCHAR(10)  NOT NULL,
  start_time TIME         NOT NULL,
  end_time   TIME         NOT NULL,
  subject    VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  CONSTRAINT fk_class_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- work_schedules — auto-generated 'Office Task' shifts filling
-- gaps (>=1h) between classes inside 08:00-18:00, max 2h each
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS work_schedules (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  day        VARCHAR(10)  NOT NULL,
  start_time TIME         NOT NULL,
  end_time   TIME         NOT NULL,
  task       VARCHAR(100) NOT NULL DEFAULT 'Office Task',
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  CONSTRAINT fk_work_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- make_up_requests — make-up hour requests.
-- The date column is named `date` throughout the app
-- (student insert, admin manage_requests, student dashboard).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS make_up_requests (
  id            INT UNSIGNED                     NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED                     NOT NULL,
  date          DATE                             NOT NULL,
  start_time    TIME                             NOT NULL,
  end_time      TIME                             NOT NULL,
  reason        TEXT                             NOT NULL,
  status        ENUM('Pending','Approved','Denied') NOT NULL DEFAULT 'Pending',
  admin_comment TEXT                             NULL,
  decision_date DATETIME                         NULL,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  CONSTRAINT fk_makeup_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- progress_history — daily snapshot of rendered hours
-- (one row per user per day; app checks before inserting)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progress_history (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  date_recorded   DATE         NOT NULL DEFAULT (CURRENT_DATE),
  rendered_hours  DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_date (user_id, date_recorded),
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- qr_codes — four codes per student per day, one per session
-- (morning_in / morning_out / afternoon_in / afternoon_out).
-- Populated by cron/generate_daily_qr.php via REPLACE INTO
-- (idempotent — hence the composite primary key).
-- Each QR is bound to exactly one student (user_id).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS qr_codes (
  date_generated DATE NOT NULL,
  qr_type        ENUM('morning_in','morning_out','afternoon_in','afternoon_out') NOT NULL DEFAULT 'morning_in',
  user_id        INT UNSIGNED NOT NULL DEFAULT 0,
  code           VARCHAR(100) NOT NULL,
  expires_at     DATETIME     NOT NULL,
  PRIMARY KEY (date_generated, qr_type, user_id),
  KEY idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- notifications — e.g. make-up request decisions; read in the
-- student dashboard (latest 5)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  message    TEXT         NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- support_tickets — student -> superadmin issue reports,
-- submitted from student/contact_support.php, resolved from the
-- super admin dashboard. A notifications row is also inserted
-- for every 'superadmin' user on each new ticket.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS support_tickets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  subject    VARCHAR(255) NOT NULL,
  message    TEXT         NOT NULL,
  status     ENUM('Open','Resolved') NOT NULL DEFAULT 'Open',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user (user_id),
  CONSTRAINT fk_ticket_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed data
-- Default superadmin (the app has no way to create the first
-- superadmin through the UI).
--   username: superadmin
--   password: admin123   <-- CHANGE AFTER FIRST LOGIN
-- ============================================================
INSERT IGNORE INTO users (fullname, username, email, password_hash, role, office, is_active)
VALUES ('System Administrator', 'superadmin', 'superadmin@myschedmate.local',
        '$2y$10$15xm2UA1jSgLq/3HHkFF1OwxHSEi5R.0RZ7brhcV7l45fF7NLKHmO',
        'superadmin', 'MIS', 1);