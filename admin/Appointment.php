<?php
// K&E Hospital - All Appointments Page
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

// Include database connection
$db_found = false;
$db_paths = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/config/database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/KE-Hospital/config/database.php',
    '../config/database.php'
];

foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_found = true;
        break;
    }
}

if (!$db_found) {
    die('Database configuration file not found. Please check your installation.');
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query for appointments
$sql = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.amount,
        a.created_at,
        d.doctor_id,
        d.name as doctor_name,
        d.speciality,
        d.profile_image,
        d.fees,
        u.user_id,
        u.full_name as patient_name,
        u.email as patient_email,
        u.phone as patient_phone,
        u.profile_image as patient_image,
        TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) as patient_age
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.doctor_id 
    JOIN users u ON a.user_id = u.user_id 
    WHERE 1=1
";

$params = [];

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR d.name LIKE ? OR d.speciality LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get statistics
$total_appointments = count($appointments);
$pending_count = 0;
$confirmed_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($appointments as $app) {
    switch($app['status']) {
        case 'Pending': $pending_count++; break;
        case 'Confirmed': $confirmed_count++; break;
        case 'Completed': $completed_count++; break;
        case 'Cancelled': $cancelled_count++; break;
    }
}

$admin_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>All Appointments - K&E Hospital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            overflow-x: hidden;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #334155;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 1.75rem 1.5rem;
            border-bottom: 1px solid #334155;
            margin-bottom: 1rem;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-logo i {
            font-size: 1.8rem;
            color: #3b82f6;
        }

        .sidebar-logo span {
            background: linear-gradient(135deg, #fff 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-nav {
            padding: 0.5rem 0;
        }

        .nav-item {
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            margin: 0.25rem 0.75rem;
            border-radius: 0.75rem;
            font-weight: 500;
        }

        .nav-item:hover {
            background: #334155;
            color: white;
        }

        .nav-item.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        .nav-item i {
            width: 24px;
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
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
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,38,38,0.3);
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card-sm {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        .stat-card-sm:hover {
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

        /* Filters */
        .filters-bar {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            border: 1px solid #e2e8f0;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #475569;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.875rem;
            background: white;
        }

        .search-box {
            flex: 1;
            display: flex;
            gap: 0.5rem;
        }

        .search-box input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .search-box button {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        .reset-btn {
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        /* Appointments Table */
        .table-container {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .appointments-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            font-weight: 600;
            font-size: 0.875rem;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.875rem;
        }

        .patient-info, .doctor-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: #eef2ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-weight: 600;
            overflow: hidden;
            flex-shrink: 0;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .details h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }

        .details p {
            font-size: 0.75rem;
            color: #64748b;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #e0e7ff;
            color: #4338ca;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            color: #3b82f6;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #eef2ff;
        }

        .action-btn.delete {
            color: #ef4444;
        }

        .action-btn.delete:hover {
            background: #fee2e2;
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

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem;
        }

        .page-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .page-btn:hover, .page-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            font-size: 1.25rem;
            color: #1f2937;
        }

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

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
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
                align-items: center;
                justify-content: center;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                justify-content: space-between;
            }
            
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin-dashboard.php" class="sidebar-logo">
                    <i class="fas fa-hospital-user"></i>
                    <span>K&E Hospital</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="admin-dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin-appointments.php" class="nav-item active">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
                <a href="admin-add-doctor.php" class="nav-item">
                    <i class="fas fa-user-md"></i>
                    <span>Add Doctor</span>
                </a>
                <a href="admin-doctors-list.php" class="nav-item">
                    <i class="fas fa-list"></i>
                    <span>Doctors List</span>
                </a>
                <a href="admin-patients.php" class="nav-item">
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
                    <h1>All Appointments</h1>
                    <p>Manage and track all patient appointments</p>
                </div>
                <div class="user-info">
                    <div class="admin-badge">
                        <div class="admin-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-row">
                <div class="stat-card-sm">
                    <div class="stat-number"><?php echo $total_appointments; ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
                <div class="stat-card-sm">
                    <div class="stat-number" style="color: #d97706;"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card-sm">
                    <div class="stat-number" style="color: #065f46;"><?php echo $confirmed_count; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
                <div class="stat-card-sm">
                    <div class="stat-number" style="color: #4338ca;"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-bar">
                <form method="GET" style="display: contents;">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo $status_filter == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Date:</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search patient or doctor..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <?php if (!empty($status_filter) || !empty($date_filter) || !empty($search)): ?>
                        <a href="admin-appointments.php" class="reset-btn"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Appointments Table -->
            <div class="table-container">
                <?php if (count($appointments) > 0): ?>
                    <div class="appointments-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Department</th>
                                    <th>Date & Time</th>
                                    <th>Fees</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <div class="patient-info">
                                                <div class="avatar">
                                                    <?php if (!empty($appointment['patient_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($appointment['patient_image']); ?>" alt="">
                                                    <?php else: ?>
                                                        <i class="fas fa-user"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="details">
                                                    <h4><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                                    <p><?php echo $appointment['patient_age'] ? $appointment['patient_age'] . ' yrs' : 'N/A'; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="doctor-info">
                                                <div class="avatar">
                                                    <?php if (!empty($appointment['profile_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($appointment['profile_image']); ?>" alt="">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-md"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="details">
                                                    <h4><?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
                                                    <p><?php echo htmlspecialchars($appointment['speciality']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['speciality']); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small>
                                        </td>
                                        <td>
                                            K<?php echo number_format($appointment['fees'], 2); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $appointment['status'];
                                            $statusClass = '';
                                            switch($status) {
                                                case 'Pending': $statusClass = 'status-pending'; break;
                                                case 'Confirmed': $statusClass = 'status-confirmed'; break;
                                                case 'Completed': $statusClass = 'status-completed'; break;
                                                case 'Cancelled': $statusClass = 'status-cancelled'; break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin-appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="action-btn" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="action-btn delete" onclick="deleteAppointment(<?php echo $appointment['appointment_id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <p>No appointments found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Mobile menu functionality
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        function closeMenu() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
        
        function openMenu() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
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
        
        function deleteAppointment(id) {
            if (confirm('Are you sure you want to delete this appointment?')) {
                window.location.href = 'admin-delete-appointment.php?id=' + id;
            }
        }
    </script>
</body>
</html>