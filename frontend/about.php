<?php
// Prescripto – About Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About Us – Prescripto</title>

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
            --white:        #ffffff;
            --border:       #e5e7eb;
            --transition:   .25s ease;
            --radius-md:    12px;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            line-height: 1.7;
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

        /* ─── Page Body ──────────────────────────────────────────── */
        .page-body {
            padding: 56px 0 80px;
        }

        /* ─── About Section ──────────────────────────────────────── */
        .about__heading {
            text-align: center;
            font-size: 1.55rem;
            font-weight: 400;
            letter-spacing: .04em;
            color: var(--text-dark);
            margin-bottom: 44px;
            text-transform: uppercase;
        }
        .about__heading strong {
            font-weight: 700;
            color: var(--text-dark);
        }

        .about__content {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 52px;
            align-items: start;
            margin-bottom: 72px;
        }

        /* Image */
        .about__image-wrap {
            border-radius: var(--radius-md);
            overflow: hidden;
            background: linear-gradient(160deg, #dde3f9 0%, #c5ccf0 100%);
            aspect-ratio: 4/4.5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .about__image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
        }
        .about__image-wrap .img-placeholder {
            width: 55%; opacity: .3;
        }

        /* Text */
        .about__text p {
            font-size: .93rem;
            color: var(--text-mid);
            margin-bottom: 1.25rem;
        }
        .about__text p:last-child { margin-bottom: 0; }

        .about__text .vision-label {
            font-weight: 700;
            font-size: .93rem;
            color: var(--text-dark);
            margin-bottom: .4rem;
            margin-top: 1.6rem;
        }

        /* ─── Why Choose Us ──────────────────────────────────────── */
        .why__heading {
            font-size: 1.3rem;
            font-weight: 400;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-dark);
            margin-bottom: 28px;
        }
        .why__heading strong {
            font-weight: 700;
        }

        .why__grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .why__card {
            padding: 36px 32px 44px;
            border-right: 1px solid var(--border);
            transition: background var(--transition);
        }
        .why__card:last-child { border-right: none; }
        .why__card:hover { background: var(--brand-light); }

        .why__card-title {
            font-size: .92rem;
            font-weight: 700;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 18px;
        }

        .why__card-text {
            font-size: .88rem;
            color: var(--text-mid);
            line-height: 1.65;
        }

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
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up   { animation: fadeUp .5s ease both; }
        .delay-1   { animation-delay: .1s; }
        .delay-2   { animation-delay: .2s; }
        .delay-3   { animation-delay: .3s; }

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 860px) {
            .about__content { grid-template-columns: 1fr; gap: 28px; }
            .about__image-wrap { aspect-ratio: 16/9; }
            .why__grid { grid-template-columns: 1fr; }
            .why__card { border-right: none; border-bottom: 1px solid var(--border); }
            .why__card:last-child { border-bottom: none; }
        }
        @media (max-width: 700px) {
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
            <a href="doctors.php">ALL DOCTORS</a>
            <a href="about.php" class="active">ABOUT</a>
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

        <!-- ── ABOUT US ── -->
        <h1 class="about__heading fade-up">ABOUT <strong>US</strong></h1>

        <div class="about__content">

            <!-- Image -->
            <div class="about__image-wrap fade-up delay-1">
                <img
                    src="frontend/assets/images/about-doctors.png"
                    alt="Prescripto Doctors"
                    onerror="
                        this.style.display='none';
                        this.parentElement.innerHTML='<svg class=\'img-placeholder\' xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 120 140\' fill=\'#8b97d8\'>
                            <ellipse cx=\'38\' cy=\'38\' rx=\'18\' ry=\'18\'/>
                            <path d=\'M5 100 Q5 68 38 68 Q60 68 70 80\'/>
                            <ellipse cx=\'82\' cy=\'42\' rx=\'20\' ry=\'20\'/>
                            <path d=\'M45 105 Q55 72 82 72 Q115 72 115 105Z\'/>
                        </svg>';
                    "
                />
            </div>

            <!-- Text -->
            <div class="about__text fade-up delay-2">
                <p>
                    Welcome To Prescripto, Your Trusted Partner In Managing Your Healthcare Needs Conveniently And Efficiently.
                    At Prescripto, We Understand The Challenges Individuals Face When It Comes To Scheduling Doctor
                    Appointments And Managing Their Health Records.
                </p>

                <p>
                    Prescripto Is Committed To Excellence In Healthcare Technology. We Continuously Strive To Enhance Our
                    Platform, Integrating The Latest Advancements To Improve User Experience And Deliver Superior Service.
                    Whether You're Booking Your First Appointment Or Managing Ongoing Care, Prescripto Is Here To Support You
                    Every Step Of The Way.
                </p>

                <p class="vision-label">Our Vision</p>

                <p>
                    Our Vision At Prescripto Is To Create A Seamless Healthcare Experience For Every User. We Aim To Bridge The
                    Gap Between Patients And Healthcare Providers, Making It Easier For You To Access The Care You Need, When
                    You Need It.
                </p>
            </div>

        </div>

        <!-- ── WHY CHOOSE US ── -->
        <div class="fade-up delay-3">
            <h2 class="why__heading">WHY <strong>CHOOSE US</strong></h2>

            <div class="why__grid">

                <div class="why__card">
                    <div class="why__card-title">EFFICIENCY:</div>
                    <p class="why__card-text">
                        Streamlined Appointment Scheduling<br>
                        That Fits Into Your Busy Lifestyle.
                    </p>
                </div>

                <div class="why__card">
                    <div class="why__card-title">CONVENIENCE:</div>
                    <p class="why__card-text">
                        Access To A Network Of Trusted<br>
                        Healthcare Professionals In Your Area.
                    </p>
                </div>

                <div class="why__card">
                    <div class="why__card-title">PERSONALIZATION:</div>
                    <p class="why__card-text">
                        Tailored Recommendations And Reminders<br>
                        To Help You Stay On Top Of Your Health.
                    </p>
                </div>

            </div>
        </div>

    </div>
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