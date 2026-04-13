<?php
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$doctors      = array();
$specialities = array();

// Active speciality filter from URL
$active_spec = isset($_GET['speciality']) ? trim($_GET['speciality']) : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // All distinct specialities for sidebar
    $spec_stmt    = $pdo->query("SELECT DISTINCT speciality FROM doctors ORDER BY speciality");
    $specialities = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Doctors – filtered or all
    if (!empty($active_spec)) {
        $stmt = $pdo->prepare("
            SELECT doctor_id, name, profile_image, speciality, degree, experience, fees, rating, total_reviews, is_available
            FROM doctors
            WHERE speciality = ? AND is_available = 1
            ORDER BY rating DESC, name ASC
        ");
        $stmt->execute(array($active_spec));
        $doctors = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("
            SELECT doctor_id, name, profile_image, speciality, degree, experience, fees, rating, total_reviews, is_available
            FROM doctors
            WHERE is_available = 1
            ORDER BY rating DESC, name ASC
        ");
        $doctors = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    // Fallback data
    $specialities = array('Dermatologist','General Physician','Gastroenterologist','Gynecologist','Neurologist','Pediatrician');
    $all_fallback = array(
        array('doctor_id'=>'doc1',  'name'=>'Dr. Mwila Banda',       'profile_image'=>'assets/doc1.png',  'speciality'=>'General Physician','degree'=>'MBChB','experience'=>'5 Years','fees'=>250,'rating'=>4.5,'total_reviews'=>28,'is_available'=>1),
        array('doctor_id'=>'doc2',  'name'=>'Dr. Mutinta Phiri',     'profile_image'=>'assets/doc2.png',  'speciality'=>'Gynecologist',     'degree'=>'MBChB','experience'=>'3 Years','fees'=>300,'rating'=>4.8,'total_reviews'=>42,'is_available'=>1),
        array('doctor_id'=>'doc3',  'name'=>'Dr. Luyando Zulu',      'profile_image'=>'assets/doc3.png',  'speciality'=>'Dermatologist',    'degree'=>'MBChB','experience'=>'2 Years','fees'=>220,'rating'=>4.2,'total_reviews'=>15,'is_available'=>1),
        array('doctor_id'=>'doc4',  'name'=>'Dr. Christopher Tembo', 'profile_image'=>'assets/doc4.png',  'speciality'=>'Pediatrician',     'degree'=>'MBChB','experience'=>'4 Years','fees'=>280,'rating'=>4.7,'total_reviews'=>35,'is_available'=>1),
        array('doctor_id'=>'doc5',  'name'=>'Dr. Chipo Mwansa',      'profile_image'=>'assets/doc5.png',  'speciality'=>'Neurologist',      'degree'=>'MBChB','experience'=>'6 Years','fees'=>350,'rating'=>4.9,'total_reviews'=>52,'is_available'=>1),
        array('doctor_id'=>'doc6',  'name'=>'Dr. Kelvin Mulenga',    'profile_image'=>'assets/doc6.png',  'speciality'=>'Neurologist',      'degree'=>'MBChB','experience'=>'5 Years','fees'=>320,'rating'=>4.6,'total_reviews'=>31,'is_available'=>1),
        array('doctor_id'=>'doc7',  'name'=>'Dr. Patrick Tembo',     'profile_image'=>'assets/doc7.png',  'speciality'=>'General Physician','degree'=>'MBChB','experience'=>'4 Years','fees'=>260,'rating'=>4.4,'total_reviews'=>23,'is_available'=>1),
        array('doctor_id'=>'doc8',  'name'=>'Dr. Lillian Chanda',    'profile_image'=>'assets/doc8.png',  'speciality'=>'Gynecologist',     'degree'=>'MBChB','experience'=>'3 Years','fees'=>300,'rating'=>4.7,'total_reviews'=>38,'is_available'=>1),
        array('doctor_id'=>'doc9',  'name'=>'Dr. Thandiwe Kapasa',   'profile_image'=>'assets/doc9.png',  'speciality'=>'Dermatologist',    'degree'=>'MBChB','experience'=>'2 Years','fees'=>220,'rating'=>4.3,'total_reviews'=>19,'is_available'=>1),
        array('doctor_id'=>'doc10', 'name'=>'Dr. Joseph Mwansa',     'profile_image'=>'assets/doc10.png', 'speciality'=>'Pediatrician',     'degree'=>'MBChB','experience'=>'4 Years','fees'=>280,'rating'=>4.5,'total_reviews'=>27,'is_available'=>1),
    );
    if (!empty($active_spec)) {
        $doctors = array_filter($all_fallback, function($d) use ($active_spec) {
            return $d['speciality'] === $active_spec && $d['is_available'] == 1;
        });
        $doctors = array_values($doctors);
    } else {
        $doctors = array_filter($all_fallback, function($d) {
            return $d['is_available'] == 1;
        });
        $doctors = array_values($doctors);
    }
}

// Function to get correct image path
function getImagePath($image_path) {
    // Remove leading slash if exists
    $clean_path = ltrim($image_path, '/');
    // If path starts with assets/, use as is
    if (strpos($clean_path, 'assets/') === 0) {
        return $clean_path;
    }
    // Default fallback
    return 'assets/doctor-placeholder.png';
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name    = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page = 'Alldoctors.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Doctors - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="./Css/Alldoctors.css">
</head>
<body>

<!-- ═════════════ NAVBAR ═════════════ -->
<?php require_once 'navbar.php'; ?>

<!-- ═════════════ PAGE CONTENT ═════════════ -->
<div class="page-wrap">

    <p class="browse-label">Browse through the doctors specialist.</p>

    <div class="doctors-layout">

        <!-- ── SIDEBAR: Speciality filters ── -->
        <aside class="sidebar">
            <div class="spec-filter-list">
                <?php foreach ($specialities as $spec): ?>
                <a
                    href="Alldoctors.php<?php echo ($active_spec === $spec) ? '' : '?speciality='.urlencode($spec); ?>"
                    class="spec-filter-item<?php echo ($active_spec === $spec) ? ' active' : ''; ?>"
                >
                    <?php echo htmlspecialchars($spec); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($active_spec)): ?>
            <a href="Alldoctors.php" class="clear-filter">
                <i class="fas fa-times"></i> Clear Filter
            </a>
            <?php endif; ?>
        </aside>

        <!-- ── MAIN: Doctor cards grid ── -->
        <main class="doctors-main">
            <div class="results-count">
                <?php if (!empty($active_spec)): ?>
                    Showing <span><?php echo count($doctors); ?></span> doctor(s) in <span><?php echo htmlspecialchars($active_spec); ?></span>
                <?php else: ?>
                    Showing all <span><?php echo count($doctors); ?></span> available doctor(s)
                <?php endif; ?>
            </div>

            <div class="doctors-grid stagger">
                <?php if (!empty($doctors)): ?>
                    <?php foreach ($doctors as $doc):
                        // Fix the image path
                        $image_path = ltrim($doc['profile_image'], '/');
                        $fallback = "https://placehold.co/300x370/dce3ff/5f6fff?text=" . rawurlencode($doc['name']);
                    ?>
                    <a href="appointment.php?doctor=<?php echo urlencode($doc['doctor_id']); ?>" class="doc-card fade-up">
                        <div class="doc-img-wrap">
                            <img
                                src="<?php echo htmlspecialchars($image_path); ?>"
                                alt="<?php echo htmlspecialchars($doc['name']); ?>"
                                loading="lazy"
                                onerror="this.src='<?php echo $fallback; ?>'"
                            >
                        </div>
                        <div class="doc-body">
                            <?php if ($doc['is_available']): ?>
                                <div class="doc-avail">Available</div>
                            <?php else: ?>
                                <div class="doc-unavail">Not Available</div>
                            <?php endif; ?>
                            <div class="doc-name"><?php echo htmlspecialchars($doc['name']); ?></div>
                            <div class="doc-spec"><?php echo htmlspecialchars($doc['speciality']); ?></div>
                            <?php if (isset($doc['fees'])): ?>
                            <div class="doc-fees">K<?php echo number_format($doc['fees'], 2); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-doctor"></i>
                        <p>No doctors found<?php echo !empty($active_spec) ? ' for "' . htmlspecialchars($active_spec) . '"' : ''; ?>.</p>
                        <?php if (!empty($active_spec)): ?>
                        <a href="Alldoctors.php" style="display:inline-block; margin-top:1rem; color:#5f6fff;">View all doctors</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

    </div><!-- /.doctors-layout -->
</div><!-- /.page-wrap -->


<!-- ═════════════ FOOTER ═════════════ -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <img src="assets/logo.svg" width="100px" alt="K&amp;E Hospital">
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
(function() {
    /* Scroll fade-in */
    var fadeEls = document.querySelectorAll('.fade-up');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
            });
        }, { threshold: 0.08 });
        for (var i = 0; i < fadeEls.length; i++) { io.observe(fadeEls[i]); }
    } else {
        for (var j = 0; j < fadeEls.length; j++) { fadeEls[j].classList.add('visible'); }
    }
})();
</script>
</body>
</html>