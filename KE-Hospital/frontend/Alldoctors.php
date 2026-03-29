<?php
// Prescripto – Doctors Page

$specialities = [
    'General physician',
    'Gynecologist',
    'Dermatologist',
    'Pediatricians',
    'Neurologist',
    'Gastroenterologist',
];

// Active filter from URL, default to none
$active = isset($_GET['speciality']) ? $_GET['speciality'] : '';

// Sample doctors data (replace with DB query)
$all_doctors = [];
for ($i = 0; $i < 14; $i++) {
    $all_doctors[] = [
        'name'      => 'Dr. Richard James',
        'specialty' => 'General physician',
        'available' => true,
        'photo'     => 'frontend/assets/images/doctor-' . (($i % 7) + 1) . '.png',
    ];
}

// Filter if speciality selected
$doctors = $active
    ? array_filter($all_doctors, fn($d) => strtolower($d['specialty']) === strtolower($active))
    : $all_doctors;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
            --shadow-card:  0 2px 16px rgba(95,111,255,.10);
            --shadow-hover: 0 6px 28px rgba(95,111,255,.20);
            --transition:   .25s ease;
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
        .navbar__logo-icon {
            width: 34px; height: 34px;
            background: var(--brand);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
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

        /* ─── Page Layout ────────────────────────────────────────── */
        .page-body {
            padding: 28px 0 72px;
        }

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
        }
        .doctor-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(95,111,255,.18);
            transform: translateY(-3px);
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
        .doctor-card__photo-wrap .placeholder-svg {
            width: 55%; opacity: .35;
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
        .footer__brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brand);
            margin-bottom: 1rem;
        }
        .footer__desc { font-size: .87rem; color: var(--text-light); line-height: 1.7; }
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

        /* ─── Animations ─────────────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .doctor-card {
            animation: fadeUp .4s ease both;
        }

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 960px) {
            .doctors-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 720px) {
            .doctors-layout { grid-template-columns: 1fr; }
            .speciality-sidebar { flex-direction: row; flex-wrap: wrap; position: static; }
            .speciality-sidebar__item { flex: 1 1 auto; text-align: center; }
            .doctors-grid { grid-template-columns: repeat(2, 1fr); }
            .navbar__nav { display: none; }
            .footer__grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════════════════ -->
<header class="navbar">
    <div class="container navbar__inner">
        <a href="index.php" class="navbar__logo">
            <div class="navbar__logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            Prescripto
        </a>

        <nav class="navbar__nav">
            <a href="index.php">HOME</a>
            <a href="doctors.php" class="active">ALL DOCTORS</a>
            <a href="about.php">ABOUT</a>
            <a href="contact.php">CONTACT</a>
        </nav>

        <a href="register.php" class="btn-primary">Create account</a>
    </div>
</header>


<!-- ═══════════════════════════════════════════════════════════
     MAIN
═══════════════════════════════════════════════════════════ -->
<main class="page-body">
    <div class="container">

        <p class="page-intro">Browse through the doctors specialist.</p>

        <div class="doctors-layout">

            <!-- ── Speciality Sidebar ── -->
            <aside class="speciality-sidebar">
                <?php foreach ($specialities as $sp):
                    $is_active = strtolower($active) === strtolower($sp);
                    $href = $is_active
                        ? 'doctors.php'                            // click again = clear filter
                        : 'doctors.php?speciality=' . urlencode($sp);
                ?>
                <a
                    href="<?= htmlspecialchars($href) ?>"
                    class="speciality-sidebar__item<?= $is_active ? ' active' : '' ?>"
                >
                    <?= htmlspecialchars($sp) ?>
                </a>
                <?php endforeach; ?>
            </aside>

            <!-- ── Doctors Grid ── -->
            <section>
                <div class="doctors-grid">
                    <?php if (empty($doctors)): ?>
                        <div class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                            <p>No doctors found for this speciality.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($doctors as $i => $doc): ?>
                        <a
                            href="appointment.php"
                            class="doctor-card"
                            style="animation-delay: <?= ($i % 4) * 0.07 ?>s"
                        >
                            <div class="doctor-card__photo-wrap">
                                <img
                                    src="<?= htmlspecialchars($doc['photo']) ?>"
                                    alt="<?= htmlspecialchars($doc['name']) ?>"
                                    onerror="
                                        this.style.display='none';
                                        this.parentElement.innerHTML='<svg class=\'placeholder-svg\' xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 80 100\' fill=\'#8b97d8\'><ellipse cx=\'40\' cy=\'32\' rx=\'18\' ry=\'18\'/><path d=\'M10 85 Q10 58 40 58 Q70 58 70 85Z\'/></svg>';
                                    "
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
                    <?php endif; ?>
                </div>
            </section>

        </div><!-- /doctors-layout -->
    </div><!-- /container -->
</main>


<!-- ═══════════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════ -->
<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div>
                <div class="footer__brand">
                    <div class="navbar__logo-icon" style="width:30px;height:30px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    Prescripto
                </div>
                <p class="footer__desc">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
            </div>

            <div>
                <h4 class="footer__heading">COMPANY</h4>
                <ul class="footer__links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About us</a></li>
                    <li><a href="contact.php">Contact us</a></li>
                    <li><a href="privacy.php">Privacy policy</a></li>
                </ul>
            </div>

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
