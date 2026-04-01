<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K&E Hospital - <?= $page_title ?? 'Healthcare Excellence' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            color: #1f2937;
            line-height: 1.6;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .navbar__inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        
        .navbar__logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
            text-decoration: none;
        }
        
        .navbar__nav {
            display: flex;
            gap: 2rem;
        }
        
        .navbar__nav a {
            text-decoration: none;
            color: #6b7280;
            transition: color 0.3s;
        }
        
        .navbar__nav a:hover {
            color: #3b82f6;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .navbar__nav {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container">
            <div class="navbar__inner">
                <a href="index.php" class="navbar__logo">
                    <i class="fas fa-hospital"></i> K&E Hospital
                </a>
                <nav class="navbar__nav">
                    <a href="index.php">Home</a>
                    <a href="Alldoctors.php">Doctors</a>
                    <a href="about.php">About</a>
                    <a href="contact.php">Contact</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="Myprofile.php">My Profile</a>
                        <a href="Myappointment.php">My Appointments</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                    <?php endif; ?>
                </nav>
                <?php if (!isLoggedIn()): ?>
                    <a href="login.php" class="btn-primary">Book Appointment</a>
                <?php endif; ?>
            </div>
        </div>
    </header>