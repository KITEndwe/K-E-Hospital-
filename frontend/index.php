<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>Prescripto – Book Appointment With Trusted Doctors</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet" />

    <style>
        /* ─── Reset & Base ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:       #5F6FFF;
            --brand-dark:  #4a58e8;
            --brand-light: #eef0ff;
            --text-dark:   #1a1d2e;
            --text-mid:    #4b5264;
            --text-light:  #6b7280;
            --card-bg:     #f5f7ff;
            --white:       #ffffff;
            --green:       #22c55e;
            --radius-lg:   18px;
            --radius-md:   12px;
            --radius-sm:   8px;
            --shadow-card: 0 2px 16px rgba(95,111,255,.10);
            --shadow-hover:0 6px 28px rgba(95,111,255,.20);
            --transition:  .25s ease;
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

        /* ─── Utility ───────────────────────────────────────────────── */
        .container {
            width: 92%;
            max-width: 1160px;
            margin-inline: auto;
        }

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

        .btn-outline {
            display: inline-flex;
            align-items: center;
            background: var(--white);
            color: var(--text-dark);
            border: 2px solid var(--white);
            padding: 11px 26px;
            border-radius: 50px;
            font-size: .95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background var(--transition), color var(--transition), transform var(--transition);
        }
        .btn-outline:hover {
            background: transparent;
            color: var(--white);
            transform: translateY(-1px);
        }

        .section-title {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(1.6rem, 3vw, 2rem);
            text-align: center;
            color: var(--text-dark);
        }
        .section-sub {
            text-align: center;
            color: var(--text-light);
            font-size: .93rem;
            max-width: 480px;
            margin: .5rem auto 0;
        }

        /* ─── Navbar (Enhanced with Mobile Menu) ───────────────────── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(95,111,255,.12);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
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
            width: auto;
            height: 48px;
            object-fit: contain;
        }

        /* Desktop Navigation */
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

        /* Desktop create account button */
        .navbar__desktop-btn {
            flex-shrink: 0;
        }

        /* ─── Mobile Menu Toggle (Hamburger) ──────────────────────── */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            width: 44px;
            height: 44px;
            position: relative;
            z-index: 1010;
            border-radius: 30px;
            transition: background 0.2s;
            align-items: center;
            justify-content: center;
        }
        .mobile-menu-toggle:hover {
            background: var(--brand-light);
        }
        .hamburger-icon {
            width: 26px;
            height: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        .hamburger-icon span {
            display: block;
            height: 2.5px;
            width: 100%;
            background: var(--text-dark);
            border-radius: 4px;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.3, 1);
        }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(1) {
            transform: translateY(8.5px) rotate(45deg);
        }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0.8);
        }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(3) {
            transform: translateY(-8.5px) rotate(-45deg);
        }

        /* Mobile Navigation Panel (Slide-out) */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: min(75%, 320px);
            height: 100vh;
            background: var(--white);
            box-shadow: -8px 0 32px rgba(0,0,0,0.1);
            z-index: 1005;
            transition: right 0.35s ease-out;
            display: flex;
            flex-direction: column;
            padding: 90px 28px 40px;
            gap: 1.8rem;
            backdrop-filter: blur(20px);
            background: rgba(255,255,255,0.98);
            border-left: 1px solid rgba(95,111,255,0.2);
        }
        .mobile-nav.open {
            right: 0;
        }
        .mobile-nav a {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--text-dark);
            padding: 10px 0;
            border-bottom: 1px solid #f0f2ff;
            transition: color 0.2s, padding-left 0.2s;
            display: inline-block;
        }
        .mobile-nav a:hover, .mobile-nav a.active {
            color: var(--brand);
            padding-left: 8px;
        }
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
            transition: background 0.2s;
        }
        .mobile-nav .mobile-account-btn:hover {
            background: var(--brand-dark);
            padding-left: 0;
        }

        /* Overlay when menu open */
        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(2px);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Hide desktop elements on mobile, adjust layout */
        @media (max-width: 768px) {
            .navbar__nav {
                display: none;
            }
            .navbar__desktop-btn {
                display: none;
            }
            .mobile-menu-toggle {
                display: flex;
            }
            .navbar__inner {
                height: 66px;
            }
            .navbar__logo img {
                height: 42px;
            }
        }

        /* Rest of the original responsive styles (keep as is) */
        @media (max-width: 900px) {
            .doctors__grid { grid-template-columns: repeat(3, 1fr); }
            .hero__inner { padding: 36px 28px 0; }
            .cta-banner__inner { padding: 36px 28px 0; }
        }
        @media (max-width: 700px) {
            .doctors__grid { grid-template-columns: repeat(2, 1fr); }
            .hero__image-placeholder, .cta-banner__image-placeholder { display: none; }
            .footer__grid { grid-template-columns: 1fr; }
            .hero__inner, .cta-banner__inner { flex-direction: column; }
            .hero__content { padding-bottom: 30px; }
        }
        @media (max-width: 480px) {
            .speciality__grid { gap: 1.2rem; }
            .speciality__icon { width: 72px; height: 72px; }
            .speciality__icon img { width: 44px; height: 44px; }
            .btn-primary, .btn-outline { padding: 8px 20px; font-size: 0.85rem; }
        }

        /* ─── Keep original hero, cards, etc unchanged (only added menu) */
        .hero {
            background: var(--brand);
            border-radius: var(--radius-lg);
            margin: 20px 0;
            overflow: hidden;
            position: relative;
            min-height: 320px;
        }
        .hero__inner {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 52px 60px 0;
        }
        .hero__content { max-width: 480px; padding-bottom: 52px; }
        .hero__title {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(2rem, 4.5vw, 3rem);
            color: var(--white);
            line-height: 1.18;
            margin-bottom: 1rem;
        }
        .hero__avatars {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }
        .hero__avatar-group { display: flex; }
        .hero__avatar-group span {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 2px solid var(--white);
            background: rgba(255,255,255,.4);
            margin-left: -10px;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem; color: white; font-weight: 600;
        }
        .hero__sub {
            color: rgba(255,255,255,.88);
            font-size: .88rem;
            line-height: 1.5;
            max-width: 320px;
        }
        .hero__cta { margin-top: 1.6rem; }
        .hero__image {
            flex-shrink: 0;
            align-self: flex-end;
            max-height: 340px;
            object-fit: contain;
            pointer-events: none;
        }
        .hero__image-placeholder {
            width: 420px;
            max-height: 340px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 0;
        }
        .hero::before {
            content: '';
            position: absolute;
            right: -60px; top: -60px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
        }
        .hero::after {
            content: '';
            position: absolute;
            right: 120px; bottom: -80px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
        }
        .speciality { padding: 64px 0; }
        .speciality__grid {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1.8rem;
            margin-top: 2.5rem;
        }
        .speciality__item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            cursor: pointer;
            transition: transform var(--transition);
        }
        .speciality__item:hover { transform: translateY(-4px); }
        .speciality__icon {
            width: 90px; height: 90px;
            border-radius: 50%;
            background: var(--brand-light);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: box-shadow var(--transition);
        }
        .speciality__icon img { width: 54px; height: 54px; object-fit: contain; }
        .speciality__name { font-size: .83rem; font-weight: 500; color: var(--text-mid); text-align: center; }
        .doctors { padding: 20px 0 64px; }
        .doctors__grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.1rem;
            margin-top: 2.5rem;
        }
        .doctor-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: box-shadow var(--transition), transform var(--transition);
            cursor: pointer;
        }
        .doctor-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }
        .doctor-card__photo-placeholder {
            width: 100%;
            aspect-ratio: 4/4.2;
            background: linear-gradient(160deg, #dde3f9 0%, #c5ccf0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .doctor-card__body { padding: 12px 14px 14px; }
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
        .doctor-card__name { font-weight: 600; font-size: .95rem; }
        .doctor-card__specialty { font-size: .8rem; color: var(--text-light); margin-top: 2px; }
        .doctors__more { display: flex; justify-content: center; margin-top: 2rem; }
        .btn-more {
            padding: 10px 36px;
            border-radius: 50px;
            border: 1.5px solid #d1d5db;
            background: transparent;
            color: var(--text-mid);
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition);
        }
        .btn-more:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-light); }
        .cta-banner {
            background: var(--brand);
            border-radius: var(--radius-lg);
            margin: 20px 0 64px;
            overflow: hidden;
            position: relative;
        }
        .cta-banner__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 52px 64px 0;
        }
        .cta-banner__content { padding-bottom: 52px; }
        .cta-banner__title {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            color: var(--white);
            line-height: 1.25;
            margin-bottom: 1.6rem;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            padding: 52px 0 24px;
        }
        .footer__grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr 1fr;
            gap: 2.5rem;
            padding-bottom: 36px;
        }
        .footer__brand img { height: 44px; width: auto; }
        .footer__desc { font-size: .87rem; color: var(--text-light); line-height: 1.7; margin-top: 12px; }
        .footer__heading { font-weight: 600; font-size: .95rem; margin-bottom: 1rem; }
        .footer__links li + li { margin-top: .55rem; }
        .footer__links a { font-size: .87rem; color: var(--text-light); transition: color var(--transition); }
        .footer__links a:hover { color: var(--brand); }
        .footer__contact p { font-size: .87rem; color: var(--text-light); margin-bottom: .5rem; }
        .footer__bottom {
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            text-align: center;
            font-size: .82rem;
            color: var(--text-light);
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp .55s ease both; }
        .fade-up-1 { animation-delay: .08s; }
        .fade-up-2 { animation-delay: .18s; }
        .fade-up-3 { animation-delay: .28s; }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     NAVBAR (Fully Responsive with Hamburger Menu)
═══════════════════════════════════════════════════════════ -->
<header class="navbar">
    <div class="container navbar__inner">
        <!-- Logo -->
        <a href="index.php" class="navbar__logo">
            <img src="assets/logo.svg" alt="Prescripto Logo" style="height:48px; width:auto;" onerror="this.src='https://placehold.co/120x40/5F6FFF/white?text=Prescripto'">
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="navbar__nav">
            <a href="index.php" class="active">HOME</a>
            <a href="Alldoctors.php">ALL DOCTORS</a>
            <a href="about.php">ABOUT</a>
            <a href="contact.php">CONTACT</a>
        </nav>

        <!-- Desktop Create Account Button -->
        <div class="navbar__desktop-btn">
            <a href="login.php" class="btn-primary">Create account</a>
        </div>

        <!-- Mobile Hamburger Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu">
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
    </div>

    <!-- Mobile Slide-out Navigation Panel -->
    <div class="mobile-nav" id="mobileNav">
        <a href="index.php" class="active">🏠 HOME</a>
        <a href="Alldoctors.php">👨‍⚕️ ALL DOCTORS</a>
        <a href="about.php">ℹ️ ABOUT</a>
        <a href="contact.php">📞 CONTACT</a>
        <a href="login.php" class="mobile-account-btn">✨ Create account</a>
    </div>
    <!-- Overlay -->
    <div class="menu-overlay" id="menuOverlay"></div>
</header>

<main>
<!-- Hero Section (unchanged but kept functional) -->
<section class="container">
    <div class="hero">
        <div class="hero__inner">
            <div class="hero__content fade-up">
                <h1 class="hero__title">Book Appointment<br>With Trusted Doctors</h1>
                <div class="hero__avatars">
                    <div class="hero__avatar-group">
                        <img src="assets/group_profiles.png" alt="doctor group" style="width:100px;">
                    </div>
                    <p class="hero__sub">Simply browse through our extensive list of trusted doctors, schedule your appointment hassle-free.</p>
                </div>
                <div class="hero__cta">
                    <a href="doctors.php" class="btn-outline">
                        Book appointment
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
            <div class="hero__image-placeholder">
                <img src="assets/header_img.png" alt="Trusted Doctors" class="hero__image" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</section>

<!-- Speciality section (dynamic PHP) -->
<?php
$specialities = [
    ['name' => 'General physician', 'icon' => 'assets/images/speciality-general.png'],
    ['name' => 'Gynecologist',      'icon' => 'assets/images/speciality-gynecologist.png'],
    ['name' => 'Dermatologist',     'icon' => 'assets/images/speciality-dermatologist.png'],
    ['name' => 'Pediatricians',     'icon' => 'assets/images/speciality-pediatricians.png'],
    ['name' => 'Neurologist',       'icon' => 'assets/images/speciality-neurologist.png'],
    ['name' => 'Gastroenterologist','icon' => 'assets/images/speciality-gastroenterologist.png'],
];
$speciality_icons = ['🩺','👩‍⚕️','🧴','👶','🧠','🫁'];
?>
<section class="speciality">
    <div class="container">
        <h2 class="section-title fade-up fade-up-1">Find by Speciality</h2>
        <p class="section-sub fade-up fade-up-2">Simply browse through our extensive list of trusted doctors, schedule your appointment hassle-free.</p>
        <div class="speciality__grid fade-up fade-up-3">
            <?php foreach ($specialities as $idx => $sp): ?>
                <a href="doctors.php?speciality=<?= urlencode($sp['name']) ?>" class="speciality__item">
                    <div class="speciality__icon">
                        <img src="frontend/<?= htmlspecialchars($sp['icon']) ?>" alt="<?= htmlspecialchars($sp['name']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <span class="speciality__icon-fallback" style="display:none;"><?= $speciality_icons[$idx] ?></span>
                    </div>
                    <span class="speciality__name"><?= htmlspecialchars($sp['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Top Doctors (sample) -->
<?php
$doctors = [];
for ($i = 0; $i < 10; $i++) {
    $doctors[] = [
        'name'      => 'Dr. Richard James',
        'specialty' => 'General physician',
        'available' => true,
        'photo'     => 'assets/images/doctor-' . (($i % 5) + 1) . '.png',
    ];
}
?>
<section class="doctors">
    <div class="container">
        <h2 class="section-title">Top Doctors to Book</h2>
        <p class="section-sub">Simply browse through our extensive list of trusted doctors.</p>
        <div class="doctors__grid">
            <?php foreach ($doctors as $doc): ?>
            <a href="appointment.php" class="doctor-card">
                <div class="doctor-card__photo-placeholder">
                    <img src="frontend/<?= htmlspecialchars($doc['photo']) ?>" alt="<?= htmlspecialchars($doc['name']) ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.parentElement.innerHTML='<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 80 100\' fill=\'#8b97d8\'><ellipse cx=\'40\' cy=\'32\' rx=\'18\' ry=\'18\'/><path d=\'M10 85 Q10 58 40 58 Q70 58 70 85Z\'/></svg>'">
                </div>
                <div class="doctor-card__body">
                    <?php if ($doc['available']): ?>
                    <div class="doctor-card__available"><span></span> Available</div>
                    <?php endif; ?>
                    <div class="doctor-card__name"><?= htmlspecialchars($doc['name']) ?></div>
                    <div class="doctor-card__specialty"><?= htmlspecialchars($doc['specialty']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="doctors__more"><a href="doctors.php" class="btn-more">more</a></div>
    </div>
</section>

<!-- CTA Banner -->
<section class="container">
    <div class="cta-banner">
        <div class="cta-banner__inner">
            <div class="cta-banner__content">
                <h2 class="cta-banner__title">Book Appointment<br>With 100+ Trusted Doctors</h2>
                <a href="register.php" class="btn-outline">Create account</a>
            </div>
            <div class="cta-banner__image-placeholder">
                <img src="assets/appointment_img.png" alt="Doctor" class="cta-banner__image" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</section>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div>
                <div class="footer__brand"><img src="assets/logo.svg" alt="logo" style="height:45px;"></div>
                <p class="footer__desc">Your Health, Our Priority Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips, Anywhere in Zambia.</p>
            </div>
            <div><h4 class="footer__heading">COMPANY</h4><ul class="footer__links"><li><a href="index.php">Home</a></li><li><a href="about.php">About us</a></li><li><a href="contact.php">Contact us</a></li><li><a href="privacy.php">Privacy policy</a></li></ul></div>
            <div><h4 class="footer__heading">GET IN TOUCH</h4><div class="footer__contact"><p>+260-7610-16446</p><p>elijahmwange55@gmail.com</p></div></div>
        </div>
        <div class="footer__bottom"><p>Copyright &copy; <?= date('Y') ?> KE-Hospital – All Right Reserved.</p></div>
    </div>
</footer>

<!-- JavaScript for Mobile Menu Interactions -->
<script>
    (function() {
        const toggleBtn = document.getElementById('mobileMenuToggle');
        const mobileNav = document.getElementById('mobileNav');
        const overlay = document.getElementById('menuOverlay');
        
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
        
        function toggleMenu() {
            if (mobileNav.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        }
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleMenu();
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }
        
        // Close on window resize if open (optional, improves UX)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && mobileNav.classList.contains('open')) {
                closeMenu();
            }
        });
        
        // Optional: close menu when clicking any mobile nav link
        const mobileLinks = document.querySelectorAll('.mobile-nav a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                closeMenu();
            });
        });
        
        // Active link highlight based on current URL (basic)
        const currentPath = window.location.pathname;
        const desktopLinks = document.querySelectorAll('.navbar__nav a');
        const mobileNavLinks = document.querySelectorAll('.mobile-nav a');
        function setActive(links) {
            links.forEach(link => {
                const href = link.getAttribute('href');
                if (href && (currentPath.endsWith(href) || (currentPath === '/' && href === 'index.php'))) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }
        setActive(desktopLinks);
        setActive(mobileNavLinks);
    })();
</script>
</body>
</html>