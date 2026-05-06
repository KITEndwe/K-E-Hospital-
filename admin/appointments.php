<?php
// K&E Hospital - All Appointments Page
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../frontend/login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter   = isset($_GET['date'])   ? $_GET['date']   : '';
$search        = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "
    SELECT
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.payment_status,
        a.amount,
        a.created_at,
        d.doctor_id,
        d.name          AS doctor_name,
        d.speciality,
        d.profile_image AS doctor_image,
        d.fees,
        u.user_id,
        u.full_name     AS patient_name,
        u.email         AS patient_email,
        u.phone         AS patient_phone,
        u.profile_image AS patient_image,
        TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS patient_age
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users   u ON a.user_id   = u.user_id
    WHERE 1=1
";

$params = array();

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

$total_appointments = count($appointments);
$pending_count = $confirmed_count = $completed_count = $cancelled_count = 0;
foreach ($appointments as $app) {
    switch ($app['status']) {
        case 'Pending':   $pending_count++;   break;
        case 'Confirmed': $confirmed_count++; break;
        case 'Completed': $completed_count++; break;
        case 'Cancelled': $cancelled_count++; break;
    }
}

$admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';

/*
 * IMAGE PATH RESOLVER
 * ─────────────────────────────────────────────────────────────
 * This file lives at:  /KE-Hospital/admin/appointments.php
 *
 * Doctor images in DB:  /assets/doc1.png
 *   → served as:        ../frontend/assets/doc1.png
 *
 * Patient images in DB: uploads/profiles/user_1_xxx.jpg
 *   (stored relative to project root by my-profile.php)
 *   → served as:        ../uploads/profiles/user_1_xxx.jpg
 *
 * Default patient image from users table default:
 *   /frontend/assets/profile_pic.png
 *   → served as:        ../frontend/assets/profile_pic.png
 */
function resolveImage($path, $type = 'doctor') {
    if (empty($path)) return '';

    // Already an absolute URL
    if (strpos($path, 'http') === 0) return $path;

    // Strip any leading slash for uniform processing
    $path = ltrim($path, '/');

    // Doctor images: stored as  assets/docX.png  (after stripping /)
    // They live in  frontend/assets/  so prefix with  ../frontend/
    if ($type === 'doctor') {
        // e.g. assets/doc1.png  →  ../frontend/assets/doc1.png
        return '../frontend/' . $path;
    }

    // Patient images: two cases
    // Case 1: uploaded profile  →  uploads/profiles/user_1_xxx.jpg
    //         lives at project root so prefix ../
    // Case 2: default           →  frontend/assets/profile_pic.png
    //         also from project root so prefix ../
    if ($type === 'patient') {
        return '../' . $path;
    }

    return '../frontend/' . $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>All Appointments - K&amp;E Hospital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./css/appointment.css">
</head>
<body>
<div class="dashboard-container">

    <!-- ── Sidebar ── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <img src="assets/admin_logo.svg" width="150px" alt="">
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"   class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="appointments.php" class="nav-item active"><i class="fas fa-calendar-alt"></i><span>Appointments</span></a>
            <a href="add-doctor.php"  class="nav-item"><i class="fas fa-user-md"></i><span>Add Doctor</span></a>
            <a href="doctors-list.php" class="nav-item"><i class="fas fa-list"></i><span>Doctors List</span></a>
            <a href="patients.php"    class="nav-item"><i class="fas fa-users"></i><span>Patients</span></a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">

        <!-- Top bar -->
        <div class="top-bar">
            <div class="page-title">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1>All Appointments</h1>
                    <p>Manage and track all patient appointments</p>
                </div>
            </div>
            <div class="user-info">
                <div class="admin-badge">
                    <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
                    <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card-sm">
                <div class="stat-number"><?php echo $total_appointments; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card-sm">
                <div class="stat-number" style="color:#d97706;"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card-sm">
                <div class="stat-number" style="color:#065f46;"><?php echo $confirmed_count; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card-sm">
                <div class="stat-number" style="color:#4338ca;"><?php echo $completed_count; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form method="GET" style="display:contents;">
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="Pending"   <?php echo $status_filter==='Pending'   ?'selected':''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo $status_filter==='Confirmed' ?'selected':''; ?>>Confirmed</option>
                        <option value="Completed" <?php echo $status_filter==='Completed' ?'selected':''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $status_filter==='Cancelled' ?'selected':''; ?>>Cancelled</option>
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
                    <a href="appointments.php" class="reset-btn"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="table-container">
            <?php if (!empty($appointments)): ?>
            <div class="appointments-table">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Speciality</th>
                            <th>Date &amp; Time</th>
                            <th>Fees</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $n = 1; foreach ($appointments as $apt): ?>
                    <?php
                        /* ── Resolve doctor image ── */
                        $doc_img_raw = isset($apt['doctor_image']) ? $apt['doctor_image'] : '';
                        $doc_img     = resolveImage($doc_img_raw, 'doctor');

                        /* ── Resolve patient image ── */
                        $pat_img_raw = isset($apt['patient_image']) ? $apt['patient_image'] : '';
                        $pat_img     = '';
                        if (!empty($pat_img_raw)) {
                            /* Default value in DB schema is /frontend/assets/profile_pic.png
                               Uploaded images are stored as uploads/profiles/... */
                            $pat_img = resolveImage(ltrim($pat_img_raw, '/'), 'patient');
                        }

                        /* Status CSS class */
                        $sc = array(
                            'Pending'   => 'status-pending',
                            'Confirmed' => 'status-confirmed',
                            'Completed' => 'status-completed',
                            'Cancelled' => 'status-cancelled',
                        );
                        $status_cls = isset($sc[$apt['status']]) ? $sc[$apt['status']] : 'status-pending';

                        /* Payment CSS class */
                        $pc = array('Paid'=>'pay-paid','Pending'=>'pay-pending','Refunded'=>'pay-refunded');
                        $pay_cls = isset($pc[$apt['payment_status']]) ? $pc[$apt['payment_status']] : 'pay-pending';
                        $pay_lbl = isset($apt['payment_status']) ? $apt['payment_status'] : 'Pending';
                    ?>
                        <tr>
                            <td style="color:#94a3b8;font-weight:500;"><?php echo $n++; ?></td>

                            <!-- Patient -->
                            <td>
                                <div class="person-info">
                                    <div class="avatar" id="pat-ava-<?php echo $apt['appointment_id']; ?>">
                                        <?php if ($pat_img): ?>
                                            <img
                                                src="<?php echo htmlspecialchars($pat_img); ?>"
                                                alt="<?php echo htmlspecialchars($apt['patient_name']); ?>"
                                                onerror="this.style.display='none';document.getElementById('pat-fb-<?php echo $apt['appointment_id']; ?>').style.display='flex';">
                                            <div id="pat-fb-<?php echo $apt['appointment_id']; ?>" class="avatar-fallback" style="display:none;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="avatar-fallback"><i class="fas fa-user"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="person-details">
                                        <h4><?php echo htmlspecialchars($apt['patient_name']); ?></h4>
                                        <p><?php echo $apt['patient_age'] ? intval($apt['patient_age']).' yrs' : $apt['patient_email']; ?></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Doctor -->
                            <td>
                                <div class="person-info">
                                    <div class="avatar" id="doc-ava-<?php echo $apt['appointment_id']; ?>">
                                        <?php if ($doc_img): ?>
                                            <img
                                                src="<?php echo htmlspecialchars($doc_img); ?>"
                                                alt="<?php echo htmlspecialchars($apt['doctor_name']); ?>"
                                                onerror="this.style.display='none';document.getElementById('doc-fb-<?php echo $apt['appointment_id']; ?>').style.display='flex';">
                                            <div id="doc-fb-<?php echo $apt['appointment_id']; ?>" class="avatar-fallback" style="display:none;">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="avatar-fallback"><i class="fas fa-user-md"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="person-details">
                                        <h4><?php echo htmlspecialchars($apt['doctor_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($apt['speciality']); ?></p>
                                    </div>
                                </div>
                            </td>

                            <td><?php echo htmlspecialchars($apt['speciality']); ?></td>

                            <td>
                                <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?><br>
                                <small style="color:#64748b;"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                            </td>

                            <td style="font-weight:600;">K<?php echo number_format($apt['fees'], 2); ?></td>

                            <td>
                                <span class="status-badge <?php echo $status_cls; ?>">
                                    <?php echo htmlspecialchars($apt['status']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="pay-badge <?php echo $pay_cls; ?>">
                                    <?php echo htmlspecialchars($pay_lbl); ?>
                                </span>
                            </td>

                            <td>
                                <div class="action-buttons">
                                    <a href="patients.php" class="action-btn" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="action-btn delete" onclick="deleteAppointment(<?php echo $apt['appointment_id']; ?>)" title="Delete">
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
                    <p>No appointments found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
(function() {
    var toggle  = document.getElementById('mobileMenuToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function open()  { sidebar.classList.add('open');    overlay.classList.add('active');    document.body.style.overflow='hidden'; }
    function close() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow=''; }

    if (toggle)  toggle.addEventListener('click',  function(e){ e.stopPropagation(); sidebar.classList.contains('open') ? close() : open(); });
    if (overlay) overlay.addEventListener('click',  close);
    document.addEventListener('keydown', function(e){ if (e.key==='Escape') close(); });
    window.addEventListener('resize',   function()  { if (window.innerWidth > 768) close(); });
})();

function deleteAppointment(id) {
    if (confirm('Are you sure you want to delete this appointment? This cannot be undone.')) {
        window.location.href = 'delete-appointment.php?id=' + id;
    }
}
</script>
</body>
</html>