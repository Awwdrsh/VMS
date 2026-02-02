<?php
$host = 'localhost';
$dbname = 'visitor_sys'; 
$username = 'root'; 
$password = ''; 

try {
    // 1. Connect without Database to Create it if missing
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    
    // 2. Connect to the Database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-Create Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS Assessment_Users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )");

    // Auto-Create Visitors Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS Assessment_Visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        purpose VARCHAR(255) NOT NULL,
        image_path VARCHAR(255) DEFAULT NULL,
        check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        check_out_time DATETIME NULL,
        status ENUM('Signed In', 'Signed Out') DEFAULT 'Signed In'
    )");

    // Add image_path column if it doesn't exist (Migration)
    $colList = $pdo->query("SHOW COLUMNS FROM Assessment_Visitors LIKE 'image_path'")->fetchAll();
    if (empty($colList)) {
        $pdo->exec("ALTER TABLE Assessment_Visitors ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER purpose");
    }

    // Create Default Admin: admin / password123
    $check = $pdo->query("SELECT COUNT(*) FROM Assessment_Users")->fetchColumn();
    if ($check == 0) {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO Assessment_Users (username, password) VALUES ('admin', ?)")->execute([$hash]);
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}