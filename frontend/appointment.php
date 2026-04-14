<?php
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$is_logged_in  = isset($_SESSION['user_id']);
$user_name     = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
$current_page  = 'appointment.php';

/* ── Get doctor ID from URL ── */
$doctor_id = isset($_GET['doctor']) ? trim($_GET['doctor']) : '';
if (empty($doctor_id)) {
    header('Location: Alldoctors.php');
    exit();
}

$doctor          = array();
$related_doctors = array();
$booked_slots    = array();
$success_message = '';
$error_message   = '';

/* ── Generate next 7 days ── */
$days = array();
for ($i = 0; $i < 7; $i++) {
    $ts      = strtotime('+' . $i . ' days');
    $days[]  = array(
        'ts'    => $ts,
        'day'   => strtoupper(date('D', $ts)),   // MON
        'num'   => date('j', $ts),                // 10
        'date'  => date('Y-m-d', $ts),
    );
}

/* ── Fixed time slots ── */
$time_slots = array(
    '08:00' => '8:00 am',
    '08:30' => '8:30 am',
    '09:00' => '9:00 am',
    '09:30' => '9:30 am',
    '10:00' => '10:00 am',
    '10:30' => '10:30 am',
    '11:00' => '11:00 am',
    '11:30' => '11:30 am',
);

/* ── Selected day/time (from POST or default to today) ── */
$selected_date = isset($_POST['selected_date']) ? $_POST['selected_date'] : $days[0]['date'];
$selected_time = isset($_POST['selected_time']) ? $_POST['selected_time'] : '';

/* ── Validate selected_date is in our 7-day window ── */
$valid_dates = array();
foreach ($days as $d) { $valid_dates[] = $d['date']; }
if (!in_array($selected_date, $valid_dates)) {
    $selected_date = $days[0]['date'];
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /* Fetch this doctor */
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
    $stmt->execute(array($doctor_id));
    $doctor = $stmt->fetch();

    if (!$doctor) {
        header('Location: Alldoctors.php');
        exit();
    }

    /* Already-booked slots for selected date */
    $stmt2 = $pdo->prepare("
        SELECT appointment_time FROM appointments
        WHERE doctor_id = ? AND appointment_date = ?
        AND status NOT IN ('Cancelled')
    ");
    $stmt2->execute(array($doctor_id, $selected_date));
    $booked_rows = $stmt2->fetchAll();
    foreach ($booked_rows as $row) {
        $t = substr($row['appointment_time'], 0, 5); // HH:MM
        $booked_slots[$t] = true;
    }

    /* Related doctors — same speciality, exclude current */
    $stmt3 = $pdo->prepare("
        SELECT doctor_id, name, speciality, profile_image, is_available
        FROM doctors
        WHERE speciality = ? AND doctor_id != ?
        ORDER BY rating DESC
        LIMIT 5
    ");
    $stmt3->execute(array($doctor['speciality'], $doctor_id));
    $related_doctors = $stmt3->fetchAll();

    /* ── Handle booking ── */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
        if (!$is_logged_in) {
            header('Location: login.php?redirect=' . urlencode('appointment.php?doctor=' . $doctor_id));
            exit();
        }

        $book_date = isset($_POST['selected_date']) ? trim($_POST['selected_date']) : '';
        $book_time = isset($_POST['selected_time']) ? trim($_POST['selected_time']) : '';

        if (empty($book_date) || empty($book_time)) {
            $error_message = 'Please select a date and time slot.';
        } elseif (!in_array($book_date, $valid_dates)) {
            $error_message = 'Invalid date selected.';
        } elseif (!array_key_exists($book_time, $time_slots)) {
            $error_message = 'Invalid time slot selected.';
        } elseif (isset($booked_slots[$book_time])) {
            $error_message = 'This slot is already booked. Please choose another.';
        } else {
            /* Check user hasn't already booked this doctor on this date */
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM appointments
                WHERE user_id = ? AND doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled')
            ");
            $chk->execute(array($_SESSION['user_id'], $doctor_id, $book_date));
            if ($chk->fetchColumn() > 0) {
                $error_message = 'You already have an appointment with this doctor on this date.';
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO appointments
                    (user_id, doctor_id, appointment_date, appointment_time, status, payment_status, amount)
                    VALUES (?, ?, ?, ?, 'Pending', 'Pending', ?)
                ");
                $time_full = $book_time . ':00'; // HH:MM:SS
                $ins->execute(array($_SESSION['user_id'], $doctor_id, $book_date, $time_full, $doctor['fees']));
                $success_message = 'Appointment booked successfully!';

                /* Refresh booked slots */
                $stmt2->execute(array($doctor_id, $selected_date));
                $booked_rows = $stmt2->fetchAll();
                $booked_slots = array();
                foreach ($booked_rows as $row) {
                    $t = substr($row['appointment_time'], 0, 5);
                    $booked_slots[$t] = true;
                }
                $selected_time = '';
            }
        }
    }

} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    /* Fallback doctor */
    if (empty($doctor)) {
        $doctor = array(
            'doctor_id'    => $doctor_id,
            'name'         => 'Dr. Mwila Banda',
            'profile_image'=> 'assets/doc1.png',
            'speciality'   => 'General Physician',
            'degree'       => 'MBChB',
            'experience'   => '5 Years',
            'about'        => 'Dr. Banda provides comprehensive medical care, focusing on preventive health, chronic disease management, and family medicine.',
            'fees'         => 250,
            'address_line1'=> 'K&E-Hospital',
            'address_line2'=> 'Great East Road, Lusaka',
            'rating'       => 4.5,
            'total_reviews'=> 28,
            'is_available' => 1,
        );
    }
}

/* ── Doctor image web path ── */
$doc_img = isset($doctor['profile_image']) ? $doctor['profile_image'] : '';
/* If stored as /assets/docX.png, strip leading slash for web use */
if (strpos($doc_img, '/') === 0) $doc_img = ltrim($doc_img, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars(isset($doctor['name']) ? $doctor['name'] : 'Book Appointment'); ?> - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="./Css/appointment.css">
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="page-wrap">

    <!-- Alerts -->
    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <a href="Myappointment.php" style="margin-left:auto;color:#065f46;font-weight:600;text-decoration:underline;">View Appointments</a>
    </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- ═══ DOCTOR INFO CARD ═══ -->
    <div class="doctor-card">

        <!-- Photo -->
        <div class="doc-photo-col">
            <?php if ($doc_img): ?>
                <img
                    src="<?php echo htmlspecialchars($doc_img); ?>"
                    alt="<?php echo htmlspecialchars(isset($doctor['name']) ? $doctor['name'] : ''); ?>"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="doc-photo-fallback" style="display:none;"><i class="fas fa-user-doctor"></i></div>
            <?php else: ?>
                <div class="doc-photo-fallback"><i class="fas fa-user-doctor"></i></div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="doc-info-col">
            <div class="doc-name-row">
                <span class="doc-name"><?php echo htmlspecialchars(isset($doctor['name']) ? $doctor['name'] : ''); ?></span>
                <i class="fas fa-circle-check verified-icon"></i>
            </div>

            <div class="doc-meta-row">
                <span class="doc-degree-spec">
                    <?php echo htmlspecialchars(isset($doctor['degree']) ? $doctor['degree'] : ''); ?>
                    &ndash;
                    <?php echo htmlspecialchars(isset($doctor['speciality']) ? $doctor['speciality'] : ''); ?>
                </span>
                <span class="exp-badge"><?php echo htmlspecialchars(isset($doctor['experience']) ? $doctor['experience'] : ''); ?></span>
            </div>

            <?php if (!empty($doctor['rating']) && $doctor['rating'] > 0): ?>
            <div class="rating-row">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                    <?php if ($s <= floor($doctor['rating'])): ?>
                        <i class="fas fa-star"></i>
                    <?php elseif ($s - $doctor['rating'] < 1): ?>
                        <i class="fas fa-star-half-alt"></i>
                    <?php else: ?>
                        <i class="far fa-star"></i>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php echo number_format($doctor['rating'], 1); ?>
                <span>(<?php echo intval($doctor['total_reviews']); ?> reviews)</span>
            </div>
            <?php endif; ?>

            <div class="about-label" style="margin-top:0.9rem;">
                About <i class="fas fa-circle-info"></i>
            </div>
            <p class="about-text"><?php echo htmlspecialchars(isset($doctor['about']) ? $doctor['about'] : ''); ?></p>

            <div class="fee-row">
                Appointment fee: <strong>K<?php echo number_format(isset($doctor['fees']) ? $doctor['fees'] : 0); ?></strong>
            </div>
        </div>
    </div>

    <!-- ═══ BOOKING SLOTS ═══ -->
    <div class="booking-section">
        <h2 class="booking-title">Booking slots</h2>

        <form method="POST" action="" id="bookingForm">
            <input type="hidden" name="selected_date" id="hiddenDate" value="<?php echo htmlspecialchars($selected_date); ?>">
            <input type="hidden" name="selected_time" id="hiddenTime" value="<?php echo htmlspecialchars($selected_time); ?>">

            <!-- Day picker -->
            <div class="day-picker">
                <?php foreach ($days as $day): ?>
                <button
                    type="button"
                    class="day-btn <?php echo $day['date'] === $selected_date ? 'active' : ''; ?>"
                    onclick="selectDay('<?php echo $day['date']; ?>', this)"
                >
                    <span class="day-abbr"><?php echo $day['day']; ?></span>
                    <span class="day-num"><?php echo $day['num']; ?></span>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Time slots -->
            <div class="time-picker" id="timePicker">
                <?php foreach ($time_slots as $val => $label):
                    $is_booked   = isset($booked_slots[$val]);
                    $is_selected = ($val === $selected_time);
                    $cls = 'time-btn';
                    if ($is_booked)   $cls .= ' booked';
                    if ($is_selected) $cls .= ' active';
                ?>
                <button
                    type="button"
                    class="<?php echo $cls; ?>"
                    <?php echo $is_booked ? 'disabled title="Already booked"' : ''; ?>
                    onclick="selectTime('<?php echo $val; ?>', this)"
                >
                    <?php echo $label; ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Book button -->
            <?php if ($is_logged_in): ?>
                <button type="submit" name="book_appointment" class="btn-book" id="bookBtn"
                        <?php echo empty($selected_time) ? 'disabled' : ''; ?>>
                    Book an appointment
                </button>
            <?php else: ?>
                <a href="login.php?redirect=<?php echo urlencode('appointment.php?doctor=' . $doctor_id); ?>" class="btn-book" style="display:inline-flex;">
                    Login to Book
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ═══ RELATED DOCTORS ═══ -->
    <?php if (!empty($related_doctors)): ?>
    <div class="related-section">
        <div class="related-header">
            <h2>Related Doctors</h2>
            <p>Simply browse through our extensive list of trusted doctors.</p>
        </div>
        <div class="related-grid">
            <?php foreach ($related_doctors as $rel):
                $rel_img = isset($rel['profile_image']) ? ltrim($rel['profile_image'], '/') : '';
                $rel_fallback = 'https://placehold.co/200x200/dce3ff/5f6fff?text=Dr';
            ?>
            <a href="appointment.php?doctor=<?php echo urlencode($rel['doctor_id']); ?>" class="rel-card">
                <div class="rel-img-wrap">
                    <?php if ($rel_img): ?>
                        <img
                            src="<?php echo htmlspecialchars($rel_img); ?>"
                            alt="<?php echo htmlspecialchars($rel['name']); ?>"
                            onerror="this.src='<?php echo $rel_fallback; ?>'">
                    <?php else: ?>
                        <div class="rel-img-fallback"><i class="fas fa-user-doctor"></i></div>
                    <?php endif; ?>
                </div>
                <div class="rel-body">
                    <?php if ($rel['is_available']): ?>
                    <div class="rel-avail">Available</div>
                    <?php endif; ?>
                    <div class="rel-name"><?php echo htmlspecialchars($rel['name']); ?></div>
                    <div class="rel-spec"><?php echo htmlspecialchars($rel['speciality']); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.page-wrap -->


<!-- ═══ FOOTER ═══ -->
<footer>
    <div class="footer-grid">
        <div>
            <div >
                <div ><img src="assets/logo.svg" width="100px" alt=""></div>
                
            </div>
            <p class="footer-desc">Your Health, Our Priority. Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips, Anywhere in Zambia.</p>
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
                <li>+260-7610-16446</li>
                <li>admin@kehospital.co.zm</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        Copyright &copy; <?php echo date('Y'); ?> K&amp;E Hospital - All Right Reserved.
    </div>
</footer>


<script>
/* ── Day selection ── */
function selectDay(date, btn) {
    /* Update hidden input */
    document.getElementById('hiddenDate').value = date;
    /* Update active class on day buttons */
    var dayBtns = document.querySelectorAll('.day-btn');
    for (var i = 0; i < dayBtns.length; i++) {
        dayBtns[i].classList.remove('active');
    }
    btn.classList.add('active');
    /* Reset time selection and reload page to get fresh booked slots */
    document.getElementById('hiddenTime').value = '';
    document.getElementById('bookingForm').submit();
}

/* ── Time selection ── */
function selectTime(val, btn) {
    document.getElementById('hiddenTime').value = val;
    var timeBtns = document.querySelectorAll('.time-btn:not(.booked)');
    for (var i = 0; i < timeBtns.length; i++) {
        timeBtns[i].classList.remove('active');
    }
    btn.classList.add('active');
    /* Enable book button */
    var bookBtn = document.getElementById('bookBtn');
    if (bookBtn) bookBtn.removeAttribute('disabled');
}
</script>

</body>
</html>