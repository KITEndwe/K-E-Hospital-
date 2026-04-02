<?php
// Prescripto - Homepage
$specialities = [
    ['name' => 'General physician', 'icon' => 'assets/images/speciality-general.png'],
    ['name' => 'Gynecologist',      'icon' => 'assets/images/speciality-gynecologist.png'],
    ['name' => 'Dermatologist',     'icon' => 'assets/images/speciality-dermatologist.png'],
    ['name' => 'Pediatricians',     'icon' => 'assets/images/speciality-pediatricians.png'],
    ['name' => 'Neurologist',       'icon' => 'assets/images/speciality-neurologist.png'],
    ['name' => 'Gastroenterologist','icon' => 'assets/images/speciality-gastroenterologist.png'],
];

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

        /* ─── Navbar ────────────────────────────────────────────────── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(95,111,255,.10);
        }

        .navbar__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 66px;
        }

        .navbar__logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--brand);
        }
        .navbar__logo img {
            width: 34px;
        }
        .navbar__logo-icon {
            width: 34px;
            height: 34px;
            background: var(--brand);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .navbar__logo-icon svg { color: white; }

        .navbar__nav {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .navbar__nav a {
            font-size: .93rem;
            font-weight: 500;
            color: var(--text-mid);
            position: relative;
            padding-bottom: 3px;
            transition: color var(--transition);
        }
        .navbar__nav a::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 0; height: 2px;
            background: var(--brand);
            border-radius: 2px;
            transition: width var(--transition);
        }
        .navbar__nav a:hover,
        .navbar__nav a.active { color: var(--brand); }
        .navbar__nav a:hover::after,
        .navbar__nav a.active::after { width: 100%; }

        /* ─── Hero ──────────────────────────────────────────────────── */
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
        .hero__avatar-group {
            display: flex;
        }
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
        .hero__avatar-group span:first-child { margin-left: 0; }

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
            gap: -20px;
            padding-bottom: 0;
        }

        /* Decorative dots on hero */
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

        /* ─── Speciality Section ─────────────────────────────────────── */
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
        .speciality__item:hover .speciality__icon {
            box-shadow: 0 6px 20px rgba(95,111,255,.25);
        }
        .speciality__icon img { width: 54px; height: 54px; object-fit: contain; }
        .speciality__icon-fallback {
            font-size: 2.2rem;
        }
        .speciality__name {
            font-size: .83rem;
            font-weight: 500;
            color: var(--text-mid);
            text-align: center;
        }

        /* ─── Top Doctors ─────────────────────────────────────────── */
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
            border: 1px solid transparent;
            transition: box-shadow var(--transition), border-color var(--transition), transform var(--transition);
            cursor: pointer;
        }
        .doctor-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(95,111,255,.18);
            transform: translateY(-3px);
        }

        .doctor-card__photo {
            width: 100%;
            aspect-ratio: 4/4.2;
            object-fit: cover;
            object-position: top;
            background: #dde3f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .doctor-card__photo-placeholder {
            width: 100%;
            aspect-ratio: 4/4.2;
            background: linear-gradient(160deg, #dde3f9 0%, #c5ccf0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .doctor-card__photo-placeholder svg {
            width: 50%; height: 50%; opacity: .35;
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

        .doctor-card__name {
            font-weight: 600;
            font-size: .95rem;
            color: var(--text-dark);
        }
        .doctor-card__specialty {
            font-size: .8rem;
            color: var(--text-light);
            margin-top: 2px;
        }

        .doctors__more {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        .btn-more {
            padding: 10px 36px;
            border-radius: 50px;
            border: 1.5px solid #d1d5db;
            background: transparent;
            color: var(--text-mid);
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            transition: border-color var(--transition), color var(--transition), background var(--transition);
            font-family: 'Outfit', sans-serif;
        }
        .btn-more:hover {
            border-color: var(--brand);
            color: var(--brand);
            background: var(--brand-light);
        }

        /* ─── CTA Banner ─────────────────────────────────────────── */
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
        .cta-banner__image {
            max-height: 300px;
            object-fit: contain;
            align-self: flex-end;
        }
        .cta-banner__image-placeholder {
            width: 260px;
            height: 280px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .cta-banner::before {
            content: '';
            position: absolute;
            left: -40px; bottom: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
        }

        /* ─── Footer ─────────────────────────────────────────────── */
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
        .footer__brand { display: flex; align-items: center; gap: 10px; font-size: 1.1rem; font-weight: 700; color: var(--brand); margin-bottom: 1rem; }
        .footer__desc { font-size: .87rem; color: var(--text-light); line-height: 1.7; }

        .footer__heading { font-weight: 600; font-size: .95rem; color: var(--text-dark); margin-bottom: 1rem; }
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

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 900px) {
            .doctors__grid { grid-template-columns: repeat(3, 1fr); }
            .hero__inner { padding: 36px 28px 0; }
            .cta-banner__inner { padding: 36px 28px 0; }
        }
        @media (max-width: 700px) {
            .navbar__nav { display: none; }
            .doctors__grid { grid-template-columns: repeat(2, 1fr); }
            .hero__image-placeholder, .cta-banner__image-placeholder { display: none; }
            .footer__grid { grid-template-columns: 1fr; }
            .hero__inner, .cta-banner__inner { flex-direction: column; }
        }

        /* ─── Animations ─────────────────────────────────────────── */
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
     NAVBAR
═══════════════════════════════════════════════════════════ -->
<header class="navbar">
    <div class="container navbar__inner">
        <!-- Logo -->
        <a href="index.php" class="navbar__logo">
            <div class="navbar__logo-icon">
                <!-- Simple home/cross icon SVG -->
                 
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            Prescripto
        </a>

        <!-- Nav links -->
        <nav class="navbar__nav">
            <a href="index.php" class="active">HOME</a>
            <a href="doctors.php">ALL DOCTORS</a>
            <a href="about.php">ABOUT</a>
            <a href="contact.php">CONTACT</a>
        </nav>

        <a href="login.php" class="btn-primary">Create account</a>
    </div>
</header>


<!-- ═══════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════ -->
<main>
<section class="container">
    <div class="hero">
        <div class="hero__inner">
            <div class="hero__content fade-up">
                <h1 class="hero__title">Book Appointment<br>With Trusted Doctors</h1>

                <div class="hero__avatars">
                    <div class="hero__avatar-group">
                        <span>A</span><span>B</span><span>C</span><span>D</span>
                    </div>
                    <p class="hero__sub">Simply browse through our extensive list of trusted doctors, schedule your appointment hassle-free.</p>
                </div>

                <div class="hero__cta">
                    <a href="doctors.php" class="btn-outline">
                        Book appointment
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            <!-- Doctor group image — place real image at frontend/assets/images/hero-doctors.png -->
            <div class="hero__image-placeholder">
                <img
                    src="frontend/assets/images/hero-doctors.png"
                    alt="Trusted Doctors"
                    class="hero__image"
                    onerror="this.style.display='none'"
                />
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     SPECIALITY
═══════════════════════════════════════════════════════════ -->
<section class="speciality">
    <div class="container">
        <h2 class="section-title fade-up fade-up-1">Find by Speciality</h2>
        <p class="section-sub fade-up fade-up-2">Simply browse through our extensive list of trusted doctors, schedule your appointment hassle-free.</p>

        <div class="speciality__grid fade-up fade-up-3">
            <?php
            $speciality_icons = ['🩺','👩‍⚕️','🧴','👶','🧠','🫁'];
            foreach ($specialities as $idx => $sp): ?>
                <a href="doctors.php?speciality=<?= urlencode($sp['name']) ?>" class="speciality__item">
                    <div class="speciality__icon">
                        <!-- Try real image first; if missing, show emoji fallback -->
                        <img
                            src="frontend/<?= htmlspecialchars($sp['icon']) ?>"
                            alt="<?= htmlspecialchars($sp['name']) ?>"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        />
                        <span class="speciality__icon-fallback" style="display:none;"><?= $speciality_icons[$idx] ?></span>
                    </div>
                    <span class="speciality__name"><?= htmlspecialchars($sp['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     TOP DOCTORS
═══════════════════════════════════════════════════════════ -->
<section class="doctors">
    <div class="container">
        <h2 class="section-title">Top Doctors to Book</h2>
        <p class="section-sub">Simply browse through our extensive list of trusted doctors.</p>

        <div class="doctors__grid">
            <?php foreach ($doctors as $doc): ?>
            <a href="appointment.php" class="doctor-card">
                <!-- Doctor photo with fallback SVG placeholder -->
                <?php $photo = 'frontend/' . $doc['photo']; ?>
                <div class="doctor-card__photo-placeholder">
                    <img
                        src="<?= htmlspecialchars($photo) ?>"
                        alt="<?= htmlspecialchars($doc['name']) ?>"
                        style="width:100%; height:100%; object-fit:cover; object-position:top;"
                        onerror="this.parentElement.innerHTML='<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 80 100\' fill=\'#8b97d8\'><ellipse cx=\'40\' cy=\'32\' rx=\'18\' ry=\'18\'/><path d=\'M10 85 Q10 58 40 58 Q70 58 70 85Z\'/></svg>';"
                    />
                </div>

                <div class="doctor-card__body">
                    <?php if ($doc['available']): ?>
                    <div class="doctor-card__available">
                        <span></span> Available
                    </div>
                    <?php endif; ?>
                    <div class="doctor-card__name"><?= htmlspecialchars($doc['name']) ?></div>
                    <div class="doctor-card__specialty"><?= htmlspecialchars($doc['specialty']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="doctors__more">
            <a href="doctors.php" class="btn-more">more</a>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════════════════════════ -->
<section class="container">
    <div class="cta-banner">
        <div class="cta-banner__inner">
            <div class="cta-banner__content">
                <h2 class="cta-banner__title">Book Appointment<br>With 100+ Trusted Doctors</h2>
                <a href="register.php" class="btn-outline">Create account</a>
            </div>

            <!-- Place image at frontend/assets/images/cta-doctor.png -->
            <div class="cta-banner__image-placeholder">
                <img
                    src="frontend/assets/images/cta-doctor.png"
                    alt="Trusted Doctor"
                    class="cta-banner__image"
                    onerror="this.style.display='none'"
                />
            </div>
        </div>
    </div>
</section>
</main>


<!-- ═══════════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════ -->
<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <!-- Brand col -->
            <div>
                <div class="footer__brand">
                    <div class="navbar__logo-icon" style="width:30px;height:30px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    Prescripto
                </div>
                <p class="footer__desc">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
            </div>

            <!-- Company col -->
            <div>
                <h4 class="footer__heading">COMPANY</h4>
                <ul class="footer__links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About us</a></li>
                    <li><a href="contact.php">Contact us</a></li>
                    <li><a href="privacy.php">Privacy policy</a></li>
                </ul>
            </div>

            <!-- Get in touch col -->
            <div>
                <h4 class="footer__heading">GET IN TOUCH</h4>
                <div class="footer__contact">
                    <p>+1-212-456-7890</p>
                    <p>greatstackdev@gmail.com</p>
                </div>
            </div>
        </div>

        <div class="footer__bottom">
            <p>Copyright &copy; <?= date('Y') ?> GreatStack – All Right Reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
