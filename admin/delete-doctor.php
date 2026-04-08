<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../frontend/login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$doctor_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($doctor_id)) {
    header('Location: doctors-list.php?error=no_id');
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* Check doctor exists */
    $chk = $pdo->prepare("SELECT doctor_id, name, profile_image FROM doctors WHERE doctor_id = ?");
    $chk->execute(array($doctor_id));
    $doctor = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        header('Location: doctors-list.php?error=not_found');
        exit();
    }

    /* Cancel pending appointments first */
    $upd = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE doctor_id = ? AND status IN ('Pending','Confirmed')");
    $upd->execute(array($doctor_id));

    /* Delete doctor */
    $del = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?");
    $del->execute(array($doctor_id));

    /* Delete profile image file if it's an uploaded one */
    if (!empty($doctor['profile_image'])) {
        $img = ltrim($doctor['profile_image'], '/');
        /* Only delete if it's an uploaded file (not the default assets) */
        if (strpos($img, 'uploads/') === 0) {
            $abs = dirname(__DIR__) . '/' . $img;
            if (file_exists($abs)) unlink($abs);
        }
    }

    header('Location: doctors-list.php?success=deleted&name=' . urlencode($doctor['name']));
    exit();

} catch (PDOException $e) {
    header('Location: doctors-list.php?error=db&msg=' . urlencode($e->getMessage()));
    exit();
}