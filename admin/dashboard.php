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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            transition: all 0.3s;
            z-index: 100;
        }

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
        }

        .nav-item:hover, .nav-item.active {
            background: #eef2ff;
            color: #3b82f6;
            border-right: 3px solid #3b82f6;
        }

        .nav-item i {
            width: 24px;
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
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
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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

        /* Tables */
        .appointments-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .appointments-table {
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

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <i class="fas fa-hospital-user"></i>
                    <span>K&E Hospital</span>
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

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?= htmlspecialchars($admin_name) ?>!</p>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value"><?= $total_appointments ?></span>
                        <i class="fas fa-calendar-check" style="color: #3b82f6;"></i>
                    </div>
                    <div>Total Appointments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value"><?= $total_doctors ?></span>
                        <i class="fas fa-user-md" style="color: #10b981;"></i>
                    </div>
                    <div>Total Doctors</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value"><?= $total_patients ?></span>
                        <i class="fas fa-users" style="color: #8b5cf6;"></i>
                    </div>
                    <div>Total Patients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-value">$<?= number_format($monthly_revenue, 2) ?></span>
                        <i class="fas fa-dollar-sign" style="color: #f59e0b;"></i>
                    </div>
                    <div>Monthly Revenue</div>
                </div>
            </div>

            <div class="appointments-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Latest Appointments</h2>
                    <a href="appointments.php" class="view-all" style="color: #3b82f6;">View All →</a>
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
                    <p>No appointments found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>