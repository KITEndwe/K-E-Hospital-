<?php
// K&E Hospital - Admin Dashboard
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

    $total_appointments  = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    $total_doctors       = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
    $total_patients      = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    $pending_apts        = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();
    $today_apts          = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
    $completed_apts      = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn();

    /*
     * Monthly revenue — sums payments.amount WHERE payment_status = 'Completed'
     * AND the payment was made this calendar month.
     * Doctors mark appointments Completed → doctor-dashboard.php inserts into payments table.
     */
    $rev_stmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0)
        FROM payments
        WHERE payment_status = 'Completed'
          AND MONTH(payment_date) = MONTH(CURDATE())
          AND YEAR(payment_date)  = YEAR(CURDATE())
    ");
    $monthly_revenue = $rev_stmt->fetchColumn();

    /* Total all-time revenue */
    $total_rev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Completed'")->fetchColumn();

    /* Latest 10 appointments */
    $latest_stmt = $pdo->query("
        SELECT
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.payment_status,
            a.created_at,
            d.name          AS doctor_name,
            d.speciality,
            d.profile_image AS doctor_image,
            u.full_name     AS patient_name,
            u.profile_image AS patient_image
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users   u ON a.user_id   = u.user_id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $latest_appointments = $latest_stmt->fetchAll();

    /* Top 5 doctors by completed appointments */
    $top_stmt = $pdo->query("
        SELECT
            d.name,
            d.speciality,
            d.rating,
            COUNT(a.appointment_id)                                          AS total_apts,
            COUNT(CASE WHEN a.status = 'Completed' THEN 1 END)               AS completed_apts,
            COALESCE(SUM(CASE WHEN p.payment_status='Completed' THEN p.amount END),0) AS earned
        FROM doctors d
        LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
        LEFT JOIN payments     p ON a.appointment_id = p.appointment_id
        GROUP BY d.doctor_id
        ORDER BY completed_apts DESC
        LIMIT 5
    ");
    $top_doctors = $top_stmt->fetchAll();

} catch (PDOException $e) {
    $total_appointments = $total_doctors = $total_patients = $pending_apts = $today_apts = $completed_apts = 0;
    $monthly_revenue = $total_rev = 0;
    $latest_appointments = $top_doctors = array();
}

$admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';

/*
 * Image path resolver — admin panel is in /KE-Hospital/admin/
 * Doctor images stored as /assets/docX.png → ../frontend/assets/docX.png
 * Patient images stored as uploads/profiles/... → ../uploads/profiles/...
 */
function resolveImg($path, $type) {
    if (empty($path)) return '';
    $path = ltrim($path, '/');
    return ($type === 'doctor') ? '../frontend/' . $path : '../' . $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Admin Dashboard - K&amp;E Hospital</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Outfit',sans-serif;background:#f5f7fb;color:#1f2937;overflow-x:hidden;}
.dashboard-container{display:flex;min-height:100vh;}
a{text-decoration:none;color:inherit;}

/* ── Sidebar ── */
.sidebar{width:280px;background:#fff;box-shadow:2px 0 10px rgba(0,0,0,0.05);position:fixed;height:100vh;overflow-y:auto;transition:transform 0.3s ease-in-out;z-index:1000;}
.sidebar::-webkit-scrollbar{width:6px;}
.sidebar::-webkit-scrollbar-thumb{background:#3b82f6;border-radius:3px;}
.sidebar-header{padding:1.5rem;border-bottom:1px solid #e5e7eb;}
.sidebar-logo{font-size:1.4rem;font-weight:700;color:#3b82f6;display:flex;align-items:center;gap:0.75rem;}
.sidebar-logo i{font-size:1.6rem;}
.sidebar-nav{padding:1.5rem 0;}
.nav-item{padding:0.875rem 1.5rem;display:flex;align-items:center;gap:1rem;color:#6b7280;transition:all 0.3s;font-weight:500;border-right:3px solid transparent;}
.nav-item:hover,.nav-item.active{background:#eef2ff;color:#3b82f6;border-right-color:#3b82f6;}
.nav-item i{width:24px;font-size:1.1rem;}

/* ── Main ── */
.main-content{flex:1;margin-left:280px;padding:1.5rem;}
.top-bar{background:#fff;border-radius:1rem;padding:1rem 1.5rem;margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px rgba(0,0,0,0.05);border:1px solid #e2e8f0;flex-wrap:wrap;gap:1rem;}
.page-title{display:flex;align-items:center;gap:1rem;}
.page-title h1{font-size:1.5rem;font-weight:700;color:#0f172a;}
.page-title p{font-size:0.875rem;color:#64748b;margin-top:0.25rem;}
.user-info{display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;}
.admin-badge{display:flex;align-items:center;gap:0.75rem;background:#f1f5f9;padding:0.5rem 1rem;border-radius:2rem;}
.admin-avatar{width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;}
.admin-name{font-weight:500;color:#1e293b;}
.logout-btn{background:#ef4444;color:#fff;padding:0.5rem 1.25rem;border-radius:0.5rem;font-size:0.875rem;font-weight:500;transition:all 0.3s;display:inline-flex;align-items:center;gap:0.5rem;}
.logout-btn:hover{background:#dc2626;transform:translateY(-2px);}

/* ── Stats ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem;margin-bottom:2rem;}
.stat-card{background:#fff;border-radius:1rem;padding:1.4rem;border:1px solid #e2e8f0;transition:all 0.3s;position:relative;overflow:hidden;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(0,0,0,0.08);}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat-card.blue::before{background:#3b82f6;}
.stat-card.green::before{background:#10b981;}
.stat-card.purple::before{background:#8b5cf6;}
.stat-card.amber::before{background:#f59e0b;}
.stat-card.teal::before{background:#14b8a6;}
.stat-card.indigo::before{background:#6366f1;}
.stat-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;}
.stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;}
.stat-icon.blue{background:#eff6ff;color:#3b82f6;}
.stat-icon.green{background:#ecfdf5;color:#10b981;}
.stat-icon.purple{background:#f5f3ff;color:#8b5cf6;}
.stat-icon.amber{background:#fffbeb;color:#f59e0b;}
.stat-icon.teal{background:#f0fdfa;color:#14b8a6;}
.stat-icon.indigo{background:#eef2ff;color:#6366f1;}
.stat-value{font-size:2rem;font-weight:700;color:#0f172a;margin-bottom:0.2rem;}
.stat-label{font-size:0.8rem;color:#64748b;}
.stat-sub{font-size:0.72rem;color:#94a3b8;margin-top:0.2rem;}

/* ── Revenue highlight card ── */
.revenue-card{background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;}
.revenue-card .stat-value,.revenue-card .stat-label,.revenue-card .stat-sub{color:#fff;}
.revenue-card .stat-label{color:rgba(255,255,255,0.8);}
.revenue-card .stat-sub{color:rgba(255,255,255,0.65);}
.revenue-card .stat-icon{background:rgba(255,255,255,0.2);color:#fff;}

/* ── Section boxes ── */
.section-box{background:#fff;border-radius:1rem;padding:1.5rem;border:1px solid #e2e8f0;margin-bottom:2rem;}
.section-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.75rem;}
.section-head h2{font-size:1.1rem;font-weight:700;color:#0f172a;}
.section-head h2 i{color:#3b82f6;margin-right:0.5rem;}
.view-all{color:#3b82f6;font-size:0.875rem;font-weight:500;}
.view-all:hover{text-decoration:underline;}

/* ── Table ── */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
table{width:100%;border-collapse:collapse;}
th,td{padding:0.875rem 1rem;text-align:left;border-bottom:1px solid #f3f4f6;font-size:0.875rem;}
th{background:#f9fafb;font-weight:600;color:#6b7280;}
td{color:#374151;}
tbody tr:hover td{background:#fafbff;}

/* Avatar in table */
.tbl-avatar{width:36px;height:36px;border-radius:50%;overflow:hidden;background:#eef2ff;border:2px solid #e0e7ff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:#3b82f6;flex-shrink:0;}
.tbl-avatar img{width:100%;height:100%;object-fit:cover;object-position:top center;display:block;}
.person-cell{display:flex;align-items:center;gap:0.75rem;}
.person-details h4{font-weight:600;font-size:0.875rem;color:#0f172a;margin-bottom:0.15rem;}
.person-details p{font-size:0.75rem;color:#6b7280;}

/* Status badges */
.badge{display:inline-flex;align-items:center;padding:0.25rem 0.65rem;border-radius:9999px;font-size:0.72rem;font-weight:600;}
.badge-pending{background:#fef3c7;color:#d97706;}
.badge-confirmed{background:#d1fae5;color:#065f46;}
.badge-completed{background:#e0e7ff;color:#4338ca;}
.badge-cancelled{background:#fee2e2;color:#dc2626;}
.badge-paid{background:#d1fae5;color:#065f46;}
.badge-unpaid{background:#fef9c3;color:#854d0e;}

/* Empty state */
.empty-state{text-align:center;padding:3rem;color:#64748b;}
.empty-state i{font-size:2.5rem;margin-bottom:0.875rem;display:block;opacity:0.4;}

/* Top doctors table */
.dr-rating i{color:#f59e0b;font-size:0.7rem;}
.dr-earned{font-weight:700;color:#065f46;}

/* Mobile */
.mobile-menu-toggle{display:none;background:#f1f5f9;border:none;cursor:pointer;width:40px;height:40px;border-radius:0.5rem;font-size:1.25rem;color:#1f2937;align-items:center;justify-content:center;}
.sidebar-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;display:none;}
.sidebar-overlay.active{display:block;}

@media(max-width:1024px){.stats-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:768px){
    .sidebar{transform:translateX(-100%);position:fixed;z-index:1001;}
    .sidebar.open{transform:translateX(0);}
    .main-content{margin-left:0;padding:1rem;}
    .mobile-menu-toggle{display:flex;}
    .top-bar{flex-direction:column;align-items:stretch;}
    .page-title{justify-content:space-between;width:100%;}
    .user-info{width:100%;justify-content:space-between;}
    .stats-grid{grid-template-columns:repeat(2,1fr);gap:0.75rem;}
    table{min-width:560px;}
}
@media(max-width:480px){.stats-grid{grid-template-columns:repeat(2,1fr);}.stat-value{font-size:1.5rem;}}
</style>
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
            <a href="dashboard.php"    class="nav-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="appointments.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Appointments</span></a>
            <a href="add-doctor.php"   class="nav-item"><i class="fas fa-user-md"></i><span>Add Doctor</span></a>
            <a href="doctors-list.php" class="nav-item"><i class="fas fa-list"></i><span>Doctors List</span></a>
            <a href="patients.php"     class="nav-item"><i class="fas fa-users"></i><span>Patients</span></a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">

        <!-- Top bar -->
        <div class="top-bar">
            <div class="page-title">
                <button class="mobile-menu-toggle" id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</p>
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

        <!-- Stats grid -->
        <div class="stats-grid">

            <div class="stat-card blue">
                <div class="stat-top">
                    <div><div class="stat-value"><?php echo $total_appointments; ?></div><div class="stat-label">Total Appointments</div></div>
                    <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="stat-sub"><?php echo $today_apts; ?> today &bull; <?php echo $pending_apts; ?> pending</div>
            </div>

            <div class="stat-card green">
                <div class="stat-top">
                    <div><div class="stat-value"><?php echo $total_doctors; ?></div><div class="stat-label">Total Doctors</div></div>
                    <div class="stat-icon green"><i class="fas fa-user-md"></i></div>
                </div>
                <div class="stat-sub">Active specialists on platform</div>
            </div>

            <div class="stat-card purple">
                <div class="stat-top">
                    <div><div class="stat-value"><?php echo $total_patients; ?></div><div class="stat-label">Total Patients</div></div>
                    <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-sub">Registered active patients</div>
            </div>

            <div class="stat-card teal">
                <div class="stat-top">
                    <div><div class="stat-value"><?php echo $completed_apts; ?></div><div class="stat-label">Completed</div></div>
                    <div class="stat-icon teal"><i class="fas fa-clipboard-check"></i></div>
                </div>
                <div class="stat-sub">Appointments marked done by doctors</div>
            </div>

            <!-- Monthly revenue — highlighted blue gradient -->
            <div class="stat-card revenue-card">
                <div class="stat-top">
                    <div>
                        <div class="stat-value">K<?php echo number_format($monthly_revenue, 2); ?></div>
                        <div class="stat-label">This Month's Revenue</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                </div>
                <div class="stat-sub">All-time: K<?php echo number_format($total_rev, 2); ?></div>
            </div>

            <div class="stat-card amber">
                <div class="stat-top">
                    <div><div class="stat-value"><?php echo $pending_apts; ?></div><div class="stat-label">Pending</div></div>
                    <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-sub">Awaiting doctor confirmation</div>
            </div>

        </div>

        <!-- Latest appointments -->
        <div class="section-box">
            <div class="section-head">
                <h2><i class="fas fa-clock"></i> Latest Appointments</h2>
                <a href="appointments.php" class="view-all">View All &rarr;</a>
            </div>
            <?php if (!empty($latest_appointments)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date &amp; Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($latest_appointments as $apt):
                        $doc_img = resolveImg(isset($apt['doctor_image'])  ? $apt['doctor_image']  : '', 'doctor');
                        $pat_img = resolveImg(isset($apt['patient_image']) ? $apt['patient_image'] : '', 'patient');
                        $sc = array('Pending'=>'badge-pending','Confirmed'=>'badge-confirmed','Completed'=>'badge-completed','Cancelled'=>'badge-cancelled');
                        $s_cls = isset($sc[$apt['status']]) ? $sc[$apt['status']] : 'badge-pending';
                        $p_cls = ($apt['payment_status']==='Paid') ? 'badge-paid' : 'badge-unpaid';
                    ?>
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <div class="tbl-avatar">
                                        <?php if ($pat_img): ?>
                                            <img src="<?php echo htmlspecialchars($pat_img); ?>" alt=""
                                                 onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fas fa-user\'></i>';">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="person-details">
                                        <h4><?php echo htmlspecialchars($apt['patient_name']); ?></h4>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="person-cell">
                                    <div class="tbl-avatar">
                                        <?php if ($doc_img): ?>
                                            <img src="<?php echo htmlspecialchars($doc_img); ?>" alt=""
                                                 onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fas fa-user-md\'></i>';">
                                        <?php else: ?>
                                            <i class="fas fa-user-md"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="person-details">
                                        <h4><?php echo htmlspecialchars($apt['doctor_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($apt['speciality']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?><br>
                                <small style="color:#94a3b8;"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                            </td>
                            <td><span class="badge <?php echo $s_cls; ?>"><?php echo htmlspecialchars($apt['status']); ?></span></td>
                            <td><span class="badge <?php echo $p_cls; ?>"><?php echo htmlspecialchars($apt['payment_status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-calendar-alt"></i><p>No appointments yet.</p></div>
            <?php endif; ?>
        </div>

        <!-- Top doctors by completed appointments & earnings -->
        <div class="section-box">
            <div class="section-head">
                <h2><i class="fas fa-star"></i> Top Performing Doctors</h2>
                <a href="doctors-list.php" class="view-all">View All &rarr;</a>
            </div>
            <?php if (!empty($top_doctors)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Doctor</th>
                            <th>Speciality</th>
                            <th>Completed</th>
                            <th>Total Apts</th>
                            <th>Rating</th>
                            <th>Earned (K)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $rank=1; foreach ($top_doctors as $dr): ?>
                        <tr>
                            <td style="color:#94a3b8;font-weight:600;">#<?php echo $rank++; ?></td>
                            <td style="font-weight:600;color:#0f172a;"><?php echo htmlspecialchars($dr['name']); ?></td>
                            <td style="color:#6b7280;"><?php echo htmlspecialchars($dr['speciality']); ?></td>
                            <td><span class="badge badge-completed"><?php echo intval($dr['completed_apts']); ?></span></td>
                            <td><?php echo intval($dr['total_apts']); ?></td>
                            <td>
                                <span class="dr-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format(floatval($dr['rating']),1); ?>
                                </span>
                            </td>
                            <td class="dr-earned"><?php echo number_format(floatval($dr['earned']),2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-user-md"></i><p>No doctor data yet.</p></div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
(function(){
    var toggle  = document.getElementById('mobileMenuToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    function open(){sidebar.classList.add('open');overlay.classList.add('active');document.body.style.overflow='hidden';}
    function close(){sidebar.classList.remove('open');overlay.classList.remove('active');document.body.style.overflow='';}
    if(toggle)  toggle.addEventListener('click',function(e){e.stopPropagation();sidebar.classList.contains('open')?close():open();});
    if(overlay) overlay.addEventListener('click',close);
    document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
    window.addEventListener('resize',function(){if(window.innerWidth>768)close();});
})();
</script>
</body>
</html>