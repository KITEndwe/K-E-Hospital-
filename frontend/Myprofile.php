<?php
// K&E Hospital - User Profile Page
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$user_id      = $_SESSION['user_id'];
$user_name    = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$current_page = 'my-profile.php';

$profile         = array();
$success_message = '';
$error_message   = '';
$is_editing_contact = isset($_GET['edit_contact']) && $_GET['edit_contact'] == 'true';
$is_editing_basic   = isset($_GET['edit_basic'])   && $_GET['edit_basic']   == 'true';

/*
 * Upload directory is  /KE-Hospital/uploads/profiles/
 * This file lives at   /KE-Hospital/frontend/my-profile.php
 * So __DIR__ = …/frontend  →  go one level up with dirname(__DIR__)
 */
$upload_dir_abs  = dirname(__DIR__) . '/uploads/profiles/';
$upload_dir_web  = '../uploads/profiles/';   // web path relative to frontend/

if (!file_exists($upload_dir_abs)) {
    mkdir($upload_dir_abs, 0755, true);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch user
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, date_of_birth, gender, address, profile_image, created_at FROM users WHERE user_id = ?");
    $stmt->execute(array($user_id));
    $profile = $stmt->fetch();

    if (!$profile) {
        $stmt = $pdo->prepare("SELECT admin_id AS user_id, full_name, email, phone, NULL AS date_of_birth, NULL AS gender, NULL AS address, profile_image, created_at FROM admin WHERE admin_id = ?");
        $stmt->execute(array($user_id));
        $profile = $stmt->fetch();
    }

    /* ── Handle profile image upload ── */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {

        if (empty($_FILES['profile_image']['name']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $error_message = 'Please select an image to upload.';

        } elseif ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            $codes = array(
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'No temporary folder on server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
            );
            $error_message = isset($codes[$_FILES['profile_image']['error']]) ? $codes[$_FILES['profile_image']['error']] : 'Upload error code: ' . $_FILES['profile_image']['error'];

        } else {
            $allowed_types = array('image/jpeg','image/jpg','image/png','image/gif','image/webp');
            $allowed_exts  = array('jpg','jpeg','png','gif','webp');

            $file_tmp  = $_FILES['profile_image']['tmp_name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_name = $_FILES['profile_image']['name'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate MIME type using finfo (more secure than extension alone)
            $finfo     = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file_tmp);

            if ($file_size > 5 * 1024 * 1024) {
                $error_message = 'File too large. Maximum allowed size is 5 MB.';
            } elseif (!in_array($file_ext, $allowed_exts) || !in_array($mime_type, $allowed_types)) {
                $error_message = 'Invalid file type. Only JPG, PNG, GIF and WEBP images are allowed.';
            } else {
                // Unique filename
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                $dest_abs     = $upload_dir_abs . $new_filename;
                // Web path stored in DB (relative to project root, e.g. uploads/profiles/user_1_xxx.jpg)
                $dest_db      = 'uploads/profiles/' . $new_filename;
                // Web path used in <img> src (relative to frontend/)
                $dest_web     = $upload_dir_web . $new_filename;

                if (move_uploaded_file($file_tmp, $dest_abs)) {
                    // Delete old image if it exists
                    if (!empty($profile['profile_image'])) {
                        $old_abs = dirname(__DIR__) . '/' . $profile['profile_image'];
                        if (file_exists($old_abs)) {
                            unlink($old_abs);
                        }
                    }

                    $upd = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                    if ($upd->execute(array($dest_db, $user_id))) {
                        $profile['profile_image'] = $dest_db;
                        $_SESSION['profile_image'] = $dest_web; // for navbar
                        $success_message = 'Profile picture updated successfully!';
                    } else {
                        $error_message = 'Image uploaded but database update failed.';
                    }
                } else {
                    $error_message = 'Could not save the file. Check that ' . $upload_dir_abs . ' is writable (chmod 755).';
                }
            }
        }
    }

    /* ── Handle contact information update ── */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
        $full_name = trim(isset($_POST['full_name']) ? $_POST['full_name'] : '');
        $phone     = trim(isset($_POST['phone'])     ? $_POST['phone']     : '');
        $address   = trim(isset($_POST['address'])   ? $_POST['address']   : '');
        $email     = trim(isset($_POST['email'])     ? $_POST['email']     : '');

        if (empty($full_name)) {
            $error_message = 'Full name is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'A valid email address is required.';
        } else {
            $upd = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, email = ? WHERE user_id = ?");
            if ($upd->execute(array($full_name, $phone, $address, $email, $user_id))) {
                $_SESSION['full_name']  = $full_name;
                $profile['full_name']   = $full_name;
                $profile['phone']       = $phone;
                $profile['address']     = $address;
                $profile['email']       = $email;
                $success_message        = 'Contact information updated successfully!';
                $is_editing_contact     = false;
            } else {
                $error_message = 'Failed to update contact information.';
            }
        }
    }

    /* ── Handle basic information update ── */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_basic'])) {
        $date_of_birth = trim(isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '');
        $gender        = trim(isset($_POST['gender'])        ? $_POST['gender']        : '');

        $upd = $pdo->prepare("UPDATE users SET date_of_birth = ?, gender = ? WHERE user_id = ?");
        if ($upd->execute(array($date_of_birth ? $date_of_birth : null, $gender ? $gender : null, $user_id))) {
            $profile['date_of_birth'] = $date_of_birth;
            $profile['gender']        = $gender;
            $success_message          = 'Basic information updated successfully!';
            $is_editing_basic         = false;
        } else {
            $error_message = 'Failed to update basic information.';
        }
    }

} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}

/* Determine avatar image web path */
$avatar_web = '';
if (!empty($profile['profile_image'])) {
    // profile_image in DB is like  uploads/profiles/user_1_xxx.jpg  (relative to project root)
    // this file is in frontend/ so prepend ../
    $candidate = '../' . $profile['profile_image'];
    $candidate_abs = dirname(__DIR__) . '/' . $profile['profile_image'];
    if (file_exists($candidate_abs)) {
        $avatar_web = $candidate;
    }
}

// Update session profile_image for navbar dropdown
if ($avatar_web) {
    $_SESSION['profile_image'] = $avatar_web;
}

$is_logged_in  = true;
$user_name     = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$profile_image = $avatar_web;

function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') return 'Not specified';
    return date('j F, Y', strtotime($date));
}

$user_initials = strtoupper(substr(isset($profile['full_name']) ? $profile['full_name'] : $user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - K&amp;E Hospital</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior:smooth; }
body { font-family:'Outfit',sans-serif; background:#f5f7fb; color:#1e293b; line-height:1.5; }
a { text-decoration:none; color:inherit; }
img { display:block; max-width:100%; }

/* ── Page wrapper ── */
.profile-container {
    max-width:1100px; margin:2rem auto; padding:0 1.5rem;
}

/* ── Alerts ── */
.alert {
    padding:0.9rem 1.1rem; border-radius:12px;
    margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;
    font-size:0.9rem; font-weight:500;
}
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

/* ── Profile header ── */
.profile-header {
    background:linear-gradient(135deg,#5f6fff 0%,#7b8bff 60%,#9ea8ff 100%);
    border-radius:20px; padding:2rem 2.5rem;
    margin-bottom:2rem; color:#fff;
    display:flex; align-items:center; gap:2rem; flex-wrap:wrap;
}

/* Avatar */
.profile-avatar { position:relative; flex-shrink:0; }
.avatar-large {
    width:110px; height:110px; border-radius:50%;
    background:rgba(255,255,255,0.2);
    border:3px solid rgba(255,255,255,0.45);
    display:flex; align-items:center; justify-content:center;
    font-size:2.75rem; font-weight:700; color:#fff;
    overflow:hidden; cursor:pointer;
    transition:all 0.25s;
}
.avatar-large:hover { border-color:rgba(255,255,255,0.8); }
.avatar-large img {
    width:100%; height:100%; object-fit:cover; object-position:center;
    display:block;
}

/* Camera button overlay */
.camera-btn {
    position:absolute; bottom:4px; right:4px;
    width:32px; height:32px; border-radius:50%;
    background:#fff; color:#5f6fff;
    display:flex; align-items:center; justify-content:center;
    font-size:0.8rem; cursor:pointer;
    box-shadow:0 2px 8px rgba(0,0,0,0.18);
    transition:all 0.2s; border:none;
}
.camera-btn:hover { transform:scale(1.1); background:#eef0ff; }

.profile-title h1 { font-size:1.75rem; font-weight:700; margin-bottom:0.5rem; }
.profile-title .meta {
    display:flex; flex-wrap:wrap; gap:1.25rem;
    font-size:0.875rem; opacity:0.9; margin-top:0.3rem;
}
.profile-title .meta span { display:flex; align-items:center; gap:0.4rem; }

/* ── Grid ── */
.profile-grid {
    display:grid; grid-template-columns:1fr 1.1fr; gap:1.5rem;
}

/* ── Cards ── */
.card {
    background:#fff; border-radius:18px; padding:1.5rem;
    box-shadow:0 2px 12px rgba(0,0,0,0.05);
    margin-bottom:1.5rem;
}
.card-header {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:1.25rem; padding-bottom:0.75rem;
    border-bottom:2px solid #f0f2f5;
}
.card-header h2 {
    font-size:1.05rem; font-weight:600; color:#1e293b;
    display:flex; align-items:center; gap:0.5rem;
}
.card-header h2 i { color:#5f6fff; font-size:1rem; }

.btn-edit {
    background:#eef0ff; color:#5f6fff; border:none;
    padding:0.45rem 1.1rem; border-radius:50px;
    font-size:0.8rem; font-weight:600; cursor:pointer;
    transition:all 0.2s; font-family:'Outfit',sans-serif;
    display:inline-flex; align-items:center; gap:0.4rem;
}
.btn-edit:hover { background:#5f6fff; color:#fff; }
.btn-save {
    background:#5f6fff; color:#fff; border:none;
    padding:0.55rem 1.3rem; border-radius:50px;
    font-size:0.85rem; font-weight:600; cursor:pointer;
    transition:all 0.2s; font-family:'Outfit',sans-serif;
    display:inline-flex; align-items:center; gap:0.4rem;
}
.btn-save:hover { background:#4a5af0; transform:translateY(-1px); box-shadow:0 4px 12px rgba(95,111,255,0.3); }
.btn-cancel {
    background:#f1f5f9; color:#64748b; border:none;
    padding:0.55rem 1.1rem; border-radius:50px;
    font-size:0.85rem; font-weight:500; cursor:pointer;
    transition:all 0.2s; font-family:'Outfit',sans-serif;
    display:inline-flex; align-items:center; gap:0.4rem;
}
.btn-cancel:hover { background:#e2e8f0; }

/* Info rows */
.info-row {
    display:flex; padding:0.875rem 0;
    border-bottom:1px solid #f0f2f5;
}
.info-row:last-child { border-bottom:none; }
.info-label { width:130px; flex-shrink:0; font-weight:500; color:#64748b; font-size:0.82rem; padding-top:0.1rem; }
.info-value { flex:1; color:#1e293b; font-size:0.9rem; font-weight:500; }

/* Form */
.form-group { margin-bottom:1rem; }
.form-group label {
    display:block; font-size:0.8rem; font-weight:500;
    color:#64748b; margin-bottom:0.4rem;
}
.form-group input,
.form-group select,
.form-group textarea {
    width:100%; padding:0.65rem 0.9rem;
    border:1.5px solid #e2e8f0; border-radius:10px;
    font-family:'Outfit',sans-serif; font-size:0.875rem;
    color:#1e293b; transition:all 0.2s; background:#fff;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline:none; border-color:#5f6fff;
    box-shadow:0 0 0 3px rgba(95,111,255,0.1);
}
.form-group textarea { resize:vertical; min-height:75px; }
.form-actions {
    display:flex; justify-content:flex-end; gap:0.6rem;
    margin-top:1.25rem; padding-top:1rem; border-top:1px solid #f0f2f5;
}

/* Gender radios */
.gender-options { display:flex; flex-wrap:wrap; gap:1rem; }
.gender-option {
    display:flex; align-items:center; gap:0.45rem;
    cursor:pointer; font-size:0.875rem; font-weight:500;
}
.gender-option input[type="radio"] { width:auto; accent-color:#5f6fff; cursor:pointer; }

/* ═══════════ MODAL ═══════════ */
.modal-backdrop {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.45); z-index:1050;
    align-items:center; justify-content:center;
    padding:1rem;
}
.modal-backdrop.active { display:flex; }

.modal-box {
    background:#fff; border-radius:20px; padding:2rem;
    max-width:440px; width:100%;
    box-shadow:0 24px 48px rgba(0,0,0,0.2);
    animation:popIn 0.25s ease;
}
@keyframes popIn {
    from { opacity:0; transform:scale(0.93) translateY(12px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}

.modal-box h3 {
    font-size:1.2rem; font-weight:700; color:#1e293b;
    margin-bottom:0.4rem; display:flex; align-items:center; gap:0.5rem;
}
.modal-box h3 i { color:#5f6fff; }
.modal-box .modal-sub { font-size:0.82rem; color:#94a3b8; margin-bottom:1.5rem; }

/* Image preview circle in modal */
.img-preview-wrap {
    width:110px; height:110px; border-radius:50%;
    margin:0 auto 1.25rem;
    background:#f0f2f5; overflow:hidden;
    border:3px solid #e0e3ff;
    display:flex; align-items:center; justify-content:center;
    position:relative;
}
.img-preview-wrap img {
    width:100%; height:100%; object-fit:cover; display:block;
}
.img-preview-placeholder { font-size:2.5rem; color:#cbd5e1; }

/* Custom file input */
.file-input-wrap {
    position:relative; margin-bottom:0.5rem;
}
.file-input-wrap input[type="file"] {
    position:absolute; inset:0; opacity:0; cursor:pointer; z-index:2;
    width:100%; height:100%;
}
.file-input-label {
    display:flex; align-items:center; justify-content:center; gap:0.6rem;
    padding:0.7rem 1rem; border:2px dashed #c7d2fe;
    border-radius:12px; background:#f5f7ff;
    font-size:0.875rem; font-weight:500; color:#5f6fff;
    cursor:pointer; transition:all 0.2s; min-height:52px;
}
.file-input-wrap:hover .file-input-label {
    background:#eef0ff; border-color:#5f6fff;
}
.file-chosen {
    font-size:0.78rem; color:#64748b; margin-top:0.4rem;
    text-align:center; min-height:1rem;
}

/* Upload progress / spinner */
.upload-spinner {
    display:none; text-align:center; margin:0.75rem 0;
    color:#5f6fff; font-size:0.875rem;
}
.upload-spinner i { animation:spin 1s linear infinite; margin-right:0.4rem; }
@keyframes spin { to { transform:rotate(360deg); } }

.modal-actions {
    display:flex; justify-content:flex-end; gap:0.6rem; margin-top:1.25rem;
}

/* ── Responsive ── */
@media (max-width:900px) {
    .profile-grid { grid-template-columns:1fr; }
}
@media (max-width:768px) {
    .profile-header { flex-direction:column; text-align:center; }
    .profile-title .meta { justify-content:center; }
}
@media (max-width:600px) {
    .profile-container { padding:0 1rem; margin:1rem auto; }
    .card { padding:1.1rem; }
    .info-row { flex-direction:column; gap:0.2rem; }
    .info-label { width:auto; }
    .gender-options { flex-direction:column; gap:0.6rem; }
    .card-header { flex-wrap:wrap; gap:0.6rem; }
    .profile-header { padding:1.5rem 1.25rem; }
}
</style>
</head>
<body>

<?php require_once 'navbar.php'; ?>

<div class="profile-container">

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- ── Profile Header ── -->
    <div class="profile-header">
        <div class="profile-avatar">
            <div class="avatar-large" onclick="openModal()" title="Change profile picture">
                <?php if ($avatar_web): ?>
                    <img src="<?php echo htmlspecialchars($avatar_web); ?>"
                         alt="Profile picture"
                         onerror="this.style.display='none';this.parentElement.innerHTML='<?php echo $user_initials; ?>';">
                <?php else: ?>
                    <?php echo htmlspecialchars($user_initials); ?>
                <?php endif; ?>
            </div>
            <button class="camera-btn" onclick="openModal()" title="Upload photo" type="button">
                <i class="fas fa-camera"></i>
            </button>
        </div>

        <div class="profile-title">
            <h1><?php echo htmlspecialchars(isset($profile['full_name']) ? $profile['full_name'] : $user_name); ?></h1>
            <div class="meta">
                <?php if (!empty($profile['email'])): ?>
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email']); ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['phone'])): ?>
                <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($profile['phone']); ?></span>
                <?php endif; ?>
                <?php if (isset($profile['created_at'])): ?>
                <span><i class="fas fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($profile['created_at'])); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Two-column grid ── -->
    <div class="profile-grid">

        <!-- Contact Information -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-address-card"></i> Contact Information</h2>
                <?php if (!$is_editing_contact): ?>
                    <a href="?edit_contact=true" class="btn-edit"><i class="fas fa-pen"></i> Edit</a>
                <?php endif; ?>
            </div>

            <?php if ($is_editing_contact): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars(isset($profile['full_name']) ? $profile['full_name'] : ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars(isset($profile['email']) ? $profile['email'] : ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars(isset($profile['phone']) ? $profile['phone'] : ''); ?>" placeholder="+260 7XX XXX XXX">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" placeholder="Your full address"><?php echo htmlspecialchars(isset($profile['address']) ? $profile['address'] : ''); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="my-profile.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                        <button type="submit" name="update_contact" class="btn-save"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars(isset($profile['full_name']) ? $profile['full_name'] : 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars(isset($profile['email']) ? $profile['email'] : 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars(!empty($profile['phone']) ? $profile['phone'] : 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars(!empty($profile['address']) ? $profile['address'] : 'Not provided'); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-circle"></i> Basic Information</h2>
                <?php if (!$is_editing_basic): ?>
                    <a href="?edit_basic=true" class="btn-edit"><i class="fas fa-pen"></i> Edit</a>
                <?php endif; ?>
            </div>

            <?php if ($is_editing_basic): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars(isset($profile['date_of_birth']) ? $profile['date_of_birth'] : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="gender-options">
                            <?php
                            $g = isset($profile['gender']) ? $profile['gender'] : '';
                            $genders = array('Male'=>'fa-mars','Female'=>'fa-venus','Other'=>'fa-genderless');
                            foreach ($genders as $val => $icon):
                            ?>
                            <label class="gender-option">
                                <input type="radio" name="gender" value="<?php echo $val; ?>" <?php echo $g === $val ? 'checked' : ''; ?>>
                                <i class="fas <?php echo $icon; ?>"></i> <?php echo $val; ?>
                            </label>
                            <?php endforeach; ?>
                            <label class="gender-option">
                                <input type="radio" name="gender" value="" <?php echo empty($g) ? 'checked' : ''; ?>>
                                <i class="fas fa-question"></i> Prefer not to say
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="my-profile.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                        <button type="submit" name="update_basic" class="btn-save"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="info-row">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo formatDate(isset($profile['date_of_birth']) ? $profile['date_of_birth'] : ''); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Gender</div>
                    <div class="info-value">
                        <?php
                        $g = isset($profile['gender']) ? $profile['gender'] : '';
                        if ($g === 'Male')   echo '<i class="fas fa-mars"></i> Male';
                        elseif ($g === 'Female') echo '<i class="fas fa-venus"></i> Female';
                        elseif ($g === 'Other')  echo '<i class="fas fa-genderless"></i> Other';
                        else echo '<i class="fas fa-question"></i> Not specified';
                        ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo isset($profile['created_at']) ? date('j F, Y', strtotime($profile['created_at'])) : 'N/A'; ?></div>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /.profile-grid -->
</div><!-- /.profile-container -->


<!-- ═══════════ UPLOAD MODAL ═══════════ -->
<div class="modal-backdrop" id="uploadModal">
    <div class="modal-box">
        <h3><i class="fas fa-camera"></i> Update Profile Picture</h3>
        <p class="modal-sub">JPG, PNG, GIF or WEBP &bull; Max 5 MB</p>

        <!-- Live preview -->
        <div class="img-preview-wrap" id="previewWrap">
            <?php if ($avatar_web): ?>
                <img src="<?php echo htmlspecialchars($avatar_web); ?>" id="previewImg" alt="Preview">
            <?php else: ?>
                <i class="fas fa-user img-preview-placeholder" id="previewPlaceholder"></i>
            <?php endif; ?>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
            <!-- Custom styled file input -->
            <div class="file-input-wrap">
                <input type="file" name="profile_image" id="fileInput"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       onchange="handleFileSelect(this)">
                <div class="file-input-label" id="fileLabel">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Click to choose an image</span>
                </div>
            </div>
            <div class="file-chosen" id="fileChosen">No file chosen</div>

            <!-- Uploading spinner (shown on submit) -->
            <div class="upload-spinner" id="uploadSpinner">
                <i class="fas fa-circle-notch"></i> Uploading, please wait…
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" name="upload_image" class="btn-save" id="uploadBtn" disabled>
                    <i class="fas fa-upload"></i> Upload Photo
                </button>
            </div>
        </form>
    </div>
</div>


<script>
/* ── Modal open/close ── */
function openModal() {
    document.getElementById('uploadModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('uploadModal').classList.remove('active');
    document.body.style.overflow = '';
}
document.getElementById('uploadModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });

/* ── File select: preview + enable button ── */
function handleFileSelect(input) {
    if (!input.files || !input.files[0]) return;

    var file = input.files[0];
    var maxSize = 5 * 1024 * 1024;

    // Client-side size check
    if (file.size > maxSize) {
        alert('File is too large. Maximum allowed size is 5 MB.');
        input.value = '';
        return;
    }

    // Show filename
    document.getElementById('fileChosen').textContent = file.name;

    // Update label text
    var label = document.getElementById('fileLabel');
    label.innerHTML = '<i class="fas fa-check-circle" style="color:#22c55e;"></i><span>' + file.name + '</span>';

    // Live image preview
    var reader = new FileReader();
    reader.onload = function(e) {
        var wrap = document.getElementById('previewWrap');
        // Remove placeholder icon if present
        var ph = document.getElementById('previewPlaceholder');
        if (ph) ph.remove();
        // Update or create preview img
        var img = document.getElementById('previewImg');
        if (!img) {
            img = document.createElement('img');
            img.id = 'previewImg';
            img.alt = 'Preview';
            wrap.appendChild(img);
        }
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Enable upload button
    document.getElementById('uploadBtn').removeAttribute('disabled');
}

/* ── Show spinner on form submit ── */
document.getElementById('uploadForm').addEventListener('submit', function() {
    document.getElementById('uploadSpinner').style.display = 'block';
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('uploadBtn').innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Uploading…';
});

/* ── Navbar JS (handled by navbar.php, but repeat closeMenu guard) ── */
</script>

</body>
</html>