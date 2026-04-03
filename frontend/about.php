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

/* ═══════════════════ NAVBAR ═══════════════════ */
.navbar {
    position:sticky; top:0; z-index:999;
    background:#fff;
    border-bottom:1px solid #ebebeb;
    height:70px;
    display:flex; align-items:center;
    padding:0 6%;
    justify-content:space-between;
    gap:1rem;
}
.nav-logo {
    display:flex; align-items:center; gap:8px;
    font-size:1.25rem; font-weight:700; color:#1a1a2e;
    flex-shrink:0;
}
.nav-logo img {
    width:100px;
}
.nav-links {
    display:flex; align-items:center; gap:2rem;
    list-style:none; flex:1; justify-content:center;
}
.nav-links a {
    font-size:0.88rem; font-weight:500; color:#595959;
    transition:color 0.2s; position:relative; padding-bottom:4px;
    white-space:nowrap;
}
.nav-links a:hover { color:#1a1a2e; }
.nav-links a.active { color:#1a1a2e; font-weight:600; }
.nav-links a.active::after {
    content:''; position:absolute; bottom:0; left:0; right:0;
    height:2px; background:#5f6fff; border-radius:2px;
}
.nav-cta { display:flex; align-items:center; gap:0.75rem; flex-shrink:0; }
.btn-nav-login {
    background:#fff; color:#5f6fff; border:1.5px solid #5f6fff;
    padding:0.5rem 1.25rem; border-radius:50px;
    font-size:0.85rem; font-weight:600; transition:all 0.2s;
    white-space:nowrap; cursor:pointer; font-family:'Outfit',sans-serif;
}
.btn-nav-login:hover { background:#f0f1ff; }
.btn-nav-create {
    background:#5f6fff; color:#fff; border:none;
    padding:0.55rem 1.4rem; border-radius:50px;
    font-size:0.85rem; font-weight:600; transition:all 0.25s;
    white-space:nowrap; cursor:pointer; font-family:'Outfit',sans-serif;
}
.btn-nav-create:hover {
    background:#4a5af0;
    box-shadow:0 4px 14px rgba(95,111,255,0.35);
    transform:translateY(-1px);
}
.user-pill {
    display:flex; align-items:center; gap:0.5rem;
    background:#f5f5ff; padding:0.35rem 0.9rem; border-radius:50px;
    font-size:0.85rem; font-weight:500; white-space:nowrap;
}
.user-avatar {
    width:26px; height:26px; border-radius:50%;
    background:#5f6fff; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:0.7rem; font-weight:700;
}
.btn-logout-sm {
    font-size:0.78rem; color:#ef4444; font-weight:500;
    border:1px solid #ef4444; padding:0.3rem 0.75rem;
    border-radius:50px; transition:all 0.2s; background:#fff;
}
.btn-logout-sm:hover { background:#ef4444; color:#fff; }

/* Hamburger */
.hamburger {
    display:none; flex-direction:column; justify-content:center; gap:5px;
    width:40px; height:40px; background:none; border:none;
    cursor:pointer; padding:6px; flex-shrink:0;
}
.hamburger span {
    display:block; width:22px; height:2px;
    background:#1a1a2e; border-radius:2px; transition:all 0.3s ease;
}
.hamburger.open span:nth-child(1) { transform:translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity:0; transform:scaleX(0); }
.hamburger.open span:nth-child(3) { transform:translateY(-7px) rotate(-45deg); }

/* Mobile drawer */
.mobile-menu {
    position:fixed; top:70px; left:0; right:0;
    background:#fff; border-bottom:1px solid #ebebeb;
    box-shadow:0 8px 32px rgba(0,0,0,0.12);
    z-index:998; padding:0 6% 1.5rem;
    max-height:0; overflow:hidden;
    transition:max-height 0.35s ease, padding 0.35s ease;
}
.mobile-menu.open { max-height:600px; padding:1rem 6% 1.5rem; }
.mobile-menu a.mob-link {
    display:block; padding:0.85rem 0;
    font-size:0.95rem; font-weight:500; color:#3c3c3c;
    border-bottom:1px solid #f2f2f2; transition:color 0.2s;
}
.mobile-menu a.mob-link:hover,
.mobile-menu a.mob-link.active { color:#5f6fff; }
.mobile-menu .mob-actions {
    margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem;
}
.mob-btn {
    display:block; text-align:center; padding:0.75rem;
    border-radius:50px; font-size:0.9rem; font-weight:600; transition:all 0.2s;
}
.mob-btn-outline { border:1.5px solid #5f6fff; color:#5f6fff; background:#fff; }
.mob-btn-outline:hover { background:#f0f1ff; }
.mob-btn-fill { background:#5f6fff; color:#fff; border:none; }
.mob-btn-fill:hover { background:#4a5af0; }
.nav-overlay {
    display:none; position:fixed; inset:0; top:70px;
    background:rgba(0,0,0,0.25); z-index:997;
}
.nav-overlay.open { display:block; }

/* ═══════════════════ PAGE CONTENT ═══════════════════ */
.page-wrap {
    max-width:1200px; margin:0 auto;
    padding:3rem 6% 5rem;
}

/* About Hero Section */
.about-hero {
    text-align:center;
    margin-bottom:3.5rem;
}
.about-hero h1 {
    font-size:clamp(1.75rem,3.5vw,2.5rem);
    font-weight:700;
    color:#1a1a2e;
    margin-bottom:1rem;
}
.about-hero .hero-line {
    width:80px;
    height:4px;
    background:#5f6fff;
    margin:1rem auto;
    border-radius:4px;
}
.about-hero p {
    font-size:1rem;
    color:#696969;
    max-width:650px;
    margin:0 auto;
    line-height:1.7;
}

/* About Grid Layout */
.about-grid {
    display:grid;
    grid-template-columns:1fr 1.2fr;
    gap:3rem;
    margin-bottom:5rem;
    align-items:center;
}
.about-image {
    border-radius:20px;
    overflow:hidden;
    background:linear-gradient(160deg, #dce3ff 0%, #eaf0ff 100%);
    box-shadow:0 20px 40px rgba(95,111,255,0.15);
}
.about-image img {
    width:100%;
    height:100%;
    object-fit:cover;
    transition:transform 0.4s;
}
.about-image:hover img {
    transform:scale(1.02);
}
.about-text h2 {
    font-size:1.8rem;
    font-weight:600;
    color:#1a1a2e;
    margin-bottom:1rem;
}
.about-text .subhead {
    font-size:1rem;
    color:#5f6fff;
    font-weight:500;
    margin-bottom:1.5rem;
}
.about-text p {
    font-size:0.95rem;
    color:#696969;
    line-height:1.7;
    margin-bottom:1.2rem;
}
.about-stats {
    display:flex;
    gap:2rem;
    margin-top:2rem;
}
.stat-item {
    text-align:center;
}
.stat-number {
    font-size:2rem;
    font-weight:700;
    color:#5f6fff;
    display:block;
}
.stat-label {
    font-size:0.8rem;
    color:#696969;
}

/* Mission & Vision Cards */
.mission-vision {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:2rem;
    margin-bottom:5rem;
}
.mv-card {
    background:#fff;
    border:1px solid #e5e7f0;
    border-radius:16px;
    padding:2rem;
    transition:all 0.3s;
    text-align:center;
}
.mv-card:hover {
    transform:translateY(-5px);
    box-shadow:0 12px 32px rgba(95,111,255,0.1);
    border-color:#c5caff;
}
.mv-icon {
    width:70px;
    height:70px;
    background:#eef0ff;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 1.5rem;
}
.mv-icon i {
    font-size:2rem;
    color:#5f6fff;
}
.mv-card h3 {
    font-size:1.3rem;
    font-weight:600;
    color:#1a1a2e;
    margin-bottom:1rem;
}
.mv-card p {
    font-size:0.9rem;
    color:#696969;
    line-height:1.7;
}

/* Values Section */
.values-section {
    margin-bottom:5rem;
}
.section-header {
    text-align:center;
    margin-bottom:2.5rem;
}
.section-header h2 {
    font-size:clamp(1.35rem,2.5vw,1.9rem);
    font-weight:700;
    color:#1a1a2e;
    margin-bottom:0.55rem;
}
.section-header p {
    font-size:0.875rem;
    color:#696969;
    max-width:500px;
    margin:0 auto;
}
.values-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:1.5rem;
}
.value-card {
    text-align:center;
    padding:1.5rem;
    background:#fafaff;
    border-radius:12px;
    transition:all 0.3s;
}
.value-card:hover {
    background:#fff;
    box-shadow:0 8px 24px rgba(95,111,255,0.1);
    transform:translateY(-3px);
}
.value-icon {
    width:60px;
    height:60px;
    background:#eef0ff;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 1rem;
}
.value-icon i {
    font-size:1.6rem;
    color:#5f6fff;
}
.value-card h4 {
    font-size:1rem;
    font-weight:600;
    color:#1a1a2e;
    margin-bottom:0.5rem;
}
.value-card p {
    font-size:0.8rem;
    color:#696969;
}

/* Team Section */
.team-section {
    margin-bottom:5rem;
}
.team-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:2rem;
}
.team-card {
    text-align:center;
    background:#fff;
    border:1px solid #e5e7f0;
    border-radius:16px;
    overflow:hidden;
    transition:all 0.3s;
}
.team-card:hover {
    transform:translateY(-5px);
    box-shadow:0 12px 32px rgba(95,111,255,0.1);
    border-color:#c5caff;
}
.team-image {
    width:100%;
    height:250px;
    background:linear-gradient(160deg, #dce3ff 0%, #eaf0ff 100%);
    display:flex;
    align-items:center;
    justify-content:center;
}
.team-image i {
    font-size:4rem;
    color:#8b9aff;
}
.team-info {
    padding:1.5rem;
}
.team-info h4 {
    font-size:1.1rem;
    font-weight:600;
    color:#1a1a2e;
    margin-bottom:0.25rem;
}
.team-info p {
    font-size:0.8rem;
    color:#696969;
}

/* CTA Section */
.cta-section {
    background:linear-gradient(120deg,#5f6fff 0%,#7c8cff 100%);
    border-radius:20px;
    padding:3rem;
    text-align:center;
    position:relative;
    overflow:hidden;
}
.cta-section::before {
    content:'';
    position:absolute;
    top:-60px;
    left:35%;
    width:320px;
    height:320px;
    background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 70%);
    border-radius:50%;
    pointer-events:none;
}
.cta-section h2 {
    font-size:clamp(1.3rem,2.5vw,1.8rem);
    font-weight:700;
    color:#fff;
    margin-bottom:1rem;
    position:relative;
    z-index:2;
}
.cta-section p {
    font-size:0.9rem;
    color:rgba(255,255,255,0.9);
    margin-bottom:1.5rem;
    position:relative;
    z-index:2;
}
.cta-btn {
    display:inline-block;
    background:#fff;
    color:#5f6fff;
    padding:0.75rem 2rem;
    border-radius:50px;
    font-size:0.9rem;
    font-weight:600;
    transition:all 0.25s;
    position:relative;
    z-index:2;
}
.cta-btn:hover {
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(0,0,0,0.18);
}

/* ═══════════════════ FOOTER ═══════════════════ */
footer {
    background:#fff;
    border-top:1px solid #ebebeb;
    padding:3.5rem 6% 2rem;
}
.footer-grid {
    display:grid;
    grid-template-columns:2fr 1fr 1fr;
    gap:3rem;
    margin-bottom:2.5rem;
}
.footer-logo {
    display:flex;
    align-items:center;
    gap:8px;
    font-size:1.15rem;
    font-weight:700;
    color:#1a1a2e;
    margin-bottom:0.875rem;
}
.footer-logo img {
    width:100px;
}
.footer-desc {
    font-size:0.85rem;
    color:#777;
    line-height:1.7;
    max-width:300px;
}
.footer-col h4 {
    font-size:0.82rem;
    font-weight:700;
    color:#1a1a2e;
    text-transform:uppercase;
    letter-spacing:0.06em;
    margin-bottom:1.1rem;
}
.footer-col ul {
    list-style:none;
}
.footer-col ul li {
    margin-bottom:0.6rem;
}
.footer-col ul li a {
    font-size:0.875rem;
    color:#696969;
    transition:color 0.2s;
}
.footer-col ul li a:hover {
    color:#5f6fff;
}
.footer-col ul li {
    font-size:0.875rem;
    color:#696969;
}
.footer-bottom {
    border-top:1px solid #ebebeb;
    padding-top:1.25rem;
    text-align:center;
    font-size:0.8rem;
    color:#aaa;
}

/* ═══════════════════ FADE-IN ═══════════════════ */
.fade-up {
    opacity:0;
    transform:translateY(20px);
    transition:opacity 0.5s ease, transform 0.5s ease;
}
.fade-up.visible {
    opacity:1;
    transform:translateY(0);
}
.stagger .fade-up:nth-child(1) { transition-delay:0.05s; }
.stagger .fade-up:nth-child(2) { transition-delay:0.1s; }
.stagger .fade-up:nth-child(3) { transition-delay:0.15s; }
.stagger .fade-up:nth-child(4) { transition-delay:0.2s; }
.stagger .fade-up:nth-child(5) { transition-delay:0.25s; }
.stagger .fade-up:nth-child(6) { transition-delay:0.3s; }
.stagger .fade-up:nth-child(7) { transition-delay:0.35s; }
.stagger .fade-up:nth-child(8) { transition-delay:0.4s; }

/* ═══════════════════ RESPONSIVE ═══════════════════ */
@media (max-width:1024px) {
    .values-grid { grid-template-columns:repeat(2,1fr); }
    .team-grid { grid-template-columns:repeat(2,1fr); }
}

@media (max-width:768px) {
    .nav-links { display:none !important; }
    .nav-cta   { display:none !important; }
    .hamburger { display:flex; }
    .navbar { padding:0 5%; height:64px; }
    .mobile-menu { top:64px; }
    .nav-overlay { top:64px; }

    .about-grid { grid-template-columns:1fr; gap:2rem; }
    .mission-vision { grid-template-columns:1fr; }
    .values-grid { grid-template-columns:1fr; }
    .team-grid { grid-template-columns:1fr; }
    .about-stats { justify-content:center; }
    .footer-grid { grid-template-columns:1fr; gap:2rem; }
    .page-wrap { padding:2rem 5% 3rem; }
    .cta-section { padding:2rem; }
}

@media (max-width:480px) {
    .about-stats { flex-direction:column; gap:1rem; align-items:center; }
    .mv-card { padding:1.5rem; }
}
</style>
</head>
<body>

<!-- ═════════════ NAVBAR ═════════════ -->
<nav class="navbar">
    <a href="index.php" class="nav-logo">
        <img src="assets/logo.svg" alt="K&amp;E Hospital">
    </a>

    <ul class="nav-links">
        <li><a href="index.php">HOME</a></li>
        <li><a href="Alldoctors.php">ALL DOCTORS</a></li>
        <li><a href="about.php" class="active">ABOUT</a></li>
        <li><a href="contact.php">CONTACT</a></li>
    </ul>

    <div class="nav-cta">
        <?php if ($is_logged_in): ?>
            <div class="user-pill">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name,0,1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
            <a href="frontend/logout.php" class="btn-logout-sm">Logout</a>
        <?php else: ?>
            <a href="frontend/login.php" class="btn-nav-login">Login</a>
            <a href="frontend/register.php" class="btn-nav-create">Create account</a>
        <?php endif; ?>
    </div>

    <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- Mobile drawer -->
<div class="mobile-menu" id="mobileMenu">
    <a href="index.php" class="mob-link">Home</a>
    <a href="Alldoctors.php" class="mob-link">All Doctors</a>
    <a href="about.php" class="mob-link active">About</a>
    <a href="contact.php" class="mob-link">Contact</a>
    <div class="mob-actions">
        <?php if ($is_logged_in): ?>
            <span style="font-size:0.875rem;color:#3c3c3c;padding:0.25rem 0;">Hi, <?php echo htmlspecialchars($user_name); ?></span>
            <a href="Myappointments.php" class="mob-btn mob-btn-fill">My Appointments</a>
            <a href="logout.php" style="text-align:center;font-size:0.875rem;color:#ef4444;font-weight:500;padding:0.35rem;">Logout</a>
        <?php else: ?>
            <a href="login.php" class="mob-btn mob-btn-outline">Login</a>
            <a href="register.php" class="mob-btn mob-btn-fill">Create account</a>
        <?php endif; ?>
    </div>
</div>
<div class="nav-overlay" id="navOverlay"></div>

<!-- ═════════════ PAGE CONTENT ═════════════ -->
<div class="page-wrap">

    <!-- Hero Section -->
    <div class="about-hero fade-up">
        <h1>About K&amp;E Hospital</h1>
        <div class="hero-line"></div>
        <p>Your Health, Our Priority — Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips.</p>
    </div>

    <!-- Main About Grid -->
    <div class="about-grid">
        <div class="about-image fade-up">
            <img src="assets/about_image.png" alt="K&amp;E Hospital Doctors" onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;\'><i class=\'fas fa-hospital-user\' style=\'font-size:5rem;color:#8b9aff;\'></i></div>'">
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
            <div class="mv-icon">
                <i class="fas fa-bullseye"></i>
            </div>
            <h3>Our Mission</h3>
            <p>To provide accessible, affordable, and quality healthcare services to every Zambian through innovative technology and compassionate care.</p>
        </div>
        <div class="mv-card fade-up">
            <div class="mv-icon">
                <i class="fas fa-eye"></i>
            </div>
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
                <div class="value-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h4>Compassion</h4>
                <p>We care for every patient with empathy and respect</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4>Integrity</h4>
                <p>We uphold the highest standards of honesty and ethics</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4>Excellence</h4>
                <p>We strive for excellence in everything we do</p>
            </div>
            <div class="value-card fade-up">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
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
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="team-info">
                    <h4>Dr. Kasonde Banda</h4>
                    <p>Chief Medical Officer</p>
                </div>
            </div>
            <div class="team-card fade-up">
                <div class="team-image">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="team-info">
                    <h4>Mrs. Esther Phiri</h4>
                    <p>Hospital Administrator</p>
                </div>
            </div>
            <div class="team-card fade-up">
                <div class="team-image">
                    <i class="fas fa-user-nurse"></i>
                </div>
                <div class="team-info">
                    <h4>Ms. Grace Mwila</h4>
                    <p>Head of Nursing</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section fade-up">
        <h2>Ready to Book Your Appointment?</h2>
        <p>Join thousands of satisfied patients who trust K&amp;E Hospital for their healthcare needs.</p>
        <a href="Alldoctors.php" class="cta-btn">Find a Doctor <i class="fas fa-arrow-right"></i></a>
    </div>

</div>

<!-- ═════════════ FOOTER ═════════════ -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <img src="assets/logo.svg" alt="K&amp;E Hospital">
            </div>
            <p class="footer-desc">Your Health, Our Priority Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips, Anywhere in Zambia.</p>
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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMenu();
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) closeMenu();
    });

    /* Scroll fade-in */
    var fadeEls = document.querySelectorAll('.fade-up');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    io.unobserve(e.target);
                }
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