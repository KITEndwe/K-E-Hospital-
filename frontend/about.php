<?php
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$is_logged_in  = isset($_SESSION['user_id']);
$user_name     = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page  = 'about.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>About Us - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="./Css/about.css">
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="page-wrap">

    <div class="about-hero fade-up">
        <h1>About K&amp;E Hospital</h1>
        <div class="hero-line"></div>
        <p>Your Health, Our Priority — Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips.</p>
    </div>

    <div class="about-grid">
        <div class="about-image fade-up">
            <img src="assets/about_image.png" alt="K&amp;E Hospital Doctors"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="img-placeholder" style="display:none;"><i class="fas fa-hospital-user"></i></div>
        </div>
        <div class="about-text fade-up">
            <h2>Welcome to K&amp;E Hospital</h2>
            <div class="subhead">Your Trusted Healthcare Partner</div>
            <p>At K&amp;E Hospital, we understand the challenges individuals face when it comes to scheduling doctor appointments and managing their health records. We are committed to providing accessible, quality healthcare services to all Zambians.</p>
            <p>K&amp;E Hospital is committed to excellence in healthcare technology. We continuously strive to enhance our platform, integrating the latest advancements to improve user experience and deliver superior service.</p>
            <div class="about-stats">
                <div class="stat-item"><span class="stat-number">10+</span><span class="stat-label">Trusted Doctors</span></div>
                <div class="stat-item"><span class="stat-number">100+</span><span class="stat-label">Happy Patients</span></div>
                <div class="stat-item"><span class="stat-number">24/7</span><span class="stat-label">Support Available</span></div>
            </div>
        </div>
    </div>

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

    <div class="values-section">
        <div class="section-header fade-up">
            <h2>Our Core Values</h2>
            <p>The principles that guide everything we do</p>
        </div>
        <div class="values-grid stagger">
            <div class="value-card fade-up"><div class="value-icon"><i class="fas fa-heartbeat"></i></div><h4>Compassion</h4><p>We care for every patient with empathy and respect</p></div>
            <div class="value-card fade-up"><div class="value-icon"><i class="fas fa-shield-alt"></i></div><h4>Integrity</h4><p>We uphold the highest standards of honesty and ethics</p></div>
            <div class="value-card fade-up"><div class="value-icon"><i class="fas fa-chart-line"></i></div><h4>Excellence</h4><p>We strive for excellence in everything we do</p></div>
            <div class="value-card fade-up"><div class="value-icon"><i class="fas fa-users"></i></div><h4>Innovation</h4><p>We embrace technology to improve healthcare delivery</p></div>
        </div>
    </div>

    <!-- ── NEW: PARTNERSHIPS SECTION ── -->
    <div class="partnerships-section">
        <div class="section-header fade-up">
            <h2>Our Trusted Partners</h2>
            <p>Collaborating with leading healthcare institutions worldwide</p>
        </div>
        <div class="partnerships-intro fade-up">
            <p>We are proud to partner with these reputable organizations to deliver exceptional healthcare services to our patients.</p>
        </div>
        
        <div class="partners-grid stagger">
            <!-- Partner 1: UAP (Local - Zambia) -->
            <div class="partner-card fade-up">
                <span class="partner-badge badge-local"><i class="fas fa-map-marker-alt"></i> Zambia</span>
                <div class="partner-logo">
                    <i class="fas fa-handshake"></i>
                </div>
                <h4>UAP Insurance Zambia</h4>
                <div class="partner-type">Better. Simple. Life.</div>
                <p>Strategic health insurance partner providing seamless medical coverage for our patients.</p>
            </div>

            <!-- Partner 2: TAKAFUL Insurance (Local - Zambia) -->
            <div class="partner-card fade-up">
                <span class="partner-badge badge-local"><i class="fas fa-map-marker-alt"></i> Zambia</span>
                <div class="partner-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4>TAKAFUL Insurance of Africa</h4>
                <div class="partner-type">A bond beyond insurance</div>
                <p>Islamic insurance partner offering comprehensive healthcare coverage solutions.</p>
            </div>

            <!-- Partner 3: Sanlam (International - South Africa) -->
            <div class="partner-card fade-up">
                <span class="partner-badge badge-international"><i class="fas fa-globe"></i> International</span>
                <div class="partner-logo">
                    <i class="fas fa-building"></i>
                </div>
                <h4>Sanlam Insurance Company</h4>
                <div class="partner-type">South Africa</div>
                <p>Pan-African financial services group providing medical aid solutions across the continent.</p>
            </div>

            <!-- Partner 4: World Health Organization (International) -->
            <div class="partner-card fade-up">
                <span class="partner-badge badge-international"><i class="fas fa-globe"></i> International</span>
                <div class="partner-logo">
                    <i class="fas fa-globe-africa"></i>
                </div>
                <h4>World Health Organization</h4>
                <div class="partner-type">Global Health Partner</div>
                <p>Collaborating on public health initiatives and healthcare standards improvement.</p>
            </div>

            <!-- Partner 5: Levy Mwanawasa Hospital (Local - Zambia) -->
            <div class="partner-card fade-up">
                <span class="partner-badge badge-local"><i class="fas fa-map-marker-alt"></i> Zambia</span>
                <div class="partner-logo">
                    <i class="fas fa-hospital"></i>
                </div>
                <h4>Levy Mwanawasa Hospital</h4>
                <div class="partner-type">Referral Partner</div>
                <p>Strategic referral partnership for specialized medical procedures and consultations.</p>
            </div>

            <!-- Partner 6: University Teaching Hospital - UTH (Local - Zambia) -->
            <div class="partner-card fade-up">
                <span class="partner-badge badge-local"><i class="fas fa-map-marker-alt"></i> Zambia</span>
                <div class="partner-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h4>University Teaching Hospital</h4>
                <div class="partner-type">Academic & Research Partner</div>
                <p>Medical research collaboration and specialist training partnerships.</p>
            </div>
        </div>

        <!-- Partnership CTA -->
        <div class="partnership-cta fade-up">
            <h3><i class="fas fa-handshake"></i> Interested in Partnering With Us?</h3>
            <p>Join our network of trusted healthcare partners and make a difference in Zambian healthcare.</p>
            <a href="contact.php" class="partnership-btn">Become a Partner →</a>
        </div>
    </div>

    <div class="team-section">
        <div class="section-header fade-up">
            <h2>Our Leadership</h2>
            <p>Dedicated professionals committed to your health</p>
        </div>
        <div class="team-grid stagger">
            <div class="team-card fade-up">
                <div class="team-image">
                    <img src="./assets/Elijah.jpg" alt="Dr. Elijah Mwange"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="team-fallback"><i class="fas fa-user-doctor"></i></div>
                </div>
                <div class="team-info"><h4>Dr. Elijah Mwange</h4><p>Chief Medical Officer</p></div>
            </div>
            <div class="team-card fade-up">
                <div class="team-image">
                    <img src="./assets/Kalenga.jpeg" alt="Dr. Kelenga Muma"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="team-fallback"><i class="fas fa-user-doctor"></i></div>
                </div>
                <div class="team-info"><h4>Dr. Kelenga Muma</h4><p>Hospital Administrator</p></div>
            </div>
            <div class="team-card fade-up">
                <div class="team-image">
                    <img src="./assets/juannita.jpeg" alt="Dr. Joannita Kabemba"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="team-fallback"><i class="fas fa-user-doctor"></i></div>
                </div>
                <div class="team-info"><h4>Dr. Joannita Kabemba</h4><p>Head of Nursing</p></div>
            </div>
        </div>
    </div>

    <div class="cta-section fade-up">
        <h2>Ready to Book Your Appointment?</h2>
        <p>Join thousands of satisfied patients who trust K&amp;E Hospital for their healthcare needs.</p>
        <a href="Alldoctors.php" class="cta-btn">Find a Doctor <i class="fas fa-arrow-right"></i></a>
    </div>

</div>

<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <div><img src="assets/logo.svg" width="100px" alt="K&amp;E Hospital"></div>
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

<script>
(function() {
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