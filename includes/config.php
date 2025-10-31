<?php
// ===============================
// Prestige Perfumery - Config
// ===============================

// Database credentials
$host = "localhost";
$user = "root";
$pass = ""; // change if needed
$dbname = "prestige_perfumery";

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Create mysqli connection for backward compatibility
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("MySQLi Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Optional: set timezone
date_default_timezone_set('Asia/Manila');
?>
