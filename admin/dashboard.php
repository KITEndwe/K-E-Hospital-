<?php
// K&E Hospital - Admin Dashboard
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . FRONTEND_URL . '/login.php');
    exit();
}

// Get statistics
try {
    // Total appointments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
    $total_appointments = $stmt->fetch()['total'];
    
    // Total doctors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM doctors");
    $total_doctors = $stmt->fetch()['total'];
    
    // Total patients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $total_patients = $stmt->fetch()['total'];
    
    // Pending appointments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'Pending'");
    $pending_appointments = $stmt->fetch()['total'];
    
    // Today's appointments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = CURDATE()");
    $today_appointments = $stmt->fetch()['total'];
    
    // Monthly revenue
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'Completed' AND MONTH(payment_date) = MONTH(CURRENT_DATE())");
    $monthly_revenue = $stmt->fetch()['total'] ?? 0;
    
    // Latest appointments
    $stmt = $pdo->query("
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.created_at,
            d.name as doctor_name,
            d.speciality,
            d.profile_image,
            u.full_name as patient_name,
            u.profile_image as patient_image
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.doctor_id 
        JOIN users u ON a.user_id = u.user_id 
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $latest_appointments = $stmt->fetchAll();
    
    // Recent activity
    $stmt = $pdo->query("
        SELECT 
            a.created_at,
            u.full_name as patient_name,
            d.name as doctor_name,
            a.status,
            a.appointment_date
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $recent_activity = $stmt->fetchAll();
    
    // Doctor performance
    $stmt = $pdo->query("
        SELECT 
            d.name,
            d.speciality,
            d.rating,
            COUNT(a.appointment_id) as total_appointments,
            COUNT(CASE WHEN a.status = 'Completed' THEN 1 END) as completed_appointments
        FROM doctors d
        LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
        GROUP BY d.doctor_id
        ORDER BY completed_appointments DESC
        LIMIT 5
    ");
    $top_doctors = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_appointments = 0;
    $total_doctors = 0;
    $total_patients = 0;
    $pending_appointments = 0;
    $today_appointments = 0;
    $monthly_revenue = 0;
    $latest_appointments = [];
    $recent_activity = [];
    $top_doctors = [];
}

$admin_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
    <title>Admin Dashboard - K&E Hospital</title>
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
            background: #f5f7fb;
            color: #1f2937;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            transform: translateX(0);
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: #f1f5f9; }
        .sidebar::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 3px; }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s;
            border-right: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: #eef2ff;
            color: #3b82f6;
            border-right: 3px solid #3b82f6;
        }

        .nav-item i {
            width: 24px;
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: #f1f5f9;
            border: none;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            font-size: 1.25rem;
            color: #1f2937;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .mobile-menu-toggle:hover {
            background: #e2e8f0;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
            width: 100%;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .page-title p {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .admin-name {
            font-weight: 500;
            color: #1e293b;
        }

        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,38,38,0.3);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-header i {
            font-size: 2rem;
            opacity: 0.7;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Appointments Section */
        .appointments-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
        }

        .section-header h2 i {
            color: #3b82f6;
            margin-right: 0.5rem;
        }

        .view-all {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .appointments-table {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
        }

        td {
            font-size: 0.875rem;
        }

        .status-badge {
            display: inline-flex;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-completed { background: #e0e7ff; color: #4338ca; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* ========== RESPONSIVE STYLES ========== */
        
        /* Tablet Landscape (1024px - 1200px) */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        /* Tablet Portrait (768px - 1024px) */
        @media (max-width: 900px) {
            .stats-grid {
                gap: 0.75rem;
            }
            .stat-card {
                padding: 1.25rem;
            }
            .stat-value {
                font-size: 1.75rem;
            }
        }

        /* Mobile (up to 768px) */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1001;
                width: 280px;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .top-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
            }
            
            .page-title {
                justify-content: space-between;
                width: 100%;
            }
            
            .page-title h1 {
                font-size: 1.25rem;
            }
            
            .page-title p {
                font-size: 0.75rem;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .admin-badge {
                flex: 1;
                justify-content: center;
            }
            
            .admin-name {
                font-size: 0.875rem;
            }
            
            .logout-btn {
                padding: 0.4rem 1rem;
                font-size: 0.8rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-header i {
                font-size: 1.5rem;
            }
            
            .appointments-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .section-header h2 {
                font-size: 1.1rem;
            }
            
            .appointments-table {
                overflow-x: auto;
            }
            
            table {
                min-width: 500px;
            }
            
            th, td {
                padding: 0.75rem;
                font-size: 0.8rem;
            }
        }

        /* Small Mobile (480px and below) */
        @media (max-width: 480px) {
            .main-content {
                padding: 0.75rem;
            }
            
            .top-bar {
                padding: 0.875rem;
                margin-bottom: 1rem;
            }
            
            .page-title h1 {
                font-size: 1.1rem;
            }
            
            .admin-badge {
                padding: 0.4rem 0.75rem;
            }
            
            .admin-avatar {
                width: 30px;
                height: 30px;
            }
            
            .admin-name {
                font-size: 0.8rem;
            }
            
            .logout-btn {
                padding: 0.35rem 0.875rem;
                font-size: 0.75rem;
            }
            
            .stat-card {
                padding: 0.875rem;
            }
            
            .stat-value {
                font-size: 1.25rem;
            }
            
            .stat-label {
                font-size: 0.75rem;
            }
            
            .appointments-section {
                padding: 0.875rem;
            }
            
            .section-header h2 {
                font-size: 1rem;
            }
            
            .section-header h2 i {
                font-size: 0.9rem;
            }
            
            .view-all {
                font-size: 0.75rem;
            }
            
            .empty-state {
                padding: 2rem;
            }
            
            .empty-state i {
                font-size: 2rem;
            }
            
            .empty-state p {
                font-size: 0.85rem;
            }
        }

        /* Extra Small Mobile (375px and below) */
        @media (max-width: 375px) {
            .user-info {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }
            
            .admin-badge {
                justify-content: center;
            }
            
            .logout-btn {
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <img src="assets/admin_logo.svg" width="150px" alt="">
                    
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
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
                <a href="patients.php" class="nav-item">
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
                    <div>
                        <h1>Dashboard</h1>
                        <p>Welcome back, <?= htmlspecialchars($admin_name) ?>!</p>
                    </div>
                </div>
                <div class="user-info">
                    <div class="admin-badge">
                        <div class="admin-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="admin-name"><?= htmlspecialchars($admin_name) ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value"><?= $total_appointments ?></span>
                        <i class="fas fa-calendar-check" style="color: #3b82f6;"></i>
                    </div>
                    <div class="stat-label">Total Appointments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value"><?= $total_doctors ?></span>
                        <i class="fas fa-user-md" style="color: #10b981;"></i>
                    </div>
                    <div class="stat-label">Total Doctors</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value"><?= $total_patients ?></span>
                        <i class="fas fa-users" style="color: #8b5cf6;"></i>
                    </div>
                    <div class="stat-label">Total Patients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value">$<?= number_format($monthly_revenue, 2) ?></span>
                        <i class="fas fa-dollar-sign" style="color: #f59e0b;"></i>
                    </div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
            </div>

            <div class="appointments-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Latest Appointments</h2>
                    <a href="appointments.php" class="view-all">View All →</a>
                </div>
                
                <?php if (count($latest_appointments) > 0): ?>
                    <div class="appointments-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_appointments as $appointment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                                        <td><?= htmlspecialchars($appointment['doctor_name']) ?></td>
                                        <td>
                                            <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?><br>
                                            <small><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                                                <?= $appointment['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <p>No appointments found.</p>
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
            if (sidebar) sidebar.classList.remove('open'); 
            if (overlay) overlay.classList.remove('active'); 
            document.body.style.overflow = '';
        }
        
        function openMenu() { 
            if (sidebar) sidebar.classList.add('open');    
            if (overlay) overlay.classList.add('active');    
            document.body.style.overflow = 'hidden';
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function(e) { 
                e.stopPropagation(); 
                if (sidebar && sidebar.classList.contains('open')) {
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
            if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('open')) {
                closeMenu();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
                closeMenu();
            }
        });
    </script>
</body>
</html>