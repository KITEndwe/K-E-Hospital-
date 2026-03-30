<?php
// Prescripto – All Doctors Page

// ─── Database Connection ────────────────────────────────────────
$db_host = 'localhost';
$db_name = 'ke_hospital';
$db_user = 'root';
$db_pass = '';          // Change to your MySQL password if set

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('<p style="color:red;text-align:center;padding:40px;">Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</p>');
}
$conn->set_charset('utf8mb4');

// ─── Active speciality filter from URL ─────────────────────────
$active = isset($_GET['speciality']) ? trim($_GET['speciality']) : '';

// ─── Fetch distinct specialities for sidebar ───────────────────
$specialities = [];
$sp_result = $conn->query("SELECT DISTINCT speciality FROM doctors WHERE is_available = 1 ORDER BY speciality ASC");
if ($sp_result) {
    while ($row = $sp_result->fetch_assoc()) {
        $specialities[] = $row['speciality'];
    }
}

// ─── Fetch doctors (filtered or all) ──────────────────────────
if ($active !== '') {
    $stmt = $conn->prepare("
        SELECT doctor_id, name, speciality, profile_image, is_available, rating, experience, fees
        FROM doctors
        WHERE is_available = 1 AND speciality = ?
        ORDER BY rating DESC
    ");
    $stmt->bind_param('s', $active);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT doctor_id, name, speciality, profile_image, is_available, rating, experience, fees
        FROM doctors
        WHERE is_available = 1
        ORDER BY rating DESC
    ");
}

$doctors = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>All Doctors – Prescripto</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet" />

    <style>
        /* ─── Reset & Base ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:        #5F6FFF;
            --brand-dark:   #4a58e8;
            --brand-light:  #eef0ff;
            --text-dark:    #1a1d2e;
            --text-mid:     #4b5264;
            --text-light:   #6b7280;
            --card-bg:      #f5f7ff;
            --white:        #ffffff;
            --green:        #22c55e;
            --border:       #e5e7eb;
            --radius-lg:    18px;
            --radius-md:    12px;
            --radius-sm:    8px;
            --shadow-hover: 0 6px 28px rgba(95,111,255,.20);
            --transition:   .25s ease;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            line-height: 1.6;
            overflow-x: hidden;
        }
        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }
        ul { list-style: none; }

        .container {
            width: 92%;
            max-width: 1160px;
            margin-inline: auto;
        }

        /* ─── Navbar ────────────────────────────────────────────────── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(95,111,255,.12);
            box-shadow: 0 2px 10px rgba(0,0,0,.02);
        }
        .navbar__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
            gap: 1rem;
        }
        .navbar__logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--brand);
            flex-shrink: 0;
        }
        .navbar__logo img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }
        .navbar__nav {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .navbar__nav a {
            font-size: .95rem;
            font-weight: 500;
            color: var(--text-mid);
            position: relative;
            padding-bottom: 4px;
            transition: color var(--transition);
        }
        .navbar__nav a::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 0; height: 2.5px;
            background: var(--brand);
            border-radius: 3px;
            transition: width var(--transition);
        }
        .navbar__nav a:hover,
        .navbar__nav a.active { color: var(--brand); }
        .navbar__nav a:hover::after,
        .navbar__nav a.active::after { width: 100%; }

        .navbar__desktop-btn { flex-shrink: 0; }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--brand);
            color: var(--white);
            border: 2px solid var(--brand);
            padding: 11px 26px;
            border-radius: 50px;
            font-size: .95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
        }
        .btn-primary:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(95,111,255,.35);
        }

        /* ─── Mobile Menu Toggle ─────────────────────────────────── */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            width: 44px; height: 44px;
            position: relative;
            z-index: 1010;
            border-radius: 30px;
            transition: background .2s;
            align-items: center;
            justify-content: center;
        }
        .mobile-menu-toggle:hover { background: var(--brand-light); }
        .hamburger-icon {
            width: 26px; height: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .hamburger-icon span {
            display: block;
            height: 2.5px; width: 100%;
            background: var(--text-dark);
            border-radius: 4px;
            transition: all .3s cubic-bezier(.2,.8,.3,1);
        }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(1) { transform: translateY(8.5px) rotate(45deg); }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(2) { opacity: 0; transform: scaleX(.8); }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(3) { transform: translateY(-8.5px) rotate(-45deg); }

        /* Mobile slide-out nav */
        .mobile-nav {
            position: fixed;
            top: 0; right: -100%;
            width: min(75%, 320px);
            height: 100vh;
            background: rgba(255,255,255,.98);
            box-shadow: -8px 0 32px rgba(0,0,0,.1);
            z-index: 1005;
            transition: right .35s ease-out;
            display: flex;
            flex-direction: column;
            padding: 90px 28px 40px;
            gap: 1.8rem;
            border-left: 1px solid rgba(95,111,255,.2);
        }
        .mobile-nav.open { right: 0; }
        .mobile-nav a {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--text-dark);
            padding: 10px 0;
            border-bottom: 1px solid #f0f2ff;
            transition: color .2s, padding-left .2s;
            display: inline-block;
        }
        .mobile-nav a:hover,
        .mobile-nav a.active { color: var(--brand); padding-left: 8px; }
        .mobile-nav .mobile-account-btn {
            margin-top: 20px;
            background: var(--brand);
            text-align: center;
            border-radius: 60px;
            padding: 12px 0;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: background .2s;
        }
        .mobile-nav .mobile-account-btn:hover { background: var(--brand-dark); padding-left: 0; }

        .menu-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,.3);
            backdrop-filter: blur(2px);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: opacity .3s, visibility .3s;
        }
        .menu-overlay.active { opacity: 1; visibility: visible; }

        /* ─── Page Layout ────────────────────────────────────────── */
        .page-body { padding: 28px 0 72px; }

        .page-intro {
            font-size: .95rem;
            color: var(--text-mid);
            margin-bottom: 24px;
        }

        .doctors-layout {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 28px;
            align-items: start;
        }

        /* ─── Speciality Sidebar ─────────────────────────────────── */
        .speciality-sidebar {
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: sticky;
            top: 86px;
        }
        .speciality-sidebar__item {
            display: block;
            padding: 11px 18px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: .88rem;
            font-weight: 500;
            color: var(--text-mid);
            background: var(--white);
            cursor: pointer;
            transition: background var(--transition), color var(--transition), border-color var(--transition);
            text-align: left;
        }
        .speciality-sidebar__item:hover {
            border-color: var(--brand);
            color: var(--brand);
            background: var(--brand-light);
        }
        .speciality-sidebar__item.active {
            background: var(--brand-light);
            border-color: var(--brand);
            color: var(--brand);
            font-weight: 600;
        }

        /* ─── Doctors Grid ───────────────────────────────────────── */
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.1rem;
        }

        .doctor-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid transparent;
            transition: box-shadow var(--transition), border-color var(--transition), transform var(--transition);
            cursor: pointer;
            animation: fadeUp .4s ease both;
        }
        .doctor-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(95,111,255,.18);
            transform: translateY(-3px);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .doctor-card__photo-wrap {
            width: 100%;
            aspect-ratio: 3/3.4;
            background: linear-gradient(160deg, #dde3f9 0%, #c5ccf0 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .doctor-card__photo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top;
        }

        .doctor-card__body {
            padding: 12px 14px 14px;
            border-top: 1px solid rgba(95,111,255,.08);
        }
        .doctor-card__available {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: .75rem;
            color: var(--green);
            font-weight: 600;
            margin-bottom: 4px;
        }
        .doctor-card__available span {
            width: 7px; height: 7px;
            background: var(--green);
            border-radius: 50%;
        }
        .doctor-card__name { font-weight: 600; font-size: .95rem; color: var(--text-dark); }
        .doctor-card__specialty { font-size: .8rem; color: var(--text-light); margin-top: 2px; }

        .doctor-card__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
        }
        .doctor-card__rating {
            font-size: .78rem;
            font-weight: 600;
            color: #f59e0b;
        }
        .doctor-card__exp {
            font-size: .75rem;
            color: var(--text-light);
        }
        .doctor-card__fees {
            margin-top: 6px;
            font-size: .78rem;
            font-weight: 600;
            color: var(--brand);
        }

        /* Empty state */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        .empty-state svg { margin: 0 auto 16px; opacity: .35; }

        /* ─── Footer ─────────────────────────────────────────────── */
        .footer {
            border-top: 1px solid var(--border);
            padding: 52px 0 24px;
        }
        .footer__grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr 1fr;
            gap: 2.5rem;
            padding-bottom: 36px;
        }
        .footer__brand img { height: 45px; width: auto; }
        .footer__desc { font-size: .87rem; color: var(--text-light); line-height: 1.7; margin-top: 12px; }
        .footer__heading { font-weight: 600; font-size: .95rem; color: var(--text-dark); margin-bottom: 1rem; }
        .footer__links li + li { margin-top: .55rem; }
        .footer__links a { font-size: .87rem; color: var(--text-light); transition: color var(--transition); }
        .footer__links a:hover { color: var(--brand); }
        .footer__contact p { font-size: .87rem; color: var(--text-light); margin-bottom: .5rem; }
        .footer__bottom {
            border-top: 1px solid var(--border);
            padding-top: 20px;
            text-align: center;
            font-size: .82rem;
            color: var(--text-light);
        }

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 960px) {
            .doctors-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .navbar__nav { display: none; }
            .navbar__desktop-btn { display: none; }
            .mobile-menu-toggle { display: flex; }
            .navbar__inner { height: 66px; }
            .navbar__logo img { height: 42px; }
        }
        @media (max-width: 720px) {
            .doctors-layout { grid-template-columns: 1fr; }
            .speciality-sidebar { flex-direction: row; flex-wrap: wrap; position: static; }
            .speciality-sidebar__item { flex: 1 1 auto; text-align: center; }
            .doctors-grid { grid-template-columns: repeat(2, 1fr); }
            .footer__grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .doctors-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════════════════ -->
<header class="navbar">
    <div class="container navbar__inner">
        <!-- Logo -->
        <a href="index.php" class="navbar__logo">
            <img
                src="assets/logo.svg"
                alt="Prescripto Logo"
                onerror="this.src='https://placehold.co/120x40/5F6FFF/white?text=Prescripto'"
            />
        </a>

        <!-- Desktop Nav -->
        <nav class="navbar__nav">
            <a href="index.php">HOME</a>
            <a href="Alldoctors.php" class="active">ALL DOCTORS</a>
            <a href="about.php">ABOUT</a>
            <a href="contact.php">CONTACT</a>
        </nav>

        <!-- Desktop CTA -->
        <div class="navbar__desktop-btn">
            <a href="login.php" class="btn-primary">Create account</a>
        </div>

        <!-- Hamburger -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu">
            <div class="hamburger-icon">
                <span></span><span></span><span></span>
            </div>
        </button>
    </div>

    <!-- Mobile Slide-out Panel -->
    <div class="mobile-nav" id="mobileNav">
        <a href="index.php">🏠 HOME</a>
        <a href="Alldoctors.php" class="active">👨‍⚕️ ALL DOCTORS</a>
        <a href="about.php">ℹ️ ABOUT</a>
        <a href="contact.php">📞 CONTACT</a>
        <a href="login.php" class="mobile-account-btn">✨ Create account</a>
    </div>
    <div class="menu-overlay" id="menuOverlay"></div>
</header>


<!-- ═══════════════════════════════════════════════════════════
     MAIN
═══════════════════════════════════════════════════════════ -->
<main class="page-body">
    <div class="container">

        <p class="page-intro">Browse through the doctors specialist.</p>

        <div class="doctors-layout">

            <!-- Speciality Sidebar -->
            <aside class="speciality-sidebar">
                <?php foreach ($specialities as $sp):
                    $is_active = strtolower($active) === strtolower($sp);
                    $href = $is_active
                        ? 'Alldoctors.php'
                        : 'Alldoctors.php?speciality=' . urlencode($sp);
                ?>
                <a
                    href="<?= htmlspecialchars($href) ?>"
                    class="speciality-sidebar__item<?= $is_active ? ' active' : '' ?>"
                >
                    <?= htmlspecialchars($sp) ?>
                </a>
                <?php endforeach; ?>
            </aside>

            <!-- Doctors Grid -->
            <section>
                <div class="doctors-grid">
                    <?php if (empty($doctors)): ?>
                        <div class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            <p>No doctors found<?= $active ? ' for <strong>' . htmlspecialchars($active) . '</strong>' : '' ?>.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($doctors as $i => $doc): ?>
                        <a
                            href="appointment.php?doctor_id=<?= urlencode($doc['doctor_id']) ?>"
                            class="doctor-card"
                            style="animation-delay: <?= ($i % 4) * 0.07 ?>s"
                        >
                            <!-- Doctor Photo -->
                            <div class="doctor-card__photo-wrap">
                                <img
                                    src="<?= htmlspecialchars($doc['profile_image']) ?>"
                                    alt="<?= htmlspecialchars($doc['name']) ?>"
                                    onerror="
                                        this.style.display='none';
                                        this.parentElement.innerHTML='<svg style=\'width:50%;opacity:.35\' xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 80 100\' fill=\'#8b97d8\'><ellipse cx=\'40\' cy=\'32\' rx=\'18\' ry=\'18\'/><path d=\'M10 85 Q10 58 40 58 Q70 58 70 85Z\'/></svg>';
                                    "
                                />
                            </div>

                            <!-- Card Body -->
                            <div class="doctor-card__body">

                                <!-- Available badge -->
                                <?php if ($doc['is_available']): ?>
                                <div class="doctor-card__available">
                                    <span></span> Available
                                </div>
                                <?php endif; ?>

                                <!-- Name & Speciality -->
                                <div class="doctor-card__name"><?= htmlspecialchars($doc['name']) ?></div>
                                <div class="doctor-card__specialty"><?= htmlspecialchars($doc['speciality']) ?></div>

                                <!-- Rating & Experience -->
                                <div class="doctor-card__meta">
                                    <span class="doctor-card__rating">
                                        &#9733; <?= number_format((float)$doc['rating'], 1) ?>
                                    </span>
                                    <span class="doctor-card__exp"><?= htmlspecialchars($doc['experience']) ?></span>
                                </div>

                                <!-- Fees -->
                                <div class="doctor-card__fees">
                                    K<?= number_format((float)$doc['fees'], 2) ?> / visit
                                </div>

                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
</main>


<!-- ═══════════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════ -->
<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <!-- Brand -->
            <div>
                <div class="footer__brand">
                    <img
                        src="assets/logo.svg"
                        alt="Prescripto"
                        onerror="this.src='https://placehold.co/120x40/5F6FFF/white?text=Prescripto'"
                    />
                </div>
                <p class="footer__desc">Your Health, Our Priority Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips, Anywhere in Zambia.</p>
            </div>

            <!-- Company Links -->
            <div>
                <h4 class="footer__heading">COMPANY</h4>
                <ul class="footer__links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About us</a></li>
                    <li><a href="contact.php">Contact us</a></li>
                    <li><a href="privacy.php">Privacy policy</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="footer__heading">GET IN TOUCH</h4>
                <div class="footer__contact">
                    <p>+260-7610-16446</p>
                    <p>elijahmwange55@gmail.com</p>
                </div>
            </div>
        </div>

        <div class="footer__bottom">
            <p>Copyright &copy; <?= date('Y') ?> KE-Hospital – All Right Reserved.</p>
        </div>
    </div>
</footer>


<!-- ═══════════════════════════════════════════════════════════
     MOBILE MENU JS
═══════════════════════════════════════════════════════════ -->
<script>
(function () {
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const mobileNav = document.getElementById('mobileNav');
    const overlay   = document.getElementById('menuOverlay');

    function closeMenu() {
        mobileNav.classList.remove('open');
        overlay.classList.remove('active');
        toggleBtn.classList.remove('active');
        document.body.style.overflow = '';
    }
    function openMenu() {
        mobileNav.classList.add('open');
        overlay.classList.add('active');
        toggleBtn.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    if (toggleBtn) toggleBtn.addEventListener('click', e => {
        e.stopPropagation();
        mobileNav.classList.contains('open') ? closeMenu() : openMenu();
    });

    if (overlay) overlay.addEventListener('click', closeMenu);

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && mobileNav.classList.contains('open')) closeMenu();
    });

    document.querySelectorAll('.mobile-nav a').forEach(l => l.addEventListener('click', closeMenu));
})();
</script>

</body>
</html>