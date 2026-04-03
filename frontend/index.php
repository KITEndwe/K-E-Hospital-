<?php
// K&E Hospital - Dynamic Homepage
session_start();

// Database connection
$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$doctors      = [];
$specialities = [];
$db_error     = false;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch top 10 available doctors ordered by rating
    $stmt = $pdo->query("
        SELECT doctor_id, name, profile_image, speciality, degree, experience, fees, rating, total_reviews, is_available
        FROM doctors
        WHERE is_available = 1
        ORDER BY rating DESC, total_reviews DESC
        LIMIT 10
    ");
    $doctors = $stmt->fetchAll();

    // Fetch distinct specialities
    $spec_stmt    = $pdo->query("SELECT DISTINCT speciality FROM doctors ORDER BY speciality");
    $specialities = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $db_error = true;
    // Fallback static data matching your doctors table
    $doctors = [
        ['doctor_id'=>'doc5',  'name'=>'Dr. Chipo Mwansa',     'profile_image'=>'/assets/doc5.jpg',  'speciality'=>'Neurologist',       'degree'=>'MBChB', 'experience'=>'6 Years', 'fees'=>350, 'rating'=>4.9, 'total_reviews'=>52, 'is_available'=>1],
        ['doctor_id'=>'doc2',  'name'=>'Dr. Mutinta Phiri',    'profile_image'=>'/assets/doc2.jpg',  'speciality'=>'Gynecologist',      'degree'=>'MBChB', 'experience'=>'3 Years', 'fees'=>300, 'rating'=>4.8, 'total_reviews'=>42, 'is_available'=>1],
        ['doctor_id'=>'doc4',  'name'=>'Dr. Christopher Tembo','profile_image'=>'/assets/doc4.jpg',  'speciality'=>'Pediatrician',     'degree'=>'MBChB', 'experience'=>'4 Years', 'fees'=>280, 'rating'=>4.7, 'total_reviews'=>35, 'is_available'=>1],
        ['doctor_id'=>'doc8',  'name'=>'Dr. Lillian Chanda',   'profile_image'=>'/assets/doc8.jpg',  'speciality'=>'Gynecologist',      'degree'=>'MBChB', 'experience'=>'3 Years', 'fees'=>300, 'rating'=>4.7, 'total_reviews'=>38, 'is_available'=>1],
        ['doctor_id'=>'doc6',  'name'=>'Dr. Kelvin Mulenga',   'profile_image'=>'/assets/doc6.jpg',  'speciality'=>'Neurologist',       'degree'=>'MBChB', 'experience'=>'5 Years', 'fees'=>320, 'rating'=>4.6, 'total_reviews'=>31, 'is_available'=>1],
        ['doctor_id'=>'doc1',  'name'=>'Dr. Mwila Banda',      'profile_image'=>'/assets/doc1.jpg',  'speciality'=>'General Physician', 'degree'=>'MBChB', 'experience'=>'5 Years', 'fees'=>250, 'rating'=>4.5, 'total_reviews'=>28, 'is_available'=>1],
        ['doctor_id'=>'doc10', 'name'=>'Dr. Joseph Mwansa',    'profile_image'=>'/assets/doc10.jpg', 'speciality'=>'Pediatrician',     'degree'=>'MBChB', 'experience'=>'4 Years', 'fees'=>280, 'rating'=>4.5, 'total_reviews'=>27, 'is_available'=>1],
        ['doctor_id'=>'doc7',  'name'=>'Dr. Patrick Tembo',    'profile_image'=>'/assets/doc7.jpg',  'speciality'=>'General Physician', 'degree'=>'MBChB', 'experience'=>'4 Years', 'fees'=>260, 'rating'=>4.4, 'total_reviews'=>23, 'is_available'=>1],
        ['doctor_id'=>'doc9',  'name'=>'Dr. Thandiwe Kapasa',  'profile_image'=>'/assets/doc9.jpg',  'speciality'=>'Dermatologist',     'degree'=>'MBChB', 'experience'=>'2 Years', 'fees'=>220, 'rating'=>4.3, 'total_reviews'=>19, 'is_available'=>1],
        ['doctor_id'=>'doc3',  'name'=>'Dr. Luyando Zulu',     'profile_image'=>'/assets/doc3.jpg',  'speciality'=>'Dermatologist',     'degree'=>'MBChB', 'experience'=>'2 Years', 'fees'=>220, 'rating'=>4.2, 'total_reviews'=>15, 'is_available'=>1],
    ];
    $specialities = ['General Physician','Gynecologist','Dermatologist','Pediatrician','Neurologist','Gastroenterologist'];
}

// Speciality icon map
$speciality_icons = [
    'General Physician'  => '🩺',
    'Gynecologist'       => '👩‍⚕️',
    'Dermatologist'      => '🧴',
    'Pediatrician'       => '👶',
    'Neurologist'        => '🧠',
    'Gastroenterologist' => '🫁',
];

$speciality_colors = [
    'General Physician'  => '#4f8ef7',
    'Gynecologist'       => '#f76fa8',
    'Dermatologist'      => '#f7a84f',
    'Pediatrician'       => '#4fc9f7',
    'Neurologist'        => '#9b6bf7',
    'Gastroenterologist' => '#4ff7a0',
];

$is_logged_in = isset($_SESSION['user_id']);
$user_name    = $_SESSION['full_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K&E Hospital – Book Appointment With Trusted Doctors</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ── CSS VARIABLES ── */
        :root {
            --blue:        #5f6fff;
            --blue-light:  #eef0ff;
            --blue-mid:    #c8ccff;
            --dark:        #0d1117;
            --text:        #2d3748;
            --muted:       #718096;
            --border:      #e8ecf0;
            --white:       #ffffff;
            --card-bg:     #f7f9fc;
            --green:       #22c55e;
            --yellow:      #fbbf24;
            --radius:      14px;
            --shadow:      0 4px 24px rgba(95,111,255,0.10);
            --shadow-hover:0 8px 40px rgba(95,111,255,0.18);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background: var(--white);
            overflow-x: hidden;
        }

        h1,h2,h3 { font-family: 'Fraunces', serif; }

        /* ── NAVBAR ── */
        nav {
            position: sticky; top: 0; z-index: 200;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 5%;
            display: flex; align-items: center; justify-content: space-between;
            height: 68px;
        }

        .nav-logo {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }

        .nav-logo .logo-icon {
            width: 36px; height: 36px;
            background: var(--blue);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
        }

        .nav-logo .logo-text {
            font-family: 'Fraunces', serif;
            font-size: 1.25rem; font-weight: 700;
            color: var(--dark);
        }

        .nav-logo .logo-text span { color: var(--blue); }

        .nav-links {
            display: flex; align-items: center; gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none; color: var(--muted);
            font-size: 0.9rem; font-weight: 500;
            transition: color 0.2s; position: relative;
        }

        .nav-links a:hover { color: var(--blue); }

        .nav-links a.active {
            color: var(--dark); font-weight: 600;
        }

        .nav-links a.active::after {
            content: ''; position: absolute; bottom: -4px; left: 0; right: 0;
            height: 2px; background: var(--blue); border-radius: 2px;
        }

        .nav-actions { display: flex; align-items: center; gap: 1rem; }

        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.55rem 1.4rem;
            border-radius: 50px; font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; transition: all 0.25s; text-decoration: none; border: none;
        }

        .btn-primary {
            background: var(--blue); color: white;
            box-shadow: 0 4px 14px rgba(95,111,255,0.35);
        }

        .btn-primary:hover {
            background: #4a5af0;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(95,111,255,0.4);
        }

        .btn-outline {
            background: transparent; color: var(--blue);
            border: 1.5px solid var(--blue);
        }

        .btn-outline:hover { background: var(--blue-light); }

        .user-nav {
            display: flex; align-items: center; gap: 0.75rem;
            background: var(--card-bg); padding: 0.4rem 1rem; border-radius: 50px;
        }

        .user-nav .avatar {
            width: 30px; height: 30px;
            background: var(--blue); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.75rem; font-weight: 700;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, #5f6fff 0%, #7b8bff 50%, #9ba5ff 100%);
            min-height: 520px;
            display: flex; align-items: center;
            padding: 3rem 5%;
            position: relative; overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute; top: -40%; right: -10%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero::after {
            content: '';
            position: absolute; bottom: -30%; left: 20%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-content {
            position: relative; z-index: 2;
            flex: 1; max-width: 520px;
        }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(255,255,255,0.2);
            padding: 0.4rem 1rem; border-radius: 50px;
            color: white; font-size: 0.8rem; font-weight: 500;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .hero h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            color: white; font-weight: 700;
            line-height: 1.15; margin-bottom: 1.25rem;
        }

        .hero-patients {
            display: flex; align-items: center; gap: 0.75rem;
            margin-bottom: 1.75rem;
        }

        .patient-avatars { display: flex; }

        .patient-avatars span {
            width: 32px; height: 32px; border-radius: 50%;
            border: 2px solid white;
            background: linear-gradient(135deg, #a8b4ff, #7b8bff);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem; font-weight: 700; color: white;
            margin-left: -8px;
        }

        .patient-avatars span:first-child { margin-left: 0; }

        .hero-patients p {
            color: rgba(255,255,255,0.9); font-size: 0.85rem;
            max-width: 200px; line-height: 1.4;
        }

        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; }

        .btn-hero {
            background: white; color: var(--blue);
            padding: 0.75rem 1.75rem;
            border-radius: 50px; font-weight: 600;
            font-size: 0.95rem; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem;
            transition: all 0.25s;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.2);
        }

        .hero-image {
            position: absolute; right: 5%; bottom: 0;
            height: 90%; max-height: 480px;
            z-index: 2;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.2));
        }

        /* ── SECTION COMMONS ── */
        section { padding: 5rem 5%; }

        .section-header { text-align: center; margin-bottom: 3rem; }

        .section-header h2 {
            font-size: clamp(1.5rem, 3vw, 2.25rem);
            color: var(--dark); font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .section-header p { color: var(--muted); font-size: 0.95rem; max-width: 500px; margin: 0 auto; }

        /* ── SPECIALITIES ── */
        .specialities-section { background: var(--white); }

        .specialities-grid {
            display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem;
        }

        .speciality-card {
            display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
            background: var(--card-bg);
            padding: 1.5rem 1.75rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            cursor: pointer; transition: all 0.3s;
            min-width: 120px; text-decoration: none;
        }

        .speciality-card:hover {
            transform: translateY(-5px);
            border-color: var(--blue);
            box-shadow: var(--shadow);
            background: var(--blue-light);
        }

        .speciality-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: var(--blue-light);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.75rem;
            transition: all 0.3s;
        }

        .speciality-card:hover .speciality-icon {
            background: var(--blue);
        }

        .speciality-name {
            font-size: 0.8rem; font-weight: 500;
            color: var(--text); text-align: center;
        }

        /* ── DOCTORS SECTION ── */
        .doctors-section { background: var(--card-bg); }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }

        .doctor-card {
            background: white; border-radius: var(--radius);
            overflow: hidden; border: 1px solid var(--border);
            transition: all 0.3s; cursor: pointer;
            text-decoration: none; color: inherit;
            display: block;
        }

        .doctor-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: var(--blue-mid);
        }

        .doctor-img-wrap {
            background: linear-gradient(160deg, #e8eeff 0%, #d4daff 100%);
            height: 200px; position: relative; overflow: hidden;
        }

        .doctor-img-wrap img {
            width: 100%; height: 100%; object-fit: cover; object-position: top;
            transition: transform 0.4s;
        }

        .doctor-card:hover .doctor-img-wrap img { transform: scale(1.04); }

        .availability-dot {
            position: absolute; top: 10px; left: 10px;
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.92);
            padding: 3px 10px; border-radius: 50px;
            font-size: 0.7rem; font-weight: 600;
        }

        .availability-dot .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.6; transform: scale(1.3); }
        }

        .doctor-body { padding: 1rem 1.1rem; }

        .doctor-name {
            font-family: 'Fraunces', serif;
            font-size: 1rem; font-weight: 600; color: var(--dark);
            margin-bottom: 0.2rem;
        }

        .doctor-spec {
            font-size: 0.78rem; color: var(--blue); font-weight: 500;
        }

        .doctor-meta {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 0.75rem; padding-top: 0.75rem;
            border-top: 1px solid var(--border);
        }

        .doctor-fee { font-weight: 700; color: var(--dark); font-size: 0.9rem; }
        .doctor-fee span { font-size: 0.7rem; color: var(--muted); font-weight: 400; }

        .doctor-rating {
            display: flex; align-items: center; gap: 3px;
            font-size: 0.78rem; color: var(--yellow); font-weight: 600;
        }

        .doctor-rating em { color: var(--muted); font-style: normal; font-weight: 400; }

        .more-btn-wrap { text-align: center; }

        /* ── CTA BANNER ── */
        .cta-section {
            background: linear-gradient(135deg, #5f6fff 0%, #7b8bff 100%);
            padding: 4rem 5%;
            display: flex; align-items: center; justify-content: space-between;
            gap: 2rem; flex-wrap: wrap; position: relative; overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute; top: -50%; right: -5%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .cta-content { position: relative; z-index: 2; }

        .cta-content h2 {
            font-size: clamp(1.5rem, 3vw, 2.25rem);
            color: white; font-weight: 700;
            line-height: 1.2; margin-bottom: 1.5rem;
        }

        .cta-image {
            height: 260px; position: relative; z-index: 2;
            filter: drop-shadow(0 16px 32px rgba(0,0,0,0.2));
        }

        /* ── FOOTER ── */
        footer {
            background: var(--dark); color: rgba(255,255,255,0.7);
            padding: 4rem 5% 2rem;
        }

        .footer-grid {
            display: grid; grid-template-columns: 2fr 1fr 1fr;
            gap: 3rem; margin-bottom: 3rem;
        }

        .footer-logo {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 1.25rem; text-decoration: none;
        }

        .footer-logo .logo-icon {
            width: 36px; height: 36px;
            background: var(--blue); border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
        }

        .footer-logo .logo-text {
            font-family: 'Fraunces', serif;
            font-size: 1.2rem; font-weight: 700; color: white;
        }

        footer p { font-size: 0.875rem; line-height: 1.7; color: rgba(255,255,255,0.55); }

        .footer-col h4 {
            color: white; font-size: 0.875rem; font-weight: 600;
            margin-bottom: 1.25rem; letter-spacing: 0.05em; text-transform: uppercase;
        }

        .footer-col ul { list-style: none; }

        .footer-col ul li { margin-bottom: 0.75rem; }

        .footer-col ul li a {
            color: rgba(255,255,255,0.55); text-decoration: none;
            font-size: 0.875rem; transition: color 0.2s;
        }

        .footer-col ul li a:hover { color: white; }

        .footer-col ul li { font-size: 0.875rem; }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 1rem;
        }

        .footer-bottom p { font-size: 0.8rem; color: rgba(255,255,255,0.35); }

        /* ── MOBILE MENU ── */
        .hamburger {
            display: none; flex-direction: column; gap: 5px;
            cursor: pointer; padding: 5px; background: none; border: none;
        }

        .hamburger span {
            display: block; width: 24px; height: 2px;
            background: var(--dark); border-radius: 2px; transition: all 0.3s;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .hero-image { display: none; }
            .hero { min-height: 400px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 640px) {
            nav { padding: 0 1.25rem; }
            .nav-links { display: none; }
            .hamburger { display: flex; }
            section { padding: 3rem 1.25rem; }
            .hero { padding: 2.5rem 1.25rem; }
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
            .doctors-grid { grid-template-columns: repeat(2, 1fr); gap: 0.875rem; }
            .cta-image { display: none; }
            .cta-section { justify-content: center; text-align: center; }
        }

        /* ── SCROLL ANIMATIONS ── */
        .fade-up {
            opacity: 0; transform: translateY(28px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .fade-up.visible { opacity: 1; transform: translateY(0); }

        .stagger .fade-up:nth-child(1)  { transition-delay: 0.05s; }
        .stagger .fade-up:nth-child(2)  { transition-delay: 0.10s; }
        .stagger .fade-up:nth-child(3)  { transition-delay: 0.15s; }
        .stagger .fade-up:nth-child(4)  { transition-delay: 0.20s; }
        .stagger .fade-up:nth-child(5)  { transition-delay: 0.25s; }
        .stagger .fade-up:nth-child(6)  { transition-delay: 0.30s; }
        .stagger .fade-up:nth-child(7)  { transition-delay: 0.35s; }
        .stagger .fade-up:nth-child(8)  { transition-delay: 0.40s; }
        .stagger .fade-up:nth-child(9)  { transition-delay: 0.45s; }
        .stagger .fade-up:nth-child(10) { transition-delay: 0.50s; }
    </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="logo-icon"><i class="fas fa-hospital-user"></i></div>
        <span class="logo-text">K&amp;<span>E</span> Hospital</span>
    </a>

    <ul class="nav-links">
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="frontend/all-doctors.php">All Doctors</a></li>
        <li><a href="frontend/about.php">About</a></li>
        <li><a href="frontend/contact.php">Contact</a></li>
    </ul>

    <div class="nav-actions">
        <?php if ($is_logged_in): ?>
            <div class="user-nav">
                <div class="avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span style="font-size:0.875rem;font-weight:500;"><?php echo htmlspecialchars($user_name); ?></span>
            </div>
            <a href="frontend/my-appointments.php" class="btn btn-outline">My Appointments</a>
        <?php else: ?>
            <a href="frontend/login.php" class="btn btn-outline">Log in</a>
            <a href="frontend/register.php" class="btn btn-primary">Create account</a>
        <?php endif; ?>
    </div>

    <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- ══ HERO ══ -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fas fa-shield-heart"></i>
            Zambia's Trusted Healthcare Platform
        </div>

        <h1>Book Appointment<br>With Trusted Doctors</h1>

        <div class="hero-patients">
            <div class="patient-avatars">
                <span>BM</span><span>TK</span><span>CP</span><span>LZ</span>
            </div>
            <p>Simply browse our trusted doctors and schedule your appointment hassle-free.</p>
        </div>

        <div class="hero-actions">
            <a href="frontend/all-doctors.php" class="btn-hero">
                Book appointment <i class="fas fa-arrow-right"></i>
            </a>
            <?php if (!$is_logged_in): ?>
            <a href="frontend/register.php" class="btn-hero" style="background:rgba(255,255,255,0.18);color:white;border:1px solid rgba(255,255,255,0.4);">
                Get started free
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Placeholder hero image – replace src with your actual asset -->
    <img
        class="hero-image"
        src="https://placehold.co/480x500/FFFFFF/5f6fff?text=Doctors"
        alt="Trusted Doctors"
        onerror="this.style.display='none'"
    >
</section>

<!-- ══ SPECIALITIES ══ -->
<section class="specialities-section">
    <div class="section-header fade-up">
        <h2>Find by Speciality</h2>
        <p>Simply browse through our extensive list of trusted doctors, schedule your appointment hassle-free.</p>
    </div>

    <div class="specialities-grid stagger">
        <?php
        // All specialities (from DB or merged with defaults)
        $all_specs = array_unique(array_merge(
            $specialities,
            ['General Physician','Gynecologist','Dermatologist','Pediatrician','Neurologist','Gastroenterologist']
        ));
        foreach ($all_specs as $spec):
            $icon  = $speciality_icons[$spec]  ?? '🏥';
        ?>
        <a href="frontend/all-doctors.php?speciality=<?php echo urlencode($spec); ?>" class="speciality-card fade-up">
            <div class="speciality-icon"><?php echo $icon; ?></div>
            <span class="speciality-name"><?php echo htmlspecialchars($spec); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ══ TOP DOCTORS ══ -->
<section class="doctors-section">
    <div class="section-header fade-up">
        <h2>Top Doctors to Book</h2>
        <p>Simply browse through our extensive list of trusted doctors.</p>
    </div>

    <?php if (empty($doctors)): ?>
        <p style="text-align:center;color:var(--muted);padding:2rem;">No doctors available at the moment. Please check back soon.</p>
    <?php else: ?>
    <div class="doctors-grid stagger">
        <?php foreach ($doctors as $doc):
            $img_fallback = "https://placehold.co/400x500/DBEAFE/3B82F6?text=" . urlencode($doc['name']);
            $initials     = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $doc['name']), -2)));
        ?>
        <a href="frontend/appointment.php?doctor=<?php echo urlencode($doc['doctor_id']); ?>" class="doctor-card fade-up">
            <div class="doctor-img-wrap">
                <img
                    src="<?php echo htmlspecialchars($doc['profile_image']); ?>"
                    alt="<?php echo htmlspecialchars($doc['name']); ?>"
                    loading="lazy"
                    onerror="this.src='<?php echo $img_fallback; ?>'"
                >
                <?php if ($doc['is_available']): ?>
                <div class="availability-dot">
                    <span class="dot"></span> Available
                </div>
                <?php endif; ?>
            </div>
            <div class="doctor-body">
                <div class="doctor-name"><?php echo htmlspecialchars($doc['name']); ?></div>
                <div class="doctor-spec"><?php echo htmlspecialchars($doc['speciality']); ?></div>
                <div class="doctor-meta">
                    <div class="doctor-fee">
                        K<?php echo number_format($doc['fees']); ?>
                        <span>/ visit</span>
                    </div>
                    <?php if (!empty($doc['rating']) && $doc['rating'] > 0): ?>
                    <div class="doctor-rating">
                        <i class="fas fa-star"></i>
                        <?php echo number_format($doc['rating'], 1); ?>
                        <em>(<?php echo $doc['total_reviews']; ?>)</em>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="more-btn-wrap">
        <a href="frontend/all-doctors.php" class="btn btn-outline" style="font-size:0.9rem;padding:0.65rem 2rem;">
            View all doctors <i class="fas fa-arrow-right" style="font-size:0.8rem;"></i>
        </a>
    </div>
    <?php endif; ?>
</section>

<!-- ══ CTA BANNER ══ -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Book Appointment<br>With 100+ Trusted Doctors</h2>
        <a href="frontend/register.php" class="btn-hero">Create account <i class="fas fa-arrow-right"></i></a>
    </div>
    <img
        class="cta-image"
        src="https://placehold.co/300x260/FFFFFF/5f6fff?text=Doctor"
        alt="Doctor"
        onerror="this.style.display='none'"
    >
</section>

<!-- ══ FOOTER ══ -->
<footer>
    <div class="footer-grid">
        <div>
            <a href="index.php" class="footer-logo">
                <div class="logo-icon"><i class="fas fa-hospital-user"></i></div>
                <span class="logo-text">K&amp;E Hospital</span>
            </a>
            <p>Providing quality healthcare to the people of Zambia. Book appointments with our trusted specialists hassle-free from the comfort of your home.</p>
        </div>

        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="frontend/about.php">About us</a></li>
                <li><a href="frontend/contact.php">Contact us</a></li>
                <li><a href="frontend/privacy.php">Privacy policy</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Get In Touch</h4>
            <ul>
                <li><i class="fas fa-phone" style="color:var(--blue);margin-right:6px;"></i> +260 123 456 789</li>
                <li><i class="fas fa-envelope" style="color:var(--blue);margin-right:6px;"></i> info@kehospital.co.zm</li>
                <li><i class="fas fa-location-dot" style="color:var(--blue);margin-right:6px;"></i> Great East Road, Lusaka</li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> K&amp;E Hospital – All Rights Reserved.</p>
        <p style="font-size:0.75rem;">Built with ❤️ for Zambia's Health</p>
    </div>
</footer>

<script>
    // ── Scroll animations ──
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.12 });

    document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

    // ── Mobile hamburger (placeholder) ──
    document.getElementById('hamburger').addEventListener('click', function() {
        const links = document.querySelector('.nav-links');
        if (links.style.display === 'flex') {
            links.style.display = 'none';
        } else {
            links.style.cssText = 'display:flex;flex-direction:column;position:fixed;top:68px;left:0;right:0;background:white;padding:1.5rem;gap:1.25rem;border-bottom:1px solid var(--border);z-index:300;box-shadow:0 8px 24px rgba(0,0,0,0.1);';
        }
    });

    // ── Speciality card colour on hover ──
    document.querySelectorAll('.speciality-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.querySelector('.speciality-icon').style.fontSize = '2rem';
        });
        card.addEventListener('mouseleave', () => {
            card.querySelector('.speciality-icon').style.fontSize = '1.75rem';
        });
    });
</script>
</body>
</html>