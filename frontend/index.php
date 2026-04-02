<?php
// K&E Hospital - Home Page
session_start();
require_once __DIR__ . '/config/database.php';

// Get featured doctors (top rated)
$stmt = $pdo->query("
    SELECT * FROM doctors 
    WHERE is_available = 1 
    ORDER BY rating DESC, total_reviews DESC 
    LIMIT 6
");
$featured_doctors = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM doctors WHERE is_available = 1");
$total_doctors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
$total_patients = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'Completed'");
$total_appointments = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM doctors WHERE speciality = 'General Physician'");
$general_physicians = $stmt->fetch()['total'];

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['full_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K&E Hospital - Quality Healthcare Services in Lusaka, Zambia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            color: #1f2937;
            line-height: 1.6;
        }

        /* Header/Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar__inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .navbar__logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: #3b82f6;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar__logo i {
            font-size: 2rem;
        }

        .navbar__nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .navbar__nav a {
            text-decoration: none;
            color: #4b5563;
            font-weight: 500;
            transition: color 0.3s;
        }

        .navbar__nav a:hover {
            color: #3b82f6;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
            padding: 0.6rem 1.5rem;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #3b82f6;
            color: white;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            font-weight: 500;
            color: #1f2937;
        }

        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .hero .container {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        /* Stats Section */
        .stats {
            padding: 3rem 0;
            background: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }

        .stat-card {
            padding: 1.5rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #3b82f6;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        /* Services Section */
        .services {
            padding: 4rem 0;
            background: #f9fafb;
        }

        .section-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 3rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .service-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .service-icon i {
            font-size: 2rem;
            color: white;
        }

        .service-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .service-card p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* Featured Doctors */
        .featured-doctors {
            padding: 4rem 0;
            background: white;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .doctor-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .doctor-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .doctor-info {
            padding: 1.5rem;
        }

        .doctor-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .doctor-speciality {
            color: #3b82f6;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .doctor-details {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .doctor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .doctor-fees {
            font-size: 1.1rem;
            font-weight: 700;
            color: #3b82f6;
        }

        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #f59e0b;
            font-size: 0.8rem;
        }

        .btn-book {
            background: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-book:hover {
            background: #2563eb;
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .cta h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta p {
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .cta .btn-primary {
            background: white;
            color: #667eea;
        }

        .cta .btn-primary:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background: #1f2937;
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .footer p, .footer a {
            color: #9ca3af;
            text-decoration: none;
            line-height: 1.8;
        }

        .footer a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #374151;
            color: #9ca3af;
            font-size: 0.875rem;
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #1f2937;
        }

        @media (max-width: 768px) {
            .navbar__nav {
                display: none;
            }
            .mobile-menu-toggle {
                display: block;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .hero h1 {
                font-size: 2rem;
            }
            .hero-buttons {
                flex-direction: column;
            }
            .doctors-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header class="navbar">
        <div class="container">
            <div class="navbar__inner">
                <a href="index.php" class="navbar__logo">
                    <i class="fas fa-hospital-user"></i>
                    <span>K&E Hospital</span>
                </a>
                
                <nav class="navbar__nav">
                    <a href="index.php">Home</a>
                    <a href="Alldoctors.php">Doctors</a>
                    <a href="about.php">About</a>
                    <a href="contact.php">Contact</a>
                    <?php if ($is_logged_in): ?>
                        <a href="Myappointment.php">My Appointments</a>
                        <a href="Myprofile.php">My Profile</a>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-primary">Book Appointment</a>
                    <?php endif; ?>
                </nav>
                
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Your Health, Our Priority</h1>
            <p>Providing quality healthcare services with compassion and excellence.<br>Book appointments with top doctors in Lusaka, Zambia.</p>
            <div class="hero-buttons">
                <a href="Alldoctors.php" class="btn-primary">Find a Doctor</a>
                <a href="contact.php" class="btn-secondary">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_doctors; ?>+</div>
                    <div class="stat-label">Expert Doctors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_patients; ?>+</div>
                    <div class="stat-label">Happy Patients</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_appointments; ?>+</div>
                    <div class="stat-label">Appointments Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $general_physicians; ?>+</div>
                    <div class="stat-label">General Physicians</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section class="services">
        <div class="container">
            <h2 class="section-title">Our Medical Services</h2>
            <p class="section-subtitle">We offer a wide range of medical services to meet your healthcare needs</p>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h3>General Medicine</h3>
                    <p>Comprehensive primary care for all your health concerns</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-baby-carriage"></i>
                    </div>
                    <h3>Maternity Care</h3>
                    <p>Specialized care for mothers and newborns</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h3>Cardiology</h3>
                    <p>Expert heart care and treatment</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Neurology</h3>
                    <p>Advanced neurological treatments</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-bone"></i>
                    </div>
                    <h3>Orthopedics</h3>
                    <p>Bone and joint care specialists</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <h3>Pediatrics</h3>
                    <p>Specialized care for children</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Doctors -->
    <?php if (count($featured_doctors) > 0): ?>
    <section class="featured-doctors">
        <div class="container">
            <h2 class="section-title">Our Expert Doctors</h2>
            <p class="section-subtitle">Meet our team of experienced medical professionals</p>
            <div class="doctors-grid">
                <?php foreach ($featured_doctors as $doctor): ?>
                <div class="doctor-card">
                    <img src="<?php echo htmlspecialchars($doctor['profile_image'] ?: '/frontend/assets/upload_area.png'); ?>" 
                         alt="<?php echo htmlspecialchars($doctor['name']); ?>" 
                         class="doctor-image"
                         onerror="this.src='https://placehold.co/400x500/DBEAFE/3B82F6?text=Doctor'">
                    <div class="doctor-info">
                        <h3 class="doctor-name"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                        <p class="doctor-speciality"><?php echo htmlspecialchars($doctor['speciality']); ?></p>
                        <p class="doctor-details"><?php echo htmlspecialchars($doctor['degree']); ?> • <?php echo htmlspecialchars($doctor['experience']); ?> Experience</p>
                        <div class="doctor-footer">
                            <span class="doctor-fees">K<?php echo number_format($doctor['fees'], 2); ?></span>
                            <div class="doctor-rating">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($doctor['rating'], 1); ?></span>
                                <span>(<?php echo $doctor['total_reviews']; ?> reviews)</span>
                            </div>
                        </div>
                        <a href="Alldoctors.php" class="btn-book" style="display: inline-block; margin-top: 1rem; width: 100%; text-align: center;">Book Appointment</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Need Medical Assistance?</h2>
            <p>Book an appointment with our expert doctors today</p>
            <?php if ($is_logged_in): ?>
                <a href="Alldoctors.php" class="btn-primary">Book Appointment Now</a>
            <?php else: ?>
                <a href="login.php" class="btn-primary">Login to Book Appointment</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3>K&E Hospital</h3>
                    <p>Providing quality healthcare services in Lusaka, Zambia since 2010. We are committed to excellence in patient care.</p>
                </div>
                <div>
                    <h3>Quick Links</h3>
                    <p><a href="index.php">Home</a></p>
                    <p><a href="Alldoctors.php">Doctors</a></p>
                    <p><a href="about.php">About Us</a></p>
                    <p><a href="contact.php">Contact</a></p>
                </div>
                <div>
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Great East Road, Lusaka, Zambia</p>
                    <p><i class="fas fa-phone"></i> +260 761 016446</p>
                    <p><i class="fas fa-envelope"></i> info@kehospital.com</p>
                </div>
                <div>
                    <h3>Working Hours</h3>
                    <p>Monday - Friday: 8:00 AM - 6:00 PM</p>
                    <p>Saturday: 9:00 AM - 4:00 PM</p>
                    <p>Sunday: 10:00 AM - 2:00 PM</p>
                    <p>Emergency: 24/7 Available</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 K&E Hospital. All rights reserved. | Designed with <i class="fas fa-heart"></i> for better healthcare</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const toggle = document.getElementById('mobileMenuToggle');
        const nav = document.querySelector('.navbar__nav');
        
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                if (nav.style.display === 'flex') {
                    nav.style.display = 'none';
                } else {
                    nav.style.display = 'flex';
                    nav.style.flexDirection = 'column';
                    nav.style.position = 'absolute';
                    nav.style.top = '70px';
                    nav.style.left = '0';
                    nav.style.right = '0';
                    nav.style.backgroundColor = 'white';
                    nav.style.padding = '1rem';
                    nav.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                }
            });
        }
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>