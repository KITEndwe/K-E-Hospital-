<?php
// K&E Hospital - Add Doctor Page
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

// Include database connection with multiple path handling
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

if (!$db_found) {
    die('Database configuration file not found. Please check your installation.');
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = 'DOC' . date('Ymd') . rand(100, 999);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $speciality = trim($_POST['speciality'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $fees = floatval($_POST['fees'] ?? 0);
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    
    // Handle image upload
    $profile_image = '/assets/doctors/default-doctor.png';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'doctor_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/KE-Hospital/assets/doctors/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = '/assets/doctors/' . $new_filename;
            }
        } else {
            $error_message = 'Invalid file format. Please upload JPG, PNG, GIF, or WEBP images.';
        }
    }
    
    if (empty($name) || empty($speciality) || empty($degree) || empty($experience) || $fees <= 0) {
        $error_message = 'Please fill in all required fields marked with *.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO doctors (doctor_id, name, email, profile_image, speciality, degree, experience, about, fees, address_line1, address_line2, is_available, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([$doctor_id, $name, $email, $profile_image, $speciality, $degree, $experience, $about, $fees, $address_line1, $address_line2]);
            
            $success_message = 'Doctor added successfully! Doctor ID: ' . $doctor_id;
            
            // Clear form
            $_POST = [];
            
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            overflow-x: hidden;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #334155;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 1.75rem 1.5rem;
            border-bottom: 1px solid #334155;
            margin-bottom: 1rem;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-logo i {
            font-size: 1.8rem;
            color: #3b82f6;
        }

        .sidebar-logo span {
            background: linear-gradient(135deg, #fff 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-nav {
            padding: 0.5rem 0;
        }

        .nav-item {
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            margin: 0.25rem 0.75rem;
            border-radius: 0.75rem;
            font-weight: 500;
        }

        .nav-item:hover {
            background: #334155;
            color: white;
        }

        .nav-item.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        .nav-item i {
            width: 24px;
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
        }

        /* Top Bar */
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

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .page-title p {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .admin-name {
            font-weight: 500;
            color: #1e293b;
        }

        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,38,38,0.3);
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-header h2 i {
            color: #3b82f6;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #334155;
            font-size: 0.875rem;
        }

        .form-group label .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }

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
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Image Upload */
        .image-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafbfc;
        }

        .image-upload:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
        }

        .image-upload i {
            font-size: 2rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .image-upload p {
            font-size: 0.875rem;
            color: #64748b;
        }

        .image-upload small {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .image-preview {
            margin-top: 1rem;
            display: none;
        }

        .image-preview img {
            max-width: 150px;
            border-radius: 0.75rem;
            border: 2px solid #e2e8f0;
        }

        /* Form Actions */
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        .btn-reset {
            background: #f1f5f9;
            color: #475569;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-reset:hover {
            background: #e2e8f0;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
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

        .alert i {
            font-size: 1.25rem;
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            font-size: 1.25rem;
            color: #1f2937;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-submit, .btn-reset {
                width: 100%;
                justify-content: center;
            }
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <i class="fas fa-hospital-user"></i>
                    <span>K&E Hospital</span>
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
        
        <!-- Overlay for mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Add New Doctor</h1>
                    <p>Add a new medical professional to your hospital team</p>
                </div>
                <div class="user-info">
                    <div class="admin-badge">
                        <div class="admin-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="form-container">
                <div class="form-header">
                    <h2>
                        <i class="fas fa-user-md"></i>
                        Doctor Registration Form
                    </h2>
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
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="doctor@kehospital.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Speciality <span class="required">*</span></label>
                            <select name="speciality" required>
                                <option value="">Select Speciality</option>
                                <option value="General Physician" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'General Physician') ? 'selected' : ''; ?>>General Physician</option>
                                <option value="Gynecologist" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'Gynecologist') ? 'selected' : ''; ?>>Gynecologist</option>
                                <option value="Dermatologist" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'Dermatologist') ? 'selected' : ''; ?>>Dermatologist</option>
                                <option value="Pediatrician" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'Pediatrician') ? 'selected' : ''; ?>>Pediatrician</option>
                                <option value="Neurologist" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'Neurologist') ? 'selected' : ''; ?>>Neurologist</option>
                                <option value="Gastroenterologist" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'Gastroenterologist') ? 'selected' : ''; ?>>Gastroenterologist</option>
                                <option value="Cardiologist" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'Cardiologist') ? 'selected' : ''; ?>>Cardiologist</option>
                                <option value="Orthopedic" <?php echo (isset($_POST['speciality']) && $_POST['speciality'] == 'Orthopedic') ? 'selected' : ''; ?>>Orthopedic</option>
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
                        
                        <div class="form-group full-width" style="grid-column: span 2;">
                            <label>Profile Image</label>
                            <div class="image-upload" onclick="document.getElementById('imageInput').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload doctor's photo</p>
                                <small>JPG, PNG, GIF up to 5MB</small>
                                <input type="file" id="imageInput" name="profile_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                            </div>
                            <div class="image-preview" id="imagePreview">
                                <img id="previewImg" src="" alt="Preview">
                            </div>
                        </div>
                        
                        <div class="form-group full-width" style="grid-column: span 2;">
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
        // Mobile menu functionality
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        function closeMenu() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
        
        function openMenu() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        }
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                if (sidebar.classList.contains('open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
                closeMenu();
            }
        });
        
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Reset form function
        function resetForm() {
            document.getElementById('doctorForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('previewImg').src = '';
        }
        
        // Form validation
        document.getElementById('doctorForm').addEventListener('submit', function(e) {
            const fees = document.querySelector('input[name="fees"]').value;
            if (fees <= 0) {
                e.preventDefault();
                alert('Please enter a valid consultation fee.');
            }
        });
    </script>
</body>
</html>