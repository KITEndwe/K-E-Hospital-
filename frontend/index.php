<?php
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$doctors      = array();
$specialities = array();

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT doctor_id, name, profile_image, speciality, degree, experience, fees, rating, total_reviews, is_available
        FROM doctors
        WHERE is_available = 1
        ORDER BY rating DESC, total_reviews DESC
        LIMIT 10
    ");
    $doctors = $stmt->fetchAll();

    $spec_stmt    = $pdo->query("SELECT DISTINCT speciality FROM doctors ORDER BY speciality");
    $specialities = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    /* Fallback — images match SQL: /assets/docX.png stripped to assets/docX.png */
    $doctors = array(
        array('doctor_id'=>'doc5',  'name'=>'Dr. Chipo Mwansa',      'profile_image'=>'assets/doc5.png',  'speciality'=>'Neurologist',      'degree'=>'MBChB','experience'=>'6 Years','fees'=>350,'rating'=>4.9,'total_reviews'=>52,'is_available'=>1),
        array('doctor_id'=>'doc2',  'name'=>'Dr. Mutinta Phiri',     'profile_image'=>'assets/doc2.png',  'speciality'=>'Gynecologist',     'degree'=>'MBChB','experience'=>'3 Years','fees'=>300,'rating'=>4.8,'total_reviews'=>42,'is_available'=>1),
        array('doctor_id'=>'doc4',  'name'=>'Dr. Christopher Tembo', 'profile_image'=>'assets/doc4.png',  'speciality'=>'Pediatrician',     'degree'=>'MBChB','experience'=>'4 Years','fees'=>280,'rating'=>4.7,'total_reviews'=>35,'is_available'=>1),
        array('doctor_id'=>'doc8',  'name'=>'Dr. Lillian Chanda',    'profile_image'=>'assets/doc8.png',  'speciality'=>'Gynecologist',     'degree'=>'MBChB','experience'=>'3 Years','fees'=>300,'rating'=>4.7,'total_reviews'=>38,'is_available'=>1),
        array('doctor_id'=>'doc6',  'name'=>'Dr. Kelvin Mulenga',    'profile_image'=>'assets/doc6.png',  'speciality'=>'Neurologist',      'degree'=>'MBChB','experience'=>'5 Years','fees'=>320,'rating'=>4.6,'total_reviews'=>31,'is_available'=>1),
        array('doctor_id'=>'doc1',  'name'=>'Dr. Mwila Banda',       'profile_image'=>'assets/doc1.png',  'speciality'=>'General Physician','degree'=>'MBChB','experience'=>'5 Years','fees'=>250,'rating'=>4.5,'total_reviews'=>28,'is_available'=>1),
        array('doctor_id'=>'doc10', 'name'=>'Dr. Joseph Mwansa',     'profile_image'=>'assets/doc10.png', 'speciality'=>'Pediatrician',     'degree'=>'MBChB','experience'=>'4 Years','fees'=>280,'rating'=>4.5,'total_reviews'=>27,'is_available'=>1),
        array('doctor_id'=>'doc7',  'name'=>'Dr. Patrick Tembo',     'profile_image'=>'assets/doc7.png',  'speciality'=>'General Physician','degree'=>'MBChB','experience'=>'4 Years','fees'=>260,'rating'=>4.4,'total_reviews'=>23,'is_available'=>1),
        array('doctor_id'=>'doc9',  'name'=>'Dr. Thandiwe Kapasa',   'profile_image'=>'assets/doc9.png',  'speciality'=>'Dermatologist',    'degree'=>'MBChB','experience'=>'2 Years','fees'=>220,'rating'=>4.3,'total_reviews'=>19,'is_available'=>1),
        array('doctor_id'=>'doc3',  'name'=>'Dr. Luyando Zulu',      'profile_image'=>'assets/doc3.png',  'speciality'=>'Dermatologist',    'degree'=>'MBChB','experience'=>'2 Years','fees'=>220,'rating'=>4.2,'total_reviews'=>15,'is_available'=>1),
    );
}

/*
 * FIX DOCTOR IMAGE PATHS
 * The DB stores paths as  /assets/doc1.png  (leading slash, absolute from project root).
 * This file lives in  frontend/  so we need  assets/doc1.png  (no leading slash).
 * Strip any leading slash so the browser resolves it relative to frontend/.
 */
foreach ($doctors as $k => $doc) {
    $img = isset($doc['profile_image']) ? $doc['profile_image'] : '';
    $doctors[$k]['profile_image'] = ltrim($img, '/');
}

/* Speciality list */
$spec_list = array(
    array('label'=>'General physician', 'icon'=>'assets/speciality/General_physician.png',  'emoji'=>'&#x1F9BA;'),
    array('label'=>'Gynecologist',      'icon'=>'assets/speciality/Gynecologist.png',        'emoji'=>'&#x1F469;&#x200D;&#x2695;&#xFE0F;'),
    array('label'=>'Dermatologist',     'icon'=>'assets/speciality/Dermatologist.png',       'emoji'=>'&#x1F9F4;'),
    array('label'=>'Pediatricians',     'icon'=>'assets/speciality/Pediatricians.png',       'emoji'=>'&#x1F476;'),
    array('label'=>'Neurologist',       'icon'=>'assets/speciality/Neurologist.png',         'emoji'=>'&#x1F9E0;'),
    array('label'=>'Gastroenterologist','icon'=>'assets/speciality/Gastroenterologist.png',  'emoji'=>'&#x1FAC1;'),
);

$is_logged_in  = isset($_SESSION['user_id']);
$user_name     = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page  = 'index.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>K&amp;E Hospital - Book Appointment With Trusted Doctors</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="./Css/index.css">
</head>
<body>

<?php require_once 'navbar.php'; ?>

<!-- ── HERO ── -->
<section class="hero">
    <div class="hero-left">
        <h1>Book Appointment<br>With Trusted Doctors</h1>
        <div class="hero-trust">
            <div class="trust-avatars">
                <img src="assets/group_profiles.png" alt="">
            </div>
            <p>Simply browse through our extensive list of trusted doctors, schedule your appointment hassle-free.</p>
        </div>
        <a href="Alldoctors.php" class="btn-book">
            Book appointment <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="hero-right">
        <img src="assets/header_img.png" alt="Trusted Doctors" onerror="this.parentElement.style.display='none'">
    </div>
</section>

<!-- ── FIND BY SPECIALITY ── -->
<section class="section-speciality">
    <div class="section-header fade-up">
        <h2>Find by Speciality</h2>
        <p>Simply browse through our extensive list of trusted doctors, schedule your appointment hassle-free.</p>
    </div>
    <div class="speciality-row stagger">
        <?php foreach ($spec_list as $spec): ?>
        <a href="Alldoctors.php?speciality=<?php echo urlencode($spec['label']); ?>" class="spec-card fade-up">
            <div class="spec-icon">
                <img
                    src="<?php echo htmlspecialchars($spec['icon']); ?>"
                    alt="<?php echo htmlspecialchars($spec['label']); ?>"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                <span class="spec-emoji" style="display:none;"><?php echo $spec['emoji']; ?></span>
            </div>
            <span><?php echo htmlspecialchars($spec['label']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ── TOP DOCTORS ── -->
<section class="section-doctors">
    <div class="section-header fade-up">
        <h2>Top Doctors to Book</h2>
        <p>Simply browse through our extensive list of trusted doctors.</p>
    </div>
    <?php if (!empty($doctors)): ?>
    <div class="doctors-grid stagger">
        <?php foreach ($doctors as $doc):
            /* DB path: /assets/doc1.png → strip leading slash → assets/doc1.png
               This works because index.php is in frontend/ and assets/ is also in frontend/ */
            $img_src  = ltrim(isset($doc['profile_image']) ? $doc['profile_image'] : '', '/');
            $fallback = 'https://placehold.co/300x370/dce3ff/5f6fff?text=' . rawurlencode($doc['name']);
        ?>
        <a href="appointment.php?doctor=<?php echo urlencode($doc['doctor_id']); ?>" class="doc-card fade-up">
            <div class="doc-img-wrap">
                <img
                    src="<?php echo htmlspecialchars($img_src); ?>"
                    alt="<?php echo htmlspecialchars($doc['name']); ?>"
                    loading="lazy"
                    onerror="this.src='<?php echo $fallback; ?>'">
            </div>
            <div class="doc-body">
                <?php if ($doc['is_available']): ?>
                <div class="doc-avail">Available</div>
                <?php endif; ?>
                <div class="doc-name"><?php echo htmlspecialchars($doc['name']); ?></div>
                <div class="doc-spec"><?php echo htmlspecialchars($doc['speciality']); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="more-wrap fade-up">
        <a href="Alldoctors.php" class="btn-more">More....</a>
    </div>
    <?php else: ?>
    <p style="text-align:center;color:#888;padding:2rem 0;">No doctors available at the moment.</p>
    <?php endif; ?>
</section>

<!-- ── CTA BANNER ── -->
<div class="section-cta fade-up">
    <div class="cta-text">
        <h2>Book Appointment<br>With 100+ Trusted Doctors</h2>
        <a href="login.php" class="btn-cta">Create account</a>
    </div>
    <img class="cta-img" src="assets/appointment_img.png" alt="Doctor" onerror="this.style.display='none'">
</div>

<!-- ── FOOTER ── -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
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

<!-- ── SCRIPTS: scroll fade only (navbar JS is in navbar.php) ── -->
<script>
(function() {
    var fadeEls = document.querySelectorAll('.fade-up');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
            });
        }, { threshold: 0.1 });
        for (var i = 0; i < fadeEls.length; i++) { io.observe(fadeEls[i]); }
    } else {
        for (var j = 0; j < fadeEls.length; j++) { fadeEls[j].classList.add('visible'); }
    }
})();
</script>
</body>
</html>