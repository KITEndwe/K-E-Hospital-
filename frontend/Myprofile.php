<?php
// K&E Hospital - User Profile Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$current_page = 'my-profile.php';

// Initialize variables
$profile = [];
$success_message = '';
$error_message = '';
$is_editing_contact = isset($_GET['edit_contact']) && $_GET['edit_contact'] == 'true';
$is_editing_basic = isset($_GET['edit_basic']) && $_GET['edit_basic'] == 'true';

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/../uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user profile data
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, date_of_birth, gender, address, profile_image, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        // If user not found in users table, check admin table
        $stmt = $pdo->prepare("SELECT admin_id as user_id, full_name, email, phone, NULL as date_of_birth, NULL as gender, NULL as address, profile_image, created_at FROM admin WHERE admin_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($profile) {
            $_SESSION['role'] = 'admin';
        }
    }

    // Handle contact information update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validation
        if (empty($full_name)) {
            $error_message = 'Full name is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Valid email is required.';
        } else {
            // Update user data
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, email = ? WHERE user_id = ?");
            if ($updateStmt->execute([$full_name, $phone, $address, $email, $user_id])) {
                $_SESSION['full_name'] = $full_name;
                $profile['full_name'] = $full_name;
                $profile['phone'] = $phone;
                $profile['address'] = $address;
                $profile['email'] = $email;
                $success_message = 'Contact information updated successfully!';
                $is_editing_contact = false;
            } else {
                $error_message = 'Failed to update profile. Please try again.';
            }
        }
    }

    // Handle basic information update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_basic'])) {
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $gender = trim($_POST['gender'] ?? '');

        // Update user data
        $updateStmt = $pdo->prepare("UPDATE users SET date_of_birth = ?, gender = ? WHERE user_id = ?");
        if ($updateStmt->execute([$date_of_birth ?: null, $gender ?: null, $user_id])) {
            $profile['date_of_birth'] = $date_of_birth;
            $profile['gender'] = $gender;
            $success_message = 'Basic information updated successfully!';
            $is_editing_basic = false;
        } else {
            $error_message = 'Failed to update basic information. Please try again.';
        }
    }

    // Handle profile image upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $file_size = $_FILES['profile_image']['size'];
            
            // Check file size (max 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $error_message = 'File size too large. Maximum 5MB allowed.';
            } elseif (!in_array($file_ext, $allowed)) {
                $error_message = 'Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.';
            } else {
                // Create unique filename
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_path_relative = 'uploads/profiles/' . $new_filename;
                $upload_path_absolute = __DIR__ . '/../' . $upload_path_relative;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path_absolute)) {
                    // Delete old profile image if exists
                    if (!empty($profile['profile_image']) && file_exists(__DIR__ . '/../' . $profile['profile_image'])) {
                        unlink(__DIR__ . '/../' . $profile['profile_image']);
                    }
                    
                    $updateStmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                    if ($updateStmt->execute([$upload_path_relative, $user_id])) {
                        $profile['profile_image'] = $upload_path_relative;
                        $success_message = 'Profile picture updated successfully!';
                    } else {
                        $error_message = 'Failed to update profile picture.';
                    }
                } else {
                    $error_message = 'Failed to upload image. Please check folder permissions.';
                }
            }
        } else {
            $error_message = 'Please select an image to upload.';
        }
    }

    // Get profile image
    $profile_image = !empty($profile['profile_image']) ? $profile['profile_image'] : '';

} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}

// Format date for display
function formatDate($date) {
    if (empty($date) || $date == '0000-00-00') return 'Not specified';
    return date('j F, Y', strtotime($date));
}

// Get user initials for avatar
$user_initials = strtoupper(substr($profile['full_name'] ?? $user_name, 0, 1));
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
/* ═══════════════════ RESET & BASE ═══════════════════ */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Outfit', sans-serif;
    background: #f5f7fb;
    color: #1e293b;
    line-height: 1.5;
}

/* Page Container */
.profile-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

/* Profile Header */
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.profile-avatar {
    position: relative;
}

.avatar-large {
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    border: 3px solid rgba(255,255,255,0.4);
    overflow: hidden;
}

.avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.camera-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #5f6fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: all 0.2s;
}

.camera-btn:hover {
    transform: scale(1.05);
    background: #f0f1ff;
}

.profile-title h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.profile-title p {
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Main Grid */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 1.5rem;
}

/* Cards */
.card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f2f5;
}

.card-header h2 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e293b;
}

.card-header h2 i {
    color: #5f6fff;
    margin-right: 0.5rem;
}

.btn-edit, .btn-save {
    background: #5f6fff;
    color: white;
    border: none;
    padding: 0.5rem 1.2rem;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Outfit', sans-serif;
    text-decoration: none;
    display: inline-block;
}

.btn-edit:hover, .btn-save:hover {
    background: #4a5af0;
    transform: translateY(-1px);
}

.btn-cancel {
    background: #e2e8f0;
    color: #64748b;
    border: none;
    padding: 0.5rem 1.2rem;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Outfit', sans-serif;
    margin-left: 0.5rem;
    text-decoration: none;
    display: inline-block;
}

.btn-cancel:hover {
    background: #cbd5e1;
}

/* Info Rows */
.info-row {
    display: flex;
    padding: 0.9rem 0;
    border-bottom: 1px solid #f0f2f5;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    width: 130px;
    font-weight: 500;
    color: #64748b;
    font-size: 0.85rem;
}

.info-value {
    flex: 1;
    color: #1e293b;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Form Styles */
.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 0.4rem;
}

.form-group input, 
.form-group select, 
.form-group textarea {
    width: 100%;
    padding: 0.7rem 1rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.form-group input:focus, 
.form-group select:focus, 
.form-group textarea:focus {
    outline: none;
    border-color: #5f6fff;
    box-shadow: 0 0 0 3px rgba(95,111,255,0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

/* Action Buttons */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #f0f2f5;
}

/* Alert Messages */
.alert {
    padding: 1rem 1.2rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 24px;
    padding: 1.8rem;
    max-width: 450px;
    width: 90%;
}

.modal-content h3 {
    margin-bottom: 1rem;
    font-size: 1.3rem;
}

.modal-content p {
    margin-bottom: 1.5rem;
    color: #64748b;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

/* Upload Form */
.upload-form {
    margin-top: 1rem;
}

.upload-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #f0f2f5;
    margin: 1rem auto;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.upload-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Gender Radio Buttons */
.gender-options {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.gender-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.gender-option input[type="radio"] {
    width: auto;
    margin: 0;
    cursor: pointer;
}

.gender-option label {
    margin: 0;
    cursor: pointer;
    font-weight: normal;
}

/* Responsive */
@media (max-width: 900px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    .profile-title p {
        justify-content: center;
    }
}

@media (max-width: 600px) {
    .profile-container {
        padding: 0 1rem;
        margin: 1rem auto;
    }
    .card {
        padding: 1.2rem;
    }
    .info-row {
        flex-direction: column;
        gap: 0.3rem;
    }
    .info-label {
        width: auto;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
    .card-header {
        flex-direction: column;
        gap: 0.8rem;
        align-items: flex-start;
    }
    .gender-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
}
</style>
</head>
<body>

<?php 
// Define variables for navbar before including it
$is_logged_in = true;
$user_name = $_SESSION['full_name'];
$current_page = 'my-profile.php';
$profile_image = !empty($profile_image) ? $profile_image : '';
require_once 'navbar.php'; 
?>

<div class="profile-container">

    <!-- Success/Error Messages -->
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

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <div class="avatar-large">
                <?php if (!empty($profile_image) && file_exists(__DIR__ . '/../' . $profile_image)): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture">
                <?php else: ?>
                    <?php echo htmlspecialchars($user_initials); ?>
                <?php endif; ?>
            </div>
            <div class="camera-btn" onclick="openUploadModal()">
                <i class="fas fa-camera"></i>
            </div>
        </div>
        <div class="profile-title">
            <h1><?php echo htmlspecialchars($profile['full_name'] ?? $user_name); ?></h1>
            <p>
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email'] ?? ''); ?>
                <i class="fas fa-phone" style="margin-left: 1rem;"></i> <?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?>
            </p>
            <p>
                <i class="fas fa-calendar-alt"></i> Member since <?php echo isset($profile['created_at']) ? date('F Y', strtotime($profile['created_at'])) : '2024'; ?>
            </p>
        </div>
    </div>

    <div class="profile-grid">
        <!-- Left Column: Contact Information -->
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
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="+260 XXX XXX XXX">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" placeholder="Your full address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="my-profile.php" class="btn-cancel">Cancel</a>
                        <button type="submit" name="update_contact" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['full_name'] ?? 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['email'] ?? 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['address'] ?? 'Not provided'); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Basic Information -->
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
                        <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="gender-options">
                            <label class="gender-option">
                                <input type="radio" name="gender" value="Male" <?php echo (($profile['gender'] ?? '') == 'Male') ? 'checked' : ''; ?>>
                                <i class="fas fa-mars"></i> Male
                            </label>
                            <label class="gender-option">
                                <input type="radio" name="gender" value="Female" <?php echo (($profile['gender'] ?? '') == 'Female') ? 'checked' : ''; ?>>
                                <i class="fas fa-venus"></i> Female
                            </label>
                            <label class="gender-option">
                                <input type="radio" name="gender" value="Other" <?php echo (($profile['gender'] ?? '') == 'Other') ? 'checked' : ''; ?>>
                                <i class="fas fa-genderless"></i> Other
                            </label>
                            <label class="gender-option">
                                <input type="radio" name="gender" value="" <?php echo (empty($profile['gender'])) ? 'checked' : ''; ?>>
                                <i class="fas fa-question"></i> Prefer not to say
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="my-profile.php" class="btn-cancel">Cancel</a>
                        <button type="submit" name="update_basic" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="info-row">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo formatDate($profile['date_of_birth'] ?? ''); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Gender</div>
                    <div class="info-value">
                        <?php 
                        $gender = $profile['gender'] ?? '';
                        if ($gender == 'Male') echo '<i class="fas fa-mars"></i> Male';
                        elseif ($gender == 'Female') echo '<i class="fas fa-venus"></i> Female';
                        elseif ($gender == 'Other') echo '<i class="fas fa-genderless"></i> Other';
                        else echo '<i class="fas fa-question"></i> Not specified';
                        ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo isset($profile['created_at']) ? date('j F, Y', strtotime($profile['created_at'])) : 'January 2024'; ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Upload Image Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-camera"></i> Update Profile Picture</h3>
        <p>Upload a new profile picture (JPG, PNG, GIF up to 5MB)</p>
        <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
            <div class="upload-preview" id="imagePreview">
                <?php if (!empty($profile_image) && file_exists(__DIR__ . '/../' . $profile_image)): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Preview">
                <?php else: ?>
                    <i class="fas fa-user" style="font-size: 3rem; color: #cbd5e1;"></i>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="file" name="profile_image" id="profileImage" accept="image/*" required onchange="previewImage(this)">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeUploadModal()">Cancel</button>
                <button type="submit" name="upload_image" class="btn-save"><i class="fas fa-upload"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal() {
    document.getElementById('uploadModal').classList.add('active');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('active');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('imagePreview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Close modal when clicking outside
document.getElementById('uploadModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUploadModal();
    }
});
</script>

</body>
</html>