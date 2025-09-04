<?php
session_start();
require_once 'includes/db.php';

// === CONFIGURE YOUR SUPERADMIN ACCOUNT HERE ===
$fullname = "System Super Admin";
$username = "superadmin";
$email = "superadmin@example.com";
$plainPassword = "YourStrongPassword123"; // Change this before running
$office = "N/A";
$role = "superadmin";

// === HASH PASSWORD ===
$passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

try {
    // Check if a superadmin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'superadmin' LIMIT 1");
    $stmt->execute();

    if ($stmt->fetch()) {
        die("❌ A superadmin account already exists. Delete this file for security.");
    }

    // Insert new superadmin
    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password_hash, role, office) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullname, $username, $email, $passwordHash, $role, $office]);

    echo "✅ Superadmin account created successfully!<br>";
    echo "➡ Username: <strong>$username</strong><br>";
    echo "➡ Password: <strong>$plainPassword</strong><br>";
    echo "<p><b>⚠️ Please delete create_superadmin.php immediately for security.</b></p>";

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
