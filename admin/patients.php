<?php
// Admin - Patients List (Simplified)
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../frontend/login.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get all patients
$stmt = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY created_at DESC");
$patients = $stmt->fetchAll();

$admin_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - K&E Hospital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copy the same styles from dashboard.php here */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: #f5f7fb; color: #1f2937; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: white; box-shadow: 2px 0 10px rgba(0,0,0,0.05); position: fixed; height: 100vh; overflow-y: auto; transition: all 0.3s; z-index: 100; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid #e5e7eb; }
        .sidebar-logo { font-size: 1.5rem; font-weight: bold; color: #3b82f6; text-decoration: none; display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-nav { padding: 1.5rem 0; }
        .nav-item { padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 1rem; color: #6b7280; text-decoration: none; transition: all 0.3s; }
        .nav-item:hover, .nav-item.active { background: #eef2ff; color: #3b82f6; border-right: 3px solid #3b82f6; }
        .nav-item i { width: 24px; }
        .main-content { flex: 1; margin-left: 280px; padding: 1.5rem; }
        .top-bar { background: white; border-radius: 1rem; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .page-title h1 { font-size: 1.5rem; font-weight: 600; }
        .page-title p { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }
        .logout-btn { background: #ef4444; color: white; padding: 0.5rem 1.25rem; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s; }
        .logout-btn:hover { background: #dc2626; transform: translateY(-1px); }
        .patients-table-container { background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #f3f4f6; }
        th { background: #f9fafb; font-weight: 600; color: #6b7280; font-size: 0.875rem; }
        td { font-size: 0.875rem; }
        .mobile-menu-toggle { display: none; background: none; border: none; font-size: 1.25rem; cursor: pointer; margin-right: 1rem; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: none; }
        .sidebar-overlay.active { display: block; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-menu-toggle { display: block; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <i class="fas fa-hospital-user"></i>
                    <span>K&E Hospital</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="appointments.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
                <a href="add-doctor.php" class="nav-item">
                    <i class="fas fa-user-md"></i>
                    <span>Add Doctor</span>
                </a>
                <a href="doctors-list.php" class="nav-item">
                    <i class="fas fa-list"></i>
                    <span>Doctors List</span>
                </a>
                <a href="patients.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Patients</span>
                </a>
            </nav>
        </aside>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Patients</h1>
                    <p>Manage all registered patients</p>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <div class="patients-table-container">
                <?php if (count($patients) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Blood Group</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td>#<?= $patient['user_id'] ?></td>
                                    <td><strong><?= htmlspecialchars($patient['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($patient['email']) ?></td>
                                    <td><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($patient['gender'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></td>
                                    <td><?= date('M d, Y', strtotime($patient['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-users" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
                        <p>No patients found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        function closeMenu() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function openMenu() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                if (sidebar.classList.contains('open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
                closeMenu();
            }
        });
    </script>
</body>
</html>