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
    $blood_group  = trim(isset($_POST['blood_group'])  ? $_POST['blood_group']  : '');

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
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, date_of_birth, gender, address, blood_group, is_active) VALUES (?,?,?,?,?,?,?,?,1)");
        try {
            $stmt->execute(array($full_name, $email, $hashed, $phone, $date_of_birth, $gender, $address, $blood_group));
            $success = 'Account created successfully! Please login.';
            $active_form = 'login';
            $errors = array(); // Clear errors
        } catch(PDOException $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
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
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? AND is_active = 1");
            $stmt->execute(array($email));
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin && password_verify($pass, $admin['password'])) {
                $_SESSION['user_id']   = $admin['admin_id'];
                $_SESSION['full_name'] = $admin['full_name'];
                $_SESSION['email']     = $admin['email'];
                $_SESSION['role']      = 'admin';
                $_SESSION['is_admin']  = true;
                $_SESSION['profile_image'] = $admin['profile_image'] ?? '';
                
                $upd = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE admin_id = ?");
                $upd->execute(array($admin['admin_id']));
                header('Location: ../admin/dashboard.php'); 
                exit();
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        }
        // Doctor Login - FIXED
        elseif ($role === 'doctor') {
            // Remove is_available condition temporarily for testing
            $stmt = $pdo->prepare("SELECT * FROM doctors WHERE email = ?");
            $stmt->execute(array($email));
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug - uncomment to see what's happening
            // error_log("Doctor login attempt - Email: " . $email);
            // error_log("Doctor found: " . ($doctor ? 'Yes' : 'No'));
            // if($doctor) error_log("Password in DB: " . $doctor['password']);
            
            if ($doctor) {
                // Check if password exists and verify
                if (!empty($doctor['password']) && password_verify($pass, $doctor['password'])) {
                    $_SESSION['doctor_id']    = $doctor['doctor_id'];
                    $_SESSION['doctor_name']  = $doctor['name'];
                    $_SESSION['doctor_email'] = $doctor['email'];
                    $_SESSION['doctor_spec']  = $doctor['speciality'];
                    $_SESSION['role']         = 'doctor';
                    $_SESSION['is_doctor']    = true;
                    $_SESSION['profile_image'] = $doctor['profile_image'] ?? '';
                    
                    header('Location: doctor-dashboard.php'); 
                    exit();
                } else {
                    $errors[] = 'Invalid password. Please check your credentials.';
                }
            } else {
                $errors[] = 'Doctor not found with this email.';
            }
        }
        // Patient Login
        else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute(array($email));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($pass, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = 'patient';
                $_SESSION['is_admin']  = false;
                $_SESSION['profile_image'] = $user['profile_image'] ?? '';
                
                header('Location: index.php'); 
                exit();
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

// Clear errors for display
$display_errors = $errors;
$display_success = $success;
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
<link rel="stylesheet" href="./Css/login.css">
</head>
<body>

<!-- ═════════════ NAVBAR ═════════════ -->
<?php
$current_page = 'login.php';
$profile_image = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : '';
if (file_exists('navbar.php')) {
    require_once 'navbar.php';
}
?>

<!-- ═════════════ AUTH BODY ═════════════ -->
<div class="page-body">
    <div class="auth-container">

        <!-- Tabs -->
        <div class="auth-tabs">
            <button class="tab-btn <?php echo $active_form==='login' ? 'active':''; ?>" onclick="switchTab('login')">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            <button class="tab-btn <?php echo $active_form==='register' ? 'active':''; ?>" onclick="switchTab('register')">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </div>

        <!-- ── LOGIN FORM ── -->
        <div id="loginForm" class="auth-form" style="display:<?php echo $active_form==='login' ? 'block':'none'; ?>;">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Select your role and login to continue</p>

            <?php if ($display_success && $active_form==='login'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($display_success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($display_errors) && $active_form==='login'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin-top:5px;">
                        <?php foreach($display_errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
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
                    <label for="loginEmail"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="loginEmail" name="email" placeholder="you@example.com"
                           value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="loginPassword"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrap">
                        <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('loginPassword')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>

                <button type="submit" name="login" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <!-- Dynamic info note -->
            <div id="infoNote" class="info-note">
                <i class="fas fa-info-circle"></i> 
                <span id="infoNoteText">Patients: Login to book appointments and view the profile and edit.</span>
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
            <h2 class="form-title">Create Patient Account</h2>
            <p class="form-subtitle">Sign up to book appointments with our doctors</p>

            <div class="info-note warning" style="margin-bottom:1rem;">
                <i class="fas fa-info-circle"></i> 
                This registration is for <strong>patients only</strong>. Doctors should contact hospital administration.
            </div>

            <?php if (!empty($display_errors) && $active_form==='register'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin-top:5px;">
                        <?php foreach($display_errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="?action=register" novalidate>

                <div class="form-group">
                    <label for="fullName"><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                    <input type="text" id="fullName" name="full_name" placeholder="e.g., Mwange Sylvia"
                           value="<?php echo htmlspecialchars(isset($_POST['full_name']) ? $_POST['full_name'] : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="regEmail"><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                    <input type="email" id="regEmail" name="email" placeholder="you@example.com"
                           value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="regPassword"><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
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
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+260 7XX XXX XXX"
                               value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="dob"><i class="fas fa-calendar"></i> Date of Birth</label>
                        <input type="date" id="dob" name="date_of_birth"
                               value="<?php echo htmlspecialchars(isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : ''); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male"   <?php echo (isset($_POST['gender']) && $_POST['gender']==='Male')   ? 'selected':''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender']==='Female') ? 'selected':''; ?>>Female</option>
                            <option value="Other"  <?php echo (isset($_POST['gender']) && $_POST['gender']==='Other')  ? 'selected':''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blood_group"><i class="fas fa-tint"></i> Blood Group</label>
                        <select id="blood_group" name="blood_group">
                            <option value="">Select Blood Group</option>
                            <option value="A+"  <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='A+')  ? 'selected':''; ?>>A+</option>
                            <option value="A-"  <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='A-')  ? 'selected':''; ?>>A-</option>
                            <option value="B+"  <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='B+')  ? 'selected':''; ?>>B+</option>
                            <option value="B-"  <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='B-')  ? 'selected':''; ?>>B-</option>
                            <option value="AB+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='AB+') ? 'selected':''; ?>>AB+</option>
                            <option value="AB-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='AB-') ? 'selected':''; ?>>AB-</option>
                            <option value="O+"  <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='O+')  ? 'selected':''; ?>>O+</option>
                            <option value="O-"  <?php echo (isset($_POST['blood_group']) && $_POST['blood_group']==='O-')  ? 'selected':''; ?>>O-</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address"><i class="fas fa-location-dot"></i> Address</label>
                    <textarea id="address" name="address" rows="2"
                              placeholder="Your residential address"><?php echo htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : ''); ?></textarea>
                </div>

                <button type="submit" name="register" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="switch-link">
                Already have an account? <a href="#" onclick="switchTab('login');return false;">Login here</a>
            </div>
        </div>

    </div><!-- /.auth-container -->
</div><!-- /.page-body -->

<script>
// Tab switching
function switchTab(tab) {
    var lf = document.getElementById('loginForm');
    var rf = document.getElementById('registerForm');
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

// Role selector with dynamic info text
function selectRole(role) {
    var opts = document.querySelectorAll('.role-option');
    var input = document.getElementById('loginRole');
    var infoText = document.getElementById('infoNoteText');
    
    opts.forEach(opt => opt.classList.remove('active'));
    
    if (role === 'admin') {
        opts[2].classList.add('active');
        input.value = 'admin';
        if (infoText) infoText.innerHTML = 'Admin: Login to manage hospital operations, doctors, and appointments.';
    } else if (role === 'doctor') {
        opts[1].classList.add('active');
        input.value = 'doctor';
        if (infoText) infoText.innerHTML = 'Doctors: Login to manage your appointments, patients, and edit your profile.';
    } else {
        opts[0].classList.add('active');
        input.value = 'patient';
        if (infoText) infoText.innerHTML = 'Patients: Login to book appointments and view/edit your profile.';
    }
}

// Password toggle
function togglePassword(id) {
    var inp = document.getElementById(id);
    var icon = inp.nextElementSibling.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
function checkStrength(pw) {
    var s = 0;
    if (pw.length >= 8) s++;
    if (/[A-Z]/.test(pw)) s++;
    if (/[0-9]/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    
    var strengthMap = [
        { width: '0%', bg: 'transparent', text: '' },
        { width: '25%', bg: '#ef4444', text: 'Weak' },
        { width: '50%', bg: '#f97316', text: 'Fair' },
        { width: '75%', bg: '#eab308', text: 'Good' },
        { width: '100%', bg: '#10b981', text: 'Strong' }
    ];
    
    var fill = document.getElementById('sf');
    var label = document.getElementById('sl');
    
    if (pw.length === 0) {
        fill.style.width = '0%';
        fill.style.background = 'transparent';

















        label.textContent = '';
        return;
    }
    
    fill.style.width = strengthMap[s].width;
    fill.style.background = strengthMap[s].bg;
    label.textContent = strengthMap[s].text;
    label.style.color = strengthMap[s].bg;
}

// Set default role on page load
document.addEventListener('DOMContentLoaded', function() {
    selectRole('patient');
});
</script>
</body>
</html>