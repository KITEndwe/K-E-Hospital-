<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$user_id       = $_SESSION['user_id'];
$user_name     = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page  = 'Myappointments.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
$is_logged_in  = true;

$appointments    = array();
$success_message = '';
$error_message   = '';

/* ── Handle Cancel ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $apt_id = intval(isset($_POST['appointment_id']) ? $_POST['appointment_id'] : 0);
    if ($apt_id > 0) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            /* Only allow cancelling if not already Completed */
            $chk = $pdo->prepare("SELECT status FROM appointments WHERE appointment_id = ? AND user_id = ?");
            $chk->execute(array($apt_id, $user_id));
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['status'] === 'Completed') {
                $error_message = 'Completed appointments cannot be cancelled.';
            } else {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ? AND user_id = ?");
                $stmt->execute(array($apt_id, $user_id));
                $success_message = 'Appointment cancelled successfully.';
            }
        } catch (PDOException $e) {
            $error_message = 'Failed to cancel appointment.';
        }
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /*
     * Fetch ALL appointments for this patient.
     * JOIN payments to show payment info when doctor marks Completed.
     * The status column reflects what the DOCTOR has set
     * (Pending → Confirmed → Completed / Cancelled).
     */
    $stmt = $pdo->prepare("
        SELECT
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.payment_status,
            a.amount,
            a.symptoms,
            d.doctor_id,
            d.name           AS doctor_name,
            d.speciality,
            d.profile_image  AS doctor_image,
            d.address_line1,
            d.address_line2,
            d.fees,
            p.payment_id,
            p.payment_status AS pay_status,
            p.transaction_id,
            p.payment_date
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN payments p ON a.appointment_id = p.appointment_id AND p.payment_status = 'Completed'
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute(array($user_id));
    $appointments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}

/* Image path helper — this file is in frontend/ */
function getDrImg($path) {
    if (empty($path)) return '';
    $path = ltrim($path, '/');
    /* DB stores /assets/docX.png → assets/docX.png (relative to frontend/) */
    if (strpos($path, 'assets/') === 0) return $path;
    return 'assets/' . $path;
}

function fmtDate($d) {
    if (empty($d) || $d === '0000-00-00') return 'N/A';
    $mo = array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December');
    $p  = explode('-', $d);
    return intval($p[2]) . ' ' . (isset($mo[intval($p[1])]) ? $mo[intval($p[1])] : $p[1]) . ' ' . $p[0];
}

function fmtTime($t) {
    if (empty($t)) return '';
    $p    = explode(':', $t);
    $h    = intval($p[0]);
    $min  = isset($p[1]) ? $p[1] : '00';
    $ampm = $h >= 12 ? 'PM' : 'AM';
    $h12  = $h % 12;
    if ($h12 === 0) $h12 = 12;
    return $h12 . ':' . $min . ' ' . $ampm;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Appointments - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{font-family:'Outfit',sans-serif;background:#fff;color:#3c3c3c;min-height:100vh;}
a{text-decoration:none;color:inherit;}
img{display:block;max-width:100%;}

.page-wrap{max-width:900px;margin:0 auto;padding:2.5rem 6% 5rem;}

.page-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1.75rem;padding-bottom:1rem;border-bottom:1px solid #f0f0f5;}
.page-title{font-size:1.3rem;font-weight:700;color:#1a1a2e;}

/* Live update notice */
.live-notice{display:inline-flex;align-items:center;gap:0.5rem;background:#eef0ff;color:#5f6fff;padding:0.35rem 0.875rem;border-radius:50px;font-size:0.75rem;font-weight:600;}
.live-dot{width:7px;height:7px;border-radius:50%;background:#5f6fff;animation:blink 2s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.3;}}

/* Tabs */
.tabs{display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.tab-btn{padding:0.45rem 1.1rem;border-radius:50px;border:1.5px solid #e0e2f0;background:#fff;font-size:0.82rem;font-weight:500;color:#64748b;cursor:pointer;transition:all 0.2s;font-family:'Outfit',sans-serif;}
.tab-btn:hover{border-color:#5f6fff;color:#5f6fff;}
.tab-btn.active{background:#5f6fff;border-color:#5f6fff;color:#fff;}

/* Alerts */
.alert{padding:0.875rem 1.1rem;border-radius:10px;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.6rem;font-size:0.875rem;font-weight:500;}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}

/* Appointment cards */
.apt-list{display:flex;flex-direction:column;}
.apt-card{display:flex;align-items:flex-start;gap:1.25rem;padding:1.25rem 0;border-bottom:1px solid #ebebeb;}
.apt-card:last-child{border-bottom:none;}
.apt-card.hidden{display:none;}

/* Doctor photo */
.apt-photo{width:110px;height:120px;border-radius:10px;overflow:hidden;background:linear-gradient(160deg,#dce3ff,#eaf0ff);flex-shrink:0;display:flex;align-items:flex-end;justify-content:center;}
.apt-photo img{width:100%;height:100%;object-fit:cover;object-position:top center;display:block;}
.apt-photo-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.25rem;color:#8b9aff;}

/* Info */
.apt-info{flex:1;min-width:0;}
.apt-doctor-name{font-size:1rem;font-weight:700;color:#1a1a2e;margin-bottom:0.15rem;}
.apt-speciality{font-size:0.8rem;color:#696969;margin-bottom:0.65rem;}
.apt-detail-label{font-size:0.78rem;font-weight:600;color:#1a1a2e;margin-bottom:0.15rem;}
.apt-detail-value{font-size:0.78rem;color:#696969;line-height:1.5;margin-bottom:0.5rem;}
.apt-datetime{font-size:0.8rem;color:#1a1a2e;font-weight:500;margin-top:0.25rem;}

/* ── STATUS TRACKER ── */
.status-tracker{display:flex;align-items:center;gap:0;margin:0.75rem 0 0.5rem;flex-wrap:nowrap;overflow-x:auto;}
.tracker-step{display:flex;flex-direction:column;align-items:center;flex:1;min-width:60px;}
.tracker-icon{
    width:30px;height:30px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:0.75rem;font-weight:700;
    background:#f0f0f5;color:#b0b7c3;
    border:2px solid #e0e2f0;
    transition:all 0.3s; flex-shrink:0;
}
.tracker-label{font-size:0.62rem;color:#b0b7c3;margin-top:4px;text-align:center;font-weight:500;white-space:nowrap;}
.tracker-line{flex:1;height:2px;background:#e0e2f0;margin-bottom:18px;min-width:12px;}

/* Active / done states */
.tracker-step.done .tracker-icon{background:#d1fae5;color:#065f46;border-color:#a7f3d0;}
.tracker-step.done .tracker-label{color:#065f46;}
.tracker-step.active .tracker-icon{background:#5f6fff;color:#fff;border-color:#5f6fff;box-shadow:0 0 0 3px rgba(95,111,255,0.2);}
.tracker-step.active .tracker-label{color:#5f6fff;font-weight:700;}
.tracker-step.cancelled .tracker-icon{background:#fee2e2;color:#dc2626;border-color:#fecaca;}
.tracker-step.cancelled .tracker-label{color:#dc2626;}
.tracker-line.done{background:#a7f3d0;}
.tracker-line.active{background:linear-gradient(to right,#a7f3d0,#e0e2f0);}

/* Payment confirmed notice */
.payment-notice{display:inline-flex;align-items:center;gap:0.4rem;background:#d1fae5;color:#065f46;padding:0.3rem 0.75rem;border-radius:50px;font-size:0.72rem;font-weight:600;margin-top:0.35rem;}
.payment-notice i{font-size:0.72rem;}

/* Actions */
.apt-actions{display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem;flex-shrink:0;padding-top:0.25rem;}
.btn-pay{background:#5f6fff;color:#fff;border:none;padding:0.55rem 1.5rem;border-radius:6px;font-size:0.82rem;font-weight:600;font-family:'Outfit',sans-serif;cursor:pointer;transition:all 0.2s;white-space:nowrap;min-width:130px;text-align:center;}
.btn-pay:hover{background:#4a5af0;transform:translateY(-1px);}
.btn-paid{background:#5f6fff;color:#fff;border:none;padding:0.55rem 1.5rem;border-radius:6px;font-size:0.82rem;font-weight:600;font-family:'Outfit',sans-serif;white-space:nowrap;min-width:130px;text-align:center;cursor:default;opacity:0.85;}
.btn-cancel{background:#fff;color:#3c3c3c;border:1px solid #d1d5db;padding:0.55rem 1.5rem;border-radius:6px;font-size:0.82rem;font-weight:500;font-family:'Outfit',sans-serif;cursor:pointer;transition:all 0.2s;white-space:nowrap;min-width:130px;text-align:center;}
.btn-cancel:hover{border-color:#ef4444;color:#ef4444;background:#fff5f5;}

/* Status badge pill */
.status-pill{display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;padding:0.22rem 0.65rem;border-radius:50px;}
.pill-pending{background:#fef9c3;color:#854d0e;}
.pill-confirmed{background:#e0f2fe;color:#0369a1;}
.pill-completed{background:#d1fae5;color:#065f46;}
.pill-cancelled{background:#fee2e2;color:#991b1b;}

/* Empty */
.empty-state{text-align:center;padding:4rem 2rem;color:#9ca3af;}
.empty-state i{font-size:3rem;margin-bottom:1rem;display:block;opacity:0.35;}
.empty-state p{font-size:0.95rem;margin-bottom:1.5rem;}
.btn-book-now{display:inline-flex;align-items:center;gap:0.5rem;background:#5f6fff;color:#fff;border:none;padding:0.75rem 1.75rem;border-radius:50px;font-size:0.9rem;font-weight:600;font-family:'Outfit',sans-serif;cursor:pointer;transition:all 0.2s;}
.btn-book-now:hover{background:#4a5af0;transform:translateY(-2px);}

/* Cancel confirm modal */
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1050;align-items:center;justify-content:center;padding:1rem;}
.modal-backdrop.active{display:flex;}
.modal-box{background:#fff;border-radius:18px;padding:2rem;max-width:400px;width:100%;box-shadow:0 20px 48px rgba(0,0,0,0.18);animation:popIn 0.22s ease;text-align:center;}
@keyframes popIn{from{opacity:0;transform:scale(0.92) translateY(10px);}to{opacity:1;transform:scale(1) translateY(0);}}
.modal-icon{width:60px;height:60px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 1.1rem;}
.modal-box h3{font-size:1.05rem;font-weight:700;color:#1a1a2e;margin-bottom:0.5rem;}
.modal-box p{font-size:0.875rem;color:#696969;margin-bottom:1.5rem;line-height:1.5;}
.modal-actions{display:flex;gap:0.75rem;justify-content:center;}
.btn-modal-keep{padding:0.65rem 1.5rem;border-radius:50px;border:1.5px solid #e2e8f0;background:#fff;font-size:0.875rem;font-weight:500;color:#64748b;cursor:pointer;font-family:'Outfit',sans-serif;}
.btn-modal-keep:hover{background:#f1f5f9;}
.btn-modal-confirm{padding:0.65rem 1.5rem;border-radius:50px;border:none;background:#ef4444;color:#fff;font-size:0.875rem;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;}
.btn-modal-confirm:hover{background:#dc2626;}

/* Footer */
footer{background:#fff;border-top:1px solid #ebebeb;padding:3rem 6% 1.5rem;}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:3rem;margin-bottom:2rem;max-width:900px;margin-left:auto;margin-right:auto;}
.footer-logo{display:flex;align-items:center;gap:8px;font-size:1.1rem;font-weight:700;color:#1a1a2e;margin-bottom:0.75rem;}
.f-icon{width:28px;height:28px;background:#5f6fff;border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.82rem;}
.footer-desc{font-size:0.82rem;color:#777;line-height:1.7;max-width:280px;}
.footer-col h4{font-size:0.78rem;font-weight:700;color:#1a1a2e;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.875rem;}
.footer-col ul{list-style:none;}
.footer-col ul li{margin-bottom:0.5rem;}
.footer-col ul li a{font-size:0.82rem;color:#696969;transition:color 0.2s;}
.footer-col ul li a:hover{color:#5f6fff;}
.footer-col ul li{font-size:0.82rem;color:#696969;}
.footer-bottom{border-top:1px solid #ebebeb;padding-top:1rem;text-align:center;font-size:0.78rem;color:#aaa;max-width:900px;margin:0 auto;}

/* Responsive */
@media(max-width:700px){
    .apt-card{flex-direction:column;gap:0.875rem;}
    .apt-photo{width:100%;height:170px;}
    .apt-actions{flex-direction:row;flex-wrap:wrap;align-items:center;width:100%;}
    .btn-pay,.btn-paid,.btn-cancel{min-width:auto;flex:1;}
    .footer-grid{grid-template-columns:1fr;gap:1.5rem;}
    .status-tracker{gap:0;}
}
@media(max-width:480px){
    .page-wrap{padding:1.75rem 5% 3rem;}
    .apt-photo{height:140px;}
    .tracker-label{font-size:0.58rem;}
}
</style>
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="page-wrap">

    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">My Appointments</h1>
        <span class="live-notice">
            <span class="live-dot"></span>
            Live status from doctors
        </span>
    </div>

    <!-- Alerts -->
    <?php if ($success_message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="filterApts('all', this)">All</button>
        <button class="tab-btn" onclick="filterApts('pending', this)">Pending</button>
        <button class="tab-btn" onclick="filterApts('confirmed', this)">Confirmed</button>
        <button class="tab-btn" onclick="filterApts('completed', this)">Completed</button>
        <button class="tab-btn" onclick="filterApts('cancelled', this)">Cancelled</button>
    </div>

    <?php if (empty($appointments)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <p>You have no appointments yet.</p>
        <a href="Alldoctors.php" class="btn-book-now"><i class="fas fa-search"></i> Find a Doctor</a>
    </div>

    <?php else: ?>
    <div class="apt-list" id="aptList">
        <?php foreach ($appointments as $apt):
            $status     = $apt['status'];
            $pay_status = $apt['payment_status'];
            $is_paid    = ($pay_status === 'Paid' || !empty($apt['pay_status']));

            /* Doctor image */
            $doc_img = getDrImg(isset($apt['doctor_image']) ? $apt['doctor_image'] : '');

            /* Address */
            $addr_parts = array();
            if (!empty($apt['address_line1'])) $addr_parts[] = $apt['address_line1'];
            if (!empty($apt['address_line2'])) $addr_parts[] = $apt['address_line2'];
            $address = !empty($addr_parts) ? implode(', ', $addr_parts) : 'K&E-Hospital, Lusaka';

            /* Fee */
            $fee = ($apt['amount'] > 0) ? $apt['amount'] : $apt['fees'];

            /* ── Tracker step states ──
             * Steps: Booked → Confirmed → Completed
             * If Cancelled: show cancelled state on whichever step was active
             */
            $s_booked    = 'done'; /* always done once appointment exists */
            $s_confirmed = 'upcoming';
            $s_completed = 'upcoming';
            $s_line1     = '';
            $s_line2     = '';
            $cancelled_at = '';

            if ($status === 'Pending') {
                $s_booked    = 'done';
                $s_confirmed = 'active';
                $s_completed = '';
                $s_line1     = 'active';
                $s_line2     = '';
            } elseif ($status === 'Confirmed') {
                $s_booked    = 'done';
                $s_confirmed = 'done';
                $s_completed = 'active';
                $s_line1     = 'done';
                $s_line2     = 'active';
            } elseif ($status === 'Completed') {
                $s_booked    = 'done';
                $s_confirmed = 'done';
                $s_completed = 'done';
                $s_line1     = 'done';
                $s_line2     = 'done';
            } elseif ($status === 'Cancelled') {
                $s_booked    = 'done';
                $s_confirmed = 'cancelled';
                $s_completed = '';
                $s_line1     = '';
            }

            /* Tracker icon labels */
            $icon_booked    = '<i class="fas fa-calendar-check"></i>';
            $icon_confirmed = ($status === 'Confirmed' || $status === 'Completed') ? '<i class="fas fa-check"></i>' : ($status === 'Cancelled' ? '<i class="fas fa-times"></i>' : '<i class="fas fa-clock"></i>');
            $icon_completed = ($status === 'Completed') ? '<i class="fas fa-clipboard-check"></i>' : '<i class="fas fa-stethoscope"></i>';

            /* Pill class */
            $pill_cls = array(
                'Pending'  => 'pill-pending',
                'Confirmed'=> 'pill-confirmed',
                'Completed'=> 'pill-completed',
                'Cancelled'=> 'pill-cancelled',
            );
            $p_cls = isset($pill_cls[$status]) ? $pill_cls[$status] : 'pill-pending';
        ?>
        <div class="apt-card" data-status="<?php echo strtolower($status); ?>">

            <!-- Doctor photo -->
            <div class="apt-photo">
                <?php if ($doc_img): ?>
                    <img src="<?php echo htmlspecialchars($doc_img); ?>"
                         alt="<?php echo htmlspecialchars($apt['doctor_name']); ?>"
                         onerror="this.parentElement.innerHTML='<div class=apt-photo-fallback><i class=\'fas fa-user-doctor\'></i></div>'">
                <?php else: ?>
                    <div class="apt-photo-fallback"><i class="fas fa-user-doctor"></i></div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="apt-info">
                <div class="apt-doctor-name"><?php echo htmlspecialchars($apt['doctor_name']); ?></div>
                <div class="apt-speciality"><?php echo htmlspecialchars($apt['speciality']); ?></div>

                <div class="apt-detail-label">Address:</div>
                <div class="apt-detail-value"><?php echo htmlspecialchars($address); ?></div>

                <div class="apt-datetime">
                    <strong>Date &amp; Time:</strong>
                    <?php echo htmlspecialchars(fmtDate($apt['appointment_date']) . ' &nbsp;|&nbsp; ' . fmtTime($apt['appointment_time'])); ?>
                </div>

                <!-- ═══ STATUS TRACKER ═══ -->
                <?php if ($status !== 'Cancelled'): ?>
                <div class="status-tracker" style="margin-top:0.875rem;">

                    <!-- Step 1: Booked -->
                    <div class="tracker-step <?php echo $s_booked; ?>">
                        <div class="tracker-icon"><?php echo $icon_booked; ?></div>
                        <div class="tracker-label">Booked</div>
                    </div>

                    <div class="tracker-line <?php echo $s_line1; ?>"></div>

                    <!-- Step 2: Confirmed by doctor -->
                    <div class="tracker-step <?php echo $s_confirmed; ?>">
                        <div class="tracker-icon"><?php echo $icon_confirmed; ?></div>
                        <div class="tracker-label">
                            <?php echo ($status === 'Pending') ? 'Awaiting' : 'Confirmed'; ?>
                        </div>
                    </div>

                    <div class="tracker-line <?php echo $s_line2; ?>"></div>

                    <!-- Step 3: Completed -->
                    <div class="tracker-step <?php echo $s_completed; ?>">
                        <div class="tracker-icon"><?php echo $icon_completed; ?></div>
                        <div class="tracker-label">Completed</div>
                    </div>

                </div>

                <?php else: /* Cancelled */ ?>
                <div style="margin-top:0.75rem;">
                    <span class="status-pill pill-cancelled">
                        <i class="fas fa-times-circle"></i> Appointment Cancelled
                    </span>
                </div>
                <?php endif; ?>

                <!-- Payment confirmed notice (shows when doctor marks Completed) -->
                <?php if ($status === 'Completed' && $is_paid): ?>
                <div class="payment-notice">
                    <i class="fas fa-receipt"></i>
                    Payment of K<?php echo number_format($fee, 2); ?> confirmed
                    <?php if (!empty($apt['transaction_id'])): ?>
                    &nbsp;&bull;&nbsp; <?php echo htmlspecialchars($apt['transaction_id']); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="apt-actions">

                <?php if ($status === 'Cancelled'): ?>
                    <!-- No actions for cancelled -->

                <?php elseif ($status === 'Completed'): ?>
                    <div class="btn-paid"><i class="fas fa-check"></i> Paid</div>
                    <!-- Completed appointments cannot be cancelled -->

                <?php else: ?>
                    <!-- Pending or Confirmed -->
                    <?php if ($pay_status === 'Pending'): ?>
                    <a href="payment.php?appointment=<?php echo $apt['appointment_id']; ?>" class="btn-pay">
                        <i class="fas fa-credit-card"></i> Pay here
                    </a>
                    <?php endif; ?>

                    <form method="POST" action="" onsubmit="return doCancel(event, <?php echo $apt['appointment_id']; ?>)">
                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                        <button type="submit" name="cancel_appointment" class="btn-cancel">Cancel appointment</button>
                    </form>
                <?php endif; ?>

                <!-- Status pill -->
                <span class="status-pill <?php echo $p_cls; ?>">
                    <?php
                    $icons = array('Pending'=>'fa-clock','Confirmed'=>'fa-check-circle','Completed'=>'fa-clipboard-check','Cancelled'=>'fa-times-circle');
                    $ic = isset($icons[$status]) ? $icons[$status] : 'fa-clock';
                    echo '<i class="fas ' . $ic . '"></i> ' . htmlspecialchars($status);
                    ?>
                </span>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- /.page-wrap -->


<!-- Cancel modal -->
<div class="modal-backdrop" id="cancelModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-calendar-xmark"></i></div>
        <h3>Cancel Appointment?</h3>
        <p>Are you sure you want to cancel this appointment?<br>This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-modal-keep" onclick="closeModal()">Keep it</button>
            <button class="btn-modal-confirm" id="modalConfirmBtn">Yes, Cancel</button>
        </div>
    </div>
</div>


<!-- Footer -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <div class="f-icon"><i class="fas fa-hospital-user"></i></div>
                K&amp;E Hospital
            </div>
            <p class="footer-desc">Your Health, Our Priority. Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips.</p>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About us</a></li>
                <li><a href="contact.php">Contact us</a></li>
                <li><a href="privacy.php">Privacy policy</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Get In Touch</h4>
            <ul>
                <li>+260 7610 16446</li>
                <li>admin@kehospital.co.zm</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        Copyright &copy; <?php echo date('Y'); ?> K&amp;E Hospital - All Right Reserved.
    </div>
</footer>


<script>
/* ── Cancel modal ── */
var pendingForm = null;

function doCancel(e, aptId) {
    e.preventDefault();
    pendingForm = e.target;
    document.getElementById('cancelModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    return false;
}

document.getElementById('modalConfirmBtn').addEventListener('click', function() {
    if (pendingForm) {
        document.body.style.overflow = '';
        pendingForm.submit();
    }
});

function closeModal() {
    document.getElementById('cancelModal').classList.remove('active');
    document.body.style.overflow = '';
    pendingForm = null;
}

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

/* ── Tab filter ── */
function filterApts(status, btn) {
    /* Update active tab */
    var tabs = document.querySelectorAll('.tab-btn');
    for (var i = 0; i < tabs.length; i++) tabs[i].classList.remove('active');
    btn.classList.add('active');

    /* Show / hide cards */
    var cards = document.querySelectorAll('.apt-card');
    for (var j = 0; j < cards.length; j++) {
        var card = cards[j];
        if (status === 'all' || card.getAttribute('data-status') === status) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    }

    /* Empty message */
    var visible = document.querySelectorAll('.apt-card:not(.hidden)');
    var existing = document.getElementById('noApts');
    if (existing) existing.remove();
    if (visible.length === 0) {
        var msg = document.createElement('div');
        msg.id = 'noApts';
        msg.style.cssText = 'text-align:center;padding:3rem 2rem;color:#9ca3af;font-size:0.95rem;';
        msg.innerHTML = '<i class="fas fa-calendar-times" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;opacity:0.3;"></i>No ' + (status === 'all' ? '' : status) + ' appointments found.';
        document.getElementById('aptList').appendChild(msg);
    }
}

/* ── Auto-refresh every 30s to pick up doctor status changes ── */
setTimeout(function() {
    window.location.reload();
}, 30000);
</script>
</body>
</html>