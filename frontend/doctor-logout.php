<?php
session_start();
// Only destroy doctor session keys, not the whole session
unset($_SESSION['doctor_id']);
unset($_SESSION['doctor_name']);
unset($_SESSION['doctor_email']);
unset($_SESSION['doctor_spec']);
session_destroy();
header('Location: login.php');
exit();