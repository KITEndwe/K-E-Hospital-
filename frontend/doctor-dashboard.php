<?php
// K&E Hospital - Doctor Dashboard
session_start();

/* ── Auth guard ── */
if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor-login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$doctor_id   = $_SESSION['doctor_id'];
$doctor_name = isset($_SESSION['doctor_name']) ? $_SESSION['doctor_name'] : 'Doctor';

$doctor      = array();
$appointments = array();
$stats       = array('total'=>0,'pending'=>0,'confirmed'=>0,'completed'=>0,'cancelled'=>0);
$success_msg = '';
$error_msg   = '';

/* ── Filters ── */
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date   = isset($_GET['date'])   ? $_GET['date']   : '';
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /* Fetch doctor profile */
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
    $stmt->execute(array($doctor_id));
    $doctor = $stmt->fetch();

    if (!$doctor) {
        session_destroy();
        header('Location: doctor-login.php');
        exit();
    }

    /* ── Handle status update ── */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $apt_id     = intval($_POST['appointment_id']);
        $new_status = $_POST['new_status'];
        $allowed    = array('Confirmed','Completed','Cancelled');

        if (in_array($new_status, $allowed)) {
            $upd = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
            if ($upd->execute(array($new_status, $apt_id, $doctor_id))) {
                $success_msg = 'Appointment status updated to ' . $new_status . '.';
            } else {
                $error_msg = 'Failed to update appointment status.';
            }
        }
    }

    /* ── Fetch appointments with filters ── */
    $sql = "
        SELECT
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.payment_status,
            a.amount,
            a.symptoms,
            a.created_at,
            u.user_id,
            u.full_name      AS patient_name,
            u.email          AS patient_email,
            u.phone          AS patient_phone,
            u.profile_image  AS patient_image,
            u.gender,
            u.blood_group,
            TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS patient_age
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.doctor_id = ?
    ";
    $params = array($doctor_id);

    if (!empty($filter_status)) {
        $sql .= " AND a.status = ?";
        $params[] = $filter_status;
    }
    if (!empty($filter_date)) {
        $sql .= " AND a.appointment_date = ?";
        $params[] = $filter_date;
    }
    if (!empty($filter_search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = '%' . $filter_search . '%';
        $params[] = '%' . $filter_search . '%';
    }

    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute($params);
    $appointments = $stmt2->fetchAll();

    /* ── Stats (always unfiltered) ── */
    $stat_stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM appointments WHERE doctor_id = ? GROUP BY status
    ");
    $stat_stmt->execute(array($doctor_id));
    foreach ($stat_stmt->fetchAll() as $row) {
        $stats['total'] += $row['cnt'];
        $key = strtolower($row['status']);
        if (isset($stats[$key])) $stats[$key] = $row['cnt'];
    }

} catch (PDOException $e) {
    $error_msg = 'Database error: ' . $e->getMessage();
}

/* ── Resolve patient image path ── */
/* This file is in frontend/ so patient uploads (uploads/profiles/...) need ../ prefix */
function resolvePatientImg($path) {
    if (empty($path)) return '';
    $path = ltrim($path, '/');
    /* uploaded: uploads/profiles/user_X_xx.jpg → ../uploads/profiles/... */
    /* default:  frontend/assets/profile_pic.png → ../frontend/assets/... */
    return '../' . $path;
}

$doc_img = ltrim(isset($doctor['profile_image']) ? $doctor['profile_image'] : '', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior:smooth; }
body { font-family:'Outfit',sans-serif; background:#f5f7fb; color:#1e293b; min-height:100vh; }
a { text-decoration:none; color:inherit; }
img { display:block; max-width:100%; }

/* ── LAYOUT ── */
.layout { display:flex; min-height:100vh; }

/* ── SIDEBAR ── */
.sidebar {
    width:260px; background:#fff; flex-shrink:0;
    box-shadow:2px 0 12px rgba(0,0,0,0.06);
    position:fixed; height:100vh; overflow-y:auto;
    transition:transform 0.3s ease; z-index:200;
}
.sidebar::-webkit-scrollbar { width:4px; }
.sidebar::-webkit-scrollbar-thumb { background:#5f6fff; border-radius:4px; }

.sidebar-brand {
    padding:1.5rem 1.25rem; border-bottom:1px solid #f0f0f5;
    display:flex; align-items:center; gap:0.75rem;
}
.brand-icon {
    width:38px; height:38px; background:#5f6fff; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:1.1rem; flex-shrink:0;
}
.brand-name { font-size:1.1rem; font-weight:700; color:#1a1a2e; }

/* Doctor profile card in sidebar */
.doc-profile-card {
    padding:1.25rem; border-bottom:1px solid #f0f0f5;
    text-align:center;
}
.doc-avatar-wrap {
    width:72px; height:72px; border-radius:50%; overflow:hidden;
    margin:0 auto 0.75rem;
    background:linear-gradient(160deg,#dce3ff,#c8d0ff);
    border:3px solid #e0e3ff;
    display:flex; align-items:center; justify-content:center;
    font-size:1.75rem; font-weight:700; color:#5f6fff;
}
.doc-avatar-wrap img { width:100%; height:100%; object-fit:cover; object-position:top center; }
.doc-profile-name  { font-size:0.9rem; font-weight:700; color:#1a1a2e; margin-bottom:0.2rem; }
.doc-profile-spec  { font-size:0.75rem; color:#5f6fff; font-weight:500; margin-bottom:0.5rem; }
.doc-avail-badge {
    display:inline-flex; align-items:center; gap:4px;
    background:#d1fae5; color:#065f46; padding:0.2rem 0.75rem;
    border-radius:50px; font-size:0.7rem; font-weight:600;
}
.doc-avail-badge::before {
    content:''; width:6px; height:6px; border-radius:50%;
    background:#22c55e; display:block; animation:pulse 2s infinite;
}
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.4;} }

/* Sidebar nav */
.sidebar-nav { padding:1rem 0; }
.nav-section-label {
    padding:0.5rem 1.25rem; font-size:0.68rem; font-weight:700;
    color:#b0b7c3; text-transform:uppercase; letter-spacing:0.08em;
}
.nav-link {
    display:flex; align-items:center; gap:0.875rem;
    padding:0.75rem 1.25rem; color:#64748b; font-weight:500; font-size:0.875rem;
    transition:all 0.2s; border-left:3px solid transparent;
}
.nav-link:hover { background:#f5f5ff; color:#5f6fff; border-left-color:#c5caff; }
.nav-link.active { background:#eef0ff; color:#5f6fff; border-left-color:#5f6fff; font-weight:600; }
.nav-link i { width:18px; text-align:center; font-size:0.9rem; }
.nav-link .badge {
    margin-left:auto; background:#5f6fff; color:#fff;
    font-size:0.65rem; font-weight:700; padding:0.15rem 0.5rem;
    border-radius:50px; min-width:20px; text-align:center;
}

/* ── MAIN ── */
.main { flex:1; margin-left:260px; padding:1.5rem; }

/* ── TOP BAR ── */
.topbar {
    background:#fff; border-radius:14px; padding:1rem 1.5rem;
    display:flex; justify-content:space-between; align-items:center;
    box-shadow:0 1px 4px rgba(0,0,0,0.05); border:1px solid #e8eaf0;
    margin-bottom:1.75rem; flex-wrap:wrap; gap:0.75rem;
}
.topbar-left { display:flex; align-items:center; gap:0.875rem; }
.hamburger-btn {
    display:none; width:38px; height:38px; border:none; background:#f5f5ff;
    border-radius:8px; cursor:pointer; font-size:1.1rem; color:#5f6fff;
    align-items:center; justify-content:center; flex-shrink:0;
}
.topbar h1 { font-size:1.3rem; font-weight:700; color:#0f172a; }
.topbar-right { display:flex; align-items:center; gap:0.875rem; }
.topbar-date { font-size:0.82rem; color:#94a3b8; }
.logout-link {
    display:inline-flex; align-items:center; gap:0.4rem;
    background:#fee2e2; color:#ef4444; padding:0.45rem 1rem;
    border-radius:8px; font-size:0.82rem; font-weight:600; transition:all 0.2s;
}
.logout-link:hover { background:#ef4444; color:#fff; }

/* ── ALERTS ── */
.alert {
    padding:0.875rem 1.1rem; border-radius:10px; margin-bottom:1.25rem;
    display:flex; align-items:center; gap:0.6rem; font-size:0.875rem; font-weight:500;
}
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

/* ── STATS ── */
.stats-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:1rem; margin-bottom:1.75rem; }
.stat-card {
    background:#fff; border-radius:14px; padding:1.1rem;
    border:1px solid #e8eaf0; transition:all 0.25s; text-align:center;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(95,111,255,0.1); }
.stat-num   { font-size:1.85rem; font-weight:700; color:#0f172a; line-height:1; margin-bottom:0.3rem; }
.stat-label { font-size:0.72rem; color:#94a3b8; font-weight:500; text-transform:uppercase; letter-spacing:0.05em; }

/* ── FILTERS ── */
.filters-bar {
    background:#fff; border-radius:14px; padding:1rem 1.25rem;
    margin-bottom:1.25rem; display:flex; gap:0.875rem; flex-wrap:wrap;
    align-items:center; border:1px solid #e8eaf0;
}
.filter-group { display:flex; align-items:center; gap:0.5rem; }
.filter-group label { font-size:0.8rem; font-weight:600; color:#64748b; }
.filter-group select,
.filter-group input[type="date"] {
    padding:0.45rem 0.875rem; border:1px solid #e2e8f0;
    border-radius:8px; font-family:'Outfit',sans-serif; font-size:0.82rem;
    background:#fff; color:#1e293b; transition:border-color 0.2s;
}
.filter-group select:focus,
.filter-group input[type="date"]:focus { outline:none; border-color:#5f6fff; }
.search-wrap { flex:1; display:flex; gap:0.5rem; min-width:200px; }
.search-wrap input {
    flex:1; padding:0.45rem 0.875rem; border:1px solid #e2e8f0;
    border-radius:8px; font-size:0.82rem; font-family:'Outfit',sans-serif;
}
.search-wrap input:focus { outline:none; border-color:#5f6fff; }
.btn-search {
    padding:0.45rem 0.875rem; background:#5f6fff; color:#fff;
    border:none; border-radius:8px; cursor:pointer; font-size:0.82rem; transition:background 0.2s;
}
.btn-search:hover { background:#4a5af0; }
.btn-reset {
    padding:0.45rem 0.875rem; background:#f1f5f9; color:#64748b;
    border:none; border-radius:8px; cursor:pointer; font-size:0.82rem;
    text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; transition:background 0.2s;
}
.btn-reset:hover { background:#e2e8f0; }

/* ── APPOINTMENTS TABLE CARD ── */
.table-card { background:#fff; border-radius:14px; border:1px solid #e8eaf0; overflow:hidden; }
.table-card-header {
    padding:1rem 1.25rem; border-bottom:1px solid #f0f0f5;
    display:flex; align-items:center; justify-content:space-between;
}
.table-card-header h2 { font-size:1rem; font-weight:700; color:#0f172a; }
.table-card-header span { font-size:0.78rem; color:#94a3b8; }
.table-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }

table { width:100%; border-collapse:collapse; }
thead th {
    text-align:left; padding:0.875rem 1rem;
    background:#f8fafc; font-weight:600; font-size:0.78rem;
    color:#64748b; border-bottom:1px solid #e8eaf0;
    white-space:nowrap;
}
tbody td { padding:0.875rem 1rem; border-bottom:1px solid #f5f5fa; font-size:0.82rem; vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr:hover td { background:#fafbff; }

/* Patient info cell */
.patient-cell { display:flex; align-items:center; gap:0.75rem; }
.pat-avatar {
    width:40px; height:40px; border-radius:50%; overflow:hidden;
    background:#eef0ff; border:2px solid #e0e3ff; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.875rem; font-weight:700; color:#5f6fff;
}
.pat-avatar img { width:100%; height:100%; object-fit:cover; object-position:top center; display:block; }
.pat-name  { font-weight:600; color:#0f172a; margin-bottom:0.15rem; }
.pat-meta  { font-size:0.72rem; color:#94a3b8; }

/* Status badges */
.badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:0.25rem 0.65rem; border-radius:50px;
    font-size:0.72rem; font-weight:600; white-space:nowrap;
}
.badge-pending   { background:#fef3c7; color:#d97706; }
.badge-confirmed { background:#d1fae5; color:#065f46; }
.badge-completed { background:#e0e7ff; color:#4338ca; }
.badge-cancelled { background:#fee2e2; color:#dc2626; }
.badge-paid      { background:#d1fae5; color:#065f46; }
.badge-unpaid    { background:#fef9c3; color:#854d0e; }

/* Action dropdown */
.action-wrap { position:relative; display:inline-block; }
.btn-action {
    display:inline-flex; align-items:center; gap:0.4rem;
    padding:0.4rem 0.875rem; border:1px solid #e2e8f0; border-radius:8px;
    background:#fff; font-size:0.78rem; font-weight:500; color:#3c3c3c;
    cursor:pointer; transition:all 0.2s; font-family:'Outfit',sans-serif;
}
.btn-action:hover { border-color:#5f6fff; color:#5f6fff; background:#f5f5ff; }
.action-menu {
    position:absolute; right:0; top:calc(100% + 4px);
    background:#fff; border:1px solid #e8eaf0; border-radius:10px;
    box-shadow:0 8px 24px rgba(0,0,0,0.1); min-width:160px;
    z-index:100; padding:0.3rem 0;
    display:none;
}
.action-menu.open { display:block; }
.action-menu-item {
    display:flex; align-items:center; gap:0.6rem;
    padding:0.55rem 1rem; font-size:0.82rem; font-weight:500; color:#3c3c3c;
    cursor:pointer; transition:background 0.15s; border:none; background:none;
    width:100%; text-align:left; font-family:'Outfit',sans-serif;
}
.action-menu-item:hover { background:#f5f5ff; color:#5f6fff; }
.action-menu-item i { width:14px; text-align:center; }
.action-menu-item.confirm  i { color:#065f46; }
.action-menu-item.complete i { color:#4338ca; }
.action-menu-item.cancel   i { color:#dc2626; }
.action-menu-item.cancel:hover { background:#fff5f5; color:#dc2626; }

/* Symptoms tooltip */
.symptoms-cell { max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#64748b; }

/* Empty state */
.empty-state { text-align:center; padding:3.5rem 2rem; color:#94a3b8; }
.empty-state i { font-size:2.5rem; margin-bottom:0.875rem; display:block; opacity:0.4; }

/* Overlay */
.sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.28); z-index:199; }
.sidebar-overlay.active { display:block; }

/* ── RESPONSIVE ── */
@media (max-width:1200px) { .stats-grid { grid-template-columns:repeat(3,1fr); } }
@media (max-width:900px)  { .stats-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:768px) {
    .sidebar { transform:translateX(-100%); }
    .sidebar.open { transform:translateX(0); }
    .main { margin-left:0; padding:1rem; }
    .hamburger-btn { display:flex; }
    .stats-grid { grid-template-columns:repeat(2,1fr); gap:0.75rem; }
    .filters-bar { flex-direction:column; align-items:stretch; }
    .filter-group { justify-content:space-between; }
    .search-wrap { width:100%; }
    table { min-width:700px; }
}
@media (max-width:480px) {
    .stats-grid { grid-template-columns:repeat(2,1fr); }
    .stat-num { font-size:1.5rem; }
    .topbar h1 { font-size:1.1rem; }
}
</style>
</head>
<body>
<div class="layout">

    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div ><img src="assets/logo.svg" width="100px" alt=""></div>
            
        </div>

        <!-- Doctor profile -->
        <div class="doc-profile-card">
            <div class="doc-avatar-wrap">
                <?php if ($doc_img): ?>
                    <img src="<?php echo htmlspecialchars($doc_img); ?>"
                         alt="<?php echo htmlspecialchars($doctor_name); ?>"
                         onerror="this.style.display='none';this.parentElement.textContent='<?php echo strtoupper(substr($doctor_name,0,1)); ?>';">
                <?php else: ?>
                    <?php echo strtoupper(substr($doctor_name,0,1)); ?>
                <?php endif; ?>
            </div>
            <div class="doc-profile-name"><?php echo htmlspecialchars(isset($doctor['name']) ? $doctor['name'] : $doctor_name); ?></div>
            <div class="doc-profile-spec"><?php echo htmlspecialchars(isset($doctor['speciality']) ? $doctor['speciality'] : ''); ?></div>
            <span class="doc-avail-badge">Available</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="doctor-dashboard.php" class="nav-link active">
                <i class="fas fa-calendar-check"></i> My Appointments
                <?php if ($stats['pending'] > 0): ?>
                <span class="badge"><?php echo $stats['pending']; ?></span>
                <?php endif; ?>
            </a>
            <a href="doctor-profile.php" class="nav-link">
                <i class="fas fa-user-circle"></i> My Profile
            </a>

            <div class="nav-section-label" style="margin-top:0.5rem;">Account</div>
            <a href="logout.php" class="nav-link" style="color:#ef4444;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ══ MAIN CONTENT ══ -->
    <main class="main">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="hamburger-btn" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
                <h1>My Appointments</h1>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?php echo date('l, d F Y'); ?></span>
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-num"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-num" style="color:#d97706;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-num" style="color:#065f46;"><?php echo $stats['confirmed']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-num" style="color:#4338ca;"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-num" style="color:#dc2626;"><?php echo $stats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form method="GET" style="display:contents;">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="Pending"   <?php echo $filter_status==='Pending'   ?'selected':''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo $filter_status==='Confirmed' ?'selected':''; ?>>Confirmed</option>
                        <option value="Completed" <?php echo $filter_status==='Completed' ?'selected':''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $filter_status==='Cancelled' ?'selected':''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit()">
                </div>
                <div class="search-wrap">
                    <input type="text" name="search" placeholder="Search patient name or email..."
                           value="<?php echo htmlspecialchars($filter_search); ?>">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </div>
                <?php if (!empty($filter_status) || !empty($filter_date) || !empty($filter_search)): ?>
                <a href="doctor-dashboard.php" class="btn-reset"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Appointments table -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>Appointments</h2>
                <span><?php echo count($appointments); ?> record<?php echo count($appointments)!==1?'s':''; ?></span>
            </div>

            <?php if (!empty($appointments)): ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Date &amp; Time</th>
                            <th>Symptoms</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Fees (K)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $n = 1; foreach ($appointments as $apt): ?>
                    <?php
                        $pat_img_raw = isset($apt['patient_image']) ? $apt['patient_image'] : '';
                        $pat_img     = !empty($pat_img_raw) ? resolvePatientImg($pat_img_raw) : '';
                        $initials    = strtoupper(substr(isset($apt['patient_name']) ? $apt['patient_name'] : 'P', 0, 1));

                        $sc = array(
                            'Pending'  =>'badge-pending','Confirmed'=>'badge-confirmed',
                            'Completed'=>'badge-completed','Cancelled'=>'badge-cancelled'
                        );
                        $s_cls = isset($sc[$apt['status']]) ? $sc[$apt['status']] : 'badge-pending';
                        $p_cls = ($apt['payment_status']==='Paid') ? 'badge-paid' : 'badge-unpaid';

                        $apt_date = date('M d, Y', strtotime($apt['appointment_date']));
                        $apt_time = date('h:i A', strtotime($apt['appointment_time']));
                        $uid      = $apt['appointment_id'];
                    ?>
                        <tr>
                            <td style="color:#b0b7c3;font-weight:500;"><?php echo $n++; ?></td>

                            <!-- Patient -->
                            <td>
                                <div class="patient-cell">
                                    <div class="pat-avatar" id="pava-<?php echo $uid; ?>">
                                        <?php if ($pat_img): ?>
                                            <img src="<?php echo htmlspecialchars($pat_img); ?>"
                                                 alt="<?php echo htmlspecialchars($apt['patient_name']); ?>"
                                                 onerror="this.style.display='none';this.parentElement.textContent='<?php echo $initials; ?>';">
                                        <?php else: ?>
                                            <?php echo $initials; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="pat-name"><?php echo htmlspecialchars($apt['patient_name']); ?></div>
                                        <div class="pat-meta">
                                            <?php echo $apt['patient_age'] ? intval($apt['patient_age']).' yrs' : ''; ?>
                                            <?php if (!empty($apt['gender'])): ?>
                                                <?php echo $apt['patient_age'] ? ' &bull; ' : ''; ?>
                                                <?php echo htmlspecialchars($apt['gender']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($apt['blood_group'])): ?>
                                                &bull; <?php echo htmlspecialchars($apt['blood_group']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pat-meta" style="margin-top:1px;"><?php echo htmlspecialchars($apt['patient_email']); ?></div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span style="font-weight:600;color:#0f172a;"><?php echo $apt_date; ?></span><br>
                                <span style="font-size:0.75rem;color:#94a3b8;"><?php echo $apt_time; ?></span>
                            </td>

                            <td>
                                <div class="symptoms-cell" title="<?php echo htmlspecialchars(isset($apt['symptoms']) ? $apt['symptoms'] : ''); ?>">
                                    <?php echo !empty($apt['symptoms']) ? htmlspecialchars($apt['symptoms']) : '<span style="color:#d1d5db;">—</span>'; ?>
                                </div>
                            </td>

                            <td><span class="badge <?php echo $s_cls; ?>"><?php echo htmlspecialchars($apt['status']); ?></span></td>

                            <td><span class="badge <?php echo $p_cls; ?>"><?php echo htmlspecialchars($apt['payment_status']); ?></span></td>

                            <td style="font-weight:600;"><?php echo number_format(isset($apt['amount']) && $apt['amount'] > 0 ? $apt['amount'] : (isset($doctor['fees']) ? $doctor['fees'] : 0), 2); ?></td>

                            <!-- Action dropdown -->
                            <td>
                                <?php if ($apt['status'] !== 'Cancelled' && $apt['status'] !== 'Completed'): ?>
                                <div class="action-wrap">
                                    <button class="btn-action" onclick="toggleMenu(<?php echo $uid; ?>)">
                                        Update <i class="fas fa-chevron-down" style="font-size:0.65rem;"></i>
                                    </button>
                                    <div class="action-menu" id="menu-<?php echo $uid; ?>">

                                        <?php if ($apt['status'] === 'Pending'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $uid; ?>">
                                            <input type="hidden" name="new_status" value="Confirmed">
                                            <button type="submit" name="update_status" class="action-menu-item confirm">
                                                <i class="fas fa-check-circle"></i> Confirm
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if ($apt['status'] === 'Confirmed'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $uid; ?>">
                                            <input type="hidden" name="new_status" value="Completed">
                                            <button type="submit" name="update_status" class="action-menu-item complete">
                                                <i class="fas fa-clipboard-check"></i> Mark Completed
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $uid; ?>">
                                            <input type="hidden" name="new_status" value="Cancelled">
                                            <button type="submit" name="update_status" class="action-menu-item cancel"
                                                    onclick="return confirm('Cancel this appointment?')">
                                                <i class="fas fa-times-circle"></i> Cancel
                                            </button>
                                        </form>

                                    </div>
                                </div>
                                <?php else: ?>
                                    <span style="font-size:0.75rem;color:#b0b7c3;">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>No appointments found<?php echo !empty($filter_status)||!empty($filter_date)||!empty($filter_search) ? ' matching your filters' : ''; ?>.</p>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
/* ── Sidebar hamburger ── */
(function() {
    var btn     = document.getElementById('hamburgerBtn');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('active');    document.body.style.overflow='hidden'; }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); document.body.style.overflow=''; }

    if (btn)     btn.addEventListener('click',  function(){ sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
    if (overlay) overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeSidebar(); });
    window.addEventListener('resize',    function(){ if(window.innerWidth > 768) closeSidebar(); });
})();

/* ── Action dropdown toggle ── */
var openMenuId = null;

function toggleMenu(id) {
    var menu = document.getElementById('menu-' + id);
    if (!menu) return;
    if (openMenuId && openMenuId !== id) {
        var prev = document.getElementById('menu-' + openMenuId);
        if (prev) prev.classList.remove('open');
    }
    menu.classList.toggle('open');
    openMenuId = menu.classList.contains('open') ? id : null;
}

/* Close dropdown when clicking outside */
document.addEventListener('click', function(e) {
    if (openMenuId !== null) {
        var menu = document.getElementById('menu-' + openMenuId);
        if (menu && !menu.closest('.action-wrap').contains(e.target)) {
            menu.classList.remove('open');
            openMenuId = null;
        }
    }
});
</script>
</body>
</html>