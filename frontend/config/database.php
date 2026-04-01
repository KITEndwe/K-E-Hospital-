<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ke_hospital');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL configuration
define('BASE_URL', 'http://localhost/KE-Hospital');
define('ADMIN_URL', BASE_URL . '/admin');
define('FRONTEND_URL', BASE_URL . '/frontend');

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('DOCTOR_UPLOAD_PATH', UPLOAD_PATH . 'doctors/');
define('PATIENT_UPLOAD_PATH', UPLOAD_PATH . 'patients/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create upload directories if they don't exist
if (!file_exists(DOCTOR_UPLOAD_PATH)) {
    mkdir(DOCTOR_UPLOAD_PATH, 0777, true);
}
if (!file_exists(PATIENT_UPLOAD_PATH)) {
    mkdir(PATIENT_UPLOAD_PATH, 0777, true);
}
?>