<?php
session_start();

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

$doctor       = array();
$appointments = array();
$stats        = array('total'=>0,'pending'=>0,'confirmed'=>0,'completed'=>0,'cancelled'=>0);
$success_msg  = '';
$error_msg    = '';

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date   = isset($_GET['date'])   ? $_GET['date']   : '';
$filter_search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /* Doctor profile */
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
    $stmt->execute(array($doctor_id));
    $doctor = $stmt->fetch();
    if (!$doctor) { session_destroy(); header('Location: doctor-login.php'); exit(); }

    /* ══════════════════════════════════════════════════
       HANDLE STATUS UPDATE
       When doctor marks "Completed" → also insert/update
       a payment record so admin revenue dashboard shows it.
    ══════════════════════════════════════════════════ */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $apt_id     = intval($_POST['appointment_id']);
        $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
        $allowed    = array('Confirmed', 'Completed', 'Cancelled');

        if (in_array($new_status, $allowed) && $apt_id > 0) {

            /* Fetch the appointment to get fees / user_id */
            $apt_chk = $pdo->prepare("
                SELECT a.appointment_id, a.user_id, a.doctor_id, a.amount, a.payment_status, d.fees
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.doctor_id
                WHERE a.appointment_id = ? AND a.doctor_id = ?
            ");
            $apt_chk->execute(array($apt_id, $doctor_id));
            $apt_row = $apt_chk->fetch();

            if ($apt_row) {
                $pdo->beginTransaction();
                try {
                    /* 1. Update appointment status */
                    $upd = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
                    $upd->execute(array($new_status, $apt_id, $doctor_id));

                    /* 2. If Completed → create/update payment record */
                    if ($new_status === 'Completed') {
                        $fee = ($apt_row['amount'] > 0) ? $apt_row['amount'] : $apt_row['fees'];

                        /* Update appointment payment_status to Paid */
                        $upd2 = $pdo->prepare("UPDATE appointments SET payment_status = 'Paid', amount = ? WHERE appointment_id = ?");
                        $upd2->execute(array($fee, $apt_id));

                        /* Check if a payment record already exists for this appointment */
                        $chk_pay = $pdo->prepare("SELECT payment_id FROM payments WHERE appointment_id = ?");
                        $chk_pay->execute(array($apt_id));
                        $existing_pay = $chk_pay->fetch();

                        if ($existing_pay) {
                            /* Update existing payment to Completed */
                            $upd_pay = $pdo->prepare("
                                UPDATE payments
                                SET payment_status = 'Completed', amount = ?, payment_date = NOW()
                                WHERE appointment_id = ?
                            ");
                            $upd_pay->execute(array($fee, $apt_id));
                        } else {
                            /* Insert new payment record */
                            $ins_pay = $pdo->prepare("
                                INSERT INTO payments
                                    (appointment_id, user_id, amount, payment_method, payment_status, transaction_id, payment_date)
                                VALUES (?, ?, ?, 'Cash', 'Completed', ?, NOW())
                            ");
                            $txn_id = 'TXN-' . strtoupper($doctor_id) . '-' . $apt_id . '-' . time();
                            $ins_pay->execute(array($apt_id, $apt_row['user_id'], $fee, $txn_id));
                        }

                        $success_msg = 'Appointment marked as Completed and payment of K' . number_format($fee, 2) . ' recorded.';

                    } elseif ($new_status === 'Cancelled') {
                        /* If there was a completed payment, refund it */
                        $refund = $pdo->prepare("
                            UPDATE payments SET payment_status = 'Refunded'
                            WHERE appointment_id = ? AND payment_status = 'Completed'
                        ");
                        $refund->execute(array($apt_id));
                        $success_msg = 'Appointment cancelled.';

                    } else {
                        $success_msg = 'Appointment status updated to ' . $new_status . '.';
                    }

                    $pdo->commit();

                } catch (PDOException $inner) {
                    $pdo->rollBack();
                    $error_msg = 'Failed to update: ' . $inner->getMessage();
                }
            } else {
                $error_msg = 'Appointment not found or access denied.';
            }
        }
    }

    /* Fetch appointments */
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
            u.full_name     AS patient_name,
            u.email         AS patient_email,
            u.phone         AS patient_phone,
            u.profile_image AS patient_image,
            u.gender,
            u.blood_group,
            TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS patient_age
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.doctor_id = ?
    ";
    $params = array($doctor_id);

    if (!empty($filter_status)) { $sql .= " AND a.status = ?";             $params[] = $filter_status; }
    if (!empty($filter_date))   { $sql .= " AND a.appointment_date = ?";   $params[] = $filter_date;   }
    if (!empty($filter_search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = '%' . $filter_search . '%';
        $params[] = '%' . $filter_search . '%';
    }
    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute($params);
    $appointments = $stmt2->fetchAll();

    /* Stats */
    $stat_stmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM appointments WHERE doctor_id = ? GROUP BY status");
    $stat_stmt->execute(array($doctor_id));
    foreach ($stat_stmt->fetchAll() as $row) {
        $stats['total'] += $row['cnt'];
        $key = strtolower($row['status']);
        if (isset($stats[$key])) $stats[$key] = $row['cnt'];
    }

    /* Doctor earnings this month */
    $earn_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(p.amount), 0) AS earned
        FROM payments p
        JOIN appointments a ON p.appointment_id = a.appointment_id
        WHERE a.doctor_id = ?
          AND p.payment_status = 'Completed'
          AND MONTH(p.payment_date) = MONTH(CURDATE())
          AND YEAR(p.payment_date)  = YEAR(CURDATE())
    ");
    $earn_stmt->execute(array($doctor_id));
    $monthly_earned = $earn_stmt->fetch()['earned'];

} catch (PDOException $e) {
    $error_msg = 'Database error: ' . $e->getMessage();
    $monthly_earned = 0;
}

function resolvePatientImg($path) {
    if (empty($path)) return '';
    $path = ltrim($path, '/');
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
<link rel="stylesheet" href="./Css/doctor-dashboard.css">
</head>
<body>
<div class="layout">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div><img src="assets/logo.svg" width="150px" alt=""></div>
        
    </div>

    <div class="doc-profile-card">
        <div class="doc-avatar-wrap">
            <?php if ($doc_img): ?>
                <img src="<?php echo htmlspecialchars($doc_img); ?>"
                     alt="<?php echo htmlspecialchars($doctor_name); ?>"
                     onerror="this.style.display='none';this.parentElement.textContent='<?php echo strtoupper(substr($doctor_name,0,1)); ?>';">
            <?php else: ?>
                <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
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
            <span class="nav-badge"><?php echo $stats['pending']; ?></span>
            <?php endif; ?>
        </a>
        <a href="doctor-profile.php" class="nav-link">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
        <div class="nav-section-label" style="margin-top:0.5rem;">Account</div>
        <a href="doctor-logout.php" class="nav-link" style="color:#ef4444;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ══ MAIN ══ -->
<main class="main">

    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
            <h1>My Appointments</h1>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><?php echo date('l, d F Y'); ?></span>
            <a href="doctor-logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <?php if ($success_msg): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- Stats — 6 cards: 5 appointment stats + monthly earnings -->
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
        <!-- Earnings card — highlighted blue -->
        <div class="stat-card earnings-card">
            <div class="stat-num">K<?php echo number_format($monthly_earned, 0); ?></div>
            <div class="stat-label">This Month</div>
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
            <?php if (!empty($filter_status)||!empty($filter_date)||!empty($filter_search)): ?>
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
                    $sc = array('Pending'=>'badge-pending','Confirmed'=>'badge-confirmed','Completed'=>'badge-completed','Cancelled'=>'badge-cancelled');
                    $s_cls = isset($sc[$apt['status']]) ? $sc[$apt['status']] : 'badge-pending';
                    $p_cls = ($apt['payment_status']==='Paid') ? 'badge-paid' : 'badge-unpaid';
                    $apt_date = date('M d, Y', strtotime($apt['appointment_date']));
                    $apt_time = date('h:i A', strtotime($apt['appointment_time']));
                    $uid = $apt['appointment_id'];
                    $fee_display = ($apt['amount'] > 0) ? $apt['amount'] : (isset($doctor['fees']) ? $doctor['fees'] : 0);
                    $fee_cls = ($apt['status']==='Completed') ? 'fee-completed' : 'fee-pending';
                ?>
                    <tr>
                        <td style="color:#b0b7c3;font-weight:500;"><?php echo $n++; ?></td>

                        <td>
                            <div class="patient-cell">
                                <div class="pat-avatar">
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
                                            <?php echo $apt['patient_age'] ? ' &bull; ' : ''; ?><?php echo htmlspecialchars($apt['gender']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($apt['blood_group'])): ?>
                                            &bull; <?php echo htmlspecialchars($apt['blood_group']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pat-meta"><?php echo htmlspecialchars($apt['patient_email']); ?></div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span style="font-weight:600;color:#0f172a;font-size:0.82rem;"><?php echo $apt_date; ?></span><br>
                            <span style="font-size:0.73rem;color:#94a3b8;"><?php echo $apt_time; ?></span>
                        </td>

                        <td>
                            <div class="symptoms-cell" title="<?php echo htmlspecialchars(isset($apt['symptoms']) ? $apt['symptoms'] : ''); ?>">
                                <?php echo !empty($apt['symptoms']) ? htmlspecialchars($apt['symptoms']) : '<span style="color:#d1d5db;">—</span>'; ?>
                            </div>
                        </td>

                        <td><span class="badge <?php echo $s_cls; ?>"><?php echo htmlspecialchars($apt['status']); ?></span></td>

                        <td><span class="badge <?php echo $p_cls; ?>"><?php echo htmlspecialchars($apt['payment_status']); ?></span></td>

                        <td class="<?php echo $fee_cls; ?>">
                            <?php echo number_format($fee_display, 2); ?>
                            <?php if ($apt['status']==='Completed'): ?>
                                <i class="fas fa-check-circle" style="font-size:0.7rem;color:#22c55e;" title="Payment recorded"></i>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($apt['status'] !== 'Cancelled' && $apt['status'] !== 'Completed'): ?>
                            <div class="action-wrap">
                                <button class="btn-action" onclick="toggleMenu(<?php echo $uid; ?>)" type="button">
                                    Update <i class="fas fa-chevron-down" style="font-size:0.62rem;"></i>
                                </button>
                                <div class="action-menu" id="menu-<?php echo $uid; ?>">

                                    <?php if ($apt['status'] === 'Pending'): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $uid; ?>">
                                        <input type="hidden" name="new_status"      value="Confirmed">
                                        <button type="submit" name="update_status" class="action-menu-item confirm">
                                            <i class="fas fa-check-circle"></i> Confirm
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($apt['status'] === 'Confirmed'): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $uid; ?>">
                                        <input type="hidden" name="new_status"      value="Completed">
                                        <button type="submit" name="update_status" class="action-menu-item complete"
                                                onclick="return confirm('Mark as Completed? This will record K<?php echo number_format($fee_display,2); ?> payment on the admin dashboard.')">
                                            <i class="fas fa-clipboard-check"></i> Mark Completed
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $uid; ?>">
                                        <input type="hidden" name="new_status"      value="Cancelled">
                                        <button type="submit" name="update_status" class="action-menu-item cancel"
                                                onclick="return confirm('Cancel this appointment?')">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </form>

                                </div>
                            </div>
                            <?php else: ?>
                                <span style="font-size:0.73rem;color:#b0b7c3;">
                                    <?php echo $apt['status']==='Completed' ? '&#x2713; Done' : 'Cancelled'; ?>
                                </span>
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
            <p>No appointments found<?php echo (!empty($filter_status)||!empty($filter_date)||!empty($filter_search))?' matching your filters':''; ?>.</p>
        </div>
        <?php endif; ?>
    </div>

</main>
</div>

<script>
(function(){
    var btn=document.getElementById('hamburgerBtn');
    var sb=document.getElementById('sidebar');
    var ov=document.getElementById('sidebarOverlay');
    function open(){sb.classList.add('open');ov.classList.add('active');document.body.style.overflow='hidden';}
    function close(){sb.classList.remove('open');ov.classList.remove('active');document.body.style.overflow='';}
    if(btn) btn.addEventListener('click',function(){sb.classList.contains('open')?close():open();});
    if(ov)  ov.addEventListener('click',close);
    document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
    window.addEventListener('resize',function(){if(window.innerWidth>768)close();});
})();

var openMenuId=null;
function toggleMenu(id){
    var menu=document.getElementById('menu-'+id);
    if(!menu)return;
    if(openMenuId&&openMenuId!==id){
        var prev=document.getElementById('menu-'+openMenuId);
        if(prev)prev.classList.remove('open');
    }
    menu.classList.toggle('open');
    openMenuId=menu.classList.contains('open')?id:null;
}
document.addEventListener('click',function(e){
    if(openMenuId!==null){
        var menu=document.getElementById('menu-'+openMenuId);
        if(menu&&!menu.closest('.action-wrap').contains(e.target)){
            menu.classList.remove('open');
            openMenuId=null;
        }
    }
});
</script>
</body>
</html>