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
        array('doctor_id'=>'doc1',  'name'=>'Dr. Mwila Banda',       'profile_image'=>'assets/doc1.jpg',  'speciality'=>'General Physician','degree'=>'MBChB','experience'=>'5 Years','fees'=>250,'rating'=>4.5,'total_reviews'=>28,'is_available'=>1),
        array('doctor_id'=>'doc2',  'name'=>'Dr. Mutinta Phiri',     'profile_image'=>'assets/doc2.jpg',  'speciality'=>'Gynecologist',     'degree'=>'MBChB','experience'=>'3 Years','fees'=>300,'rating'=>4.8,'total_reviews'=>42,'is_available'=>1),
        array('doctor_id'=>'doc3',  'name'=>'Dr. Luyando Zulu',      'profile_image'=>'assets/doc3.jpg',  'speciality'=>'Dermatologist',    'degree'=>'MBChB','experience'=>'2 Years','fees'=>220,'rating'=>4.2,'total_reviews'=>15,'is_available'=>1),
        array('doctor_id'=>'doc4',  'name'=>'Dr. Christopher Tembo', 'profile_image'=>'assets/doc4.jpg',  'speciality'=>'Pediatrician',     'degree'=>'MBChB','experience'=>'4 Years','fees'=>280,'rating'=>4.7,'total_reviews'=>35,'is_available'=>1),
        array('doctor_id'=>'doc5',  'name'=>'Dr. Chipo Mwansa',      'profile_image'=>'assets/doc5.jpg',  'speciality'=>'Neurologist',      'degree'=>'MBChB','experience'=>'6 Years','fees'=>350,'rating'=>4.9,'total_reviews'=>52,'is_available'=>1),
        array('doctor_id'=>'doc6',  'name'=>'Dr. Kelvin Mulenga',    'profile_image'=>'assets/doc6.jpg',  'speciality'=>'Neurologist',      'degree'=>'MBChB','experience'=>'5 Years','fees'=>320,'rating'=>4.6,'total_reviews'=>31,'is_available'=>1),
        array('doctor_id'=>'doc7',  'name'=>'Dr. Patrick Tembo',     'profile_image'=>'assets/doc7.jpg',  'speciality'=>'General Physician','degree'=>'MBChB','experience'=>'4 Years','fees'=>260,'rating'=>4.4,'total_reviews'=>23,'is_available'=>1),
        array('doctor_id'=>'doc8',  'name'=>'Dr. Lillian Chanda',    'profile_image'=>'assets/doc8.jpg',  'speciality'=>'Gynecologist',     'degree'=>'MBChB','experience'=>'3 Years','fees'=>300,'rating'=>4.7,'total_reviews'=>38,'is_available'=>1),
        array('doctor_id'=>'doc9',  'name'=>'Dr. Thandiwe Kapasa',   'profile_image'=>'assets/doc9.jpg',  'speciality'=>'Dermatologist',    'degree'=>'MBChB','experience'=>'2 Years','fees'=>220,'rating'=>4.3,'total_reviews'=>19,'is_available'=>1),
        array('doctor_id'=>'doc10', 'name'=>'Dr. Joseph Mwansa',     'profile_image'=>'assets/doc10.jpg', 'speciality'=>'Pediatrician',     'degree'=>'MBChB','experience'=>'4 Years','fees'=>280,'rating'=>4.5,'total_reviews'=>27,'is_available'=>1),
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
<style>
/* ═══════════════════ RESET ═══════════════════ */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior:smooth; }
body {
    font-family:'Outfit', sans-serif;
    color:#3c3c3c;
    background:#fff;
    overflow-x:hidden;
}
a { text-decoration:none; color:inherit; }
img { display:block; max-width:100%; }

/* ═══════════════════ PAGE BODY ═══════════════════ */
.page-wrap {
    max-width:1200px; margin:0 auto;
    padding:2.5rem 6% 5rem;
}

.browse-label {
    font-size:0.9rem; color:#696969;
    margin-bottom:2rem;
}

/* ═══════════════════ TWO-COLUMN LAYOUT ═══════════════════ */
.doctors-layout {
    display:flex; gap:2rem; align-items:flex-start;
}

/* ── SIDEBAR ── */
.sidebar {
    width:220px; flex-shrink:0;
}

.spec-filter-list {
    display:flex; flex-direction:column; gap:0;
    border:1px solid #e8e8f0; border-radius:12px;
    overflow:hidden;
}

.spec-filter-item {
    display:block; padding:0.85rem 1.2rem;
    font-size:0.875rem; font-weight:500; color:#3c3c3c;
    border-bottom:1px solid #eff0f7;
    cursor:pointer; transition:all 0.2s;
    background:#fff;
}
.spec-filter-item:last-child { border-bottom:none; }
.spec-filter-item:hover { background:#f5f5ff; color:#5f6fff; }
.spec-filter-item.active {
    background:#eef0ff; color:#5f6fff;
    font-weight:600;
    border-left:3px solid #5f6fff;
    padding-left:calc(1.2rem - 3px);
}

/* Clear filter link */
.clear-filter {
    display:block; text-align:center;
    margin-top:1rem; padding:0.65rem;
    background:#f8f9fc; border-radius:10px;
    font-size:0.8rem; font-weight:500; color:#5f6fff;
    transition:all 0.2s;
}
.clear-filter:hover {
    background:#eef0ff;
}

/* ── DOCTOR GRID ── */
.doctors-main { flex:1; min-width:0; }

.doctors-grid {
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:1.25rem;
}

/* Doctor card */
.doc-card {
    border:1px solid #e5e7f0; border-radius:12px;
    overflow:hidden; cursor:pointer;
    transition:all 0.3s; background:#fff;
    display:block; color:inherit;
}
.doc-card:hover {
    transform:translateY(-5px);
    box-shadow:0 12px 32px rgba(95,111,255,0.13);
    border-color:#c5caff;
}
.doc-img-wrap {
    background:linear-gradient(160deg, #dce3ff 0%, #eaf0ff 100%);
    height:190px; overflow:hidden;
    display:flex; align-items:flex-end; justify-content:center;
}
.doc-img-wrap img {
    width:100%; height:100%;
    object-fit:cover; object-position:top center;
    transition:transform 0.4s;
}
.doc-card:hover .doc-img-wrap img { transform:scale(1.04); }

.doc-body { padding:0.875rem 1rem; }
.doc-avail {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.7rem; font-weight:600; color:#22c55e;
    margin-bottom:0.3rem;
}
.doc-avail::before {
    content:''; width:7px; height:7px; border-radius:50%;
    background:#22c55e; display:inline-block;
    animation:blink 2s infinite;
}
.doc-unavail {
    display:inline-flex; align-items:center; gap:5px;
    font-size:0.7rem; font-weight:600; color:#94a3b8;
    margin-bottom:0.3rem;
}
.doc-unavail::before {
    content:''; width:7px; height:7px; border-radius:50%;
    background:#94a3b8; display:inline-block;
}
@keyframes blink {
    0%,100%{ opacity:1; } 50%{ opacity:0.35; }
}
.doc-name {
    font-size:0.95rem; font-weight:600; color:#1a1a2e;
    margin-bottom:0.15rem;
}
.doc-spec { font-size:0.78rem; color:#696969; }
.doc-fees {
    font-size:0.75rem; color:#5f6fff; font-weight:500;
    margin-top:0.4rem;
}

/* Empty state */
.empty-state {
    grid-column:1/-1; text-align:center;
    padding:4rem 2rem; color:#aaa;
}
.empty-state i { font-size:2.5rem; margin-bottom:1rem; display:block; opacity:0.4; }
.empty-state p { font-size:0.95rem; }

/* Results count */
.results-count {
    margin-bottom:1.5rem;
    font-size:0.85rem;
    color:#64748b;
}
.results-count span {
    font-weight:600;
    color:#5f6fff;
}

/* ═══════════════════ FOOTER ═══════════════════ */
footer {
    background:#fff; border-top:1px solid #ebebeb;
    padding:3.5rem 6% 2rem;
}
.footer-grid {
    display:grid; grid-template-columns:2fr 1fr 1fr;
    gap:3rem; margin-bottom:2.5rem;
}
.footer-logo {
    display:flex; align-items:center; gap:8px;
    font-size:1.15rem; font-weight:700; color:#1a1a2e; margin-bottom:0.875rem;
}
.f-icon {
    width:30px; height:30px; background:#5f6fff; border-radius:7px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:0.85rem;
}
.footer-desc { font-size:0.85rem; color:#777; line-height:1.7; max-width:300px; }
.footer-col h4 {
    font-size:0.82rem; font-weight:700; color:#1a1a2e;
    text-transform:uppercase; letter-spacing:0.06em; margin-bottom:1.1rem;
}
.footer-col ul { list-style:none; }
.footer-col ul li { margin-bottom:0.6rem; }
.footer-col ul li a { font-size:0.875rem; color:#696969; transition:color 0.2s; }
.footer-col ul li a:hover { color:#5f6fff; }
.footer-col ul li { font-size:0.875rem; color:#696969; }
.footer-bottom {
    border-top:1px solid #ebebeb; padding-top:1.25rem;
    text-align:center; font-size:0.8rem; color:#aaa;
}

/* ═══════════════════ FADE-IN ═══════════════════ */
.fade-up {
    opacity:0; transform:translateY(20px);
    transition:opacity 0.5s ease, transform 0.5s ease;
}
.fade-up.visible { opacity:1; transform:translateY(0); }
.stagger .fade-up:nth-child(1)  { transition-delay:0.03s; }
.stagger .fade-up:nth-child(2)  { transition-delay:0.06s; }
.stagger .fade-up:nth-child(3)  { transition-delay:0.09s; }
.stagger .fade-up:nth-child(4)  { transition-delay:0.12s; }
.stagger .fade-up:nth-child(5)  { transition-delay:0.15s; }
.stagger .fade-up:nth-child(6)  { transition-delay:0.18s; }
.stagger .fade-up:nth-child(7)  { transition-delay:0.21s; }
.stagger .fade-up:nth-child(8)  { transition-delay:0.24s; }
.stagger .fade-up:nth-child(9)  { transition-delay:0.27s; }
.stagger .fade-up:nth-child(10) { transition-delay:0.30s; }
.stagger .fade-up:nth-child(11) { transition-delay:0.33s; }
.stagger .fade-up:nth-child(12) { transition-delay:0.36s; }
.stagger .fade-up:nth-child(13) { transition-delay:0.39s; }
.stagger .fade-up:nth-child(14) { transition-delay:0.42s; }
.stagger .fade-up:nth-child(15) { transition-delay:0.45s; }

/* ═══════════════════ RESPONSIVE ═══════════════════ */
@media (max-width:1024px) {
    .doctors-grid { grid-template-columns:repeat(3,1fr); }
}

@media (max-width:768px) {
    .doctors-layout { flex-direction:column; gap:1.25rem; }
    .sidebar { width:100%; }

    /* Horizontal scrolling pill filters on mobile */
    .spec-filter-list {
        flex-direction:row; flex-wrap:nowrap;
        overflow-x:auto; border-radius:50px;
        border:none; gap:0.5rem;
        padding-bottom:4px;
        -webkit-overflow-scrolling:touch;
        scrollbar-width:none;
    }
    .spec-filter-list::-webkit-scrollbar { display:none; }
    .spec-filter-item {
        border:1px solid #e0e2f0; border-bottom:1px solid #e0e2f0;
        border-radius:50px; white-space:nowrap;
        padding:0.45rem 1rem; flex-shrink:0;
    }
    .spec-filter-item.active {
        border-left:1px solid #5f6fff;
        padding-left:1rem;
        border-radius:50px;
    }
    .clear-filter {
        display:none;
    }

    .doctors-grid { grid-template-columns:repeat(2,1fr); gap:0.9rem; }
    .page-wrap { padding:1.75rem 5% 4rem; }

    .footer-grid { grid-template-columns:1fr 1fr; gap:2rem; }
}

@media (max-width:480px) {
    .doctors-grid { grid-template-columns:repeat(2,1fr); gap:0.75rem; }
    .doc-img-wrap { height:150px; }
    .doc-name { font-size:0.82rem; }
    .footer-grid { grid-template-columns:1fr; }
    footer { padding:2.5rem 5% 1.5rem; }
}
</style>
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
                        $fallback = "https://placehold.co/300x370/dce3ff/5f6fff?text=" . rawurlencode($doc['name']);
                    ?>
                    <a href="appointment.php?doctor=<?php echo urlencode($doc['doctor_id']); ?>" class="doc-card fade-up">
                        <div class="doc-img-wrap">
                            <img
                                src="<?php echo htmlspecialchars($doc['profile_image']); ?>"
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