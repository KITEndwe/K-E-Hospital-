<?php
// K&E Hospital - Unified Authentication System
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errors = [];
$success = '';
$debug_info = [];
$active_form = (isset($_GET['action']) && $_GET['action'] === 'register') ? 'register' : 'login';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $reg_password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');

    // Validation
    if (!$full_name) $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($reg_password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) $errors[] = 'Email already registered. Please login.';
    }

    if (empty($errors)) {
        $hashed = password_hash($reg_password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, date_of_birth, gender, address, blood_group) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$full_name, $email, $hashed, $phone, $date_of_birth, $gender, $address, $blood_group]);
            $success = 'Account created successfully! Please login.';
            $active_form = 'login';
        } catch(PDOException $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $log_password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'patient');

    $debug_info['email'] = $email;
    $debug_info['role'] = $role;
    $debug_info['password_length'] = strlen($log_password);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (!$log_password) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        if ($role === 'admin') {
            // Admin login
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            $debug_info['admin_found'] = $admin ? true : false;
            
            if ($admin) {
                $debug_info['stored_password'] = $admin['password'];
                $debug_info['password_type'] = strpos($admin['password'], '$2') === 0 ? 'hashed' : 'plain';
                
                // Verify password
                $password_valid = false;
                
                // Check if it's a hashed password (starts with $2y$ or $2b$)
                if (strpos($admin['password'], '$2') === 0) {
                    $password_valid = password_verify($log_password, $admin['password']);
                    $debug_info['verification_method'] = 'password_verify';
                    $debug_info['verification_result'] = $password_valid ? 'success' : 'failed';
                } else {
                    // Plain text password (for backward compatibility)
                    $password_valid = ($log_password === $admin['password']);
                    $debug_info['verification_method'] = 'direct comparison';
                    $debug_info['verification_result'] = $password_valid ? 'success' : 'failed';
                    
                    // If valid, hash it for future use
                    if ($password_valid) {
                        $hashed = password_hash($log_password, PASSWORD_BCRYPT);
                        $updateStmt = $pdo->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
                        $updateStmt->execute([$hashed, $admin['admin_id']]);
                        $debug_info['password_upgraded'] = true;
                    }
                }
                
                if ($password_valid) {
                    $_SESSION['user_id'] = $admin['admin_id'];
                    $_SESSION['full_name'] = $admin['full_name'];
                    $_SESSION['email'] = $admin['email'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['is_admin'] = true;
                    $_SESSION['profile_image'] = $admin['profile_image'];
                    
                    // Update last login
                    $upd = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                    $upd->execute([$admin['admin_id']]);
                    
                    // Redirect to admin dashboard
                    header('Location: ../admin/dashboard.php');
                    exit();
                } else {
                    $errors[] = 'Invalid admin credentials. Password verification failed.';
                    $debug_info['error'] = 'Password verification failed';
                }
            } else {
                $errors[] = 'Invalid admin credentials. Admin not found.';
            }
        } else {
            // Patient login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            $debug_info['patient_found'] = $user ? true : false;
            
            if ($user) {
                // Verify password
                $password_valid = false;
                
                // Check if it's a hashed password
                if (strpos($user['password'], '$2') === 0) {
                    $password_valid = password_verify($log_password, $user['password']);
                } else {
                    // Plain text password
                    $password_valid = ($log_password === $user['password']);
                    if ($password_valid) {
                        $hashed = password_hash($log_password, PASSWORD_BCRYPT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $updateStmt->execute([$hashed, $user['user_id']]);
                    }
                }
                
                if ($password_valid) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = 'patient';
                    $_SESSION['is_admin'] = false;
                    $_SESSION['profile_image'] = $user['profile_image'];
                    
                    // Redirect to frontend index
                    header('Location: index.php');
                    exit();
                } else {
                    $errors[] = 'Invalid email or password.';
                }
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
    <title>K&E Hospital – Login / Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-container {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .auth-tabs {
            display: flex;
            border-bottom: 2px solid #e5e7eb;
        }
        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 18px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.3s;
        }
        .tab-btn.active {
            color: #5F6FFF;
            border-bottom: 2px solid #5F6FFF;
            margin-bottom: -2px;
        }
        .auth-form {
            padding: 32px 36px 36px;
        }
        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1a1d2e;
        }
        .form-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 24px;
        }
        .role-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .role-option.active {
            border-color: #5F6FFF;
            background: #eef0ff;
            color: #5F6FFF;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: #4b5264;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #5F6FFF;
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
            color: #6b7280;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.875rem;
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
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #5F6FFF;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: #4a58e8;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(95,111,255,0.35);
        }
        .switch-link {
            text-align: center;
            margin-top: 24px;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .switch-link a {
            color: #5F6FFF;
            text-decoration: none;
            font-weight: 500;
        }
        .debug-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            font-family: monospace;
            display: none;
        }
        .debug-info.show {
            display: block;
        }
        @media (max-width: 640px) {
            .auth-form { padding: 24px 20px 28px; }
        }
    </style>
</head>
<body>
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
        <div id="loginForm" class="auth-form" style="display: <?= $active_form === 'login' ? 'block' : 'none' ?>">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Please login to book appointment</p>

            <?php if (!empty($errors) && isset($_POST['login'])): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong>
                    <ul style="margin-top: 8px; margin-left: 20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($debug_info) && isset($_POST['login'])): ?>
                <div class="debug-info show">
                    <strong>Debug Information:</strong><br>
                    <?php foreach ($debug_info as $key => $value): ?>
                        <?= htmlspecialchars($key) ?>: <?= htmlspecialchars(print_r($value, true)) ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=login">
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
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="loginPassword" name="password" 
                               placeholder="Enter your password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('loginPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="switch-link">
                Don't have an account? <a href="#" onclick="switchTab('register'); return false;">Sign up here</a>
            </div>
        </div>

        <!-- Register Form -->
        <div id="registerForm" class="auth-form" style="display: <?= $active_form === 'register' ? 'block' : 'none' ?>">
            <h2 class="form-title">Create Account</h2>
            <p class="form-subtitle">Please sign up to book appointment</p>

            <?php if (!empty($errors) && isset($_POST['register'])): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=register">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="full_name" 
                           placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" name="email" 
                           placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label for="regPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="regPassword" name="password" 
                               placeholder="Min. 8 characters" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('regPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+260 7XX XXX XXX">
                </div>

                <button type="submit" name="register" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="switch-link">
                Already have an account? <a href="#" onclick="switchTab('login'); return false;">Login here</a>
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
            document.querySelectorAll('.role-option').forEach(opt => {
                if (opt.dataset.role === role) {
                    opt.classList.add('active');
                } else {
                    opt.classList.remove('active');
                }
            });
            document.getElementById('loginRole').value = role;
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
    </script>
</body>
</html>