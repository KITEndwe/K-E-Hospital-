<?php
// K&E Hospital – Database Configuration
// File location: C:\xampp\htdocs\KE-Hospital\config\database.php

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';          // Change this if your MySQL has a password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Show a friendly error — never expose raw DB errors in production
    die('
        <div style="
            font-family: Outfit, sans-serif;
            max-width: 500px;
            margin: 80px auto;
            padding: 32px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fef2f2;
            color: #991b1b;
            text-align: center;
        ">
            <h2 style="margin-bottom:12px;">Database Connection Failed</h2>
            <p style="font-size:.9rem; color:#b91c1c;">
                Could not connect to <strong>ke_hospital</strong> on <strong>localhost</strong>.<br><br>
                ' . htmlspecialchars($e->getMessage()) . '
            </p>
            <p style="margin-top:16px; font-size:.82rem; color:#9ca3af;">
                Make sure XAMPP MySQL is running and the database exists.
            </p>
        </div>
    ');
}

// Also expose individual variables for files that use mysqli instead of PDO
$db_host = $host;
$db_name = $dbname;
$db_user = $username;
$db_pass = $password;

// mysqli connection (used by some pages)
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    // Silent — pages that need $conn will handle their own errors
    $conn = null;
} else {
    $conn->set_charset('utf8mb4');
}