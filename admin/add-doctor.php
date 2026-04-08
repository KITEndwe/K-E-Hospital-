<?php
// K&E Hospital - Add Doctor Page
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

$db_found = false;
$db_paths = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/config/database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/KE-Hospital/config/database.php',
    '../config/database.php'
];

foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_found = true;
        break;
    }
}

if (!$db_found) die('Database configuration file not found.');

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id   = 'DOC' . date('Ymd') . rand(100, 999);
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $speciality  = trim($_POST['speciality'] ?? '');
    $degree      = trim($_POST['degree'] ?? '');
    $experience  = trim($_POST['experience'] ?? '');
    $about       = trim($_POST['about'] ?? '');
    $fees        = floatval($_POST['fees'] ?? 0);
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');

    $profile_image = '/assets/doctors/default-doctor.png';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext      = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'doctor_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_dir   = $_SERVER['DOCUMENT_ROOT'] . '/KE-Hospital/assets/doctors/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                $profile_image = '/assets/doctors/' . $new_filename;
            }
        } else {
            $error_message = 'Invalid file format. Please upload JPG, PNG, GIF, or WEBP images.';
        }
    }

    // Validation
    if (empty($name)) {
        $error_message = 'Please enter doctor\'s full name.';
    } elseif (empty($speciality)) {
        $error_message = 'Please select doctor\'s speciality.';
    } elseif (empty($degree)) {
        $error_message = 'Please enter doctor\'s degree.';
    } elseif (empty($experience)) {
        $error_message = 'Please enter doctor\'s experience.';
    } elseif ($fees <= 0) {
        $error_message = 'Please enter a valid consultation fee.';
    } elseif (empty($email)) {
        $error_message = 'Please enter doctor\'s email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (empty($password)) {
        $error_message = 'Please enter a password for the doctor.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->rowCount() > 0) {
            $error_message = 'A doctor with this email already exists.';
        }
    }

    if (empty($error_message)) {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO doctors (doctor_id, name, email, password, profile_image, speciality, degree, experience, about, fees, address_line1, address_line2, is_available, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $doctor_id, $name, $email, $hashed_password, $profile_image, 
                $speciality, $degree, $experience, $about, $fees, 
                $address_line1, $address_line2
            ]);
            
            $success_message = 'Doctor added successfully! Doctor ID: ' . $doctor_id . '<br>Email: ' . htmlspecialchars($email) . '<br>Password: The password has been set.';
            $_POST = [];
            
            // Reset form via JavaScript after successful submission
            echo "<script>setTimeout(function() { resetForm(); }, 2000);</script>";
        } catch (PDOException $e) {
            $error_message = 'Failed to add doctor: ' . $e->getMessage();
        }
    }
}

$admin_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Add Doctor - K&E Hospital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f5f7fb;
            color: #1f2937;
            overflow-x: hidden;
        }

        .dashboard-container { display: flex; min-height: 100vh; }

        /* ── SIDEBAR (dashboard style) ── */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: #f1f5f9; }
        .sidebar::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 3px; }

        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid #e5e7eb; }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-logo i { font-size: 1.8rem; color: #3b82f6; }
        .sidebar-logo span { color: #3b82f6; }

        .sidebar-nav { padding: 1.5rem 0; }

        .nav-item {
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            border-right: 3px solid transparent;
        }

        .nav-item:hover { background: #eef2ff; color: #3b82f6; }

        .nav-item.active {
            background: #eef2ff;
            color: #3b82f6;
            border-right: 3px solid #3b82f6;
        }

        .nav-item i { width: 24px; font-size: 1.1rem; }

        /* ── MAIN CONTENT ── */
        .main-content { flex: 1; margin-left: 280px; padding: 1.5rem; }

        .top-bar {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .page-title h1 { font-size: 1.5rem; font-weight: 700; color: #0f172a; }
        .page-title p { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }

        .user-info { display: flex; align-items: center; gap: 1.5rem; }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
        }

        .admin-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600;
        }

        .admin-name { font-weight: 500; color: #1e293b; }

        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }

        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220,38,38,0.3); }

        /* Form */
        .form-container {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .form-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e2e8f0; }
        .form-header h2 { font-size: 1.25rem; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; }
        .form-header h2 i { color: #3b82f6; }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #334155; font-size: 0.875rem; }
        .form-group label .required { color: #ef4444; margin-left: 0.25rem; }
        .form-group .password-hint { font-size: 0.7rem; color: #64748b; margin-top: 0.25rem; display: block; }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-family: inherit;
            font-size: 0.875rem;
            transition: all 0.3s;
            background: #fefefe;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }

        .form-group textarea { resize: vertical; min-height: 100px; }

        .image-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafbfc;
        }

        .image-upload:hover { border-color: #3b82f6; background: #f0f9ff; }
        .image-upload i { font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem; }
        .image-upload p { font-size: 0.875rem; color: #64748b; }
        .image-upload small { font-size: 0.75rem; color: #94a3b8; }

        .image-preview { margin-top: 1rem; display: none; }
        .image-preview img { max-width: 150px; border-radius: 0.75rem; border: 2px solid #e2e8f0; }

        .form-actions {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-submit {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-submit:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }

        .btn-reset {
            background: #f1f5f9;
            color: #475569;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-reset:hover { background: #e2e8f0; }

        .alert { padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert i { font-size: 1.25rem; }

        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }
        .password-strength-text {
            font-size: 0.7rem;
            margin-top: 0.25rem;
            color: #64748b;
        }

        .mobile-menu-toggle {
            display: none;
            background: none; border: none;
            cursor: pointer;
            width: 40px; height: 40px;
            border-radius: 0.5rem;
            font-size: 1.25rem;
            color: #1f2937;
        }

        .sidebar-overlay {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999; display: none;
        }

        .sidebar-overlay.active { display: block; }

        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 35px;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; z-index: 1000; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .mobile-menu-toggle { display: flex; align-items: center; justify-content: center; }
            .form-grid { grid-template-columns: 1fr; gap: 1rem; }
            .top-bar { flex-direction: column; gap: 1rem; text-align: center; }
            .user-info { width: 100%; justify-content: center; }
            .form-actions { flex-direction: column; }
            .btn-submit, .btn-reset { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <img src="assets/admin_logo.svg" width="150px" alt="">
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="appointments.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </a>
            <a href="add-doctor.php" class="nav-item active">
                <i class="fas fa-user-md"></i>
                <span>Add Doctor</span>
            </a>
            <a href="doctors-list.php" class="nav-item">
                <i class="fas fa-list"></i>
                <span>Doctors List</span>
            </a>
            <a href="patients.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Patients</span>
            </a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1>Add New Doctor</h1>
                    <p>Add a new medical professional to your hospital team</p>
                </div>
            </div>
            <div class="user-info">
                <div class="admin-badge">
                    <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
                    <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-user-md"></i> Doctor Registration Form</h2>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="doctorForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="name" placeholder="Dr. John Doe" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" placeholder="doctor@kehospital.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Min. 8 characters" required oninput="checkPasswordStrength(this.value)">
                            <i class="fas fa-eye show-password" onclick="togglePassword('password')"></i>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-strength-text" id="strengthText"></div>
                        <small class="password-hint">Password must be at least 8 characters with uppercase, lowercase, and numbers</small>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                            <i class="fas fa-eye show-password" onclick="togglePassword('confirm_password')"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Speciality <span class="required">*</span></label>
                        <select name="speciality" required>
                            <option value="">Select Speciality</option>
                            <?php
                            $specialities = ['General Physician','Gynecologist','Dermatologist','Pediatrician','Neurologist','Gastroenterologist','Cardiologist','Orthopedic'];
                            foreach ($specialities as $spec):
                                $sel = (isset($_POST['speciality']) && $_POST['speciality'] == $spec) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $spec; ?>" <?php echo $sel; ?>><?php echo $spec; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Degree <span class="required">*</span></label>
                        <input type="text" name="degree" placeholder="MBChB, MD, etc." required value="<?php echo htmlspecialchars($_POST['degree'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Experience <span class="required">*</span></label>
                        <input type="text" name="experience" placeholder="e.g., 5 Years" required value="<?php echo htmlspecialchars($_POST['experience'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Consultation Fee (K) <span class="required">*</span></label>
                        <input type="number" name="fees" placeholder="250" required step="0.01" value="<?php echo htmlspecialchars($_POST['fees'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address Line 1</label>
                        <input type="text" name="address_line1" placeholder="K&E Hospital" value="<?php echo htmlspecialchars($_POST['address_line1'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" name="address_line2" placeholder="Great East Road, Lusaka" value="<?php echo htmlspecialchars($_POST['address_line2'] ?? ''); ?>">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>Profile Image</label>
                        <div class="image-upload" onclick="document.getElementById('imageInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload doctor's photo</p>
                            <small>JPG, PNG, GIF up to 5MB</small>
                            <input type="file" id="imageInput" name="profile_image" accept="image/*" style="display:none;" onchange="previewImage(this)">
                        </div>
                        <div class="image-preview" id="imagePreview">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>About Doctor</label>
                        <textarea name="about" placeholder="Write a brief description about the doctor, their expertise, and qualifications..."><?php echo htmlspecialchars($_POST['about'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-reset" onclick="resetForm()">
                        <i class="fas fa-undo-alt"></i> Reset
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus-circle"></i> Add Doctor
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function closeMenu() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }
    function openMenu()  { sidebar.classList.add('open');    overlay.classList.add('active');    }

    mobileToggle?.addEventListener('click', e => { e.stopPropagation(); sidebar.classList.contains('open') ? closeMenu() : openMenu(); });
    overlay?.addEventListener('click', closeMenu);
    window.addEventListener('resize', () => { if (window.innerWidth > 768) closeMenu(); });

    // Image preview
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Password strength checker
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        let strength = 0;
        let message = '';
        let color = '';
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;
        
        switch(strength) {
            case 0:
            case 1:
                message = 'Very Weak';
                color = '#ef4444';
                strengthBar.style.width = '20%';
                break;
            case 2:
                message = 'Weak';
                color = '#f97316';
                strengthBar.style.width = '40%';
                break;
            case 3:
                message = 'Fair';
                color = '#eab308';
                strengthBar.style.width = '60%';
                break;
            case 4:
                message = 'Good';
                color = '#10b981';
                strengthBar.style.width = '80%';
                break;
            case 5:
                message = 'Strong';
                color = '#10b981';
                strengthBar.style.width = '100%';
                break;
        }
        
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = message;
        strengthText.style.color = color;
        
        if (password.length === 0) {
            strengthBar.style.width = '0%';
            strengthText.textContent = '';
        }
    }

    // Toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling;
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Reset form
    function resetForm() {
        document.getElementById('doctorForm').reset();
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('previewImg').src = '';
        document.getElementById('strengthBar').style.width = '0%';
        document.getElementById('strengthText').textContent = '';
    }

    // Form validation before submit
    document.getElementById('doctorForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const fee = document.querySelector('input[name="fees"]').value;
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            return false;
        }
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match.');
            return false;
        }
        
        if (fee <= 0) {
            e.preventDefault();
            alert('Please enter a valid consultation fee.');
            return false;
        }
        
        return true;
    });
</script>
</body>
</html>