<?php
// K&E Hospital - Privacy Policy Page
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$is_logged_in = isset($_SESSION['user_id']);
$user_name    = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page = 'privacy.php';

// Get profile image if logged in
$profile_image = '';
if ($is_logged_in) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data && !empty($user_data['profile_image'])) {
            $profile_image = $user_data['profile_image'];
        }
    } catch (PDOException $e) {
        // Silently fail
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
<title>Privacy Policy - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ═══════════════════ RESET ═══════════════════ */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Outfit', sans-serif;
    background: #f5f7fb;
    color: #1e293b;
    line-height: 1.6;
    overflow-x: hidden;
}

/* Page Container */
.privacy-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

/* Hero Section */
.privacy-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    text-align: center;
    color: white;
}

.privacy-hero h1 {
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 700;
    margin-bottom: 1rem;
}

.privacy-hero p {
    font-size: 1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.privacy-hero .last-updated {
    margin-top: 1rem;
    font-size: 0.85rem;
    opacity: 0.75;
}

/* Main Content Card */
.privacy-card {
    background: white;
    border-radius: 24px;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

/* Table of Contents */
.toc {
    background: #f8fafc;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid #e2e8f0;
}

.toc h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.toc h3 i {
    color: #5f6fff;
}

.toc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 0.75rem;
}

.toc a {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.85rem;
    transition: color 0.2s;
    display: inline-block;
}

.toc a:hover {
    color: #2563eb;
    text-decoration: underline;
}

/* Section Styles */
.privacy-section {
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.privacy-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.privacy-section h2 {
    font-size: 1.4rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.privacy-section h2 i {
    color: #5f6fff;
    font-size: 1.3rem;
}

.privacy-section h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #334155;
    margin: 1.25rem 0 0.75rem 0;
}

.privacy-section p {
    color: #475569;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.privacy-section ul, .privacy-section ol {
    margin: 0.75rem 0 1rem 1.5rem;
    color: #475569;
}

.privacy-section li {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

/* Highlight Box */
.highlight-box {
    background: #eef2ff;
    border-left: 4px solid #5f6fff;
    padding: 1.25rem;
    border-radius: 12px;
    margin: 1.5rem 0;
}

.highlight-box p {
    margin-bottom: 0;
    color: #1e293b;
}

.highlight-box i {
    color: #5f6fff;
    margin-right: 0.5rem;
}

/* Contact Info */
.contact-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.contact-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    border: 1px solid #e2e8f0;
}

.contact-card i {
    font-size: 1.5rem;
    color: #5f6fff;
    margin-bottom: 0.5rem;
}

.contact-card h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.contact-card p {
    font-size: 0.8rem;
    margin-bottom: 0;
}

/* Footer */
footer {
    background: #fff;
    border-top: 1px solid #ebebeb;
    padding: 3rem 6% 2rem;
    margin-top: 2rem;
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 3rem;
    margin-bottom: 2.5rem;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.875rem;
}

.footer-logo img {
    width: 100px;
    height: auto;
}

.footer-desc {
    font-size: 0.85rem;
    color: #777;
    line-height: 1.7;
    max-width: 300px;
}

.footer-col h4 {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1a1a2e;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 1.1rem;
}

.footer-col ul {
    list-style: none;
}

.footer-col ul li {
    margin-bottom: 0.6rem;
}

.footer-col ul li a {
    font-size: 0.875rem;
    color: #696969;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-col ul li a:hover {
    color: #5f6fff;
}

.footer-col ul li {
    font-size: 0.875rem;
    color: #696969;
}

.footer-bottom {
    border-top: 1px solid #ebebeb;
    padding-top: 1.25rem;
    text-align: center;
    font-size: 0.8rem;
    color: #aaa;
}

/* Fade Animation */
.fade-up {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.fade-up.visible {
    opacity: 1;
    transform: translateY(0);
}

/* ========== RESPONSIVE STYLES ========== */

/* Tablet (768px - 1024px) */
@media (max-width: 1024px) {
    .privacy-container {
        margin: 1.5rem auto;
    }
    
    .privacy-card {
        padding: 2rem;
    }
}

/* Mobile (up to 768px) */
@media (max-width: 768px) {
    .privacy-container {
        padding: 0 1rem;
        margin: 1rem auto;
    }
    
    .privacy-card {
        padding: 1.5rem;
    }
    
    .privacy-hero {
        padding: 2rem 1.5rem;
        border-radius: 20px;
    }
    
    .privacy-hero h1 {
        font-size: 1.5rem;
    }
    
    .privacy-hero p {
        font-size: 0.9rem;
    }
    
    .privacy-section h2 {
        font-size: 1.2rem;
    }
    
    .privacy-section h3 {
        font-size: 1rem;
    }
    
    .privacy-section p,
    .privacy-section li {
        font-size: 0.9rem;
    }
    
    .toc {
        padding: 1rem;
    }
    
    .toc h3 {
        font-size: 1rem;
    }
    
    .toc-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .toc a {
        font-size: 0.8rem;
    }
    
    .contact-info {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .contact-card {
        padding: 0.875rem;
    }
    
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .footer-logo {
        justify-content: center;
    }
    
    .footer-desc {
        text-align: center;
        margin: 0 auto;
    }
    
    .footer-col {
        text-align: center;
    }
    
    .highlight-box {
        padding: 1rem;
    }
    
    .highlight-box p {
        font-size: 0.85rem;
    }
}

/* Small Mobile (480px and below) */
@media (max-width: 480px) {
    .privacy-container {
        padding: 0 0.75rem;
        margin: 0.75rem auto;
    }
    
    .privacy-card {
        padding: 1.25rem;
        border-radius: 20px;
    }
    
    .privacy-hero {
        padding: 1.5rem 1rem;
        border-radius: 16px;
    }
    
    .privacy-hero h1 {
        font-size: 1.3rem;
    }
    
    .privacy-hero p {
        font-size: 0.85rem;
    }
    
    .privacy-hero .last-updated {
        font-size: 0.75rem;
    }
    
    .privacy-section {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
    }
    
    .privacy-section h2 {
        font-size: 1.1rem;
    }
    
    .privacy-section h2 i {
        font-size: 1rem;
    }
    
    .privacy-section p,
    .privacy-section li {
        font-size: 0.85rem;
    }
    
    .privacy-section ul,
    .privacy-section ol {
        margin-left: 1rem;
    }
    
    .toc {
        padding: 0.875rem;
        border-radius: 12px;
    }
    
    .toc h3 {
        font-size: 0.9rem;
    }
    
    .toc a {
        font-size: 0.75rem;
    }
    
    .highlight-box {
        padding: 0.875rem;
    }
    
    .highlight-box p {
        font-size: 0.8rem;
    }
    
    .contact-card {
        padding: 0.75rem;
    }
    
    .contact-card i {
        font-size: 1.25rem;
    }
    
    .contact-card h4 {
        font-size: 0.85rem;
    }
    
    .contact-card p {
        font-size: 0.75rem;
    }
    
    footer {
        padding: 2rem 5% 1.5rem;
    }
    
    .footer-logo img {
        width: 80px;
    }
    
    .footer-desc {
        font-size: 0.8rem;
    }
    
    .footer-col h4 {
        font-size: 0.75rem;
    }
    
    .footer-col ul li a,
    .footer-col ul li {
        font-size: 0.8rem;
    }
    
    .footer-bottom {
        font-size: 0.7rem;
    }
}

/* Extra Small Mobile (375px and below) */
@media (max-width: 375px) {
    .privacy-card {
        padding: 1rem;
    }
    
    .privacy-section h2 {
        font-size: 1rem;
    }
    
    .privacy-section p,
    .privacy-section li {
        font-size: 0.8rem;
    }
    
    .toc a {
        font-size: 0.7rem;
    }
}
</style>
</head>
<body>

<!-- Navbar -->
<?php 
$is_logged_in = isset($_SESSION['user_id']);
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page = 'privacy.php';
$profile_image = isset($profile_image) ? $profile_image : '';
require_once 'navbar.php'; 
?>

<div class="privacy-container">
    
    <!-- Hero Section -->
    <div class="privacy-hero fade-up">
        <h1><i class="fas fa-shield-alt"></i> Privacy Policy</h1>
        <p>Your privacy is important to us. This policy explains how we collect, use, and protect your personal information in accordance with Zambian laws.</p>
        <div class="last-updated">
            <i class="fas fa-calendar-alt"></i> Last Updated: April 6, 2025
        </div>
    </div>

    <!-- Main Content -->
    <div class="privacy-card fade-up">
        
        <!-- Table of Contents -->
        <div class="toc">
            <h3><i class="fas fa-list"></i> Table of Contents</h3>
            <div class="toc-grid">
                <a href="#introduction">1. Introduction</a>
                <a href="#information-collect">2. Information We Collect</a>
                <a href="#how-we-use">3. How We Use Your Information</a>
                <a href="#legal-basis">4. Legal Basis for Processing (Zambia)</a>
                <a href="#data-sharing">5. Data Sharing and Disclosure</a>
                <a href="#data-security">6. Data Security</a>
                <a href="#data-retention">7. Data Retention</a>
                <a href="#your-rights">8. Your Rights Under Zambian Law</a>
                <a href="#cookies">9. Cookies and Tracking</a>
                <a href="#children">10. Children's Privacy</a>
                <a href="#third-party">11. Third-Party Links</a>
                <a href="#international">12. International Data Transfers</a>
                <a href="#policy-changes">13. Changes to This Policy</a>
                <a href="#contact-us">14. Contact Us</a>
            </div>
        </div>

        <!-- Section 1: Introduction -->
        <div id="introduction" class="privacy-section">
            <h2><i class="fas fa-info-circle"></i> 1. Introduction</h2>
            <p>Welcome to K&amp;E Hospital ("we," "our," "us"). We are committed to protecting your personal information and your right to privacy in accordance with the <strong>Data Protection Act No. 3 of 2021 of Zambia</strong> and other applicable laws. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website, mobile application, or services.</p>
            <p>By using our services, you agree to the collection and use of information in accordance with this policy. If you do not agree with any part of this policy, please do not use our services.</p>
        </div>

        <!-- Section 2: Information We Collect -->
        <div id="information-collect" class="privacy-section">
            <h2><i class="fas fa-database"></i> 2. Information We Collect</h2>
            <p>We collect several types of information from and about users of our services:</p>
            
            <h3>Personal Information You Provide</h3>
            <ul>
                <li><strong>Registration Information:</strong> Full name, email address, phone number, date of birth, gender, and residential address.</li>
                <li><strong>Medical Information:</strong> Health history, medical conditions, allergies, medications, and other health-related data necessary for providing healthcare services.</li>
                <li><strong>Payment Information:</strong> Billing details, payment methods, and transaction history.</li>
                <li><strong>Communication Data:</strong> Messages, inquiries, feedback, and support requests.</li>
            </ul>
            
            <h3>Information Automatically Collected</h3>
            <ul>
                <li><strong>Usage Data:</strong> IP address, browser type, device information, pages visited, time and date of access.</li>
                <li><strong>Location Data:</strong> Approximate location based on IP address (with your consent).</li>
                <li><strong>Cookies:</strong> Small data files stored on your device to enhance user experience.</li>
            </ul>
        </div>

        <!-- Section 3: How We Use Your Information -->
        <div id="how-we-use" class="privacy-section">
            <h2><i class="fas fa-chart-line"></i> 3. How We Use Your Information</h2>
            <p>We use your information for the following purposes:</p>
            <ul>
                <li><strong>Healthcare Services:</strong> To provide medical consultations, appointment scheduling, diagnosis, treatment, and follow-up care.</li>
                <li><strong>Account Management:</strong> To create and manage your user account, process registrations, and verify identity.</li>
                <li><strong>Communication:</strong> To send appointment reminders, health tips, service updates, and respond to inquiries.</li>
                <li><strong>Payment Processing:</strong> To process payments, issue receipts, and manage billing.</li>
                <li><strong>Service Improvement:</strong> To analyze usage patterns, improve our website and services, and develop new features.</li>
                <li><strong>Legal Compliance:</strong> To comply with Zambian healthcare regulations and legal obligations.</li>
                <li><strong>Security:</strong> To detect, prevent, and address technical issues or fraudulent activities.</li>
            </ul>
            
            <div class="highlight-box">
                <p><i class="fas fa-heartbeat"></i> <strong>Healthcare Information:</strong> Your medical information is treated with the highest confidentiality and is only accessible to authorized healthcare professionals involved in your care.</p>
            </div>
        </div>

        <!-- Section 4: Legal Basis for Processing (Zambia) -->
        <div id="legal-basis" class="privacy-section">
            <h2><i class="fas fa-gavel"></i> 4. Legal Basis for Processing (Zambia)</h2>
            <p>Under the Zambian Data Protection Act No. 3 of 2021, we process your personal data based on one or more of the following legal grounds:</p>
            <ul>
                <li><strong>Consent:</strong> You have given clear consent for us to process your personal data for specific purposes.</li>
                <li><strong>Contract:</strong> Processing is necessary for the performance of a contract with you (e.g., providing healthcare services).</li>
                <li><strong>Legal Obligation:</strong> Processing is necessary for compliance with a legal obligation under Zambian law.</li>
                <li><strong>Vital Interests:</strong> Processing is necessary to protect someone's life (e.g., in a medical emergency).</li>
                <li><strong>Legitimate Interests:</strong> Processing is necessary for our legitimate interests, provided they do not override your fundamental rights.</li>
            </ul>
        </div>

        <!-- Section 5: Data Sharing and Disclosure -->
        <div id="data-sharing" class="privacy-section">
            <h2><i class="fas fa-share-alt"></i> 5. Data Sharing and Disclosure</h2>
            <p>We do not sell your personal information. We may share your information in the following circumstances:</p>
            <ul>
                <li><strong>Healthcare Providers:</strong> With doctors, nurses, and other healthcare professionals involved in your care.</li>
                <li><strong>Service Providers:</strong> With third-party vendors who assist with payment processing, data hosting, email delivery, and analytics.</li>
                <li><strong>Legal Requirements:</strong> When required by Zambian law, court order, or government regulation.</li>
                <li><strong>Emergency Situations:</strong> To protect the vital interests of a patient or public health.</li>
                <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets (with notice to affected users).</li>
            </ul>
        </div>

        <!-- Section 6: Data Security -->
        <div id="data-security" class="privacy-section">
            <h2><i class="fas fa-lock"></i> 6. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your personal information, including:</p>
            <ul>
                <li><strong>Encryption:</strong> SSL/TLS encryption for data transmission and encrypted storage for sensitive information.</li>
                <li><strong>Access Controls:</strong> Role-based access restrictions and authentication protocols.</li>
                <li><strong>Regular Audits:</strong> Periodic security assessments and vulnerability testing.</li>
                <li><strong>Employee Training:</strong> Staff training on data protection and confidentiality.</li>
                <li><strong>Incident Response:</strong> Procedures for detecting and responding to security breaches.</li>
            </ul>
            <p>However, no method of transmission over the Internet or electronic storage is 100% secure. While we strive to protect your data, we cannot guarantee absolute security.</p>
        </div>

        <!-- Section 7: Data Retention -->
        <div id="data-retention" class="privacy-section">
            <h2><i class="fas fa-clock"></i> 7. Data Retention</h2>
            <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy, unless a longer retention period is required by Zambian law. Medical records are retained in accordance with Zambian healthcare regulations (minimum of 7 years after last treatment).</p>
            <p>After the retention period, we will securely delete or anonymize your information.</p>
        </div>

        <!-- Section 8: Your Rights Under Zambian Law -->
        <div id="your-rights" class="privacy-section">
            <h2><i class="fas fa-user-check"></i> 8. Your Rights Under Zambian Law</h2>
            <p>Under the Zambian Data Protection Act No. 3 of 2021, you have the following rights:</p>
            <ul>
                <li><strong>Right to Access:</strong> Request a copy of your personal data we hold.</li>
                <li><strong>Right to Rectification:</strong> Correct inaccurate or incomplete information.</li>
                <li><strong>Right to Erasure:</strong> Request deletion of your data (subject to legal retention requirements).</li>
                <li><strong>Right to Restrict Processing:</strong> Limit how we use your data.</li>
                <li><strong>Right to Data Portability:</strong> Receive your data in a structured, machine-readable format.</li>
                <li><strong>Right to Object:</strong> Object to processing based on legitimate interests.</li>
                <li><strong>Right to Withdraw Consent:</strong> Withdraw consent at any time (where consent is the legal basis).</li>
                <li><strong>Right to Lodge a Complaint:</strong> File a complaint with the Zambian Data Protection Authority.</li>
            </ul>
            <p>To exercise these rights, please contact us using the information in Section 14.</p>
        </div>

        <!-- Section 9: Cookies and Tracking -->
        <div id="cookies" class="privacy-section">
            <h2><i class="fas fa-cookie-bite"></i> 9. Cookies and Tracking</h2>
            <p>We use cookies and similar tracking technologies to enhance your browsing experience. Types of cookies we use include:</p>
            <ul>
                <li><strong>Essential Cookies:</strong> Required for basic website functionality (login, appointments).</li>
                <li><strong>Preference Cookies:</strong> Remember your settings and preferences.</li>
                <li><strong>Analytics Cookies:</strong> Help us understand how visitors use our website.</li>
                <li><strong>Session Cookies:</strong> Temporary cookies that expire when you close your browser.</li>
            </ul>
            <p>You can manage cookie preferences through your browser settings. However, disabling cookies may affect website functionality.</p>
        </div>

        <!-- Section 10: Children's Privacy -->
        <div id="children" class="privacy-section">
            <h2><i class="fas fa-child"></i> 10. Children's Privacy</h2>
            <p>Our services are intended for individuals who can provide legal consent under Zambian law (18 years and above). For minors, a parent or legal guardian must provide consent and manage the account. We do not knowingly collect personal information from children under 13 without parental consent. If you believe we have collected such information, please contact us immediately.</p>
        </div>

        <!-- Section 11: Third-Party Links -->
        <div id="third-party" class="privacy-section">
            <h2><i class="fas fa-external-link-alt"></i> 11. Third-Party Links</h2>
            <p>Our website may contain links to third-party websites (e.g., payment gateways, insurance providers). We are not responsible for the privacy practices of these external sites. We encourage you to review their privacy policies before providing any personal information.</p>
        </div>

        <!-- Section 12: International Data Transfers -->
        <div id="international" class="privacy-section">
            <h2><i class="fas fa-globe"></i> 12. International Data Transfers</h2>
            <p>Your information is primarily stored on servers located in Zambia. If we transfer data outside Zambia, we ensure adequate safeguards are in place, such as standard contractual clauses approved by the Zambian Data Protection Authority.</p>
        </div>

        <!-- Section 13: Changes to This Policy -->
        <div id="policy-changes" class="privacy-section">
            <h2><i class="fas fa-edit"></i> 13. Changes to This Policy</h2>
            <p>We may update this Privacy Policy periodically to reflect changes in our practices, legal requirements, or operational needs. We will notify you of material changes by:</p>
            <ul>
                <li>Posting the updated policy on our website with a new "Last Updated" date.</li>
                <li>Sending an email notification to registered users (for significant changes).</li>
                <li>Displaying a prominent notice on our website.</li>
            </ul>
            <p>We encourage you to review this policy regularly.</p>
        </div>

        <!-- Section 14: Contact Us -->
        <div id="contact-us" class="privacy-section">
            <h2><i class="fas fa-envelope"></i> 14. Contact Us</h2>
            <p>If you have questions, concerns, or wish to exercise your privacy rights, please contact our Data Protection Officer (DPO):</p>
            
            <div class="contact-info">
                <div class="contact-card">
                    <i class="fas fa-building"></i>
                    <h4>K&amp;E Hospital</h4>
                    <p>Great East Road, Lusaka, Zambia</p>
                </div>
                <div class="contact-card">
                    <i class="fas fa-phone"></i>
                    <h4>Phone</h4>
                    <p>+260-7610-16446</p>
                </div>
                <div class="contact-card">
                    <i class="fas fa-envelope"></i>
                    <h4>Email</h4>
                    <p>privacy@kehospital.co.zm</p>
                </div>
                <div class="contact-card">
                    <i class="fas fa-user-shield"></i>
                    <h4>Data Protection Officer</h4>
                    <p>dpo@kehospital.co.zm</p>
                </div>
            </div>
            
            <div class="highlight-box" style="margin-top: 1.5rem;">
                <p><i class="fas fa-gavel"></i> <strong>Regulatory Authority:</strong> You have the right to lodge a complaint with the Zambian Data Protection Authority at <strong>info@dpa.gov.zm</strong> or visit <strong>www.dpa.gov.zm</strong></p>
            </div>
        </div>
        
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <img src="assets/logo.svg" width="100px" alt="K&amp;E Hospital">
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
                <li>+260-7610-16446</li>
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
    /* Scroll fade-in animation */
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