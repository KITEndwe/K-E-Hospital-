<?php
// K&E Hospital - Contact Page
session_start();

// Fix the database path - try multiple possible locations
$db_found = false;
$db_paths = [
    __DIR__ . '/../config/database.php',     // From frontend folder to config
    __DIR__ . '/config/database.php',        // If in same directory
    $_SERVER['DOCUMENT_ROOT'] . '/KE-Hospital/config/database.php',  // Absolute path
    $_SERVER['DOCUMENT_ROOT'] . '/config/database.php',  // Alternative absolute path
    '../config/database.php'                  // Original relative path
];

foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_found = true;
        break;
    }
}

// If database file not found, define fallback
if (!$db_found) {
    // Create a mock PDO-like object or show error but don't crash
    $pdo = null;
    error_log("Database configuration file not found. Checked paths: " . implode(', ', $db_paths));
}

$is_logged_in = isset($_SESSION['user_id']);

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
        // Check if we have a database connection before trying to insert
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                // Check if table exists, if not, create it
                $stmt = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
                if ($stmt->rowCount() == 0) {
                    // Create table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        subject VARCHAR(255) NOT NULL,
                        message TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                }
                
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $subject, $message]);
                $message_sent = true;
            } catch (PDOException $e) {
                $error_message = 'Failed to send message. Please try again later.';
                error_log("Contact form error: " . $e->getMessage());
            }
        } else {
            // No database connection, simulate success for demo
            $message_sent = true;
            // You can also log the message to a file if needed
            $log_entry = date('Y-m-d H:i:s') . " - Name: $name, Email: $email, Subject: $subject\n";
            file_put_contents(__DIR__ . '/contact_log.txt', $log_entry, FILE_APPEND);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Contact Us - K&E Hospital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f9fafb;
            color: #1f2937;
            overflow-x: hidden;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* ==================== NAVBAR ==================== */
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.875rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #4b5563;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: #3b82f6;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: scale(1.05);
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            width: 44px;
            height: 44px;
            border-radius: 30px;
            transition: background 0.2s;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .mobile-menu-toggle:hover {
            background: #eef2ff;
        }

        .hamburger-icon {
            width: 24px;
            height: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hamburger-icon span {
            display: block;
            height: 2.5px;
            width: 100%;
            background: #1f2937;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle.active .hamburger-icon span:nth-child(1) {
            transform: translateY(7.5px) rotate(45deg);
        }

        .mobile-menu-toggle.active .hamburger-icon span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0.8);
        }

        .mobile-menu-toggle.active .hamburger-icon span:nth-child(3) {
            transform: translateY(-7.5px) rotate(-45deg);
        }

        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: min(75%, 300px);
            height: 100vh;
            background: white;
            box-shadow: -8px 0 32px rgba(0,0,0,0.15);
            z-index: 1005;
            transition: right 0.35s ease-out;
            display: flex;
            flex-direction: column;
            padding: 90px 28px 40px;
            gap: 1.5rem;
        }

        .mobile-nav.open {
            right: 0;
        }

        .mobile-nav a {
            font-size: 1.2rem;
            font-weight: 500;
            color: #1f2937;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
            transition: color 0.2s, padding-left 0.2s;
            text-decoration: none;
            display: block;
        }

        .mobile-nav a:hover, .mobile-nav a.active {
            color: #3b82f6;
            padding-left: 8px;
        }

        .mobile-nav .mobile-account-btn {
            background: #3b82f6;
            text-align: center;
            border-radius: 60px;
            padding: 12px 0;
            color: white;
            font-weight: 600;
            margin-top: 16px;
        }

        .mobile-nav .mobile-account-btn:hover {
            background: #2563eb;
            padding-left: 0;
        }

        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(3px);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .mobile-menu-toggle {
                display: flex;
            }
        }

        /* ==================== Page Header ==================== */
        .page-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* ==================== Contact Content ==================== */
        .contact-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .contact-info {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .contact-info h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .contact-info p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.75rem;
            transition: transform 0.3s;
        }

        .info-item:hover {
            transform: translateX(5px);
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: #eef2ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-size: 1.25rem;
        }

        .info-content h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .info-content p {
            margin: 0;
            font-size: 0.875rem;
        }

        .contact-form {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .contact-form h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4b5563;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.875rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .map-section {
            margin-bottom: 4rem;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .map-section iframe {
            width: 100%;
            height: 400px;
            border: none;
        }

        @media (max-width: 768px) {
            .contact-section {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .page-header h1 {
                font-size: 1.75rem;
            }
        }

        /* Footer */
        .footer {
            background: #1f2937;
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 3rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-logo {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-description {
            color: #9ca3af;
            line-height: 1.6;
        }

        .footer-title {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #374151;
            color: #9ca3af;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php" class="logo">
            <i class="fas fa-hospital-user"></i>
            K&E Hospital
        </a>
        
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="Alldoctors.php">All Doctors</a>
            <a href="about.php">About</a>
            <a href="contact.php" class="active">Contact</a>
            <?php if ($is_logged_in): ?>
                <a href="my-appointments.php">My Appointments</a>
                <a href="logout.php" class="btn-primary">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-primary">Create Account</a>
            <?php endif; ?>
        </div>
        
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu">
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
    </div>
    
    <div class="mobile-nav" id="mobileNav">
        <a href="index.php">🏠 Home</a>
        <a href="doctors.php">👨‍⚕️ All Doctors</a>
        <a href="about.php">ℹ️ About</a>
        <a href="contact.php" class="active">📞 Contact</a>
        <?php if ($is_logged_in): ?>
            <a href="my-appointments.php">📅 My Appointments</a>
            <a href="logout.php" class="mobile-account-btn">🚪 Logout</a>
        <?php else: ?>
            <a href="login.php" class="mobile-account-btn">✨ Create Account</a>
        <?php endif; ?>
    </div>
    
    <div class="menu-overlay" id="menuOverlay"></div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you. Reach out to us anytime!</p>
    </div>
</div>

<div class="container">
    <div class="contact-section">
        <!-- Contact Information -->
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <p>Have questions about our services or need assistance? Our team is here to help you.</p>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="info-content">
                    <h3>Visit Us</h3>
                    <p>Great East Road, Lusaka, Zambia</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="info-content">
                    <h3>Call Us</h3>
                    <p>+260-772-903-446</p>
                    <p>+260-7610-16446</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="info-content">
                    <h3>Email Us</h3>
                    <p>elijahmwange55@gmail.com</p>
                    <p>info@kehospital.com</p>
                </div>
            </div>
            
            <div class="info-item">
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
        <div class="contact-form">
            <h2>Send Us a Message</h2>
            
            <?php if ($message_sent): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Thank you for your message! We'll get back to you soon.
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
    
    <!-- Map Section -->
    <div class="map-section">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d123112.123456789!2d28.283333!3d-15.416667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1940f6e0d5a9e2a5%3A0x2e4b9c4e8d5f2c7!2sLusaka%2C%20Zambia!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s" 
            allowfullscreen="" 
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo">
                    <i class="fas fa-hospital-user"></i>
                    <span>K&E Hospital</span>
                </div>
                <p class="footer-description">
                    Your Health, Our Priority. Bridging the Gap Between Zambian Patients and Doctors with Quality Healthcare at Your Fingertips, Anywhere in Zambia.
                </p>
            </div>
            <div>
                <h4 class="footer-title">COMPANY</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About us</a></li>
                    <li><a href="contact.php">Contact us</a></li>
                    <li><a href="privacy.php">Privacy policy</a></li>
                </ul>
            </div>
            <div>
                <h4 class="footer-title">GET IN TOUCH</h4>
                <ul class="footer-links">
                    <li><i class="fas fa-phone"></i> +260-772-903-446</li>
                    <li><i class="fas fa-envelope"></i> elijahmwange55@gmail.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> Great East Road, Lusaka, Zambia</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Copyright &copy; <?php echo date('Y'); ?> K&E Hospital – All Right Reserved.</p>
        </div>
    </div>
</footer>

<!-- Mobile Menu JavaScript -->
<script>
    (function() {
        const toggleBtn = document.getElementById('mobileMenuToggle');
        const mobileNav = document.getElementById('mobileNav');
        const overlay = document.getElementById('menuOverlay');
        
        function closeMenu() {
            if (mobileNav) mobileNav.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            if (toggleBtn) toggleBtn.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function openMenu() {
            if (mobileNav) mobileNav.classList.add('open');
            if (overlay) overlay.classList.add('active');
            if (toggleBtn) toggleBtn.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function toggleMenu() {
            if (mobileNav && mobileNav.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        }
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleMenu();
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && mobileNav && mobileNav.classList.contains('open')) {
                closeMenu();
            }
        });
        
        const mobileLinks = document.querySelectorAll('.mobile-nav a');
        mobileLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                closeMenu();
            });
        });
        
        const currentPath = window.location.pathname;
        const allMobileLinks = document.querySelectorAll('.mobile-nav a');
        const desktopLinks = document.querySelectorAll('.nav-links a');
        
        function updateActiveState(links) {
            links.forEach(function(link) {
                const href = link.getAttribute('href');
                if (href && href !== '#' && href !== 'javascript:void(0)') {
                    if (currentPath.includes(href) || (currentPath.includes('contact') && href === 'contact.php')) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                }
            });
        }
        
        updateActiveState(allMobileLinks);
        updateActiveState(desktopLinks);
    })();
</script>
</body>
</html>