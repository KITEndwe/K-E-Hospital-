<?php
// Admin - Patients List
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

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all patients with search filter
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE is_active = 1 AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $patients = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY created_at DESC");
    $patients = $stmt->fetchAll();
}

$admin_name = $_SESSION['full_name'] ?? 'Admin';
$total_patients = count($patients);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
    <title>Patients - K&E Hospital Admin</title>
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

        /* Stats Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        /* Search Bar */
        .search-bar {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            display: flex;
            gap: 0.5rem;
            min-width: 200px;
        }

        .search-box input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: inherit;
        }

        .search-box button {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-box button:hover {
            background: #2563eb;
        }

        .reset-btn {
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .reset-btn:hover {
            background: #e2e8f0;
        }

        /* Patients Table */
        .patients-table-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow-x: auto;
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

        .patient-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }

        .patient-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .patient-details h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }

        .patient-details p {
            font-size: 0.75rem;
            color: #64748b;
        }

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
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
        }

        /* Tablet Portrait (768px - 1024px) */
        @media (max-width: 900px) {
            .stats-row {
                gap: 0.75rem;
            }
            .stat-number {
                font-size: 1.5rem;
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
            
            .stats-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .stat-card {
                padding: 0.875rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .search-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
            }
            
            .search-box {
                width: 100%;
            }
            
            .patients-table-container {
                padding: 1rem;
            }
            
            .patients-table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 600px;
            }
            
            th, td {
                padding: 0.75rem;
                font-size: 0.8rem;
            }
            
            .patient-avatar {
                width: 32px;
                height: 32px;
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
            
            .stat-number {
                font-size: 1.25rem;
            }
            
            .stat-label {
                font-size: 0.7rem;
            }
            
            .search-box input,
            .search-box button,
            .reset-btn {
                font-size: 0.8rem;
                padding: 0.4rem 0.75rem;
            }
            
            .patients-table-container {
                padding: 0.875rem;
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
                    <div>
                        <h1>Patients</h1>
                        <p>Manage all registered patients</p>
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

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_patients ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= date('Y') ?></div>
                    <div class="stat-label">Year Registered</div>
                </div>
            </div>

            <div class="search-bar">
                <form method="GET" style="display: contents; width: 100%;">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    <?php if (!empty($search)): ?>
                        <a href="patients.php" class="reset-btn"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="patients-table-container">
                <?php if (count($patients) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Contact Info</th>
                                    <th>Gender</th>
                                    <th>Blood Group</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td>
                                            <div class="patient-info">
                                                <div class="patient-avatar">
                                                    <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                                                </div>
                                                <div class="patient-details">
                                                    <h4><?= htmlspecialchars($patient['full_name']) ?></h4>
                                                    <p>ID: #<?= $patient['user_id'] ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($patient['email']) ?></div>
                                            <small><?= htmlspecialchars($patient['phone'] ?? 'No phone') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($patient['gender'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, Y', strtotime($patient['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p><?= !empty($search) ? 'No patients found matching "' . htmlspecialchars($search) . '"' : 'No patients found.' ?></p>
                        <?php if (!empty($search)): ?>
                            <a href="patients.php" style="display: inline-block; margin-top: 1rem; color: #3b82f6;">View all patients</a>
                        <?php endif; ?>
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