<?php
// K&E Hospital - Contact Page
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$is_logged_in = isset($_SESSION['user_id']);
$user_name    = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle contact form submission
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Simple validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=ke_hospital;charset=utf8", 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $subject, $message]);
            $message_sent = true;
        } catch (PDOException $e) {
            $error_message = 'Failed to send message. Please try again later.';
        }
    }
}

// For logged in users, get profile image from database if needed
$profile_image = '';
if ($is_logged_in) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=ke_hospital;charset=utf8", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data && !empty($user_data['profile_image'])) {
            $profile_image = $user_data['profile_image'];
        }
    } catch (PDOException $e) {
        // Silently fail - profile image not critical
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us - K&amp;E Hospital</title>
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

/* Page Header */
.page-header {
    text-align:center;
    margin-bottom:3rem;
}
.page-header h1 {
    font-size:clamp(1.75rem,3.5vw,2.5rem);
    font-weight:700;
    color:#1a1a2e;
    margin-bottom:1rem;
}
.page-header .header-line {
    width:80px;
    height:4px;
    background:#5f6fff;
    margin:1rem auto;
    border-radius:4px;
}
.page-header p {
    font-size:1rem;
    color:#696969;
    max-width:600px;
    margin:0 auto;
}

/* Contact Grid */
.contact-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:3rem;
    margin-bottom:4rem;
}

/* Contact Info Cards */
.contact-info {
    display:flex;
    flex-direction:column;
    gap:1.5rem;
}
.info-card {
    background:#fff;
    border:1px solid #e5e7f0;
    border-radius:16px;
    padding:1.5rem;
    display:flex;
    align-items:center;
    gap:1.25rem;
    transition:all 0.3s;
}
.info-card:hover {
    transform:translateX(5px);
    box-shadow:0 8px 24px rgba(95,111,255,0.1);
    border-color:#c5caff;
}
.info-icon {
    width:60px;
    height:60px;
    background:#eef0ff;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}
.info-icon i {
    font-size:1.5rem;
    color:#5f6fff;
}
.info-content h3 {
    font-size:1.1rem;
    font-weight:600;
    color:#1a1a2e;
    margin-bottom:0.5rem;
}
.info-content p {
    font-size:0.9rem;
    color:#696969;
    line-height:1.5;
}

/* Contact Form */
.contact-form-container {
    background:#fff;
    border:1px solid #e5e7f0;
    border-radius:20px;
    padding:2rem;
}
.contact-form-container h2 {
    font-size:1.5rem;
    font-weight:600;
    color:#1a1a2e;
    margin-bottom:0.5rem;
}
.form-subtitle {
    color:#696969;
    font-size:0.85rem;
    margin-bottom:1.5rem;
}
.form-group {
    margin-bottom:1.25rem;
}
.form-group label {
    display:block;
    font-size:0.85rem;
    font-weight:500;
    color:#4b5563;
    margin-bottom:0.5rem;
}
.form-group label i {
    color:#5f6fff;
    margin-right:0.5rem;
}
.form-group input,
.form-group textarea {
    width:100%;
    padding:0.85rem;
    border:1.5px solid #e5e7f0;
    border-radius:12px;
    font-family:'Outfit', sans-serif;
    font-size:0.9rem;
    transition:all 0.3s;
}
.form-group input:focus,
.form-group textarea:focus {
    outline:none;
    border-color:#5f6fff;
    box-shadow:0 0 0 3px rgba(95,111,255,0.1);
}
.form-group textarea {
    resize:vertical;
    min-height:120px;
}
.alert {
    padding:1rem;
    border-radius:12px;
    margin-bottom:1.5rem;
    display:flex;
    align-items:center;
    gap:0.75rem;
}
.alert-success {
    background:#d1fae5;
    color:#065f46;
    border:1px solid #a7f3d0;
}
.alert-error {
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}
.btn-submit {
    width:100%;
    padding:0.9rem;
    background:#5f6fff;
    color:#fff;
    border:none;
    border-radius:12px;
    font-size:1rem;
    font-weight:600;
    cursor:pointer;
    transition:all 0.3s;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:0.5rem;
}
.btn-submit:hover {
    background:#4a5af0;
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(95,111,255,0.3);
}

/* Map Section */
.map-section {
    margin-bottom:4rem;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
}
.map-section iframe {
    width:100%;
    height:400px;
    border:none;
}

/* FAQ Section */
.faq-section {
    margin-bottom:4rem;
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
.faq-grid {
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:1.5rem;
}
.faq-item {
    background:#fff;
    border:1px solid #e5e7f0;
    border-radius:12px;
    padding:1.5rem;
    transition:all 0.3s;
}
.faq-item:hover {
    transform:translateY(-3px);
    box-shadow:0 8px 20px rgba(95,111,255,0.08);
    border-color:#c5caff;
}
.faq-question {
    display:flex;
    align-items:center;
    gap:0.75rem;
    margin-bottom:0.75rem;
}
.faq-question i {
    color:#5f6fff;
    font-size:1.1rem;
}
.faq-question h4 {
    font-size:1rem;
    font-weight:600;
    color:#1a1a2e;
}
.faq-answer {
    font-size:0.85rem;
    color:#696969;
    line-height:1.6;
    padding-left:1.85rem;
}

/* Footer */
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

/* Fade Animations */
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

/* Responsive */
@media (max-width:1024px) {
    .faq-grid { grid-template-columns:1fr; }
}

@media (max-width:768px) {
    .contact-grid { grid-template-columns:1fr; gap:2rem; }
    .page-wrap { padding:2rem 5% 3rem; }
    .footer-grid { grid-template-columns:1fr; gap:2rem; }
    .map-section iframe { height:300px; }
}

@media (max-width:480px) {
    .info-card { padding:1.25rem; }
    .info-icon { width:50px; height:50px; }
    .info-icon i { font-size:1.25rem; }
    .contact-form-container { padding:1.5rem; }
}
</style>
</head>
<body>

<!-- ═════════════ SHARED NAVBAR ═════════════ -->
<?php require_once 'navbar.php'; ?>

<!-- ═════════════ PAGE CONTENT ═════════════ -->
<div class="page-wrap">

    <!-- Page Header -->
    <div class="page-header fade-up">
        <h1>Contact Us</h1>
        <div class="header-line"></div>
        <p>Have questions about our services or need assistance? Our team is here to help you.</p>
    </div>

    <!-- Contact Grid -->
    <div class="contact-grid">
        <!-- Contact Information -->
        <div class="contact-info stagger">
            <div class="info-card fade-up">
                <div class="info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="info-content">
                    <h3>Visit Us</h3>
                    <p>Great East Road, Lusaka, Zambia</p>
                </div>
            </div>

            <div class="info-card fade-up">
                <div class="info-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div class="info-content">
                    <h3>Call Us</h3>
                    <p>+260-772-903-446</p>
                    <p>+260-7610-16446</p>
                </div>
            </div>

            <div class="info-card fade-up">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="info-content">
                    <h3>Email Us</h3>
                    <p>elijahmwange55@gmail.com</p>
                    <p>info@kehospital.co.zm</p>
                </div>
            </div>

            <div class="info-card fade-up">
                <div class="info-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="info-content">
                    <h3>Working Hours</h3>
                    <p>Monday - Friday: 8:00 AM - 8:00 PM</p>
                    <p>Saturday - Sunday: 9:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-container fade-up">
            <h2>Send Us a Message</h2>
            <p class="form-subtitle">We'll get back to you within 24 hours</p>

            <?php if ($message_sent): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Thank you for your message! We'll get back to you soon.
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Your Name *</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <label for="subject"><i class="fas fa-tag"></i> Subject *</label>
                    <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" placeholder="What is this regarding?">
                </div>

                <div class="form-group">
                    <label for="message"><i class="fas fa-comment"></i> Message *</label>
                    <textarea id="message" name="message" required placeholder="Please describe your inquiry in detail..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <!-- Map Section -->
    <div class="map-section fade-up">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d123112.123456789!2d28.283333!3d-15.416667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1940f6e0d5a9e2a5%3A0x2e4b9c4e8d5f2c7!2sLusaka%2C%20Zambia!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s" 
            allowfullscreen="" 
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <div class="section-header fade-up">
            <h2>Frequently Asked Questions</h2>
            <p>Quick answers to common questions</p>
        </div>
        <div class="faq-grid stagger">
            <div class="faq-item fade-up">
                <div class="faq-question">
                    <i class="fas fa-question-circle"></i>
                    <h4>How do I book an appointment?</h4>
                </div>
                <div class="faq-answer">
                    You can book an appointment by browsing our doctors list, selecting your preferred doctor, and choosing an available time slot.
                </div>
            </div>
            <div class="faq-item fade-up">
                <div class="faq-question">
                    <i class="fas fa-question-circle"></i>
                    <h4>What are your working hours?</h4>
                </div>
                <div class="faq-answer">
                    We are open Monday-Friday 8 AM to 8 PM, and Saturday-Sunday 9 AM to 5 PM. Emergency services are available 24/7.
                </div>
            </div>
            <div class="faq-item fade-up">
                <div class="faq-question">
                    <i class="fas fa-question-circle"></i>
                    <h4>How do I cancel my appointment?</h4>
                </div>
                <div class="faq-answer">
                    You can cancel your appointment by logging into your account and navigating to "My Appointments" section.
                </div>
            </div>
            <div class="faq-item fade-up">
                <div class="faq-question">
                    <i class="fas fa-question-circle"></i>
                    <h4>Do you accept insurance?</h4>
                </div>
                <div class="faq-answer">
                    Yes, we accept most major insurance providers. Please contact our billing department for specific inquiries.
                </div>
            </div>
        </div>
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

<!-- ═════════════ SCRIPTS (only for page-specific animations) ═════════════ -->
<script>
(function() {
    /* Scroll fade-in for page content */
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