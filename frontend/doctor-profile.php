<?php
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header('Location: doctor-login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$doctor_id   = $_SESSION['doctor_id'];
$doctor_name = isset($_SESSION['doctor_name']) ? $_SESSION['doctor_name'] : 'Doctor';

$doctor = array();
$success_msg = '';
$error_msg = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /* Fetch doctor profile - using correct column names */
    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            COUNT(a.appointment_id) as total_appointments,
            SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN a.status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_appointments,
            COALESCE(SUM(p.amount), 0) as total_earnings
        FROM doctors d
        LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
        LEFT JOIN payments p ON a.appointment_id = p.appointment_id AND p.payment_status = 'Completed'
        WHERE d.doctor_id = ?
        GROUP BY d.doctor_id
    ");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();
    
    if (!$doctor) {
        session_destroy();
        header('Location: doctor-login.php');
        exit();
    }

    /* Handle profile update */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $name         = trim($_POST['name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $speciality   = trim($_POST['speciality'] ?? '');
        $degree       = trim($_POST['degree'] ?? '');
        $experience   = trim($_POST['experience'] ?? '');
        $about        = trim($_POST['about'] ?? '');
        $fees         = floatval($_POST['fees'] ?? 0);
        $address_line1 = trim($_POST['address_line1'] ?? '');
        $address_line2 = trim($_POST['address_line2'] ?? '');
        $consultation_duration = intval($_POST['consultation_duration'] ?? 30);
        
        $errors = [];
        
        if (empty($name)) $errors[] = "Name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (empty($speciality)) $errors[] = "Speciality is required";
        if ($fees <= 0) $errors[] = "Valid consultation fee is required";
        
        // Check if email already exists for another doctor
        $check_email = $pdo->prepare("SELECT doctor_id FROM doctors WHERE email = ? AND doctor_id != ?");
        $check_email->execute([$email, $doctor_id]);
        if ($check_email->fetch()) {
            $errors[] = "Email already used by another doctor";
        }
        
        // Handle profile image upload
        $profile_image = $doctor['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $file_size = $_FILES['profile_image']['size'];
            
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "Image size must be less than 5MB";
            } elseif (in_array($file_ext, $allowed)) {
                $upload_dir = '../uploads/doctors/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = 'doctor_' . $doctor_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old image if not default
                    if ($profile_image && $profile_image != '/frontend/assets/upload_area.png' && file_exists('../' . ltrim($profile_image, '/'))) {
                        @unlink('../' . ltrim($profile_image, '/'));
                    }
                    $profile_image = 'uploads/doctors/' . $new_filename;
                } else {
                    $errors[] = "Failed to upload image";
                }
            } else {
                $errors[] = "Invalid image format. Allowed: JPG, JPEG, PNG, GIF, WEBP";
            }
        }
        
        if (empty($errors)) {
            // Build dynamic UPDATE query based on available columns
            $update_fields = [];
            $update_values = [];
            
            // Always update these fields
            $update_fields[] = "name = ?";
            $update_values[] = $name;
            $update_fields[] = "email = ?";
            $update_values[] = $email;
            $update_fields[] = "speciality = ?";
            $update_values[] = $speciality;
            $update_fields[] = "fees = ?";
            $update_values[] = $fees;
            $update_fields[] = "profile_image = ?";
            $update_values[] = $profile_image;
            
            // Optional fields - check if column exists before adding
            if (isset($doctor['phone']) || true) {
                $update_fields[] = "phone = ?";
                $update_values[] = $phone;
            }
            if (isset($doctor['degree']) || true) {
                $update_fields[] = "degree = ?";
                $update_values[] = $degree;
            }
            if (isset($doctor['experience']) || true) {
                $update_fields[] = "experience = ?";
                $update_values[] = $experience;
            }
            if (isset($doctor['about']) || true) {
                $update_fields[] = "about = ?";
                $update_values[] = $about;
            }
            if (isset($doctor['address_line1']) || true) {
                $update_fields[] = "address_line1 = ?";
                $update_values[] = $address_line1;
            }
            if (isset($doctor['address_line2']) || true) {
                $update_fields[] = "address_line2 = ?";
                $update_values[] = $address_line2;
            }
            if (isset($doctor['consultation_duration']) || true) {
                $update_fields[] = "consultation_duration = ?";
                $update_values[] = $consultation_duration;
            }
            
            $update_fields[] = "updated_at = NOW()";
            $update_values[] = $doctor_id;
            
            $sql = "UPDATE doctors SET " . implode(", ", $update_fields) . " WHERE doctor_id = ?";
            $update = $pdo->prepare($sql);
            $update->execute($update_values);
            
            // Update session
            $_SESSION['doctor_name'] = $name;
            
            $success_msg = "Profile updated successfully!";
            
            // Refresh doctor data
            $stmt->execute([$doctor_id]);
            $doctor = $stmt->fetch();
        } else {
            $error_msg = implode(", ", $errors);
        }
    }
    
    /* Handle password change */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_msg = "Please fill in all password fields";
        } elseif ($new_password !== $confirm_password) {
            $error_msg = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $error_msg = "New password must be at least 6 characters";
        } else {
            // Verify current password
            $check_pass = $pdo->prepare("SELECT password FROM doctors WHERE doctor_id = ?");
            $check_pass->execute([$doctor_id]);
            $stored_hash = $check_pass->fetchColumn();
            
            if (password_verify($current_password, $stored_hash)) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass = $pdo->prepare("UPDATE doctors SET password = ? WHERE doctor_id = ?");
                $update_pass->execute([$new_hash, $doctor_id]);
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Current password is incorrect";
            }
        }
    }
    
} catch (PDOException $e) {
    $error_msg = 'Database error: ' . $e->getMessage();
}

// Helper function for profile image
function getDoctorImage($image_path) {
    if (empty($image_path)) return '';
    $path = ltrim($image_path, '/');
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    if (file_exists('../' . $path)) {
        return '../' . $path;
    }
    return '';
}

$profile_img = getDoctorImage($doctor['profile_image'] ?? '');
$initials = strtoupper(substr($doctor['name'] ?? 'Doctor', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Profile - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="./Css/doctor-profile.css">
</head>
<body>
<div class="layout">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="assets/logo.svg" width="150" alt="K&E Hospital">
    </div>
    <div class="doc-profile-card">
        <div class="doc-avatar-wrap">
            <?php if ($profile_img): ?>
                <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>">
            <?php else: ?>
                <?php echo $initials; ?>
            <?php endif; ?>
        </div>
        <div class="doc-profile-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></div>
        <div class="doc-profile-spec"><?php echo htmlspecialchars($doctor['speciality']); ?></div>
        <span class="doc-avail-badge"><i class="fas fa-circle"></i> Available</span>
    </div>
    <nav class="sidebar-nav">
        <a href="doctor-dashboard.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
        <a href="doctor-profile.php" class="nav-link active"><i class="fas fa-user-md"></i> My Profile</a>
        <a href="doctor-logout.php" class="nav-link" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
            <h1>My Profile</h1>
        </div>
        <a href="doctor-logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <?php if ($success_msg): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="profile-container">
        <div class="profile-sidebar-card">
            <div class="profile-image-section">
                <div class="profile-main-avatar">
                    <?php if ($profile_img): ?>
                        <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>" id="profilePreview">
                    <?php else: ?>
                        <span id="profileInitial"><?php echo $initials; ?></span>
                    <?php endif; ?>
                </div>
                <div class="profile-name-large">Dr. <?php echo htmlspecialchars($doctor['name']); ?></div>
                <div class="profile-speciality"><?php echo htmlspecialchars($doctor['speciality']); ?></div>
                <div class="stats-row">
                    <div class="stat-item"><div class="stat-value"><?php echo $doctor['total_appointments'] ?? 0; ?></div><div class="stat-label">Patients</div></div>
                    <div class="stat-item"><div class="stat-value"><?php echo $doctor['completed_appointments'] ?? 0; ?></div><div class="stat-label">Completed</div></div>
                    <div class="stat-item"><div class="stat-value">K<?php echo number_format($doctor['total_earnings'] ?? 0, 0); ?></div><div class="stat-label">Earnings</div></div>
                </div>
                <button class="edit-profile-btn" onclick="document.getElementById('profileForm').scrollIntoView({behavior:'smooth'})"><i class="fas fa-edit"></i> Edit Profile</button>
            </div>
        </div>

        <div class="profile-main-card">
            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <div class="form-section">
                    <h3><i class="fas fa-user-md"></i> Professional Information</h3>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-user"></i> Full Name *</label><input type="text" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required></div>
                        <div class="form-group"><label><i class="fas fa-envelope"></i> Email *</label><input type="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-phone"></i> Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>" placeholder="+260 XXX XXX XXX"></div>
                        <div class="form-group"><label><i class="fas fa-stethoscope"></i> Speciality *</label><input type="text" name="speciality" value="<?php echo htmlspecialchars($doctor['speciality']); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-graduation-cap"></i> Degree</label><input type="text" name="degree" value="<?php echo htmlspecialchars($doctor['degree'] ?? ''); ?>" placeholder="MBChB, MD"></div>
                        <div class="form-group"><label><i class="fas fa-briefcase"></i> Experience</label><input type="text" name="experience" value="<?php echo htmlspecialchars($doctor['experience'] ?? ''); ?>" placeholder="5 Years"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-money-bill"></i> Consultation Fee (K) *</label><input type="number" name="fees" value="<?php echo htmlspecialchars($doctor['fees'] ?? 0); ?>" step="10" required></div>
                        <div class="form-group"><label><i class="fas fa-clock"></i> Duration (mins)</label><input type="number" name="consultation_duration" value="<?php echo htmlspecialchars($doctor['consultation_duration'] ?? 30); ?>"></div>
                    </div>
                    <div class="form-group"><label><i class="fas fa-file-alt"></i> About / Bio</label><textarea name="about" placeholder="Write a short bio..."><?php echo htmlspecialchars($doctor['about'] ?? ''); ?></textarea></div>
                    <div class="form-group"><label><i class="fas fa-camera"></i> Profile Image</label><input type="file" name="profile_image" accept="image/*" id="profileImageInput"><small style="color:#94a3b8;"> JPG, PNG, GIF (Max 5MB)</small>
                        <div class="image-preview" id="imagePreview"><img id="previewImg" src="" alt="Preview"></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-save"><i class="fas fa-save"></i> Update Changes</button>
                        <button type="reset" class="btn-cancel" onclick="window.location.reload();"><i class="fas fa-undo"></i> Cancel</button>
                    </div>
                </div>
            </form>

            <div class="password-section">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
                <form method="POST" onsubmit="return validatePasswordForm()">
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-key"></i> Current Password</label><input type="password" name="current_password" id="current_password" required></div>
                        <div class="form-group"><label><i class="fas fa-lock"></i> New Password</label><input type="password" name="new_password" id="new_password" required></div>
                        <div class="form-group"><label><i class="fas fa-check"></i> Confirm Password</label><input type="password" name="confirm_password" id="confirm_password" required></div>
                    </div>
                    <button type="submit" name="change_password" class="btn-save" style="background:#64748b;"><i class="fas fa-key"></i> Change Password</button>
                </form>
            </div>
        </div>
    </div>
</main>
</div>

<script>
// Sidebar toggle
var btn=document.getElementById('hamburgerBtn');
var sb=document.getElementById('sidebar');
var ov=document.getElementById('sidebarOverlay');
if(btn){btn.onclick=function(){sb.classList.toggle('open');ov.classList.toggle('active');};}
if(ov){ov.onclick=function(){sb.classList.remove('open');ov.classList.remove('active');};}

// Image preview
document.getElementById('profileImageInput')?.addEventListener('change', function(e) {
    var file = e.target.files[0];
    var preview = document.getElementById('imagePreview');
    var previewImg = document.getElementById('previewImg');
    if(file){
        var reader = new FileReader();
        reader.onload = function(e){previewImg.src=e.target.result;preview.classList.add('active');}
        reader.readAsDataURL(file);
    }else{preview.classList.remove('active');}
});

// Password validation
function validatePasswordForm(){
    var newPass = document.getElementById('new_password').value;
    var confirmPass = document.getElementById('confirm_password').value;
    if(newPass.length<6){alert('Password must be at least 6 characters');return false;}
    if(newPass!==confirmPass){alert('Passwords do not match');return false;}
    return true;
}
</script>
</body>
</html>