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
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errors = [];
$success = '';
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
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $log_password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'patient');

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
            
            if ($admin) {
                // Verify password (handles both hashed and plain text for compatibility)
                $password_valid = false;
                
                // Check if it's a hashed password (starts with $2y$ or $2b$)
                if (strpos($admin['password'], '$2') === 0) {
                    $password_valid = password_verify($log_password, $admin['password']);
                } else {
                    // Plain text password (for backward compatibility)
                    $password_valid = ($log_password === $admin['password']);
                    // If valid, hash it for future use
                    if ($password_valid) {
                        $hashed = password_hash($log_password, PASSWORD_BCRYPT);
                        $updateStmt = $pdo->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
                        $updateStmt->execute([$hashed, $admin['admin_id']]);
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
                    $errors[] = 'Invalid admin credentials.';
                }
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        } else {
            // Patient login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>K&amp;E Hospital – Login / Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --brand: #5F6FFF;
            --brand-dark: #4a58e8;
            --brand-light: #eef0ff;
            --text-dark: #1a1d2e;
            --text-mid: #4b5264;
            --text-light: #6b7280;
            --white: #ffffff;
            --border: #e5e7eb;
            --red: #ef4444;
            --green: #10b981;
            --transition: 0.22s ease;
            --radius-md: 20px;
            --radius-sm: 12px;
        }

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
            background: var(--white);
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.45s ease both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
            cursor: pointer;
            color: var(--text-light);
            transition: all var(--transition);
            font-family: inherit;
        }

        .tab-btn.active {
            color: var(--brand);
            border-bottom: 2px solid var(--brand);
            margin-bottom: -2px;
        }

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
            font-size: 0.875rem;
            color: var(--text-light);
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
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition);
            background: var(--white);
        }

        .role-option.active {
            border-color: var(--brand);
            background: var(--brand-light);
            color: var(--brand);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-mid);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-family: inherit;
            transition: all var(--transition);
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
            font-size: 1rem;
        }

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
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

        .alert ul {
            margin-left: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--brand);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
            font-family: inherit;
        }

        .btn-submit:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(95,111,255,0.35);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .social-btn {
            width: 100%;
            padding: 12px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all var(--transition);
            font-family: inherit;
            font-size: 0.9rem;
        }

        .social-btn:hover {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        .switch-link {
            text-align: center;
            margin-top: 24px;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .switch-link a {
            color: var(--brand);
            text-decoration: none;
            font-weight: 500;
        }

        .switch-link a:hover {
            text-decoration: underline;
        }

        .forgot-link {
            display: block;
            text-align: right;
            font-size: 0.8rem;
            color: var(--brand);
            margin-top: -12px;
            margin-bottom: 20px;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-bar__fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-label {
            font-size: 0.75rem;
            margin-top: 4px;
        }

        @media (max-width: 640px) {
            .auth-form {
                padding: 24px 20px 28px;
            }
            .row-2 {
                grid-template-columns: 1fr;
            }
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

            <?php if ($success && $active_form === 'login'): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors) && isset($_POST['login'])): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
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

                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>

                <button type="submit" name="login" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="divider">or continue with</div>

            <button class="social-btn" onclick="alert('Google login coming soon!')">
                <i class="fab fa-google"></i> Continue with Google
            </button>

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
                           placeholder="John Doe"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" name="email" 
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="regPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="regPassword" name="password" 
                               placeholder="Min. 8 characters" required
                               oninput="checkStrength(this.value)">
                        <button type="button" class="toggle-pw" onclick="togglePassword('regPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="strength-bar">
                        <div class="strength-bar__fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-label" id="strengthLabel"></div>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="+260 7XX XXX XXX"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="date_of_birth"
                               value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                    </div>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select...</option>
                            <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group">
                            <option value="">Select...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="2" 
                              placeholder="Your address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
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

        function checkStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const colors = ['#ef4444', '#f97316', '#eab308', '#10b981'];
            const texts = ['Very Weak', 'Weak', 'Good', 'Strong'];
            const widths = ['25%', '50%', '75%', '100%'];
            
            const fillBar = document.getElementById('strengthFill');
            const label = document.getElementById('strengthLabel');
            
            if (strength === 0) {
                fillBar.style.width = '0%';
                label.textContent = '';
            } else {
                fillBar.style.width = widths[strength - 1];
                fillBar.style.backgroundColor = colors[strength - 1];
                label.textContent = texts[strength - 1];
                label.style.color = colors[strength - 1];
            }
        }
    </script>
</body>
</html>