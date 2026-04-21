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
<link rel="stylesheet" href="./Css/Myapointment.css">
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

            /* ── Tracker step states ── */
            $s_booked    = 'done';
            $s_confirmed = 'upcoming';
            $s_completed = 'upcoming';
            $s_line1     = '';
            $s_line2     = '';

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
                    <div class="tracker-step <?php echo $s_booked; ?>">
                        <div class="tracker-icon"><?php echo $icon_booked; ?></div>
                        <div class="tracker-label">Booked</div>
                    </div>
                    <div class="tracker-line <?php echo $s_line1; ?>"></div>
                    <div class="tracker-step <?php echo $s_confirmed; ?>">
                        <div class="tracker-icon"><?php echo $icon_confirmed; ?></div>
                        <div class="tracker-label">
                            <?php echo ($status === 'Pending') ? 'Awaiting' : 'Confirmed'; ?>
                        </div>
                    </div>
                    <div class="tracker-line <?php echo $s_line2; ?>"></div>
                    <div class="tracker-step <?php echo $s_completed; ?>">
                        <div class="tracker-icon"><?php echo $icon_completed; ?></div>
                        <div class="tracker-label">Completed</div>
                    </div>
                </div>
                <?php else: ?>
                <div style="margin-top:0.75rem;">
                    <span class="status-pill pill-cancelled">
                        <i class="fas fa-times-circle"></i> Appointment Cancelled
                    </span>
                </div>
                <?php endif; ?>

                <!-- Payment confirmed notice -->
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
                <?php else: ?>
                    <!-- Pending or Confirmed - Payment button triggers modal -->
                    <?php if ($pay_status === 'Pending'): ?>
                    <button type="button" class="btn-pay" onclick="showPaymentModal('<?php echo addslashes($apt['doctor_name']); ?>')">
                        <i class="fas fa-credit-card"></i> Pay here
                    </button>
                    <?php endif; ?>
                    
                    <!-- Cancel button triggers cancel modal -->
                    <form method="POST" action="" id="cancel-form-<?php echo $apt['appointment_id']; ?>">
                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                        <input type="hidden" name="cancel_appointment" value="1">
                        <button type="button" class="btn-cancel" onclick="openCancelModal(<?php echo $apt['appointment_id']; ?>)">
                            Cancel appointment
                        </button>
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

<!-- Cancel Modal -->
<div class="modal-backdrop" id="cancelModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-calendar-xmark"></i></div>
        <h3>Cancel Appointment?</h3>
        <p>Are you sure you want to cancel this appointment?<br>This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-modal-keep" onclick="closeCancelModal()">Keep it</button>
            <button class="btn-modal-confirm" id="modalConfirmBtn">Yes, Cancel</button>
        </div>
    </div>
</div>

<!-- Payment Unavailable Modal -->
<div class="modal-backdrop" id="paymentModal">
    <div class="modal-box">
        <div class="modal-icon payment-modal-icon"><i class="fas fa-credit-card"></i></div>
        <h3>Online Payment Unavailable</h3>
        <p>Online payment services are currently unavailable. Please contact the doctor directly to arrange payment.</p>
        <div class="contact-note">
            <i class="fas fa-stethoscope"></i> <strong>Doctor:</strong> <span id="modalDoctorName">-</span><br>
            <i class="fas fa-phone-alt"></i> <strong>Contact:</strong> +260 7610 16446
        </div>
        <div class="modal-actions">
            <button style="background-color: blue;" class="btn-modal-keep" onclick="closePaymentModal()">Got it, I'll contact</button>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <div ><img src="./assets/logo.svg" width="100px" alt=""></div>
                
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
/* ── Cancel Modal Functions ── */
var pendingCancelId = null;

function openCancelModal(aptId) {
    pendingCancelId = aptId;
    document.getElementById('cancelModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('active');
    document.body.style.overflow = '';
    pendingCancelId = null;
}

document.getElementById('modalConfirmBtn').addEventListener('click', function() {
    if (pendingCancelId !== null) {
        var form = document.getElementById('cancel-form-' + pendingCancelId);
        if (form) {
            document.body.style.overflow = '';
            HTMLFormElement.prototype.submit.call(form);
        }
        closeCancelModal();
    }
});

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('cancelModal').classList.contains('active')) {
            closeCancelModal();
        }
        if (document.getElementById('paymentModal').classList.contains('active')) {
            closePaymentModal();
        }
    }
});

/* ── Payment Modal Functions ── */
function showPaymentModal(doctorName) {
    document.getElementById('modalDoctorName').textContent = doctorName;
    document.getElementById('paymentModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});

/* ── Tab Filter ── */
function filterApts(status, btn) {
    var tabs = document.querySelectorAll('.tab-btn');
    for (var i = 0; i < tabs.length; i++) tabs[i].classList.remove('active');
    btn.classList.add('active');

    var cards = document.querySelectorAll('.apt-card');
    for (var j = 0; j < cards.length; j++) {
        var card = cards[j];
        if (status === 'all' || card.getAttribute('data-status') === status) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    }

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

/* ── Auto-refresh every 30s ── */
setTimeout(function() {
    window.location.reload();
}, 30000);
</script>
</body>
</html>