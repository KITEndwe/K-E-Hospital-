<?php
session_start();

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$is_logged_in  = isset($_SESSION['user_id']);
$user_name     = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page  = 'contact.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';

$message_sent  = false;
$error_message = '';

/* ══════════════════════════════════════════════
   SMTP EMAIL CONFIG
   Uses Gmail SMTP via PHPMailer (no extra lib needed —
   we use PHP's socket-level SMTP directly).
   If PHPMailer is installed via Composer, it will use that.
   Otherwise falls back to a raw SMTP socket approach.
══════════════════════════════════════════════ */

/* ── CHANGE THESE TWO VALUES ONLY ── */
define('SMTP_USER',    'kalengamuma316@gmail.com'); // Your Gmail
define('SMTP_PASS',    'efar hrde dapx phdy');   // Gmail App Password (16 chars)
define('SMTP_TO',      'kalengamuma316@gmail.com'); // Where emails arrive
define('HOSPITAL_NAME','K&E Hospital');


function sendSmtpEmail($to, $to_name, $from, $from_name, $subject, $body_html, $body_text) {
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    $smtp_user = SMTP_USER;
    $smtp_pass = SMTP_PASS;

    /* Open socket */
    $errno = 0; $errstr = '';
    $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15);
    if (!$socket) {
        return 'Connection failed: ' . $errstr . ' (' . $errno . ')';
    }

    /* Helper: read response */
    $read = function() use ($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $data;
    };

    /* Helper: send command */
    $send = function($cmd) use ($socket, $read) {
        fputs($socket, $cmd . "\r\n");
        return $read();
    };

    /* SMTP handshake */
    $read(); // banner
    $r = $send('EHLO ' . $smtp_host);
    if (strpos($r, '250') === false) { fclose($socket); return 'EHLO failed: ' . $r; }

    /* STARTTLS */
    $r = $send('STARTTLS');
    if (strpos($r, '220') === false) { fclose($socket); return 'STARTTLS failed: ' . $r; }

    /* Upgrade to TLS */
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    /* Re-handshake after TLS */
    $send('EHLO ' . $smtp_host);

    /* Auth LOGIN */
    $r = $send('AUTH LOGIN');
    if (strpos($r, '334') === false) { fclose($socket); return 'AUTH failed: ' . $r; }
    $send(base64_encode($smtp_user));
    $r = $send(base64_encode($smtp_pass));
    if (strpos($r, '235') === false) { fclose($socket); return 'Login failed — check App Password. Response: ' . $r; }

    /* MAIL FROM */
    $r = $send('MAIL FROM:<' . $smtp_user . '>');
    if (strpos($r, '250') === false) { fclose($socket); return 'MAIL FROM failed: ' . $r; }

    /* RCPT TO */
    $r = $send('RCPT TO:<' . $to . '>');
    if (strpos($r, '250') === false) { fclose($socket); return 'RCPT TO failed: ' . $r; }

    /* DATA */
    $send('DATA');

    $boundary = 'KE_' . md5(time());
    $date     = date('r');

    $headers  = "From: " . HOSPITAL_NAME . " <" . $smtp_user . ">\r\n";
    $headers .= "To: " . $to_name . " <" . $to . ">\r\n";
    $headers .= "Reply-To: " . $from_name . " <" . $from . ">\r\n";
    $headers .= "Subject: " . $subject . "\r\n";
    $headers .= "Date: " . $date . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"\r\n";
    $headers .= "X-Mailer: KE-Hospital-PHP\r\n";

    $body  = "--" . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $body_text . "\r\n";
    $body .= "--" . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $body_html . "\r\n";
    $body .= "--" . $boundary . "--\r\n";

    fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
    $r = $read();
    if (strpos($r, '250') === false) { fclose($socket); return 'DATA failed: ' . $r; }

    $send('QUIT');
    fclose($socket);
    return true; // success
}


/* ══════════════════════════════════════════════
   HANDLE FORM SUBMISSION
══════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $form_name    = trim(isset($_POST['name'])    ? $_POST['name']    : '');
    $form_email   = trim(isset($_POST['email'])   ? $_POST['email']   : '');
    $form_subject = trim(isset($_POST['subject']) ? $_POST['subject'] : '');
    $form_message = trim(isset($_POST['message']) ? $_POST['message'] : '');
    $form_phone   = trim(isset($_POST['phone'])   ? $_POST['phone']   : '');

    /* Validate */
    if (empty($form_name) || empty($form_email) || empty($form_subject) || empty($form_message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {

        /* Save to database */
        $db_saved = false;
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $ins = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?,?,?,?,?)");
            $ins->execute(array($form_name, $form_email, $form_phone, $form_subject, $form_message));
            $db_saved = true;
        } catch (PDOException $e) {
            /* DB failure is non-critical — still try to send email */
        }

        /* Build beautiful HTML email */
        $html_body = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:Arial,sans-serif;background:#f5f7fb;margin:0;padding:0;}
.wrap{max-width:580px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
.header{background:linear-gradient(135deg,#5f6fff,#7b8bff);padding:28px 32px;text-align:center;}
.header h1{color:#fff;font-size:22px;margin:0;}
.header p{color:rgba(255,255,255,0.85);font-size:13px;margin:6px 0 0;}
.body{padding:32px;}
.label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;}
.value{font-size:15px;color:#1a1a2e;margin-bottom:18px;padding:10px 14px;background:#f9fafb;border-radius:8px;border-left:3px solid #5f6fff;}
.message-box{background:#f0f1ff;border-radius:10px;padding:16px;font-size:14px;color:#374151;line-height:1.65;white-space:pre-wrap;}
.footer{background:#f8fafc;border-top:1px solid #e8eaf0;padding:18px 32px;text-align:center;font-size:12px;color:#9ca3af;}
.badge{display:inline-block;background:#5f6fff;color:#fff;padding:3px 10px;border-radius:50px;font-size:11px;font-weight:600;}
</style></head><body>
<div class="wrap">
  <div class="header">
    <h1>&#x1F4E8; New Contact Message</h1>
    <p>K&amp;E Hospital — Contact Form</p>
  </div>
  <div class="body">
    <p style="color:#6b7280;font-size:14px;margin-bottom:24px;">
      You have received a new message through the K&amp;E Hospital website contact form.
    </p>
    <div class="label">From</div>
    <div class="value">'.htmlspecialchars($form_name).' &nbsp;<span style="color:#9ca3af;font-size:13px;">&lt;'.htmlspecialchars($form_email).'&gt;</span></div>
    '.(!empty($form_phone) ? '<div class="label">Phone</div><div class="value">'.htmlspecialchars($form_phone).'</div>' : '').'
    <div class="label">Subject</div>
    <div class="value">'.htmlspecialchars($form_subject).'</div>
    <div class="label">Message</div>
    <div class="message-box">'.htmlspecialchars($form_message).'</div>
    <p style="margin-top:24px;font-size:13px;color:#9ca3af;">
      Received: '.date('l, d F Y \a\t H:i').' &bull; <span class="badge">Website Form</span>
    </p>
  </div>
  <div class="footer">
    K&amp;E Hospital &bull; Great East Road, Lusaka, Zambia<br>
    Reply directly to this email to respond to ' . htmlspecialchars($form_name) . '.
  </div>
</div>
</body></html>';

        $plain_body  = "New contact message from the K&E Hospital website\n";
        $plain_body .= str_repeat('=', 50) . "\n";
        $plain_body .= "From:    " . $form_name . " <" . $form_email . ">\n";
        if (!empty($form_phone)) $plain_body .= "Phone:   " . $form_phone . "\n";
        $plain_body .= "Subject: " . $form_subject . "\n";
        $plain_body .= "Date:    " . date('l, d F Y \a\t H:i') . "\n";
        $plain_body .= str_repeat('-', 50) . "\n";
        $plain_body .= $form_message . "\n";
        $plain_body .= str_repeat('=', 50) . "\n";
        $plain_body .= "K&E Hospital — Great East Road, Lusaka, Zambia\n";

        $email_subject = '[K&E Hospital] ' . $form_subject . ' — from ' . $form_name;

        /* Send email */
        if (SMTP_PASS === 'YOUR_APP_PASSWORD_HERE') {
            /* App password not configured yet — just save to DB */
            if ($db_saved) {
                $message_sent  = true;
            } else {
                $error_message = 'Message could not be saved. Please try again.';
            }
        } else {
            $result = sendSmtpEmail(
                SMTP_TO,
                'K&E Hospital Admin',
                $form_email,
                $form_name,
                $email_subject,
                $html_body,
                $plain_body
            );
            if ($result === true) {
                $message_sent = true;
            } else {
                /* Email failed — still show success if DB saved */
                if ($db_saved) {
                    $message_sent  = true;
                } else {
                    $error_message = 'Could not send message: ' . $result . '. Please call us directly.';
                }
            }
        }
    }
}

/* Profile image for navbar */
if ($is_logged_in && empty($profile_image)) {
    try {
        $pdo2 = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $s2 = $pdo2->prepare("SELECT profile_image FROM users WHERE user_id = ?");
        $s2->execute(array($_SESSION['user_id']));
        $ud = $s2->fetch(PDO::FETCH_ASSOC);
        if ($ud && !empty($ud['profile_image'])) $profile_image = $ud['profile_image'];
    } catch (PDOException $e) { /* silent */ }
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
<link rel="stylesheet" href="./Css/contact.css">
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="page-wrap">

    <!-- Page header -->
    <div class="page-header fade-up">
        <h1>Contact Us</h1>
        <div class="header-line"></div>
        <p>Have questions or need assistance? We're here to help you 24/7.</p>
    </div>

    <!-- Contact grid -->
    <div class="contact-grid">

        <!-- Info cards -->
        <div class="contact-info stagger">
            <div class="info-card fade-up">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-content">
                    <h3>Visit Us</h3>
                    <p>Great East Road, Lusaka, Zambia</p>
                </div>
            </div>
            <div class="info-card fade-up">
                <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="info-content">
                    <h3>Call Us</h3>
                    <p>+260-772-903-446</p>
                    <p>+260-977-104-196</p>
                </div>
            </div>
            <div class="info-card fade-up">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <h3>Email Us</h3>
                    <p>elijahmwange55@gmail.com</p>
                    <p>kalengamuma316@gmail.com</p>
                </div>
            </div>
            <div class="info-card fade-up">
                <div class="info-icon"><i class="fas fa-clock"></i></div>
                <div class="info-content">
                    <h3>Working Hours</h3>
                    <p>Mon – Fri: 8:00 AM – 8:00 PM</p>
                    <p>Sat – Sun: 9:00 AM – 5:00 PM</p>
                </div>
            </div>
        </div>

        <!-- Contact form -->
        <div class="contact-form-card fade-up">
            <h2>Send Us a Message</h2>
            <p class="form-subtitle">
                <i class="fas fa-paper-plane" style="color:#5f6fff;"></i>
                We'll reply within 24 hours
            </p>

            <?php if ($message_sent): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Thank you, <strong><?php echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : ''); ?></strong>! Your message has been received. We'll get back to you soon.</span>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if (!$message_sent): ?>
            <form method="POST" action="" id="contactForm" novalidate>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Full Name *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : ($is_logged_in ? $user_name : '')); ?>"
                               placeholder="Your full name">
                    </div>
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone (optional)</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>"
                               placeholder="+260 7XX XXX XXX">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''); ?>"
                           placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="subject"><i class="fas fa-tag"></i> Subject *</label>
                    <input type="text" id="subject" name="subject" required
                           value="<?php echo htmlspecialchars(isset($_POST['subject']) ? $_POST['subject'] : ''); ?>"
                           placeholder="What is this regarding?">
                </div>

                <div class="form-group">
                    <label for="message"><i class="fas fa-comment-dots"></i> Message *</label>
                    <textarea id="message" name="message" required
                              placeholder="Please describe your inquiry in detail..."><?php echo htmlspecialchars(isset($_POST['message']) ? $_POST['message'] : ''); ?></textarea>
                </div>

                <button type="submit" name="send_message" class="btn-submit" id="submitBtn">
                    <span id="btnText"><i class="fas fa-paper-plane"></i> Send Message</span>
                    <div class="spinner" id="btnSpinner"></div>
                </button>

            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map -->
    <div class="map-section fade-up">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d123112.1!2d28.283333!3d-15.416667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1940f6e0d5a9e2a5%3A0x2e4b9c4e8d5f2c7!2sLusaka%2C%20Zambia!5e0!3m2!1sen!2s!4v1700000000000"
            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>

    <!-- FAQ -->
    <div class="faq-section">
        <div class="section-header fade-up">
            <h2>Frequently Asked Questions</h2>
            <p>Quick answers to common questions about K&amp;E Hospital</p>
        </div>
        <div class="faq-grid stagger">
            <div class="faq-item fade-up">
                <div class="faq-q"><i class="fas fa-question-circle"></i><h4>How do I book an appointment?</h4></div>
                <div class="faq-a">Browse our doctors list, select your preferred specialist, then pick an available date and time slot. Login is required to confirm a booking.</div>
            </div>
            <div class="faq-item fade-up">
                <div class="faq-q"><i class="fas fa-question-circle"></i><h4>What are your working hours?</h4></div>
                <div class="faq-a">Mon–Fri 8 AM–8 PM, Sat–Sun 9 AM–5 PM. Emergency consultations are available 24/7 — call us directly.</div>
            </div>
            <div class="faq-item fade-up">
                <div class="faq-q"><i class="fas fa-question-circle"></i><h4>How do I cancel my appointment?</h4></div>
                <div class="faq-a">Log in to your account and go to "My Appointments". Click the "Cancel appointment" button next to the booking you wish to cancel.</div>
            </div>
            <div class="faq-item fade-up">
                <div class="faq-q"><i class="fas fa-question-circle"></i><h4>Do you accept insurance?</h4></div>
                <div class="faq-a">Yes, we accept most major insurance providers in Zambia. Please contact our billing department for specific insurer inquiries.</div>
            </div>
        </div>
    </div>

</div>

<!-- Footer -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">
                <div ><img src="assets/logo.svg" width="100px" alt=""></div>
                
            </div>
            <p class="footer-desc">Your health comes first. We connect patients across Zambia with qualified doctors, delivering quality healthcare wherever you are, right at your fingertips.</p>
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
                <li>elijahmwange55@gmail.com</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        Copyright &copy; <?php echo date('Y'); ?> K&amp;E Hospital - All Right Reserved.
    </div>
</footer>

<script>
/* Submit button spinner */
document.getElementById('contactForm') && document.getElementById('contactForm').addEventListener('submit', function() {
    var btn     = document.getElementById('submitBtn');
    var txt     = document.getElementById('btnText');
    var spinner = document.getElementById('btnSpinner');
    if (btn && txt && spinner) {
        txt.style.display     = 'none';
        spinner.style.display = 'block';
        btn.disabled          = true;
    }
});

/* Scroll fade-in */
(function() {
    var els = document.querySelectorAll('.fade-up');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
            });
        }, { threshold: 0.1 });
        for (var i = 0; i < els.length; i++) io.observe(els[i]);
    } else {
        for (var j = 0; j < els.length; j++) els[j].classList.add('visible');
    }
})();
</script>
</body>
</html>