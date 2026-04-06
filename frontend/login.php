<?php
// K&E Hospital - Unified Authentication System (Patient, Admin & Doctor)
session_start();

$host = 'localhost';
$dbname = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errors = array();
$success = '';
$active_form = (isset($_GET['action']) && $_GET['action'] === 'register') ? 'register' : 'login';

// Handle Registration (Patients only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name    = trim(isset($_POST['full_name'])    ? $_POST['full_name']    : '');
    $email        = trim(isset($_POST['email'])        ? $_POST['email']        : '');
    $pass         = trim(isset($_POST['password'])     ? $_POST['password']     : '');
    $phone        = trim(isset($_POST['phone'])        ? $_POST['phone']        : '');
    $date_of_birth= trim(isset($_POST['date_of_birth'])? $_POST['date_of_birth']: '');
    $gender       = trim(isset($_POST['gender'])       ? $_POST['gender']       : '');
    $address      = trim(isset($_POST['address'])      ? $_POST['address']      : '');

    if (!$full_name) $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute(array($email));
        if ($stmt->rowCount() > 0) $errors[] = 'Email already registered. Please login.';
    }

    if (empty($errors)) {
        $hashed = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, date_of_birth, gender, address) VALUES (?,?,?,?,?,?,?)");
        try {
            $stmt->execute(array($full_name, $email, $hashed, $phone, $date_of_birth, $gender, $address));
            $success = 'Account created successfully! Please login.';
            $active_form = 'login';
        } catch(PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

// Handle Login (Patient, Admin, or Doctor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim(isset($_POST['email'])    ? $_POST['email']    : '');
    $pass  = trim(isset($_POST['password']) ? $_POST['password'] : '');
    $role  = trim(isset($_POST['role'])     ? $_POST['role']     : 'patient');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (!$pass) $errors[] = 'Password is required.';

    if (empty($errors)) {
        // Admin Login
        if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? AND is_active = TRUE");
            $stmt->execute(array($email));
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin && password_verify($pass, $admin['password'])) {
                $_SESSION['user_id']   = $admin['admin_id'];
                $_SESSION['full_name'] = $admin['full_name'];
                $_SESSION['email']     = $admin['email'];
                $_SESSION['role']      = 'admin';
                $_SESSION['is_admin']  = true;
                $upd = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                $upd->execute(array($admin['admin_id']));
                header('Location: ../admin/dashboard.php'); exit;
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        }
        // Doctor Login
        elseif ($role === 'doctor') {
            $stmt = $pdo->prepare("SELECT * FROM doctors WHERE email = ? AND is_available = 1");
            $stmt->execute(array($email));
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctor && !empty($doctor['password']) && password_verify($pass, $doctor['password'])) {
                $_SESSION['doctor_id']    = $doctor['doctor_id'];
                $_SESSION['doctor_name']  = $doctor['name'];
                $_SESSION['doctor_email'] = $doctor['email'];
                $_SESSION['doctor_spec']  = $doctor['speciality'];
                $_SESSION['role']         = 'doctor';
                $_SESSION['is_doctor']    = true;
                header('Location: doctor-dashboard.php'); exit;
            } else {
                $errors[] = 'Invalid doctor credentials.';
            }
        }
        // Patient Login
        else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute(array($email));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($pass, $user['password'])) {
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

$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['doctor_id']);
$user_name    = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : (isset($_SESSION['doctor_name']) ? $_SESSION['doctor_name'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
<title>Login / Register - K&amp;E Hospital</title>
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
    background:#f4f6fb;
    min-height:100vh;
    display:flex; flex-direction:column;
}
a { text-decoration:none; color:inherit; }
img { display:block; max-width:100%; }

/* ═══════════════════ PAGE BODY ═══════════════════ */
.page-body {
    flex:1; display:flex;
    align-items:center; justify-content:center;
    padding:3rem 1.25rem;
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* ═══════════════════ AUTH CARD ═══════════════════ */
.auth-container {
    background:#fff;
    border-radius:20px;
    width:100%; max-width:480px;
    box-shadow:0 25px 50px rgba(0,0,0,0.2);
    overflow:hidden;
    animation:slideUp 0.45s ease;
}
@keyframes slideUp {
    from { opacity:0; transform:translateY(28px); }
    to   { opacity:1; transform:translateY(0); }
}

/* Tabs */
.auth-tabs {
    display:flex; border-bottom:2px solid #e5e7eb;
}
.tab-btn {
    flex:1; background:none; border:none;
    padding:1.1rem; font-size:0.95rem; font-weight:600;
    font-family:'Outfit',sans-serif; cursor:pointer;
    color:#9ca3af; transition:all 0.2s; position:relative;
}
.tab-btn.active { color:#5f6fff; }
.tab-btn.active::after {
    content:''; position:absolute; bottom:-2px; left:0; right:0;
    height:2px; background:#5f6fff;
}
.tab-btn:hover:not(.active) { background:#f5f5ff; color:#5f6fff; }

/* Form inner */
.auth-form { padding:2rem 2.25rem 2.25rem; }
.form-title {
    font-size:1.45rem; font-weight:700; color:#1a1a2e; margin-bottom:0.3rem;
}
.form-subtitle { font-size:0.82rem; color:#9ca3af; margin-bottom:1.75rem; }

/* Alerts */
.alert {
    padding:0.75rem 1rem; border-radius:10px;
    font-size:0.82rem; margin-bottom:1.25rem;
}
.alert-error  { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.alert-success{ background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.alert ul { padding-left:1.25rem; margin:0; }

/* Role selector - Updated for 3 roles */
.role-selector { display:flex; gap:0.75rem; margin-bottom:1.25rem; }
.role-option {
    flex:1; text-align:center; padding:0.65rem 0.5rem;
    border:1.5px solid #e5e7eb; border-radius:10px;
    cursor:pointer; transition:all 0.2s; font-size:0.875rem;
    font-weight:500; color:#595959; background:#fff;
}
.role-option.active {
    border-color:#5f6fff; background:#eef0ff; color:#5f6fff; font-weight:600;
}
.role-option i { margin-right:5px; }

/* Form fields */
.form-group { margin-bottom:1.1rem; }
.form-group label {
    display:block; font-size:0.82rem; font-weight:500;
    color:#4b5264; margin-bottom:5px;
}
.form-group input,
.form-group select,
.form-group textarea {
    width:100%; padding:0.65rem 0.875rem;
    border:1.5px solid #e5e7eb; border-radius:10px;
    font-size:0.875rem; font-family:'Outfit',sans-serif;
    transition:all 0.2s; background:#fff; color:#1a1a2e;
    resize:none;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline:none; border-color:#5f6fff;
    box-shadow:0 0 0 3px rgba(95,111,255,0.1);
}
.input-wrap { position:relative; }
.input-wrap input { padding-right:2.5rem; }
.toggle-pw {
    position:absolute; right:0.75rem; top:50%;
    transform:translateY(-50%);
    background:none; border:none; cursor:pointer;
    color:#9ca3af; transition:color 0.2s; padding:0;
}
.toggle-pw:hover { color:#5f6fff; }

/* Password strength */
.strength-bar {
    height:4px; border-radius:4px; background:#e5e7eb;
    margin-top:6px; overflow:hidden;
}
.strength-bar__fill {
    height:100%; width:0; border-radius:4px;
    transition:width 0.3s ease, background 0.3s ease;
}
.strength-label { font-size:0.68rem; color:#9ca3af; margin-top:3px; }

/* Row layout */
.row { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }

/* Forgot */
.forgot-link {
    display:block; text-align:right;
    font-size:0.78rem; color:#5f6fff;
    margin-top:-0.5rem; margin-bottom:1.1rem;
}
.forgot-link:hover { text-decoration:underline; }

/* Submit */
.btn-submit {
    width:100%; padding:0.8rem;
    background:#5f6fff; color:#fff; border:none;
    border-radius:10px; font-size:0.92rem; font-weight:600;
    font-family:'Outfit',sans-serif; cursor:pointer;
    margin-top:0.5rem; transition:all 0.25s;
}
.btn-submit:hover {
    background:#4a5af0; transform:translateY(-1px);
    box-shadow:0 6px 18px rgba(95,111,255,0.35);
}

/* Divider */
.divider {
    display:flex; align-items:center; gap:0.75rem;
    margin:1.5rem 0; color:#9ca3af; font-size:0.78rem;
}
.divider::before, .divider::after {
    content:''; flex:1; height:1px; background:#e5e7eb;
}

/* Social btn */
.social-btn {
    display:flex; align-items:center; justify-content:center; gap:0.6rem;
    width:100%; padding:0.7rem;
    border:1.5px solid #e5e7eb; border-radius:10px;
    font-size:0.85rem; font-weight:500; background:#fff;
    cursor:pointer; transition:all 0.2s; font-family:'Outfit',sans-serif;
    color:#3c3c3c;
}
.social-btn:hover { border-color:#5f6fff; background:#f5f5ff; }

/* Switch link */
.switch-link {
    text-align:center; margin-top:1.5rem;
    font-size:0.82rem; color:#9ca3af;
}
.switch-link a { color:#5f6fff; font-weight:500; }
.switch-link a:hover { text-decoration:underline; }

/* Doctor note */
.doctor-note {
    background:#eef0ff;
    border-radius:8px;
    padding:0.75rem;
    margin-top:1rem;
    text-align:center;
    font-size:0.75rem;
    color:#5f6fff;
}
.doctor-note i { margin-right:0.25rem; }

/* ═══════════════════ RESPONSIVE ═══════════════════ */
@media (max-width:520px) {
    .auth-form { padding:1.5rem 1.25rem 1.75rem; }
    .row { grid-template-columns:1fr; }
    .role-selector { gap:0.5rem; }
    .role-option { font-size:0.75rem; padding:0.5rem 0.25rem; }
}
</style>
</head>
<body>

<!-- ═════════════ NAVBAR ═════════════ -->
<?php
$current_page = 'login.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
require_once 'navbar.php';
?>

<!-- ═════════════ AUTH BODY ═════════════ -->
<div class="page-body">
    <div class="auth-container">

        <!-- Tabs -->
        <div class="auth-tabs">
            <button class="tab-btn <?php echo $active_form==='login'    ? 'active':''; ?>" onclick="switchTab('login')">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            <button class="tab-btn <?php echo $active_form==='register' ? 'active':''; ?>" onclick="switchTab('register')">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </div>

        <!-- ── LOGIN FORM ── -->
        <div id="loginForm" class="auth-form" style="display:<?php echo $active_form==='login' ? 'block':'none'; ?>;">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Please login to book an appointment</p>

            <?php if ($success && $active_form==='login'): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors) && $active_form==='login'): ?>
                <div class="alert alert-error">
                    <ul><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=login" novalidate>

                <!-- Role selector with 3 options -->
                <div class="role-selector">
                    <div class="role-option active" data-role="patient" onclick="selectRole('patient')">
                        <i class="fas fa-user"></i> Patient
                    </div>
                    <div class="role-option" data-role="doctor" onclick="selectRole('doctor')">
                        <i class="fas fa-user-md"></i> Doctor
                    </div>
                    <div class="role-option" data-role="admin" onclick="selectRole('admin')">
                        <i class="fas fa-user-shield"></i> Admin
                    </div>
                </div>
                <input type="hidden" name="role" id="loginRole" value="patient">

                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" placeholder="you@example.com"
                           value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('loginPassword')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>

                <button type="submit" name="login" class="btn-submit">Login</button>
            </form>

            <div class="doctor-note">
                <i class="fas fa-info-circle"></i> Doctors: Use your registered email and password to access your dashboard.
            </div>

            <div class="divider">or continue with</div>
            <button class="social-btn" type="button" onclick="alert('Google login coming soon!')">
                <i class="fab fa-google"></i> Continue with Google
            </button>

            <div class="switch-link">
                Don't have an account? <a href="#" onclick="switchTab('register');return false;">Create one</a>
            </div>
        </div>

        <!-- ── REGISTER FORM (Patients Only) ── -->
        <div id="registerForm" class="auth-form" style="display:<?php echo $active_form==='register' ? 'block':'none'; ?>;">
            <h2 class="form-title">Create Account</h2>
            <p class="form-subtitle">Please sign up to book an appointment</p>

            <div class="doctor-note" style="background:#fef3c7; color:#d97706; margin-bottom:1rem;">
                <i class="fas fa-info-circle"></i> This registration is for patients only. Doctors should contact administration.
            </div>

            <?php if (!empty($errors) && $active_form==='register'): ?>
                <div class="alert alert-error">
                    <ul><?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=register" novalidate>

                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="full_name" placeholder="e.g. Mwila Banda"
                           value="<?php echo htmlspecialchars(isset($_POST['full_name']) ? $_POST['full_name'] : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" name="email" placeholder="you@example.com"
                           value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''); ?>" required>
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
                        <input type="tel" id="phone" name="phone" placeholder="+260 7XX XXX XXX"
                               value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="date_of_birth"
                               value="<?php echo htmlspecialchars(isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male"   <?php echo (isset($_POST['gender']) && $_POST['gender']==='Male')   ? 'selected':''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender']==='Female') ? 'selected':''; ?>>Female</option>
                            <option value="Other"  <?php echo (isset($_POST['gender']) && $_POST['gender']==='Other')  ? 'selected':''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="2"
                                  placeholder="Your address"><?php echo htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-submit">Create account</button>
            </form>

            <div class="switch-link">
                Already have an account? <a href="#" onclick="switchTab('login');return false;">Login here</a>
            </div>
        </div>

    </div><!-- /.auth-container -->
</div><!-- /.page-body -->


<script>
/* navbar JS handled by navbar.php */

/* ── Tab switching ── */
function switchTab(tab) {
    var lf   = document.getElementById('loginForm');
    var rf   = document.getElementById('registerForm');
    var tabs = document.querySelectorAll('.tab-btn');
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

/* ── Role selector (3 roles) ── */
function selectRole(role) {
    var opts = document.querySelectorAll('.role-option');
    var input = document.getElementById('loginRole');
    
    opts.forEach(opt => opt.classList.remove('active'));
    
    if (role === 'admin') {
        opts[2].classList.add('active');
        input.value = 'admin';
    } else if (role === 'doctor') {
        opts[1].classList.add('active');
        input.value = 'doctor';
    } else {
        opts[0].classList.add('active');
        input.value = 'patient';
    }
}

/* ── Password toggle ── */
function togglePassword(id) {
    var inp  = document.getElementById(id);
    var icon = inp.nextElementSibling.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye','fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash','fa-eye');
    }
}

/* ── Password strength ── */
function checkStrength(pw) {
    var s = 0;
    if (pw.length >= 8) s++;
    if (/[A-Z]/.test(pw)) s++;
    if (/[0-9]/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    var map = [
        { w:'0%',   bg:'transparent','t':'' },
        { w:'25%',  bg:'#ef4444',    t:'Weak' },
        { w:'50%',  bg:'#f97316',    t:'Fair' },
        { w:'75%',  bg:'#eab308',    t:'Good' },
        { w:'100%', bg:'#10b981',    t:'Strong' }
    ];
    var fill  = document.getElementById('sf');
    var label = document.getElementById('sl');
    fill.style.width      = map[s].w;
    fill.style.background = map[s].bg;
    label.textContent     = map[s].t;
    label.style.color     = map[s].bg;
}
</script>
</body>
</html>