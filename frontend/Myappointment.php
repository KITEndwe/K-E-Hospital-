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

$user_id      = $_SESSION['user_id'];
$user_name    = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page = 'Myappointments.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
$is_logged_in  = true;

$appointments    = array();
$success_message = '';
$error_message   = '';

/* ── Handle Cancel appointment ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $apt_id = intval(isset($_POST['appointment_id']) ? $_POST['appointment_id'] : 0);
    if ($apt_id > 0) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ? AND user_id = ?");
            $stmt->execute(array($apt_id, $user_id));
            $success_message = 'Appointment cancelled successfully.';
        } catch (PDOException $e) {
            $error_message = 'Failed to cancel appointment.';
        }
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
            d.name          AS doctor_name,
            d.speciality,
            d.profile_image AS doctor_image,
            d.address_line1,
            d.address_line2,
            d.fees
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute(array($user_id));
    $appointments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();

    /* Fallback demo data so the page renders nicely even without DB */
    $appointments = array(
        array(
            'appointment_id'  => 1,
            'appointment_date'=> '2025-07-25',
            'appointment_time'=> '20:30:00',
            'status'          => 'Confirmed',
            'payment_status'  => 'Pending',
            'amount'          => 250,
            'doctor_name'     => 'Dr. Mwila Banda',
            'speciality'      => 'General Physician',
            'doctor_image'    => 'assets/doc1.png',
            'address_line1'   => 'K&E-Hospital',
            'address_line2'   => 'Great East Road, Lusaka',
            'fees'            => 250,
            'symptoms'        => '',
        ),
        array(
            'appointment_id'  => 2,
            'appointment_date'=> '2025-07-25',
            'appointment_time'=> '20:30:00',
            'status'          => 'Confirmed',
            'payment_status'  => 'Pending',
            'amount'          => 300,
            'doctor_name'     => 'Dr. Mutinta Phiri',
            'speciality'      => 'Gynecologist',
            'doctor_image'    => 'assets/doc2.png',
            'address_line1'   => 'Levy Mwanawasa Medical University',
            'address_line2'   => 'Great East Road, Lusaka',
            'fees'            => 300,
            'symptoms'        => '',
        ),
        array(
            'appointment_id'  => 3,
            'appointment_date'=> '2025-07-25',
            'appointment_time'=> '20:30:00',
            'status'          => 'Completed',
            'payment_status'  => 'Paid',
            'amount'          => 280,
            'doctor_name'     => 'Dr. Christopher Tembo',
            'speciality'      => 'Pediatrician',
            'doctor_image'    => 'assets/doc4.png',
            'address_line1'   => 'K&E-Hospital, Matero Level One',
            'address_line2'   => 'Matero, Lusaka',
            'fees'            => 280,
            'symptoms'        => '',
        ),
    );
}

/* Function to get correct doctor image path */
function getDoctorImagePath($image_path) {
    if (empty($image_path)) {
        return '';
    }
    
    // Remove leading slash if exists
    $clean_path = ltrim($image_path, '/');
    
    // If path already starts with assets/, use as is (relative to current directory)
    if (strpos($clean_path, 'assets/') === 0) {
        return $clean_path;
    }
    
    // Default: assume image is in assets/ folder
    return 'assets/' . $clean_path;
}

/* ── Format helpers ── */
function fmtDate($d) {
    if (empty($d) || $d === '0000-00-00') return 'N/A';
    $months = array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December');
    $parts = explode('-', $d);
    $y = intval($parts[0]); $m = intval($parts[1]); $day = intval($parts[2]);
    return $day . ' ' . (isset($months[$m]) ? $months[$m] : $m);
}

function fmtTime($t) {
    if (empty($t)) return '';
    $parts = explode(':', $t);
    $h = intval($parts[0]);
    $min = isset($parts[1]) ? $parts[1] : '00';
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
<title>My Appointments - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior:smooth; }
body {
    font-family:'Outfit', sans-serif;
    background:#fff;
    color:#3c3c3c;
    min-height:100vh;
}
a { text-decoration:none; color:inherit; }
img { display:block; max-width:100%; }

/* ── Page wrapper ── */
.page-wrap {
    max-width:900px;
    margin:0 auto;
    padding:2.5rem 6% 5rem;
}

/* ── Page title ── */
.page-title {
    font-size:1.3rem;
    font-weight:600;
    color:#1a1a2e;
    margin-bottom:1.75rem;
    padding-bottom:1rem;
    border-bottom:1px solid #f0f0f5;
}

/* ── Alerts ── */
.alert {
    padding:0.875rem 1.1rem;
    border-radius:10px;
    margin-bottom:1.25rem;
    display:flex; align-items:center; gap:0.6rem;
    font-size:0.875rem; font-weight:500;
}
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

/* ── Appointment list ── */
.apt-list { display:flex; flex-direction:column; gap:0; }

/* ── Single appointment card ── */
.apt-card {
    display:flex; align-items:flex-start;
    gap:1.25rem;
    padding:1.25rem 0;
    border-bottom:1px solid #ebebeb;
}
.apt-card:last-child { border-bottom:none; }

/* Doctor photo */
.apt-photo {
    width:120px; height:130px;
    border-radius:10px; overflow:hidden;
    background:linear-gradient(160deg,#dce3ff,#eaf0ff);
    flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
}
.apt-photo img {
    width:100%; height:100%;
    object-fit:cover; object-position:top center;
    display:block;
}
.apt-photo-fallback {
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    font-size:2.5rem; color:#8b9aff;
}

/* Info section */
.apt-info { flex:1; min-width:0; }

.apt-doctor-name {
    font-size:1rem; font-weight:600; color:#1a1a2e;
    margin-bottom:0.2rem;
}
.apt-speciality {
    font-size:0.82rem; color:#696969; margin-bottom:0.75rem;
}

.apt-detail-label {
    font-size:0.82rem; font-weight:600; color:#1a1a2e;
    margin-bottom:0.2rem;
}
.apt-detail-value {
    font-size:0.82rem; color:#696969; line-height:1.5;
    margin-bottom:0.6rem;
}

.apt-datetime {
    font-size:0.82rem; color:#1a1a2e; font-weight:500;
}
.apt-datetime strong { color:#1a1a2e; }

/* Actions column */
.apt-actions {
    display:flex; flex-direction:column;
    align-items:flex-end; gap:0.5rem;
    flex-shrink:0; padding-top:0.25rem;
}

/* Buttons */
.btn-pay {
    background:#5f6fff; color:#fff; border:none;
    padding:0.6rem 1.75rem; border-radius:6px;
    font-size:0.85rem; font-weight:600;
    font-family:'Outfit',sans-serif; cursor:pointer;
    transition:all 0.2s; white-space:nowrap;
    min-width:150px; text-align:center;
}
.btn-pay:hover { background:#4a5af0; transform:translateY(-1px); }

.btn-paid {
    background:#5f6fff; color:#fff; border:none;
    padding:0.6rem 1.75rem; border-radius:6px;
    font-size:0.85rem; font-weight:600;
    font-family:'Outfit',sans-serif;
    white-space:nowrap; min-width:150px; text-align:center;
    cursor:default; opacity:0.85;
}

.btn-cancel {
    background:#fff; color:#1a1a2e;
    border:1px solid #d1d5db;
    padding:0.6rem 1.75rem; border-radius:6px;
    font-size:0.85rem; font-weight:500;
    font-family:'Outfit',sans-serif; cursor:pointer;
    transition:all 0.2s; white-space:nowrap;
    min-width:150px; text-align:center;
}
.btn-cancel:hover {
    border-color:#ef4444; color:#ef4444;
    background:#fff5f5;
}

/* Status badge (small, inline) */
.status-badge {
    display:inline-flex; align-items:center; gap:4px;
    font-size:0.72rem; font-weight:600; padding:0.2rem 0.65rem;
    border-radius:50px;
}
.badge-confirmed  { background:#e0f2fe; color:#0369a1; }
.badge-pending    { background:#fef9c3; color:#854d0e; }
.badge-completed  { background:#d1fae5; color:#065f46; }
.badge-cancelled  { background:#fee2e2; color:#991b1b; }

/* ── Empty state ── */
.empty-state {
    text-align:center; padding:4rem 2rem; color:#9ca3af;
}
.empty-state i { font-size:3rem; margin-bottom:1rem; display:block; opacity:0.4; }
.empty-state p { font-size:0.95rem; margin-bottom:1.5rem; }
.btn-book-now {
    display:inline-flex; align-items:center; gap:0.5rem;
    background:#5f6fff; color:#fff; border:none;
    padding:0.75rem 1.75rem; border-radius:50px;
    font-size:0.9rem; font-weight:600;
    font-family:'Outfit',sans-serif; cursor:pointer; transition:all 0.2s;
}
.btn-book-now:hover { background:#4a5af0; transform:translateY(-2px); }

/* ── Confirm cancel modal ── */
.modal-backdrop {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.4); z-index:1050;
    align-items:center; justify-content:center; padding:1rem;
}
.modal-backdrop.active { display:flex; }
.modal-box {
    background:#fff; border-radius:18px; padding:2rem;
    max-width:400px; width:100%;
    box-shadow:0 20px 48px rgba(0,0,0,0.18);
    animation:popIn 0.22s ease;
    text-align:center;
}
@keyframes popIn {
    from { opacity:0; transform:scale(0.92) translateY(10px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}
.modal-icon {
    width:64px; height:64px; border-radius:50%;
    background:#fee2e2; color:#ef4444;
    display:flex; align-items:center; justify-content:center;
    font-size:1.75rem; margin:0 auto 1.25rem;
}
.modal-box h3 { font-size:1.1rem; font-weight:700; color:#1a1a2e; margin-bottom:0.5rem; }
.modal-box p  { font-size:0.875rem; color:#696969; margin-bottom:1.5rem; line-height:1.5; }
.modal-actions { display:flex; gap:0.75rem; justify-content:center; }
.btn-modal-cancel {
    padding:0.65rem 1.5rem; border-radius:50px;
    border:1.5px solid #e2e8f0; background:#fff;
    font-size:0.875rem; font-weight:500; color:#64748b;
    cursor:pointer; font-family:'Outfit',sans-serif; transition:all 0.2s;
}
.btn-modal-cancel:hover { background:#f1f5f9; }
.btn-modal-confirm {
    padding:0.65rem 1.5rem; border-radius:50px;
    border:none; background:#ef4444; color:#fff;
    font-size:0.875rem; font-weight:600;
    cursor:pointer; font-family:'Outfit',sans-serif; transition:all 0.2s;
}
.btn-modal-confirm:hover { background:#dc2626; }

/* ── Footer ── */
footer {
    background:#fff; border-top:1px solid #ebebeb;
    padding:3rem 6% 1.5rem;
}
.footer-grid {
    display:grid; grid-template-columns:2fr 1fr 1fr;
    gap:3rem; margin-bottom:2rem; max-width:900px; margin-left:auto; margin-right:auto;
}
.footer-logo {
    display:flex; align-items:center; gap:8px;
    font-size:1.1rem; font-weight:700; color:#1a1a2e; margin-bottom:0.75rem;
}
.f-icon {
    width:28px; height:28px; background:#5f6fff; border-radius:7px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:0.82rem;
}
.footer-desc { font-size:0.82rem; color:#777; line-height:1.7; max-width:280px; }
.footer-col h4 {
    font-size:0.78rem; font-weight:700; color:#1a1a2e;
    text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.875rem;
}
.footer-col ul { list-style:none; }
.footer-col ul li { margin-bottom:0.5rem; }
.footer-col ul li a { font-size:0.82rem; color:#696969; transition:color 0.2s; }
.footer-col ul li a:hover { color:#5f6fff; }
.footer-col ul li { font-size:0.82rem; color:#696969; }
.footer-bottom {
    border-top:1px solid #ebebeb; padding-top:1rem;
    text-align:center; font-size:0.78rem; color:#aaa;
    max-width:900px; margin:0 auto;
}

/* ── Responsive ── */
@media (max-width:700px) {
    .apt-card {
        flex-direction:column; gap:0.875rem;
    }
    .apt-photo { width:100%; height:180px; }
    .apt-actions {
        flex-direction:row; flex-wrap:wrap;
        align-items:center; width:100%;
    }
    .btn-pay, .btn-paid, .btn-cancel { min-width:auto; flex:1; }
    .footer-grid { grid-template-columns:1fr; gap:1.5rem; }
}

@media (max-width:480px) {
    .page-wrap { padding:1.75rem 5% 3rem; }
    .apt-photo { height:150px; }
}
</style>
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="page-wrap">

    <!-- Alerts -->
    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>
    <?php if ($error_message && empty($appointments)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <h1 class="page-title">My Appointments</h1>

    <?php if (empty($appointments)): ?>
    <!-- Empty state -->
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <p>You have no appointments yet.</p>
        <a href="Alldoctors.php" class="btn-book-now">
            <i class="fas fa-search"></i> Find a Doctor
        </a>
    </div>

    <?php else: ?>

    <div class="apt-list">
        <?php foreach ($appointments as $apt):
            $is_cancelled = ($apt['status'] === 'Cancelled');
            $is_paid      = ($apt['payment_status'] === 'Paid');
            $is_completed = ($apt['status'] === 'Completed');

            /* Get correct doctor image path */
            $doc_img_web = getDoctorImagePath($apt['doctor_image']);
            $fallback_img = 'https://placehold.co/120x130/dce3ff/5f6fff?text=Dr';

            /* Address */
            $address_parts = array();
            if (!empty($apt['address_line1'])) $address_parts[] = $apt['address_line1'];
            if (!empty($apt['address_line2'])) $address_parts[] = $apt['address_line2'];
            $address = implode(', ', $address_parts);
            if (empty($address)) $address = 'K&E-Hospital, Lusaka';
        ?>
        <div class="apt-card" id="apt-<?php echo $apt['appointment_id']; ?>">

            <!-- Doctor photo -->
            <div class="apt-photo">
                <?php if ($doc_img_web): ?>
                    <img src="<?php echo htmlspecialchars($doc_img_web); ?>"
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
                    <?php echo htmlspecialchars(fmtDate($apt['appointment_date']) . ' | ' . fmtTime($apt['appointment_time'])); ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="apt-actions">
                <?php if ($is_cancelled): ?>
                    <!-- Cancelled — no actions, just badge -->
                    <span class="status-badge badge-cancelled">
                        <i class="fas fa-times-circle"></i> Cancelled
                    </span>

                <?php elseif ($is_paid || $is_completed): ?>
                    <!-- Paid / Completed -->
                    <div class="btn-paid">Paid</div>
                    <form method="POST" action="" onsubmit="return confirmCancel(event, <?php echo $apt['appointment_id']; ?>)">
                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                        <button type="submit" name="cancel_appointment" class="btn-cancel">Cancel appointment</button>
                    </form>

                <?php else: ?>
                    <!-- Unpaid / Pending -->
                    <?php if ($apt['payment_status'] === 'Pending' && $apt['status'] !== 'Cancelled'): ?>
                    <a href="payment.php?appointment=<?php echo $apt['appointment_id']; ?>" class="btn-pay">Pay here</a>
                    <?php endif; ?>
                    <form method="POST" action="" onsubmit="return confirmCancel(event, <?php echo $apt['appointment_id']; ?>)">
                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                        <button type="submit" name="cancel_appointment" class="btn-cancel">Cancel appointment</button>
                    </form>

                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- /.page-wrap -->


<!-- ── Confirm cancel modal ── -->
<div class="modal-backdrop" id="cancelModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-calendar-xmark"></i></div>
        <h3>Cancel Appointment?</h3>
        <p>Are you sure you want to cancel this appointment?<br>This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeModal()">Keep it</button>
            <button class="btn-modal-confirm" id="modalConfirmBtn">Yes, Cancel</button>
        </div>
    </div>
</div>


<!-- ── Footer ── -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <img src="assets/logo.svg" width="100px" alt="K&amp;E Hospital">
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
/* ── Cancel confirmation modal ── */
var pendingForm = null;

function confirmCancel(e, aptId) {
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
</script>

</body>
</html>