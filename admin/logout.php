<?php
// admin/logout.php
session_start();
session_destroy();
header('Location: ../frontend/login.php');
exit();
?>