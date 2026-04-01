<?php
// K&E Hospital - All Doctors List Page
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../frontend/login.php');
    exit();
}

// Database connection - Direct connection without requiring external config
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

$speciality_filter   = isset($_GET['speciality'])   ? $_GET['speciality']   : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';
$search              = isset($_GET['search'])        ? $_GET['search']        : '';

$sql    = "SELECT * FROM doctors WHERE 1=1";
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

$spec_stmt   = $pdo->query("SELECT DISTINCT speciality FROM doctors ORDER BY speciality");
$specialities = $spec_stmt->fetchAll();

$total_doctors    = count($doctors);
$available_count   = 0;
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
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f5f7fb;
            color: #1f2937;
            overflow-x: hidden;
        }

        .dashboard-container { display: flex; min-height: 100vh; }

        /* ── SIDEBAR (dashboard style) ── */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: #f1f5f9; }
        .sidebar::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 3px; }

        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid #e5e7eb; }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-logo i { font-size: 1.8rem; color: #3b82f6; }
        .sidebar-logo span { color: #3b82f6; }

        .sidebar-nav { padding: 1.5rem 0; }

        .nav-item {
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            border-right: 3px solid transparent;
        }

        .nav-item:hover { background: #eef2ff; color: #3b82f6; }

        .nav-item.active {
            background: #eef2ff;
            color: #3b82f6;
            border-right: 3px solid #3b82f6;
        }

        .nav-item i { width: 24px; font-size: 1.1rem; }

        /* ── MAIN CONTENT ── */
        .main-content { flex: 1; margin-left: 280px; padding: 1.5rem; }

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

        .page-title h1 { font-size: 1.5rem; font-weight: 700; color: #0f172a; }
        .page-title p { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }

        .user-info { display: flex; align-items: center; gap: 1.5rem; }

        .admin-badge {
            display: flex; align-items: center; gap: 0.75rem;
            background: #f1f5f9; padding: 0.5rem 1rem; border-radius: 2rem;
        }

        .admin-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600;
        }

        .admin-name { font-weight: 500; color: #1e293b; }

        .logout-btn {
            background: #ef4444; color: white;
            padding: 0.5rem 1.25rem; border-radius: 0.5rem;
            text-decoration: none; font-size: 0.875rem; font-weight: 500;
            transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem;
        }

        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220,38,38,0.3); }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }

        .stat-card-sm {
            background: white; border-radius: 1rem; padding: 1rem;
            text-align: center; border: 1px solid #e2e8f0; transition: all 0.3s;
        }

        .stat-card-sm:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-number { font-size: 1.75rem; font-weight: 700; color: #0f172a; }
        .stat-label { font-size: 0.75rem; color: #64748b; margin-top: 0.25rem; }

        .filters-bar {
            background: white; border-radius: 1rem; padding: 1rem 1.5rem;
            margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;
            align-items: center; border: 1px solid #e2e8f0;
        }

        .filter-group { display: flex; align-items: center; gap: 0.5rem; }
        .filter-group label { font-size: 0.875rem; font-weight: 500; color: #475569; }

        .filter-group select,
        .filter-group input {
            padding: 0.5rem 1rem; border: 1px solid #e2e8f0;
            border-radius: 0.5rem; font-family: inherit; font-size: 0.875rem; background: white;
        }

        .search-box { flex: 1; display: flex; gap: 0.5rem; }
        .search-box input { flex: 1; padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; }
        .search-box button { padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.5rem; cursor: pointer; }

        .reset-btn { padding: 0.5rem 1rem; background: #f1f5f9; color: #475569; text-decoration: none; border-radius: 0.5rem; font-size: 0.875rem; }

        /* Doctor cards */
        .doctors-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }

        .doctor-card {
            background: white; border-radius: 1rem; overflow: hidden;
            transition: all 0.3s; border: 1px solid #e2e8f0; cursor: pointer;
        }

        .doctor-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }

        .doctor-image {
            width: 100%; height: 220px; object-fit: cover;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        }

        .doctor-info { padding: 1.25rem; }

        .doctor-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; }

        .doctor-name { font-size: 1.125rem; font-weight: 700; color: #0f172a; }

        .availability-badge {
            display: inline-flex; align-items: center; gap: 0.25rem;
            padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 500;
        }

        .availability-badge.available   { background: #d1fae5; color: #065f46; }
        .availability-badge.unavailable { background: #fee2e2; color: #dc2626; }

        .doctor-speciality { color: #3b82f6; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .doctor-details    { font-size: 0.75rem; color: #64748b; margin-bottom: 0.75rem; }

        .doctor-footer {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;
        }

        .doctor-fees { font-size: 1rem; font-weight: 700; color: #3b82f6; }

        .doctor-rating { display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: #f59e0b; }

        .action-buttons { display: flex; gap: 0.5rem; margin-top: 0.75rem; }

        .action-btn {
            flex: 1; padding: 0.5rem; text-align: center;
            border-radius: 0.5rem; text-decoration: none;
            font-size: 0.75rem; font-weight: 500; transition: all 0.3s;
            cursor: pointer; border: none; font-family: inherit;
        }

        .action-btn.edit  { background: #eef2ff; color: #3b82f6; }
        .action-btn.edit:hover  { background: #3b82f6; color: white; }
        .action-btn.delete { background: #fee2e2; color: #dc2626; }
        .action-btn.delete:hover { background: #dc2626; color: white; }

        .empty-state { text-align: center; padding: 3rem; color: #64748b; background: white; border-radius: 1rem; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }

        .mobile-menu-toggle {
            display: none; background: none; border: none; cursor: pointer;
            width: 40px; height: 40px; border-radius: 0.5rem;
            font-size: 1.25rem; color: #1f2937;
        }

        .sidebar-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 999; display: none;
        }

        .sidebar-overlay.active { display: block; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .mobile-menu-toggle { display: flex; align-items: center; justify-content: center; }
            .top-bar { flex-direction: column; gap: 1rem; text-align: center; }
            .user-info { width: 100%; justify-content: center; }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .stats-row { grid-template-columns: 1fr; }
            .doctors-grid { grid-template-columns: 1fr; }
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
                    <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
                    <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

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
                        <option value="available"   <?php echo $availability_filter == 'available'   ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?php echo $availability_filter == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>

                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by name, speciality, or degree..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>

                <?php if (!empty($speciality_filter) || !empty($availability_filter) || !empty($search)): ?>
                    <a href="doctors-list.php" class="reset-btn"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($doctors) > 0): ?>
            <div class="doctors-grid">
                <?php foreach ($doctors as $doctor): ?>
                    <div class="doctor-card" onclick="window.location.href='doctor-details.php?id=<?php echo $doctor['doctor_id']; ?>'">
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
                                <?php if (!empty($doctor['rating']) && $doctor['rating'] > 0): ?>
                                    <div class="doctor-rating">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo number_format($doctor['rating'], 1); ?></span>
                                        <span style="color: #94a3b8;">(<?php echo $doctor['total_reviews']; ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="action-buttons" onclick="event.stopPropagation()">
                                <a href="edit-doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="action-btn edit">
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
                <a href="add-doctor.php" class="reset-btn" style="display: inline-block; margin-top: 1rem;">
                    <i class="fas fa-plus"></i> Add New Doctor
                </a>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function closeMenu() { 
        if (sidebar) sidebar.classList.remove('open'); 
        if (overlay) overlay.classList.remove('active'); 
    }
    
    function openMenu() { 
        if (sidebar) sidebar.classList.add('open');    
        if (overlay) overlay.classList.add('active');    
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

    function deleteDoctor(doctorId) {
        if (confirm('Are you sure you want to delete this doctor? This action cannot be undone.')) {
            window.location.href = 'delete-doctor.php?id=' + doctorId;
        }
    }
</script>
</body>
</html>