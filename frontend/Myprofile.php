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

// Define upload paths - SINGLE LOCATION for all profile images
$upload_dir_abs = dirname(__DIR__) . '/uploads/profiles/';
$upload_dir_web = '../uploads/profiles/';  // Relative to frontend folder

if (!file_exists($upload_dir_abs)) {
    mkdir($upload_dir_abs, 0755, true);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch user
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, date_of_birth, gender, address, profile_image, blood_group, created_at FROM users WHERE user_id = ?");
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

            $finfo     = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file_tmp);

            if ($file_size > 5 * 1024 * 1024) {
                $error_message = 'File too large. Maximum allowed size is 5 MB.';
            } elseif (!in_array($file_ext, $allowed_exts) || !in_array($mime_type, $allowed_types)) {
                $error_message = 'Invalid file type. Only JPG, PNG, GIF and WEBP images are allowed.';
            } else {
                // Create unique filename
                $new_filename = 'patient_' . $user_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $dest_abs     = $upload_dir_abs . $new_filename;
                // Store relative path from project root (for database)
                $dest_db      = 'uploads/profiles/' . $new_filename;
                // Web path for display
                $dest_web     = '../uploads/profiles/' . $new_filename;

                if (move_uploaded_file($file_tmp, $dest_abs)) {
                    // Delete old image if it exists
                    if (!empty($profile['profile_image']) && $profile['profile_image'] != '/frontend/assets/profile_pic.png') {
                        $old_abs = dirname(__DIR__) . '/' . $profile['profile_image'];
                        if (file_exists($old_abs)) {
                            unlink($old_abs);
                        }
                    }

                    // Update database
                    $upd = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                    if ($upd->execute(array($dest_db, $user_id))) {
                        $profile['profile_image'] = $dest_db;
                        // Clear old session and set new one with cache-busting
                        unset($_SESSION['profile_image']);
                        $_SESSION['profile_image_updated'] = time();
                        $success_message = 'Profile picture updated successfully!';
                        // Force page refresh to show new image
                        echo '<meta http-equiv="refresh" content="1;url=my-profile.php?updated=' . time() . '">';
                    } else {
                        $error_message = 'Image uploaded but database update failed.';
                    }
                } else {
                    $error_message = 'Could not save the file. Please check folder permissions.';
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
        $blood_group   = trim(isset($_POST['blood_group'])   ? $_POST['blood_group']   : '');

        $upd = $pdo->prepare("UPDATE users SET date_of_birth = ?, gender = ?, blood_group = ? WHERE user_id = ?");
        if ($upd->execute(array($date_of_birth ? $date_of_birth : null, $gender ? $gender : null, $blood_group ? $blood_group : null, $user_id))) {
            $profile['date_of_birth'] = $date_of_birth;
            $profile['gender']        = $gender;
            $profile['blood_group']   = $blood_group;
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
if (!empty($profile['profile_image']) && $profile['profile_image'] != '/frontend/assets/profile_pic.png') {
    $clean_path = ltrim($profile['profile_image'], '/');
    $candidate_abs = dirname(__DIR__) . '/' . $clean_path;
    if (file_exists($candidate_abs)) {
        $filemtime = filemtime($candidate_abs);
        $avatar_web = '../' . $clean_path . '?v=' . $filemtime;
    }
}

// Update session for navbar
if ($avatar_web) {
    $_SESSION['profile_image'] = $avatar_web;
} else {
    unset($_SESSION['profile_image']);
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
<link rel="stylesheet" href="./Css/Myprofile.css">
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

    <div class="profile-header">
        <div class="profile-avatar">
            <div class="avatar-large" onclick="openModal()" title="Change profile picture">
                <?php if ($avatar_web): ?>
                    <img src="<?php echo htmlspecialchars($avatar_web); ?>"
                         alt="Profile picture"
                         id="profileAvatarImg"
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
                <?php if (!empty($profile['blood_group'])): ?>
                <span><i class="fas fa-tint"></i> <?php echo htmlspecialchars($profile['blood_group']); ?></span>
                <?php endif; ?>
                <?php if (isset($profile['created_at'])): ?>
                <span><i class="fas fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($profile['created_at'])); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="profile-grid">
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
                <div class="info-row"><div class="info-label">Full Name</div><div class="info-value"><?php echo htmlspecialchars(isset($profile['full_name']) ? $profile['full_name'] : 'Not provided'); ?></div></div>
                <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?php echo htmlspecialchars(isset($profile['email']) ? $profile['email'] : 'Not provided'); ?></div></div>
                <div class="info-row"><div class="info-label">Phone</div><div class="info-value"><?php echo htmlspecialchars(!empty($profile['phone']) ? $profile['phone'] : 'Not provided'); ?></div></div>
                <div class="info-row"><div class="info-label">Address</div><div class="info-value"><?php echo htmlspecialchars(!empty($profile['address']) ? $profile['address'] : 'Not provided'); ?></div></div>
            <?php endif; ?>
        </div>

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
                    <div class="form-group">
                        <label><i class="fas fa-tint"></i> Blood Group</label>
                        <select name="blood_group">
                            <option value="">Select Blood Group</option>
                            <option value="A+"  <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'A+')  ? 'selected' : ''; ?>>A+</option>
                            <option value="A-"  <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'A-')  ? 'selected' : ''; ?>>A-</option>
                            <option value="B+"  <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'B+')  ? 'selected' : ''; ?>>B+</option>
                            <option value="B-"  <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'B-')  ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+"  <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'O+')  ? 'selected' : ''; ?>>O+</option>
                            <option value="O-"  <?php echo (isset($profile['blood_group']) && $profile['blood_group'] === 'O-')  ? 'selected' : ''; ?>>O-</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <a href="my-profile.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                        <button type="submit" name="update_basic" class="btn-save"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="info-row"><div class="info-label">Date of Birth</div><div class="info-value"><?php echo formatDate(isset($profile['date_of_birth']) ? $profile['date_of_birth'] : ''); ?></div></div>
                <div class="info-row"><div class="info-label">Gender</div><div class="info-value"><?php
                        $g = isset($profile['gender']) ? $profile['gender'] : '';
                        if ($g === 'Male')   echo '<i class="fas fa-mars"></i> Male';
                        elseif ($g === 'Female') echo '<i class="fas fa-venus"></i> Female';
                        elseif ($g === 'Other')  echo '<i class="fas fa-genderless"></i> Other';
                        else echo '<i class="fas fa-question"></i> Not specified';
                    ?></div></div>
                <div class="info-row"><div class="info-label">Blood Group</div><div class="info-value"><?php if (!empty($profile['blood_group'])): ?><span class="blood-group-badge"><?php echo htmlspecialchars($profile['blood_group']); ?></span><?php else: ?>Not specified<?php endif; ?></div></div>
                <div class="info-row"><div class="info-label">Member Since</div><div class="info-value"><?php echo isset($profile['created_at']) ? date('j F, Y', strtotime($profile['created_at'])) : 'N/A'; ?></div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="uploadModal">
    <div class="modal-box">
        <h3><i class="fas fa-camera"></i> Update Profile Picture</h3>
        <p class="modal-sub">JPG, PNG, GIF or WEBP &bull; Max 5 MB</p>
        <div class="img-preview-wrap" id="previewWrap">
            <?php if ($avatar_web): ?>
                <img src="<?php echo htmlspecialchars($avatar_web); ?>" id="previewImg" alt="Preview">
            <?php else: ?>
                <i class="fas fa-user img-preview-placeholder" id="previewPlaceholder"></i>
            <?php endif; ?>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
            <div class="file-input-wrap">
                <input type="file" name="profile_image" id="fileInput" accept="image/jpeg,image/png,image/gif,image/webp" onchange="handleFileSelect(this)">
                <div class="file-input-label" id="fileLabel"><i class="fas fa-cloud-upload-alt"></i><span>Click to choose an image</span></div>
            </div>
            <div class="file-chosen" id="fileChosen">No file chosen</div>
            <div class="upload-spinner" id="uploadSpinner"><i class="fas fa-circle-notch"></i> Uploading, please wait…</div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" name="upload_image" class="btn-save" id="uploadBtn" disabled><i class="fas fa-upload"></i> Upload Photo</button>
            </div>
        </form>
    </div>
</div>

<script>
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

function handleFileSelect(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('File is too large. Maximum allowed size is 5 MB.');
        input.value = '';
        return;
    }
    document.getElementById('fileChosen').textContent = file.name;
    var label = document.getElementById('fileLabel');
    label.innerHTML = '<i class="fas fa-check-circle" style="color:#22c55e;"></i><span>' + file.name + '</span>';
    var reader = new FileReader();
    reader.onload = function(e) {
        var wrap = document.getElementById('previewWrap');
        var ph = document.getElementById('previewPlaceholder');
        if (ph) ph.remove();
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
    document.getElementById('uploadBtn').removeAttribute('disabled');
}

document.getElementById('uploadForm').addEventListener('submit', function() {
    document.getElementById('uploadSpinner').style.display = 'block';
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('uploadBtn').innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Uploading…';
});
</script>
</body>
</html>