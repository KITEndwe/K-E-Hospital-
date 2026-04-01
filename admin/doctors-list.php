<?php
// K&E Hospital - All Doctors List Page
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
$speciality_filter = isset($_GET['speciality']) ? $_GET['speciality'] : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query for doctors
$sql = "SELECT * FROM doctors WHERE 1=1";
$params = [];

if (!empty($speciality_filter)) {
    $sql .= " AND speciality = ?";
    $params[] = $speciality_filter;
}

if ($availability_filter !== '') {
    $sql .= " AND is_available = ?";
    $params[] = ($availability_filter == 'available') ? 1 : 0;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR speciality LIKE ? OR degree LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Get all specialities for filter
$spec_stmt = $pdo->query("SELECT DISTINCT speciality FROM doctors ORDER BY speciality");
$specialities = $spec_stmt->fetchAll();

// Get statistics
$total_doctors = count($doctors);
$available_count = 0;
$unavailable_count = 0;

foreach ($doctors as $doc) {
    if ($doc['is_available']) {
        $available_count++;
    } else {
        $unavailable_count++;
    }
}

$admin_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Doctors List - K&E Hospital Admin</title>
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
            grid-template-columns: repeat(3, 1fr);
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

        /* Doctors Grid */
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .doctor-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            cursor: pointer;
        }

        .doctor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        .doctor-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        }

        .doctor-info {
            padding: 1.25rem;
        }

        .doctor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .doctor-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }

        .availability-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .availability-badge.available {
            background: #d1fae5;
            color: #065f46;
        }

        .availability-badge.unavailable {
            background: #fee2e2;
            color: #dc2626;
        }

        .doctor-speciality {
            color: #3b82f6;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .doctor-details {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .doctor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e2e8f0;
        }

        .doctor-fees {
            font-size: 1rem;
            font-weight: 700;
            color: #3b82f6;
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            color: #f59e0b;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.5rem;
            text-align: center;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .action-btn.edit {
            background: #eef2ff;
            color: #3b82f6;
        }

        .action-btn.edit:hover {
            background: #3b82f6;
            color: white;
        }

        .action-btn.delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn.delete:hover {
            background: #dc2626;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
            background: white;
            border-radius: 1rem;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .doctors-grid {
                grid-template-columns: 1fr;
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
                <a href="doctors-list.php" class="nav-item active">
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
                    <h1>All Doctors</h1>
                    <p>Manage your hospital's medical professionals</p>
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
                    <div class="stat-number"><?php echo $total_doctors; ?></div>
                    <div class="stat-label">Total Doctors</div>
                </div>
                <div class="stat-card-sm">
                    <div class="stat-number" style="color: #065f46;"><?php echo $available_count; ?></div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-card-sm">
                    <div class="stat-number" style="color: #dc2626;"><?php echo $unavailable_count; ?></div>
                    <div class="stat-label">Unavailable</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-bar">
                <form method="GET" style="display: contents;">
                    <div class="filter-group">
                        <label>Speciality:</label>
                        <select name="speciality" onchange="this.form.submit()">
                            <option value="">All Specialities</option>
                            <?php foreach ($specialities as $spec): ?>
                                <option value="<?php echo htmlspecialchars($spec['speciality']); ?>" <?php echo $speciality_filter == $spec['speciality'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['speciality']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Availability:</label>
                        <select name="availability" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="available" <?php echo $availability_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo $availability_filter == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by name, speciality, or degree..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <?php if (!empty($speciality_filter) || !empty($availability_filter) || !empty($search)): ?>
                        <a href="admin-doctors-list.php" class="reset-btn"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Doctors Grid -->
            <?php if (count($doctors) > 0): ?>
                <div class="doctors-grid">
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card" onclick="window.location.href='admin-doctor-details.php?id=<?php echo $doctor['doctor_id']; ?>'">
                            <img src="<?php echo htmlspecialchars($doctor['profile_image'] ?: '/assets/doctors/default-doctor.png'); ?>" 
                                 class="doctor-image" 
                                 alt="<?php echo htmlspecialchars($doctor['name']); ?>"
                                 onerror="this.src='https://placehold.co/400x500/DBEAFE/3B82F6?text=Doctor'">
                            <div class="doctor-info">
                                <div class="doctor-header">
                                    <h3 class="doctor-name"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                                    <span class="availability-badge <?php echo $doctor['is_available'] ? 'available' : 'unavailable'; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo $doctor['is_available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </div>
                                <p class="doctor-speciality"><?php echo htmlspecialchars($doctor['speciality']); ?></p>
                                <p class="doctor-details">
                                    <?php echo htmlspecialchars($doctor['degree']); ?> • <?php echo htmlspecialchars($doctor['experience']); ?>
                                </p>
                                <div class="doctor-footer">
                                    <span class="doctor-fees">K<?php echo number_format($doctor['fees'], 2); ?></span>
                                    <?php if ($doctor['rating'] > 0): ?>
                                        <div class="doctor-rating">
                                            <i class="fas fa-star"></i>
                                            <span><?php echo number_format($doctor['rating'], 1); ?></span>
                                            <span style="color: #94a3b8;">(<?php echo $doctor['total_reviews']; ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="action-buttons" onclick="event.stopPropagation()">
                                    <a href="admin-edit-doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="action-btn edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="action-btn delete" onclick="deleteDoctor('<?php echo $doctor['doctor_id']; ?>')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-md"></i>
                    <p>No doctors found matching your criteria</p>
                    <a href="admin-add-doctor.php" class="reset-btn" style="display: inline-block; margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Add New Doctor
                    </a>
                </div>
            <?php endif; ?>
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
        
        function deleteDoctor(doctorId) {
            if (confirm('Are you sure you want to delete this doctor? This action cannot be undone.')) {
                window.location.href = 'admin-delete-doctor.php?id=' + doctorId;
            }
        }
    </script>
</body>
</html>