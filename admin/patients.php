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
    <link rel="stylesheet" href="./css/pantients.css">
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