<?php
// K&E Hospital - Unified Authentication System
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errors = [];
$success = '';
$active_form = isset($_GET['action']) && $_GET['action'] === 'register' ? 'register' : 'login';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (!$full_name) $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Email already registered. Please login.';
        }
    }
    
    // Insert user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, date_of_birth, gender, address) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$full_name, $email, $hashed_password, $phone, $date_of_birth, $gender, $address]);
            $success = 'Account created successfully! Please login.';
            $active_form = 'login';
        } catch(PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'patient');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (!$password) $errors[] = 'Password is required.';
    
    if (empty($errors)) {
        if ($role === 'admin') {
            // Admin login
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['admin_id'];
                $_SESSION['full_name'] = $admin['full_name'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['role'] = 'admin';
                $_SESSION['is_admin'] = true;
                
                // Update last login
                $update = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                $update->execute([$admin['admin_id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        } else {
            // Patient login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'patient';
                $_SESSION['is_admin'] = false;
                
                header('Location: dashboard.php');
                exit;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>K&E Hospital - Login or Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --brand:       #5F6FFF;
            --brand-dark:  #4a58e8;
            --brand-light: #eef2ff;
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
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a { text-decoration: none; color: inherit; }

        /* Navbar */
        .navbar {
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar__inner {
            width: 92%; max-width: 1200px; margin-inline: auto;
            display: flex; align-items: center; justify-content: space-between;
            height: 70px;
        }
        .navbar__logo {
            display: flex; align-items: center; gap: 12px;
            font-size: 1.3rem; font-weight: 700; color: var(--brand);
        }
        .navbar__logo-icon {
            width: 36px; height: 36px; background: var(--brand);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
        }
        .navbar__logo-icon i {
            font-size: 1.2rem;
            color: white;
        }
        .navbar__nav { display: flex; gap: 2rem; }
        .navbar__nav a {
            font-size: .95rem; font-weight: 500; color: var(--text-mid);
            position: relative; padding-bottom: 3px; transition: color var(--transition);
        }
        .navbar__nav a::after {
            content: ''; position: absolute; bottom: 0; left: 0;
            width: 0; height: 2px; background: var(--brand); border-radius: 2px;
            transition: width var(--transition);
        }
        .navbar__nav a:hover { color: var(--brand); }
        .navbar__nav a:hover::after { width: 100%; }
        .btn-nav {
            background: var(--brand); color: var(--white); border: none;
            padding: 10px 28px; border-radius: 50px; font-size: .9rem;
            font-weight: 600; cursor: pointer; font-family: 'Outfit', sans-serif;
            transition: all var(--transition);
        }
        .btn-nav:hover { 
            background: var(--brand-dark); 
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(95,111,255,.35);
        }

        /* Main Container */
        .page-body {
            flex: 1; display: flex;
            align-items: center; justify-content: center;
            padding: 48px 20px;
        }
        
        /* Auth Container */
        .auth-container {
            background: var(--white);
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Tabs */
        .auth-tabs {
            display: flex;
            border-bottom: 2px solid var(--border);
        }
        
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 18px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all var(--transition);
            color: var(--text-light);
            position: relative;
        }
        
        .tab-btn.active {
            color: var(--brand);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--brand);
        }
        
        .tab-btn:hover:not(.active) {
            color: var(--text-mid);
            background: var(--brand-light);
        }
        
        /* Forms */
        .auth-form {
            padding: 32px 36px 36px;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        
        .form-subtitle {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 28px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-size: 0.85rem;
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
            font-size: 0.9rem;
            font-family: 'Outfit', sans-serif;
            transition: all var(--transition);
            background: var(--white);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(95,111,255,0.1);
        }
        
        .input-wrap {
            position: relative;
        }
        
        .input-wrap input {
            padding-right: 42px;
        }
        
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            transition: color var(--transition);
        }
        
        .toggle-pw:hover {
            color: var(--brand);
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 4px;
            background: var(--border);
            margin-top: 6px;
            overflow: hidden;
        }
        
        .strength-bar__fill {
            height: 100%;
            width: 0;
            border-radius: 4px;
            transition: width 0.3s, background 0.3s;
        }
        
        .strength-label {
            font-size: 0.7rem;
            color: var(--text-light);
            margin-top: 3px;
        }
        
        .forgot-link {
            display: block;
            text-align: right;
            font-size: 0.8rem;
            color: var(--brand);
            margin-top: -10px;
            margin-bottom: 18px;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all var(--transition);
            background: var(--white);
        }
        
        .role-option.active {
            border-color: var(--brand);
            background: var(--brand-light);
            color: var(--brand);
            font-weight: 500;
        }
        
        .role-option i {
            margin-right: 6px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .alert ul {
            padding-left: 20px;
            margin: 0;
        }
        
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--brand);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            margin-top: 10px;
            transition: all var(--transition);
        }
        
        .btn-submit:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(95,111,255,0.35);
        }
        
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--text-light);
            font-size: 0.8rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 11px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 500;
            background: var(--white);
            cursor: pointer;
            transition: all var(--transition);
        }
        
        .social-btn:hover {
            border-color: var(--brand);
            background: var(--brand-light);
        }
        
        .switch-link {
            text-align: center;
            margin-top: 24px;
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .switch-link a {
            color: var(--brand);
            font-weight: 500;
        }
        
        .switch-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .navbar__nav { display: none; }
            .auth-form { padding: 24px 20px 28px; }
            .role-selector { flex-direction: column; }
        }
        
        /* Row for two fields */
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="navbar__inner">
        <a href="index.php" class="navbar__logo">
            <div class="navbar__logo-icon">
                <i class="fas fa-hospital-user"></i>
            </div>
            K&E Hospital
        </a>
        <nav class="navbar__nav">
            <a href="index.php"><i class="fas fa-home"></i> HOME</a>
            <a href="doctors.php"><i class="fas fa-user-md"></i> DOCTORS</a>
            <a href="about.php"><i class="fas fa-info-circle"></i> ABOUT</a>
            <a href="contact.php"><i class="fas fa-envelope"></i> CONTACT</a>
        </nav>
        <?php if ($active_form === 'register'): ?>
            <a href="?action=login" class="btn-nav">Login</a>
        <?php else: ?>
            <a href="?action=register" class="btn-nav">Create account</a>
        <?php endif; ?>
    </div>
</header>

<div class="page-body">
    <div class="auth-container">
        <div class="auth-tabs">
            <button class="tab-btn <?= $active_form === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            <button class="tab-btn <?= $active_form === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </div>
        
        <!-- Login Form -->
        <div id="loginForm" class="auth-form" style="display: <?= $active_form === 'login' ? 'block' : 'none' ?>;">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Please login to book appointment</p>
            
            <?php if ($success && $active_form === 'login'): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors) && $active_form === 'login'): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="?action=login" novalidate>
                <div class="role-selector">
                    <div class="role-option active" data-role="patient" onclick="selectRole('patient')">
                        <i class="fas fa-user"></i> Patient
                    </div>
                    <div class="role-option" data-role="admin" onclick="selectRole('admin')">
                        <i class="fas fa-user-shield"></i> Admin
                    </div>
                </div>
                <input type="hidden" name="role" id="loginRole" value="patient">
                
                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" 
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="loginPassword" name="password" 
                               placeholder="Enter your password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('loginPassword')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                
                <button type="submit" name="login" class="btn-submit">Login</button>
            </form>
            
            <div class="divider">or continue with</div>
            
            <button class="social-btn" onclick="alert('Google login coming soon!')">
                <i class="fab fa-google"></i> Continue with Google
            </button>
        </div>
        
        <!-- Register Form -->
        <div id="registerForm" class="auth-form" style="display: <?= $active_form === 'register' ? 'block' : 'none' ?>;">
            <h2 class="form-title">Create Account</h2>
            <p class="form-subtitle">Please sign up to book appointment</p>
            
            <?php if ($success && $active_form === 'register'): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors) && $active_form === 'register'): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="?action=register" enctype="multipart/form-data" novalidate>
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="full_name" 
                           placeholder="John Smith"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" name="email" 
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="regPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="regPassword" name="password" 
                               placeholder="Min. 8 characters" required
                               oninput="checkStrength(this.value)">
                        <button type="button" class="toggle-pw" onclick="togglePassword('regPassword')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="strength-bar"><div class="strength-bar__fill" id="sf"></div></div>
                    <div class="strength-label" id="sl"></div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="+260 XXX XXX XXX"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="date_of_birth" 
                               value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
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
                Already have an account? <a href="#" onclick="switchTab('login')">Login here</a>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const tabs = document.querySelectorAll('.tab-btn');
    
    if (tab === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        tabs[0].classList.add('active');
        tabs[1].classList.remove('active');
        // Update URL without reload
        window.history.pushState({}, '', '?action=login');
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        tabs[0].classList.remove('active');
        tabs[1].classList.add('active');
        window.history.pushState({}, '', '?action=register');
    }
}

function selectRole(role) {
    const roleOptions = document.querySelectorAll('.role-option');
    const loginRole = document.getElementById('loginRole');
    
    roleOptions.forEach(opt => {
        opt.classList.remove('active');
    });
    
    if (role === 'patient') {
        roleOptions[0].classList.add('active');
        loginRole.value = 'patient';
    } else {
        roleOptions[1].classList.add('active');
        loginRole.value = 'admin';
    }
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function checkStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    const strengthMap = [
        { width: '0%', bg: 'transparent', text: '' },
        { width: '25%', bg: '#ef4444', text: 'Weak' },
        { width: '50%', bg: '#f97316', text: 'Fair' },
        { width: '75%', bg: '#eab308', text: 'Good' },
        { width: '100%', bg: '#10b981', text: 'Strong' }
    ];
    
    const fill = document.getElementById('sf');
    const label = document.getElementById('sl');
    
    fill.style.width = strengthMap[strength].width;
    fill.style.background = strengthMap[strength].bg;
    label.textContent = strengthMap[strength].text;
    label.style.color = strengthMap[strength].bg;
}

// On page load, ensure role selector is set
document.addEventListener('DOMContentLoaded', function() {
    const role = document.getElementById('loginRole').value;
    if (role === 'admin') {
        selectRole('admin');
    } else {
        selectRole('patient');
    }
});
</script>
</body>
</html>