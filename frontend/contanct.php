<?php
// Prescripto – Contact Page

$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name)                        $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (!$message)                     $errors[] = 'Message cannot be empty.';

    if (empty($errors)) {
        // TODO: send email / save to DB
        // mail('greatstackdev@gmail.com', $subject ?: 'Contact Form', $message, "From: $email");
        $success = 'Thank you, ' . htmlspecialchars($name) . '! Your message has been sent. We\'ll get back to you shortly.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us – Prescripto</title>

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
            --green:        #22c55e;
            --red:          #ef4444;
            --transition:   .25s ease;
            --radius-md:    12px;
            --radius-sm:    8px;
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
            font-family: 'Outfit', sans-serif;
        }
        .btn-primary:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(95,111,255,.35);
        }

        /* ─── Page Body ──────────────────────────────────────────── */
        .page-body { padding: 56px 0 80px; }

        /* ─── Page Heading ───────────────────────────────────────── */
        .page-heading {
            text-align: center;
            font-size: 1.55rem;
            font-weight: 400;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-dark);
            margin-bottom: 52px;
        }
        .page-heading strong { font-weight: 700; }

        /* ─── Top Block: image + office info ─────────────────────── */
        .contact-top {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            margin-bottom: 72px;
        }

        .contact-top__image {
            border-radius: var(--radius-md);
            overflow: hidden;
            aspect-ratio: 4/3.2;
            background: linear-gradient(160deg, #dde3f9 0%, #c5ccf0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .contact-top__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .contact-top__info { display: flex; flex-direction: column; gap: 32px; }

        .info-block__label {
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        .info-block__text {
            font-size: .9rem;
            color: var(--text-mid);
            line-height: 1.75;
        }
        .info-block__text a {
            color: var(--brand);
            font-weight: 500;
        }
        .info-block__text a:hover { text-decoration: underline; }

        /* Careers sub-block */
        .careers-block { padding-top: 4px; }
        .careers-block .info-block__label { margin-bottom: 6px; }
        .careers-block p {
            font-size: .88rem;
            color: var(--text-light);
            margin-bottom: 16px;
        }
        .btn-outline-dark {
            display: inline-block;
            padding: 10px 26px;
            border: 1.5px solid var(--text-dark);
            border-radius: var(--radius-sm);
            font-size: .88rem;
            font-weight: 500;
            color: var(--text-dark);
            background: transparent;
            cursor: pointer;
            transition: background var(--transition), color var(--transition), border-color var(--transition);
            font-family: 'Outfit', sans-serif;
        }
        .btn-outline-dark:hover {
            background: var(--brand);
            border-color: var(--brand);
            color: var(--white);
        }

        /* ─── Divider ────────────────────────────────────────────── */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0 0 64px;
        }

        /* ─── Info Cards Row ─────────────────────────────────────── */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 64px;
        }
        .info-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: box-shadow var(--transition), border-color var(--transition);
        }
        .info-card:hover {
            border-color: var(--brand);
            box-shadow: 0 4px 20px rgba(95,111,255,.12);
        }
        .info-card__icon {
            width: 42px; height: 42px;
            background: var(--brand-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
        }
        .info-card__icon svg { color: var(--brand); }
        .info-card__title {
            font-weight: 600;
            font-size: .95rem;
            color: var(--text-dark);
        }
        .info-card__detail {
            font-size: .87rem;
            color: var(--text-light);
            line-height: 1.65;
        }
        .info-card__detail a { color: var(--brand); }

        /* ─── Contact Form ───────────────────────────────────────── */
        .contact-form-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        .form-block__heading {
            font-family: 'DM Serif Display', serif;
            font-size: 1.6rem;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        .form-block__sub {
            font-size: .9rem;
            color: var(--text-light);
            margin-bottom: 28px;
        }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: .9rem;
            font-family: 'Outfit', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            transition: border-color var(--transition), box-shadow var(--transition);
            outline: none;
            resize: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(95,111,255,.12);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: .88rem;
            margin-bottom: 20px;
        }
        .form-alert.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .form-alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .form-alert ul { padding-left: 18px; list-style: disc; }
        .form-alert li { margin-top: 4px; }

        /* Map placeholder */
        .map-block { display: flex; flex-direction: column; gap: 16px; }
        .map-placeholder {
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border);
            background: #f0f2f8;
            aspect-ratio: 4/3;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--text-light);
            font-size: .88rem;
        }
        .map-placeholder svg { opacity: .4; }
        .map-note {
            font-size: .82rem;
            color: var(--text-light);
        }
        .map-note a { color: var(--brand); font-weight: 500; }

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
        .fade-up  { animation: fadeUp .5s ease both; }
        .delay-1  { animation-delay: .1s; }
        .delay-2  { animation-delay: .2s; }
        .delay-3  { animation-delay: .3s; }
        .delay-4  { animation-delay: .4s; }

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 900px) {
            .contact-top           { grid-template-columns: 1fr; gap: 32px; }
            .contact-form-section  { grid-template-columns: 1fr; gap: 36px; }
            .info-cards            { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .navbar__nav  { display: none; }
            .form-row     { grid-template-columns: 1fr; }
            .info-cards   { grid-template-columns: 1fr; }
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
            <a href="about.php">ABOUT</a>
            <a href="contact.php" class="active">CONTACT</a>
        </nav>

        <a href="register.php" class="btn-primary">Create account</a>
    </div>
</header>


<!-- ═══════════════════════════════════════════════════════════
     MAIN
═══════════════════════════════════════════════════════════ -->
<main class="page-body">
    <div class="container">

        <!-- Page heading -->
        <h1 class="page-heading fade-up">CONTACT <strong>US</strong></h1>

        <!-- ── Top: image + office info ── -->
        <div class="contact-top fade-up delay-1">

            <div class="contact-top__image">
                <img
                    src="frontend/assets/images/contact-office.png"
                    alt="Our Office"
                    onerror="this.style.display='none'"
                />
            </div>

            <div class="contact-top__info">

                <!-- Office address -->
                <div class="info-block">
                    <div class="info-block__label">OUR OFFICE</div>
                    <div class="info-block__text">
                        54709 Willms Station<br>
                        Suite 350, Washington, USA<br><br>
                        Tel: <a href="tel:+14155550132">(415) 555-0132</a><br>
                        Email: <a href="mailto:greatstackdev@gmail.com">greatstackdev@gmail.com</a>
                    </div>
                </div>

                <!-- Office hours -->
                <div class="info-block">
                    <div class="info-block__label">OFFICE HOURS</div>
                    <div class="info-block__text">
                        Monday – Friday: &nbsp;8:00 AM – 6:00 PM<br>
                        Saturday: &nbsp;9:00 AM – 2:00 PM<br>
                        Sunday: &nbsp;Closed
                    </div>
                </div>

                <!-- Careers -->
                <div class="careers-block">
                    <div class="info-block__label">CAREERS AT PRESCRIPTO</div>
                    <p>Learn more about our teams and job openings.</p>
                    <a href="careers.php" class="btn-outline-dark">Explore Jobs</a>
                </div>

            </div>
        </div>

        <hr class="divider" />

        <!-- ── 3 Info Cards ── -->
        <div class="info-cards fade-up delay-2">

            <div class="info-card">
                <div class="info-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.8 19.8 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.8 19.8 0 01.03 1.18 2 2 0 012 .02h3a2 2 0 012 1.72c.13 1 .36 1.97.7 2.9a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.93.34 1.9.57 2.9.7A2 2 0 0122 14.92z"/></svg>
                </div>
                <div class="info-card__title">Phone Support</div>
                <div class="info-card__detail">
                    <a href="tel:+14155550132">(415) 555-0132</a><br>
                    Available Mon–Fri, 8 AM – 6 PM
                </div>
            </div>

            <div class="info-card">
                <div class="info-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div class="info-card__title">Email Us</div>
                <div class="info-card__detail">
                    <a href="mailto:greatstackdev@gmail.com">greatstackdev@gmail.com</a><br>
                    We reply within 24 hours
                </div>
            </div>

            <div class="info-card">
                <div class="info-card__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div class="info-card__title">Visit Us</div>
                <div class="info-card__detail">
                    54709 Willms Station, Suite 350<br>
                    Washington, USA
                </div>
            </div>

        </div>

        <!-- ── Contact Form + Map ── -->
        <div class="contact-form-section fade-up delay-3">

            <!-- Form -->
            <div class="form-block">
                <h2 class="form-block__heading">Send Us a Message</h2>
                <p class="form-block__sub">Fill in the form below and our team will reach out to you as soon as possible.</p>

                <?php if ($success): ?>
                    <div class="form-alert success"><?= $success ?></div>
                <?php elseif (!empty($errors)): ?>
                    <div class="form-alert error">
                        <ul>
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span style="color:var(--red)">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="e.g. John Smith"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            />
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address <span style="color:var(--red)">*</span></label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="you@example.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="+1 (415) 000-0000"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            />
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject">
                                <option value="">Select a topic…</option>
                                <option value="Appointment Enquiry"   <?= (($_POST['subject'] ?? '') === 'Appointment Enquiry')   ? 'selected' : '' ?>>Appointment Enquiry</option>
                                <option value="Technical Support"     <?= (($_POST['subject'] ?? '') === 'Technical Support')     ? 'selected' : '' ?>>Technical Support</option>
                                <option value="Billing & Payments"    <?= (($_POST['subject'] ?? '') === 'Billing & Payments')    ? 'selected' : '' ?>>Billing &amp; Payments</option>
                                <option value="Careers"               <?= (($_POST['subject'] ?? '') === 'Careers')               ? 'selected' : '' ?>>Careers</option>
                                <option value="Other"                 <?= (($_POST['subject'] ?? '') === 'Other')                 ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message">Message <span style="color:var(--red)">*</span></label>
                        <textarea
                            id="message"
                            name="message"
                            rows="5"
                            placeholder="Write your message here…"
                        ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%; justify-content:center; margin-top:4px;">
                        Send Message
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
            </div>

            <!-- Map placeholder -->
            <div class="map-block fade-up delay-4">
                <div class="map-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#5F6FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>54709 Willms Station, Suite 350<br>Washington, USA</span>
                </div>
                <p class="map-note">
                    Need directions? &nbsp;<a href="https://maps.google.com/?q=Washington+DC+USA" target="_blank" rel="noopener">Open in Google Maps →</a>
                </p>

                <!-- FAQ teaser -->
                <div style="margin-top:24px; padding:24px; border:1px solid var(--border); border-radius:var(--radius-md);">
                    <div style="font-weight:600; font-size:.95rem; margin-bottom:12px;">Frequently Asked Questions</div>
                    <details style="margin-bottom:10px;">
                        <summary style="cursor:pointer; font-size:.88rem; color:var(--text-mid); padding:6px 0;">How do I book an appointment?</summary>
                        <p style="font-size:.85rem; color:var(--text-light); padding:8px 0 0 12px;">Browse our doctors list, select your preferred specialist, choose an available slot, and confirm your booking in minutes.</p>
                    </details>
                    <details style="margin-bottom:10px;">
                        <summary style="cursor:pointer; font-size:.88rem; color:var(--text-mid); padding:6px 0;">Can I cancel or reschedule?</summary>
                        <p style="font-size:.85rem; color:var(--text-light); padding:8px 0 0 12px;">Yes. You can cancel or reschedule up to 24 hours before your appointment from your account dashboard.</p>
                    </details>
                    <details>
                        <summary style="cursor:pointer; font-size:.88rem; color:var(--text-mid); padding:6px 0;">Is my health data secure?</summary>
                        <p style="font-size:.85rem; color:var(--text-light); padding:8px 0 0 12px;">Absolutely. All data is encrypted and stored in compliance with HIPAA regulations.</p>
                    </details>
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