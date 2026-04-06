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
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior:smooth; }
body { font-family:'Outfit',sans-serif; color:#3c3c3c; background:#fff; overflow-x:hidden; }
a { text-decoration:none; color:inherit; }
img { display:block; max-width:100%; }

/* ── HERO ── */
.hero {
    background:linear-gradient(120deg,#5f6fff 0%,#7c8cff 55%,#a0abff 100%);
    min-height:430px; display:flex; align-items:stretch;
    padding-left:6%; position:relative; overflow:hidden;
}
.hero::before {
    content:''; position:absolute; top:-100px; right:180px;
    width:480px; height:480px;
    background:radial-gradient(circle,rgba(255,255,255,0.13) 0%,transparent 65%);
    border-radius:50%; pointer-events:none;
}
.hero-left {
    position:relative; z-index:2;
    display:flex; flex-direction:column; justify-content:center;
    padding:3.5rem 0; max-width:460px;
}
.hero-left h1 {
    font-size:clamp(1.75rem,3.5vw,2.6rem);
    font-weight:700; color:#fff; line-height:1.18; margin-bottom:1.2rem;
}
.hero-trust { display:flex; align-items:center; gap:0.75rem; margin-bottom:1.75rem; }
.trust-avatars { display:flex; }
.trust-avatars span {
    width:30px; height:30px; border-radius:50%;
    border:2px solid rgba(255,255,255,0.75);
    background:rgba(255,255,255,0.2);
    display:flex; align-items:center; justify-content:center;
    font-size:0.58rem; font-weight:700; color:#fff; margin-left:-7px;
}
.trust-avatars span:first-child { margin-left:0; }
.hero-trust p { font-size:0.8rem; color:rgba(255,255,255,0.88); line-height:1.5; max-width:190px; }
.btn-book {
    display:inline-flex; align-items:center; gap:0.6rem;
    background:#fff; color:#5f6fff; padding:0.75rem 1.75rem; border-radius:50px;
    font-size:0.95rem; font-weight:600; box-shadow:0 4px 18px rgba(0,0,0,0.14);
    transition:all 0.25s; border:none; cursor:pointer; font-family:'Outfit',sans-serif;
}
.btn-book:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(0,0,0,0.2); }
.btn-book i { font-size:0.78rem; }
.hero-right {
    position:absolute; right:4%; bottom:0; height:96%; z-index:2;
    display:flex; align-items:flex-end;
}
.hero-right img {
    height:100%; max-height:430px;
    object-fit:contain; object-position:bottom;
    filter:drop-shadow(0 16px 36px rgba(0,0,0,0.18));
}

/* ── SECTION HEADER ── */
.section-header { text-align:center; margin-bottom:2.5rem; }
.section-header h2 {
    font-size:clamp(1.35rem,2.5vw,1.9rem); font-weight:700; color:#1a1a2e; margin-bottom:0.55rem;
}
.section-header p { font-size:0.875rem; color:#696969; max-width:400px; margin:0 auto; line-height:1.65; }

/* ── SPECIALITY ── */
.section-speciality { padding:5rem 6%; background:#fff; }
.speciality-row { display:flex; flex-wrap:wrap; justify-content:center; gap:1rem; }
.spec-card {
    display:flex; flex-direction:column; align-items:center; gap:0.6rem;
    padding:1.25rem 1.4rem; border:1px solid #e0e2f0; border-radius:12px;
    cursor:pointer; transition:all 0.25s; min-width:110px; text-align:center; background:#fff;
}
.spec-card:hover { border-color:#5f6fff; background:#f0f1ff; transform:translateY(-5px); box-shadow:0 6px 22px rgba(95,111,255,0.13); }
.spec-icon { width:64px; height:64px; border-radius:50%; background:#f0f1ff; display:flex; align-items:center; justify-content:center; overflow:hidden; }
.spec-icon img { width:44px; height:44px; object-fit:contain; }
.spec-icon .spec-emoji { font-size:1.7rem; line-height:1; }
.spec-card span { font-size:0.77rem; font-weight:500; color:#3c3c3c; }

/* ── DOCTORS ── */
.section-doctors { padding:5rem 6%; background:#fff; }
.doctors-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:1.2rem; margin-bottom:2.25rem; }
.doc-card {
    border:1px solid #e5e7f0; border-radius:12px; overflow:hidden;
    cursor:pointer; transition:all 0.3s; background:#fff; display:block; color:inherit;
}
.doc-card:hover { transform:translateY(-6px); box-shadow:0 14px 36px rgba(95,111,255,0.15); border-color:#c5caff; }
.doc-img-wrap {
    background:linear-gradient(160deg,#dce3ff 0%,#eaf0ff 100%);
    height:185px; overflow:hidden; display:flex; align-items:flex-end; justify-content:center;
}
.doc-img-wrap img { width:100%; height:100%; object-fit:cover; object-position:top center; transition:transform 0.4s; }
.doc-card:hover .doc-img-wrap img { transform:scale(1.05); }
.doc-body { padding:0.85rem 1rem; }
.doc-avail {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.7rem; font-weight:600; color:#22c55e; margin-bottom:0.3rem;
}
.doc-avail::before {
    content:''; width:7px; height:7px; border-radius:50%;
    background:#22c55e; display:inline-block; animation:blink 2s infinite;
}
@keyframes blink { 0%,100%{ opacity:1; } 50%{ opacity:0.35; } }
.doc-name { font-size:0.9rem; font-weight:600; color:#1a1a2e; margin-bottom:0.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.doc-spec { font-size:0.76rem; color:#696969; }
.more-wrap { text-align:center; }
.btn-more {
    display:inline-block; border:1px solid #d0d5ee; color:#696969;
    padding:0.6rem 2.25rem; border-radius:50px; font-size:0.875rem;
    font-weight:500; background:#fff; transition:all 0.25s; cursor:pointer;
}
.btn-more:hover { background:#5f6fff; color:#fff; border-color:#5f6fff; }

/* ── CTA BANNER ── */
.section-cta {
    margin:0 6% 5rem;
    background:linear-gradient(120deg,#5f6fff 0%,#7c8cff 100%);
    border-radius:16px; padding:3rem 5%; display:flex; align-items:center;
    justify-content:space-between; gap:2rem; position:relative; overflow:hidden; min-height:180px;
}
.section-cta::before {
    content:''; position:absolute; top:-60px; left:35%; width:320px; height:320px;
    background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 70%);
    border-radius:50%; pointer-events:none;
}
.cta-text { position:relative; z-index:2; }
.cta-text h2 { font-size:clamp(1.25rem,2.5vw,1.85rem); font-weight:700; color:#fff; line-height:1.25; margin-bottom:1.4rem; }
.btn-cta {
    display:inline-block; background:#fff; color:#5f6fff; padding:0.65rem 1.75rem;
    border-radius:50px; font-size:0.875rem; font-weight:600; transition:all 0.25s;
    border:none; cursor:pointer; font-family:'Outfit',sans-serif;
}
.btn-cta:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.18); }
.cta-img { height:310px; position:relative; z-index:2; object-fit:contain; filter:drop-shadow(0 12px 24px rgba(0,0,0,0.2)); }

/* ── FOOTER ── */
footer { background:#fff; border-top:1px solid #ebebeb; padding:3.5rem 6% 2rem; }
.footer-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:3rem; margin-bottom:2.5rem; }
.footer-logo { display:flex; align-items:center; gap:8px; font-size:1.15rem; font-weight:700; color:#1a1a2e; margin-bottom:0.875rem; }
.f-icon { width:30px; height:30px; background:#5f6fff; border-radius:7px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.85rem; }
.footer-desc { font-size:0.85rem; color:#777; line-height:1.7; max-width:300px; }
.footer-col h4 { font-size:0.82rem; font-weight:700; color:#1a1a2e; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:1.1rem; }
.footer-col ul { list-style:none; }
.footer-col ul li { margin-bottom:0.6rem; }
.footer-col ul li a { font-size:0.875rem; color:#696969; transition:color 0.2s; }
.footer-col ul li a:hover { color:#5f6fff; }
.footer-col ul li { font-size:0.875rem; color:#696969; }
.footer-bottom { border-top:1px solid #ebebeb; padding-top:1.25rem; text-align:center; font-size:0.8rem; color:#aaa; }

/* ── FADE-IN ── */
.fade-up { opacity:0; transform:translateY(22px); transition:opacity 0.55s ease, transform 0.55s ease; }
.fade-up.visible { opacity:1; transform:translateY(0); }
.stagger .fade-up:nth-child(1)  { transition-delay:0.04s; }
.stagger .fade-up:nth-child(2)  { transition-delay:0.08s; }
.stagger .fade-up:nth-child(3)  { transition-delay:0.12s; }
.stagger .fade-up:nth-child(4)  { transition-delay:0.16s; }
.stagger .fade-up:nth-child(5)  { transition-delay:0.20s; }
.stagger .fade-up:nth-child(6)  { transition-delay:0.24s; }
.stagger .fade-up:nth-child(7)  { transition-delay:0.28s; }
.stagger .fade-up:nth-child(8)  { transition-delay:0.32s; }
.stagger .fade-up:nth-child(9)  { transition-delay:0.36s; }
.stagger .fade-up:nth-child(10) { transition-delay:0.40s; }

/* ── RESPONSIVE ── */
@media (max-width:1024px) { .doctors-grid { grid-template-columns:repeat(4,1fr); } }
@media (max-width:900px) {
    .hero-right { display:none; }
    .doctors-grid { grid-template-columns:repeat(3,1fr); }
    .footer-grid { grid-template-columns:1fr 1fr; }
}
@media (max-width:768px) {
    .hero { padding-left:5%; min-height:340px; }
    .hero-left { padding:2.5rem 0; }
    .section-speciality, .section-doctors { padding:3.5rem 5%; }
    .doctors-grid { grid-template-columns:repeat(2,1fr); gap:0.9rem; }
    .section-cta { margin:0 5% 4rem; flex-direction:column; text-align:center; }
    .cta-img { display:none; }
    .footer-grid { grid-template-columns:1fr; gap:2rem; }
    footer { padding:2.5rem 5% 1.5rem; }
}
@media (max-width:480px) {
    .hero-left h1 { font-size:1.55rem; }
    .btn-book { font-size:0.875rem; padding:0.65rem 1.5rem; }
    .speciality-row { gap:0.65rem; }
    .spec-card { min-width:86px; padding:0.9rem 0.75rem; }
    .spec-icon { width:52px; height:52px; }
    .spec-icon img { width:36px; height:36px; }
    .doctors-grid { grid-template-columns:repeat(2,1fr); gap:0.75rem; }
    .doc-img-wrap { height:145px; }
    .doc-name { font-size:0.82rem; }
    .section-cta { margin:0 4% 3rem; padding:2rem 5%; }
    .cta-text h2 { font-size:1.2rem; }
}
</style>
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