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
    <link rel="stylesheet" href="./css/add-doctor.css">
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
                        <input type="text" name="name" placeholder="Dr. Agness Mwila" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
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