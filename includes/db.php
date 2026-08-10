<?php
$host = 'localhost';
$db   = 'myschedmate_db';  // replace with your DB
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Align MySQL session timezone with PHP so that NOW()/CURDATE()/TIMESTAMPDIFF
    // produce the same local times the app displays (PHP tz may differ from MySQL SYSTEM).
    $pdo->exec("SET time_zone = '" . date('P') . "'");
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
