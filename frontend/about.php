<?php
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$is_logged_in = isset($_SESSION['user_id']);
$user_name    = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us - K&amp;E Hospital</title>
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

/* ═══════════════════ PAGE CONTENT ═══════════════════ */
.page-wrap {
    max-width:1200px; margin:0 auto;
    padding:3rem 6% 5rem;
}

/* About Hero */
.about-hero {
    text-align:center;
    margin-bottom:3.5rem;
}
.about-hero h1 {
    font-size:clamp(1.75rem,3.5vw,2.5rem);
    font-weight:700; color:#1a1a2e; margin-bottom:1rem;
}
.hero-line {
    width:80px; height:4px; background:#5f6fff;
    margin:1rem auto; border-radius:4px;
}
.about-hero p {
    font-size:1rem; color:#696969;
    max-width:650px; margin:0 auto; line-height:1.7;
}

/* About Grid */
.about-grid {
    display:grid; grid-template-columns:1fr 1.2fr;
    gap:3rem; margin-bottom:5rem; align-items:center;
}
.about-image {
    border-radius:20px; overflow:hidden;
    background:linear-gradient(160deg, #dce3ff 0%, #eaf0ff 100%);
    box-shadow:0 20px 40px rgba(95,111,255,0.15);
    aspect-ratio:4/3;
    display:flex; align-items:center; justify-content:center;
}
.about-image img {
    width:100%; height:100%; object-fit:cover; transition:transform 0.4s;
}
.about-image:hover img { transform:scale(1.02); }
.about-image .img-placeholder {
    display:flex; align-items:center; justify-content:center;
    width:100%; height:100%;
}
.about-image .img-placeholder i {
    font-size:5rem; color:#8b9aff;
}
.about-text h2 {
    font-size:1.8rem; font-weight:600; color:#1a1a2e; margin-bottom:1rem;
}
.about-text .subhead {
    font-size:1rem; color:#5f6fff; font-weight:500; margin-bottom:1.5rem;
}
.about-text p {
    font-size:0.95rem; color:#696969; line-height:1.7; margin-bottom:1.2rem;
}
.about-stats {
    display:flex; gap:2rem; margin-top:2rem;
}
.stat-item { text-align:center; }
.stat-number {
    font-size:2rem; font-weight:700; color:#5f6fff; display:block;
}
.stat-label { font-size:0.8rem; color:#696969; }

/* Mission & Vision */
.mission-vision {
    display:grid; grid-template-columns:1fr 1fr;
    gap:2rem; margin-bottom:5rem;
}
.mv-card {
    background:#fff; border:1px solid #e5e7f0; border-radius:16px;
    padding:2rem; transition:all 0.3s; text-align:center;
}
.mv-card:hover {
    transform:translateY(-5px);
    box-shadow:0 12px 32px rgba(95,111,255,0.1);
    border-color:#c5caff;
}
.mv-icon {
    width:70px; height:70px; background:#eef0ff; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 1.5rem;
}
.mv-icon i { font-size:2rem; color:#5f6fff; }
.mv-card h3 { font-size:1.3rem; font-weight:600; color:#1a1a2e; margin-bottom:1rem; }
.mv-card p { font-size:0.9rem; color:#696969; line-height:1.7; }

/* Values */
.values-section { margin-bottom:5rem; }
.section-header { text-align:center; margin-bottom:2.5rem; }
.section-header h2 {
    font-size:clamp(1.35rem,2.5vw,1.9rem);
    font-weight:700; color:#1a1a2e; margin-bottom:0.55rem;
}
.section-header p { font-size:0.875rem; color:#696969; max-width:500px; margin:0 auto; }
.values-grid {
    display:grid; grid-template-columns:repeat(4,1fr); gap:1.5rem;
}
.value-card {
    text-align:center; padding:1.5rem;
    background:#fafaff; border-radius:12px; transition:all 0.3s;
}
.value-card:hover {
    background:#fff;
    box-shadow:0 8px 24px rgba(95,111,255,0.1);
    transform:translateY(-3px);
}
.value-icon {
    width:60px; height:60px; background:#eef0ff; border-radius:50%;
    display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;
}
.value-icon i { font-size:1.6rem; color:#5f6fff; }
.value-card h4 { font-size:1rem; font-weight:600; color:#1a1a2e; margin-bottom:0.5rem; }
.value-card p { font-size:0.8rem; color:#696969; }

/* ═══ TEAM SECTION ═══ */
.team-section { margin-bottom:5rem; }
.team-grid {
    display:grid; grid-template-columns:repeat(3,1fr); gap:2rem;
}
.team-card {
    background:#fff; border:1px solid #e5e7f0; border-radius:16px;
    overflow:hidden; transition:all 0.3s; text-align:center;
}
.team-card:hover {
    transform:translateY(-5px);
    box-shadow:0 12px 32px rgba(95,111,255,0.1);
    border-color:#c5caff;
}

/* Fixed image container — square crop, fills card width */
.team-image {
    width:100%;
    /* 4:3 ratio so portrait photos look good */
    aspect-ratio:3/4;
    overflow:hidden;
    background:linear-gradient(160deg, #dce3ff 0%, #eaf0ff 100%);
    display:flex; align-items:center; justify-content:center;
    position:relative;
}
.team-image img {
    /* Fill the container, crop to fit */
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:top center;
    transition:transform 0.4s ease;
    /* Make sure img is not block with extra spacing */
    display:block;
    /* Remove the default img max-width:100% because we set explicit height */
    max-width:none;
}
.team-card:hover .team-image img { transform:scale(1.04); }

/* Fallback icon when image fails */
.team-image .team-fallback {
    display:none;
    position:absolute; inset:0;
    align-items:center; justify-content:center;
}
.team-image .team-fallback i { font-size:4rem; color:#8b9aff; }

.team-info { padding:1.25rem 1.5rem 1.5rem; }
.team-info h4 {
    font-size:1.05rem; font-weight:600; color:#1a1a2e; margin-bottom:0.3rem;
}
.team-info p { font-size:0.82rem; color:#696969; }

/* CTA */
.cta-section {
    background:linear-gradient(120deg,#5f6fff 0%,#7c8cff 100%);
    border-radius:20px; padding:3rem; text-align:center;
    position:relative; overflow:hidden;
}
.cta-section::before {
    content:''; position:absolute; top:-60px; left:35%;
    width:320px; height:320px;
    background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 70%);
    border-radius:50%; pointer-events:none;
}
.cta-section h2 {
    font-size:clamp(1.3rem,2.5vw,1.8rem); font-weight:700; color:#fff;
    margin-bottom:1rem; position:relative; z-index:2;
}
.cta-section p {
    font-size:0.9rem; color:rgba(255,255,255,0.9);
    margin-bottom:1.5rem; position:relative; z-index:2;
}
.cta-btn {
    display:inline-block; background:#fff; color:#5f6fff;
    padding:0.75rem 2rem; border-radius:50px;
    font-size:0.9rem; font-weight:600; transition:all 0.25s;
    position:relative; z-index:2;
}
.cta-btn:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.18); }

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
.footer-logo img { width:100px; height:auto; }
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
.stagger .fade-up:nth-child(1) { transition-delay:0.05s; }
.stagger .fade-up:nth-child(2) { transition-delay:0.10s; }
.stagger .fade-up:nth-child(3) { transition-delay:0.15s; }
.stagger .fade-up:nth-child(4) { transition-delay:0.20s; }
.stagger .fade-up:nth-child(5) { transition-delay:0.25s; }
.stagger .fade-up:nth-child(6) { transition-delay:0.30s; }
.stagger .fade-up:nth-child(7) { transition-delay:0.35s; }
.stagger .fade-up:nth-child(8) { transition-delay:0.40s; }

/* ═══════════════════ RESPONSIVE ═══════════════════ */
@media (max-width:1024px) {
    .values-grid { grid-template-columns:repeat(2,1fr); }
    .team-grid   { grid-template-columns:repeat(2,1fr); }
}

@media (max-width:768px) {

    .about-grid     { grid-template-columns:1fr; gap:2rem; }
    .mission-vision { grid-template-columns:1fr; }
    .values-grid    { grid-template-columns:repeat(2,1fr); }
    .team-grid      { grid-template-columns:repeat(2,1fr); }
    .about-stats    { justify-content:center; }
    .footer-grid    { grid-template-columns:1fr 1fr; gap:2rem; }
    .page-wrap      { padding:2rem 5% 3rem; }
    .cta-section    { padding:2rem; }
}

@media (max-width:480px) {
    .about-stats { flex-direction:column; gap:1rem; align-items:center; }
    .values-grid { grid-template-columns:1fr; }
    .team-grid   { grid-template-columns:1fr; }
    .footer-grid { grid-template-columns:1fr; }
    .mv-card     { padding:1.5rem; }
}
</style>
</head>
<body>

<!-- ═════════════ NAVBAR ═════════════ -->
<?php
$current_page = 'about.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
require_once 'navbar.php';
?>

<!-- ═════════════<!-- ═════════════ PAGE CONTENT ═════════════ -->
<div class="page-wrap">

    <!-- Hero -->
    <div class="about-hero fade-up">
        <h1>About K&amp;E Hospital</h1>
        <div class="hero-line"></div>
        <p>Your Health, Our Priority — Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips.</p>
    </div>

    <!-- About Grid -->
    <div class="about-grid">
        <div class="about-image fade-up">
            <img
                src="assets/about_image.png"
                alt="K&amp;E Hospital Doctors"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
            >
            <div class="img-placeholder" style="display:none;">
                <i class="fas fa-hospital-user"></i>
            </div>
        </div>
        <div class="about-text fade-up">
            <h2>Welcome to K&amp;E Hospital</h2>
            <div class="subhead">Your Trusted Healthcare Partner</div>
            <p>At K&amp;E Hospital, we understand the challenges individuals face when it comes to scheduling doctor appointments and managing their health records. We are committed to providing accessible, quality healthcare services to all Zambians.</p>
            <p>K&amp;E Hospital is committed to excellence in healthcare technology. We continuously strive to enhance our platform, integrating the latest advancements to improve user experience and deliver superior service.</p>
            <div class="about-stats">
                <div class="stat-item">
                    <span class="stat-number">10+</span>
                    <span class="stat-label">Trusted Doctors</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">100+</span>
                    <span class="stat-label">Happy Patients</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Support Available</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="mission-vision stagger">
        <div class="mv-card fade-up">
            <div class="mv-icon"><i class="fas fa-bullseye"></i></div>
            <h3>Our Mission</h3>
            <p>To provide accessible, affordable, and quality healthcare services to every Zambian through innovative technology and compassionate care.</p>
        </div>
        <div class="mv-card fade-up">
            <div class="mv-icon"><i class="fas fa-eye"></i></div>
            <h3>Our Vision</h3>
            <p>To create a seamless healthcare experience for every user, bridging the gap between patients and healthcare providers across Zambia.</p>
        </div>
    </div>

    <!-- Our Values -->
    <div class="values-section">
        <div class="section-header fade-up">
            <h2>Our Core Values</h2>
            <p>The principles that guide everything we do</p>
        </div>
        <div class="values-grid stagger">
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-heartbeat"></i></div>
                <h4>Compassion</h4>
                <p>We care for every patient with empathy and respect</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Integrity</h4>
                <p>We uphold the highest standards of honesty and ethics</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-chart-line"></i></div>
                <h4>Excellence</h4>
                <p>We strive for excellence in everything we do</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon"><i class="fas fa-users"></i></div>
                <h4>Innovation</h4>
                <p>We embrace technology to improve healthcare delivery</p>
            </div>
        </div>
    </div>

    <!-- Leadership Team -->
    <div class="team-section">
        <div class="section-header fade-up">
            <h2>Our Leadership</h2>
            <p>Dedicated professionals committed to your health</p>
        </div>
        <div class="team-grid stagger">

            <div class="team-card fade-up">
                <div class="team-image">
                    <img
                        src="assets/doc3.png"
                        alt="Dr. Elijah Mwange"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >
                    <div class="team-fallback"><i class="fas fa-user-doctor"></i></div>
                </div>
                <div class="team-info">
                    <h4>Dr. Elijah Mwange</h4>
                    <p>Chief Medical Officer</p>
                </div>
            </div>

            <div class="team-card fade-up">
                <div class="team-image">
                    <img
                        src="assets/doc1.png"
                        alt="Dr. Kelenga Muma"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >
                    <div class="team-fallback"><i class="fas fa-user-doctor"></i></div>
                </div>
                <div class="team-info">
                    <h4>Dr. Kelenga Muma</h4>
                    <p>Hospital Administrator</p>
                </div>
            </div>

            <div class="team-card fade-up">
                <div class="team-image">
                    <img
                        src="assets/doc13.png"
                        alt="Dr. Joannita Kabemba"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >
                    <div class="team-fallback"><i class="fas fa-user-doctor"></i></div>
                </div>
                <div class="team-info">
                    <h4>Dr. Joannita Kabemba</h4>
                    <p>Head of Nursing</p>
                </div>
            </div>

        </div>
    </div>

    <!-- CTA -->
    <div class="cta-section fade-up">
        <h2>Ready to Book Your Appointment?</h2>
        <p>Join thousands of satisfied patients who trust K&amp;E Hospital for their healthcare needs.</p>
        <a href="Alldoctors.php" class="cta-btn">Find a Doctor <i class="fas fa-arrow-right"></i></a>
    </div>

</div><!-- /.page-wrap -->


<!-- ═════════════ FOOTER ═════════════ -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <img src="assets/logo.svg" alt="K&amp;E Hospital" onerror="this.style.display='none';this.parentElement.innerHTML+='K&amp;E Hospital'">
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
                <li>+260 7610 16446</li>
                <li>admin@kehospital.co.zm</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        Copyright &copy; <?php echo date('Y'); ?> K&amp;E Hospital - All Right Reserved.
    </div>
</footer>


<!-- ═════════════ SCRIPTS ═════════════ -->
<script>
(function() {
    var hamburger  = document.getElementById('hamburger');
    var mobileMenu = document.getElementById('mobileMenu');
    var overlay    = document.getElementById('navOverlay');

    function openMenu() {
        hamburger.classList.add('open');
        mobileMenu.classList.add('open');
        overlay.classList.add('open');
        hamburger.setAttribute('aria-expanded','true');
        document.body.style.overflow = 'hidden';
    }
    function closeMenu() {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        overlay.classList.remove('open');
        hamburger.setAttribute('aria-expanded','false');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', function() {
        mobileMenu.classList.contains('open') ? closeMenu() : openMenu();
    });
    overlay.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function(e) { if (e.key==='Escape') closeMenu(); });
    window.addEventListener('resize', function() { if (window.innerWidth > 768) closeMenu(); });

    /* Scroll fade-in */
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