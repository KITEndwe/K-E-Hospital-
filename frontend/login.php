<?php
// K&E Hospital - Unified Authentication System
session_start();

$host   = 'localhost';
$dbname = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errors      = [];
$success     = '';
$active_form = (isset($_GET['action']) && $_GET['action'] === 'register') ? 'register' : 'login';

// ── Handle Registration ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name     = trim($_POST['full_name']     ?? '');
    $email         = trim($_POST['email']         ?? '');
    $reg_password  = trim($_POST['password']      ?? '');
    $phone         = trim($_POST['phone']         ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender        = trim($_POST['gender']        ?? '');
    $address       = trim($_POST['address']       ?? '');

    if (!$full_name)                                    $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'A valid email address is required.';
    if (strlen($reg_password) < 8)                      $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) $errors[] = 'Email already registered. Please login.';
    }

    if (empty($errors)) {
        $hashed = password_hash($reg_password, PASSWORD_BCRYPT);
        $stmt   = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, date_of_birth, gender, address)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$full_name, $email, $hashed, $phone, $date_of_birth, $gender, $address]);
            $success     = 'Account created successfully! Please login.';
            $active_form = 'login';
        } catch(PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

// ── Handle Login ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email        = trim($_POST['email']    ?? '');
    $log_password = trim($_POST['password'] ?? '');
    $role         = trim($_POST['role']     ?? 'patient');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (!$log_password)                             $errors[] = 'Password is required.';

    if (empty($errors)) {
        if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin && password_verify($log_password, $admin['password'])) {
                $_SESSION['user_id']   = $admin['admin_id'];
                $_SESSION['full_name'] = $admin['full_name'];
                $_SESSION['email']     = $admin['email'];
                $_SESSION['role']      = 'admin';
                $_SESSION['is_admin']  = true;
                $upd = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                $upd->execute([$admin['admin_id']]);
                header('Location: dashboard.php'); exit;
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($log_password, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = 'patient';
                $_SESSION['is_admin']  = false;
                header('Location: index.php'); exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>K&amp;E Hospital – Login / Register</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        /* ─── Reset & Base ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:       #5F6FFF;
            --brand-dark:  #4a58e8;
            --brand-light: #eef0ff;
            --text-dark:   #1a1d2e;
            --text-mid:    #4b5264;
            --text-light:  #6b7280;
            --white:       #ffffff;
            --border:      #e5e7eb;
            --red:         #ef4444;
            --green:       #10b981;
            --transition:  .22s ease;
            --radius-md:   20px;
            --radius-sm:   12px;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Outfit', sans-serif;
            background: #f7f8fc;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }

        .container {
            width: 92%;
            max-width: 1160px;
            margin-inline: auto;
        }

        /* ─── Navbar ────────────────────────────────────────────────── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(95,111,255,.12);
            box-shadow: 0 2px 10px rgba(0,0,0,.02);
        }
        .navbar__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
            gap: 1rem;
        }
        .navbar__logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--brand);
            flex-shrink: 0;
        }
        .navbar__logo img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }
        .navbar__nav {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .navbar__nav a {
            font-size: .95rem;
            font-weight: 500;
            color: var(--text-mid);
            position: relative;
            padding-bottom: 4px;
            transition: color var(--transition);
        }
        .navbar__nav a::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 0; height: 2.5px;
            background: var(--brand);
            border-radius: 3px;
            transition: width var(--transition);
        }
        .navbar__nav a:hover { color: var(--brand); }
        .navbar__nav a:hover::after { width: 100%; }

        .navbar__desktop-btn { flex-shrink: 0; }

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
            font-family: 'Outfit', sans-serif;
            transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
        }
        .btn-primary:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(95,111,255,.35);
        }

        /* ─── Mobile Hamburger ───────────────────────────────────── */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            width: 44px; height: 44px;
            z-index: 1010;
            border-radius: 30px;
            transition: background .2s;
            align-items: center;
            justify-content: center;
        }
        .mobile-menu-toggle:hover { background: var(--brand-light); }

        .hamburger-icon {
            width: 26px; height: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .hamburger-icon span {
            display: block;
            height: 2.5px; width: 100%;
            background: var(--text-dark);
            border-radius: 4px;
            transition: all .3s cubic-bezier(.2,.8,.3,1);
        }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(1) { transform: translateY(8.5px) rotate(45deg); }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(2) { opacity: 0; transform: scaleX(.8); }
        .mobile-menu-toggle.active .hamburger-icon span:nth-child(3) { transform: translateY(-8.5px) rotate(-45deg); }

        /* Mobile slide-out panel */
        .mobile-nav {
            position: fixed;
            top: 0; right: -100%;
            width: min(75%, 320px);
            height: 100vh;
            background: rgba(255,255,255,.98);
            box-shadow: -8px 0 32px rgba(0,0,0,.1);
            z-index: 1005;
            transition: right .35s ease-out;
            display: flex;
            flex-direction: column;
            padding: 90px 28px 40px;
            gap: 1.8rem;
            border-left: 1px solid rgba(95,111,255,.2);
        }
        .mobile-nav.open { right: 0; }
        .mobile-nav a {
            font-size: 1.15rem;
            font-weight: 500;
            color: var(--text-dark);
            padding: 10px 0;
            border-bottom: 1px solid #f0f2ff;
            transition: color .2s, padding-left .2s;
            display: inline-block;
        }
        .mobile-nav a:hover { color: var(--brand); padding-left: 8px; }
        .mobile-nav .mobile-account-btn {
            margin-top: 20px;
            background: var(--brand);
            text-align: center;
            border-radius: 60px;
            padding: 12px 0;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: background .2s;
        }
        .mobile-nav .mobile-account-btn:hover { background: var(--brand-dark); padding-left: 0; }

        .menu-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,.3);
            backdrop-filter: blur(2px);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: opacity .3s, visibility .3s;
        }
        .menu-overlay.active { opacity: 1; visibility: visible; }

        /* ─── Page Body ──────────────────────────────────────────── */
        .page-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 16px 64px;
        }

        /* ─── Auth Card ──────────────────────────────────────────── */
        .auth-container {
            background: var(--white);
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,.10);
            overflow: hidden;
            animation: slideUp .45s ease both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── Tabs ───────────────────────────────────────────────── */
        .auth-tabs {
            display: flex;
            border-bottom: 2px solid var(--border);
        }
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 18px;
            font-size: .95rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            color: var(--text-light);
            position: relative;
            transition: color var(--transition), background var(--transition);
        }
        .tab-btn.active { color: var(--brand); }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px; left: 0; right: 0;
            height: 2px;
            background: var(--brand);
        }
        .tab-btn:hover:not(.active) {
            color: var(--text-mid);
            background: var(--brand-light);
        }

        /* ─── Form Area ──────────────────────────────────────────── */
        .auth-form { padding: 32px 36px 36px; }

        .form-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-dark);
        }
        .form-subtitle {
            font-size: .86rem;
            color: var(--text-light);
            margin-bottom: 26px;
        }

        /* ─── Role Selector ──────────────────────────────────────── */
        .role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: .88rem;
            font-weight: 500;
            color: var(--text-mid);
            background: var(--white);
            transition: all var(--transition);
            user-select: none;
        }
        .role-option.active {
            border-color: var(--brand);
            background: var(--brand-light);
            color: var(--brand);
        }
        .role-option:hover:not(.active) {
            border-color: #c7d0ff;
            background: #f5f7ff;
        }

        /* ─── Form Fields ────────────────────────────────────────── */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: .84rem;
            font-weight: 500;
            color: var(--text-mid);
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: .9rem;
            font-family: 'Outfit', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
            resize: vertical;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(95,111,255,.12);
        }

        .input-wrap { position: relative; }
        .input-wrap input { padding-right: 42px; }
        .toggle-pw {
            position: absolute;
            right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-light);
            display: flex; align-items: center;
            transition: color var(--transition);
            font-size: .95rem;
        }
        .toggle-pw:hover { color: var(--brand); }

        /* Password strength */
        .strength-bar {
            height: 4px; border-radius: 4px;
            background: var(--border);
            margin-top: 6px; overflow: hidden;
        }
        .strength-bar__fill {
            height: 100%; width: 0; border-radius: 4px;
            transition: width .3s, background .3s;
        }
        .strength-label { font-size: .72rem; color: var(--text-light); margin-top: 3px; }

        /* Forgot link */
        .forgot-link {
            display: block;
            text-align: right;
            font-size: .8rem;
            color: var(--brand);
            margin-top: -8px;
            margin-bottom: 18px;
        }
        .forgot-link:hover { text-decoration: underline; }

        /* Two-column row */
        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: .85rem;
            margin-bottom: 20px;
        }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert ul { padding-left: 18px; list-style: disc; }
        .alert li { margin-top: 3px; }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--brand);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-size: .95rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            margin-top: 8px;
            transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
        }
        .btn-submit:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(95,111,255,.35);
        }

        /* Divider */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 22px 0; color: var(--text-light); font-size: .8rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        /* Google button */
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 11px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: .88rem;
            font-weight: 500;
            font-family: 'Outfit', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition), box-shadow var(--transition);
        }
        .social-btn:hover {
            border-color: var(--brand);
            background: var(--brand-light);
            box-shadow: 0 2px 10px rgba(95,111,255,.12);
        }

        /* Switch link */
        .switch-link {
            text-align: center;
            margin-top: 22px;
            font-size: .86rem;
            color: var(--text-light);
        }
        .switch-link a { color: var(--brand); font-weight: 500; }
        .switch-link a:hover { text-decoration: underline; }

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 768px) {
            .navbar__nav        { display: none; }
            .navbar__desktop-btn{ display: none; }
            .mobile-menu-toggle { display: flex; }
            .navbar__inner      { height: 66px; }
            .navbar__logo img   { height: 42px; }
        }
        @media (max-width: 540px) {
            .auth-form  { padding: 24px 20px 28px; }
            .row-2      { grid-template-columns: 1fr; }
            .role-selector { gap: 8px; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════════════════ -->
<header class="navbar">
    <div class="container navbar__inner">

        <!-- Logo -->
        <a href="index.php" class="navbar__logo">
            <img
                src="assets/logo.svg"
                alt="K&amp;E Hospital"
                onerror="this.src='https://placehold.co/120x40/5F6FFF/white?text=K%26E+Hospital'"
            />
        </a>

        <!-- Desktop nav -->
        <nav class="navbar__nav">
            <a href="index.php">HOME</a>
            <a href="Alldoctors.php">ALL DOCTORS</a>
            <a href="about.php">ABOUT</a>
            <a href="contact.php">CONTACT</a>
        </nav>

        <!-- Desktop CTA — toggles based on active form -->
        <div class="navbar__desktop-btn">
            <?php if ($active_form === 'register'): ?>
                <a href="?action=login" class="btn-primary">Login</a>
            <?php else: ?>
                <a href="?action=register" class="btn-primary">Create account</a>
            <?php endif; ?>
        </div>

        <!-- Hamburger -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu">
            <div class="hamburger-icon">
                <span></span><span></span><span></span>
            </div>
        </button>
    </div>

    <!-- Mobile slide-out panel -->
    <div class="mobile-nav" id="mobileNav">
        <a href="index.php">🏠 HOME</a>
        <a href="Alldoctors.php">👨‍⚕️ ALL DOCTORS</a>
        <a href="about.php">ℹ️ ABOUT</a>
        <a href="contact.php">📞 CONTACT</a>
        <?php if ($active_form === 'register'): ?>
            <a href="?action=login" class="mobile-account-btn">🔑 Login</a>
        <?php else: ?>
            <a href="?action=register" class="mobile-account-btn">✨ Create account</a>
        <?php endif; ?>
    </div>
    <div class="menu-overlay" id="menuOverlay"></div>
</header>


<!-- ═══════════════════════════════════════════════════════════
     AUTH CARD
═══════════════════════════════════════════════════════════ -->
<div class="page-body">
    <div class="auth-container">

        <!-- Tabs -->
        <div class="auth-tabs">
            <button class="tab-btn <?= $active_form === 'login'    ? 'active' : '' ?>" onclick="switchTab('login')">
                Login
            </button>
            <button class="tab-btn <?= $active_form === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">
                Register
            </button>
        </div>

        <!-- ── LOGIN FORM ─────────────────────────────────────────── -->
        <div id="loginForm" class="auth-form" style="display:<?= $active_form === 'login' ? 'block' : 'none' ?>;">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Please login to book appointment</p>

            <?php if ($success && $active_form === 'login'): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors) && isset($_POST['login'])): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=login" novalidate>

                <!-- Role selector -->
                <div class="role-selector">
                    <div class="role-option active" data-role="patient" onclick="selectRole('patient')">
                        👤 Patient
                    </div>
                    <div class="role-option" data-role="admin" onclick="selectRole('admin')">
                        🛡️ Admin
                    </div>
                </div>
                <input type="hidden" name="role" id="loginRole" value="patient" />

                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars(isset($_POST['login']) ? ($_POST['email'] ?? '') : '') ?>"
                        autocomplete="email" />
                </div>

                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="loginPassword" name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password" />
                        <button type="button" class="toggle-pw" onclick="togglePw('loginPassword', this)" aria-label="Show/hide password">
                            <!-- eye icon -->
                            <svg id="eye-login" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>

                <button type="submit" name="login" class="btn-submit">Login</button>
            </form>

            <div class="divider">or continue with</div>

            <button class="social-btn" type="button" onclick="alert('Google login coming soon!')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Continue with Google
            </button>

            <div class="switch-link">
                Don't have an account? <a href="#" onclick="switchTab('register'); return false;">Sign up here</a>
            </div>
        </div>


        <!-- ── REGISTER FORM ──────────────────────────────────────── -->
        <div id="registerForm" class="auth-form" style="display:<?= $active_form === 'register' ? 'block' : 'none' ?>;">
            <h2 class="form-title">Create Account</h2>
            <p class="form-subtitle">Please sign up to book appointment</p>

            <?php if (!empty($errors) && isset($_POST['register'])): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=register" novalidate>

                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="full_name"
                        placeholder="John Smith"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        autocomplete="name" />
                </div>

                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" name="email"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email" />
                </div>

                <div class="form-group">
                    <label for="regPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="regPassword" name="password"
                            placeholder="Min. 8 characters"
                            autocomplete="new-password"
                            oninput="checkStrength(this.value)" />
                        <button type="button" class="toggle-pw" onclick="togglePw('regPassword', this)" aria-label="Show/hide password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="strength-bar"><div class="strength-bar__fill" id="sf"></div></div>
                    <div class="strength-label" id="sl"></div>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                            placeholder="+260 7XX XXX XXX"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="date_of_birth"
                            value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>" />
                    </div>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select…</option>
                            <option value="Male"   <?= (($_POST['gender'] ?? '') === 'Male')   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other"  <?= (($_POST['gender'] ?? '') === 'Other')  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="2"
                            placeholder="Your address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-submit">Create account</button>
            </form>

            <div class="switch-link">
                Already have an account? <a href="#" onclick="switchTab('login'); return false;">Login here</a>
            </div>
        </div>

    </div><!-- /auth-container -->
</div><!-- /page-body -->


<!-- ═══════════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════════ -->
<script>
/* ── Tab switching ── */
function switchTab(tab) {
    const lf   = document.getElementById('loginForm');
    const rf   = document.getElementById('registerForm');
    const tabs = document.querySelectorAll('.tab-btn');

    if (tab === 'login') {
        lf.style.display = 'block';
        rf.style.display = 'none';
        tabs[0].classList.add('active');
        tabs[1].classList.remove('active');
        window.history.pushState({}, '', '?action=login');
    } else {
        lf.style.display = 'none';
        rf.style.display = 'block';
        tabs[0].classList.remove('active');
        tabs[1].classList.add('active');
        window.history.pushState({}, '', '?action=register');
    }
}

/* ── Role selector ── */
function selectRole(role) {
    document.querySelectorAll('.role-option').forEach(function(opt) {
        opt.classList.toggle('active', opt.dataset.role === role);
    });
    document.getElementById('loginRole').value = role;
}

/* ── Show/hide password ── */
function togglePw(inputId) {
    var inp = document.getElementById(inputId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

/* ── Password strength ── */
function checkStrength(v) {
    var s = 0;
    if (v.length >= 8)           s++;
    if (/[A-Z]/.test(v))         s++;
    if (/[0-9]/.test(v))         s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;

    var m = [
        { w: '0%',   b: 'transparent', t: '' },
        { w: '25%',  b: '#ef4444',     t: 'Weak' },
        { w: '50%',  b: '#f97316',     t: 'Fair' },
        { w: '75%',  b: '#eab308',     t: 'Good' },
        { w: '100%', b: '#10b981',     t: 'Strong' }
    ];

    document.getElementById('sf').style.width      = m[s].w;
    document.getElementById('sf').style.background = m[s].b;
    document.getElementById('sl').textContent      = m[s].t;
    document.getElementById('sl').style.color      = m[s].b;
}

/* ── Mobile menu ── */
(function () {
    var toggle  = document.getElementById('mobileMenuToggle');
    var mobileNav = document.getElementById('mobileNav');
    var overlay   = document.getElementById('menuOverlay');

    function close() {
        mobileNav.classList.remove('open');
        overlay.classList.remove('active');
        toggle.classList.remove('active');
        document.body.style.overflow = '';
    }
    function open() {
        mobileNav.classList.add('open');
        overlay.classList.add('active');
        toggle.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    if (toggle)  toggle.addEventListener('click', function(e) { e.stopPropagation(); mobileNav.classList.contains('open') ? close() : open(); });
    if (overlay) overlay.addEventListener('click', close);

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && mobileNav.classList.contains('open')) close();
    });

    document.querySelectorAll('.mobile-nav a').forEach(function(l) { l.addEventListener('click', close); });
})();
</script>

</body>
</html>