<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../frontend/login.php');
    exit();
}

$host     = 'localhost';
$dbname   = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$speciality_filter   = isset($_GET['speciality'])   ? $_GET['speciality']   : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';
$search              = isset($_GET['search'])        ? $_GET['search']       : '';

/* Flash messages from redirects */
$flash_success = isset($_GET['success']) ? $_GET['success'] : '';
$flash_name    = isset($_GET['name'])    ? htmlspecialchars($_GET['name']) : '';
$flash_error   = isset($_GET['error'])   ? $_GET['error']  : '';

$sql    = "SELECT * FROM doctors WHERE 1=1";
$params = array();

if (!empty($speciality_filter)) {
    $sql .= " AND speciality = ?";
    $params[] = $speciality_filter;
}
if ($availability_filter !== '') {
    $sql .= " AND is_available = ?";
    $params[] = ($availability_filter === 'available') ? 1 : 0;
}
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR speciality LIKE ? OR degree LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

$spec_stmt    = $pdo->query("SELECT DISTINCT speciality FROM doctors ORDER BY speciality");
$specialities = $spec_stmt->fetchAll();

$total_doctors    = count($doctors);
$available_count  = 0;
$unavailable_count = 0;
foreach ($doctors as $doc) {
    if ($doc['is_available']) $available_count++; else $unavailable_count++;
}

$admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';

/*
 * Image resolver — admin/ folder, DB stores /assets/docX.png
 * → ../frontend/assets/docX.png
 */
function getDoctorImagePath($img) {
    if (empty($img)) return '';
    $img = ltrim($img, '/');
    /* assets/docX.png → ../frontend/assets/docX.png */
    if (strpos($img, 'assets/') === 0) return '../frontend/' . $img;
    /* frontend/assets/... → ../ + path */
    if (strpos($img, 'frontend/') === 0) return '../' . $img;
    /* uploads/profiles/... (uploaded via my-profile) → ../ + path */
    if (strpos($img, 'uploads/') === 0) return '../' . $img;
    /* fallback */
    return '../frontend/assets/' . $img;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Doctors List - K&amp;E Hospital Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Outfit',sans-serif;background:#f5f7fb;color:#1f2937;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}
img{display:block;max-width:100%;}
.dashboard-container{display:flex;min-height:100vh;}

/* ── Sidebar ── */
.sidebar{width:280px;background:#fff;box-shadow:2px 0 10px rgba(0,0,0,0.05);position:fixed;height:100vh;overflow-y:auto;transition:transform 0.3s;z-index:1000;}
.sidebar::-webkit-scrollbar{width:6px;}
.sidebar::-webkit-scrollbar-thumb{background:#3b82f6;border-radius:3px;}
.sidebar-header{padding:1.5rem;border-bottom:1px solid #e5e7eb;}
.sidebar-logo{font-size:1.4rem;font-weight:700;color:#3b82f6;display:flex;align-items:center;gap:0.75rem;}
.sidebar-logo i{font-size:1.6rem;}
.sidebar-nav{padding:1.5rem 0;}
.nav-item{padding:0.875rem 1.5rem;display:flex;align-items:center;gap:1rem;color:#6b7280;transition:all 0.3s;font-weight:500;border-right:3px solid transparent;}
.nav-item:hover,.nav-item.active{background:#eef2ff;color:#3b82f6;border-right-color:#3b82f6;}
.nav-item i{width:24px;font-size:1.1rem;}

/* ── Main ── */
.main-content{flex:1;margin-left:280px;padding:1.5rem;}
.top-bar{background:#fff;border-radius:1rem;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px rgba(0,0,0,0.05);border:1px solid #e2e8f0;flex-wrap:wrap;gap:1rem;}
.page-title{display:flex;align-items:center;gap:1rem;}
.page-title h1{font-size:1.5rem;font-weight:700;color:#0f172a;}
.page-title p{font-size:0.875rem;color:#64748b;margin-top:0.25rem;}
.user-info{display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;}
.admin-badge{display:flex;align-items:center;gap:0.75rem;background:#f1f5f9;padding:0.5rem 1rem;border-radius:2rem;}
.admin-avatar{width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;}
.admin-name{font-weight:500;color:#1e293b;}
.logout-btn{background:#ef4444;color:#fff;padding:0.5rem 1.25rem;border-radius:0.5rem;font-size:0.875rem;font-weight:500;transition:all 0.3s;display:inline-flex;align-items:center;gap:0.5rem;}
.logout-btn:hover{background:#dc2626;transform:translateY(-2px);}

/* Flash alerts */
.alert{padding:0.875rem 1.25rem;border-radius:10px;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.65rem;font-size:0.875rem;font-weight:500;}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}

/* Stats */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;}
.stat-card-sm{background:#fff;border-radius:1rem;padding:1rem;text-align:center;border:1px solid #e2e8f0;transition:all 0.3s;}
.stat-card-sm:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.05);}
.stat-number{font-size:1.75rem;font-weight:700;color:#0f172a;}
.stat-label{font-size:0.75rem;color:#64748b;margin-top:0.25rem;}

/* Filters */
.filters-bar{background:#fff;border-radius:1rem;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:center;border:1px solid #e2e8f0;}
.filter-group{display:flex;align-items:center;gap:0.5rem;}
.filter-group label{font-size:0.875rem;font-weight:500;color:#475569;}
.filter-group select,.filter-group input{padding:0.5rem 1rem;border:1px solid #e2e8f0;border-radius:0.5rem;font-family:inherit;font-size:0.875rem;background:#fff;}
.search-box{flex:1;display:flex;gap:0.5rem;min-width:200px;}
.search-box input{flex:1;padding:0.5rem 1rem;border:1px solid #e2e8f0;border-radius:0.5rem;font-size:0.875rem;}
.search-box button{padding:0.5rem 1rem;background:#3b82f6;color:#fff;border:none;border-radius:0.5rem;cursor:pointer;}
.reset-btn{padding:0.5rem 1rem;background:#f1f5f9;color:#475569;text-decoration:none;border-radius:0.5rem;font-size:0.875rem;display:inline-flex;align-items:center;gap:0.5rem;}

/* Doctors grid */
.doctors-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.5rem;}
.doctor-card{background:#fff;border-radius:1rem;overflow:hidden;transition:all 0.3s;border:1px solid #e2e8f0;cursor:pointer;}
.doctor-card:hover{transform:translateY(-4px);box-shadow:0 12px 24px rgba(0,0,0,0.1);}

/* ── Doctor image — portrait style matching frontend ── */
.doctor-img-wrap{
    width:100%; height:240px;
    background:linear-gradient(160deg,#dce3ff 0%,#eaf0ff 100%);
    overflow:hidden;
    display:flex; align-items:flex-end; justify-content:center;
    position:relative;
}
.doctor-img-wrap img{
    width:100%; height:100%;
    object-fit:cover; object-position:top center;
    transition:transform 0.4s;
    display:block;
}
.doctor-card:hover .doctor-img-wrap img{transform:scale(1.04);}
.doctor-img-fallback{
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    font-size:3.5rem; color:#8b9aff; font-weight:700;
}

.doctor-info{padding:1.1rem;}
.doctor-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.4rem;flex-wrap:wrap;gap:0.4rem;}
.doctor-name{font-size:1rem;font-weight:700;color:#0f172a;}
.availability-badge{display:inline-flex;align-items:center;gap:0.25rem;padding:0.22rem 0.6rem;border-radius:9999px;font-size:0.68rem;font-weight:600;}
.availability-badge.available{background:#d1fae5;color:#065f46;}
.availability-badge.unavailable{background:#fee2e2;color:#dc2626;}
.doctor-speciality{color:#3b82f6;font-size:0.82rem;font-weight:500;margin-bottom:0.3rem;}
.doctor-details{font-size:0.75rem;color:#64748b;margin-bottom:0.65rem;}
.doctor-footer{display:flex;justify-content:space-between;align-items:center;margin-top:0.65rem;padding-top:0.65rem;border-top:1px solid #f0f0f5;flex-wrap:wrap;gap:0.4rem;}
.doctor-fees{font-size:0.95rem;font-weight:700;color:#3b82f6;}
.doctor-rating{display:flex;align-items:center;gap:0.25rem;font-size:0.72rem;color:#f59e0b;}
.action-buttons{display:flex;gap:0.5rem;margin-top:0.75rem;}
.action-btn{flex:1;padding:0.5rem;text-align:center;border-radius:0.5rem;font-size:0.75rem;font-weight:500;transition:all 0.3s;cursor:pointer;border:none;font-family:inherit;display:inline-flex;align-items:center;justify-content:center;gap:0.35rem;}
.action-btn.delete{background:#fee2e2;color:#dc2626;}
.action-btn.delete:hover{background:#dc2626;color:#fff;}

/* Empty */
.empty-state{text-align:center;padding:3rem;color:#64748b;background:#fff;border-radius:1rem;}
.empty-state i{font-size:3rem;margin-bottom:1rem;display:block;opacity:0.4;}

/* Mobile */
.mobile-menu-toggle{display:none;background:#f1f5f9;border:none;cursor:pointer;width:40px;height:40px;border-radius:0.5rem;font-size:1.25rem;color:#1f2937;align-items:center;justify-content:center;}
.sidebar-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;display:none;}
.sidebar-overlay.active{display:block;}

@media(max-width:1024px){.doctors-grid{grid-template-columns:repeat(auto-fill,minmax(240px,1fr));}}
@media(max-width:768px){
    .sidebar{transform:translateX(-100%);position:fixed;z-index:1001;}
    .sidebar.open{transform:translateX(0);}
    .main-content{margin-left:0;padding:1rem;}
    .mobile-menu-toggle{display:flex;}
    .top-bar{flex-direction:column;align-items:stretch;}
    .page-title{justify-content:space-between;width:100%;}
    .user-info{width:100%;justify-content:space-between;}
    .stats-row{grid-template-columns:repeat(3,1fr);}
    .filters-bar{flex-direction:column;align-items:stretch;}
    .filter-group{width:100%;justify-content:space-between;}
    .filter-group select,.filter-group input{flex:1;}
    .search-box{width:100%;}
    .doctors-grid{grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;}
}
@media(max-width:480px){
    .stats-row{grid-template-columns:1fr;}
    .doctors-grid{grid-template-columns:1fr;}
    .action-buttons{flex-direction:column;}
}
</style>
</head>
<body>
<div class="dashboard-container">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <i class="fas fa-hospital-user"></i>
                <span>K&amp;E Hospital</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"    class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="appointments.php" class="nav-item"><i class="fas fa-calendar-alt"></i><span>Appointments</span></a>
            <a href="add-doctor.php"   class="nav-item"><i class="fas fa-user-md"></i><span>Add Doctor</span></a>
            <a href="doctors-list.php" class="nav-item active"><i class="fas fa-list"></i><span>Doctors List</span></a>
            <a href="patients.php"     class="nav-item"><i class="fas fa-users"></i><span>Patients</span></a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <button class="mobile-menu-toggle" id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <h1>All Doctors</h1>
                    <p>Manage your hospital's medical professionals</p>
                </div>
            </div>
            <div class="user-info">
                <div class="admin-badge">
                    <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
                    <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if ($flash_success === 'deleted'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $flash_name; ?> has been deleted successfully.
        </div>
        <?php elseif ($flash_success === 'updated'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Doctor profile updated successfully.
        </div>
        <?php elseif ($flash_error === 'not_found'): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Doctor not found.</div>
        <?php elseif ($flash_error === 'db'): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Database error occurred. Please try again.</div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card-sm">
                <div class="stat-number"><?php echo $total_doctors; ?></div>
                <div class="stat-label">Total Doctors</div>
            </div>
            <div class="stat-card-sm">
                <div class="stat-number" style="color:#065f46;"><?php echo $available_count; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card-sm">
                <div class="stat-number" style="color:#dc2626;"><?php echo $unavailable_count; ?></div>
                <div class="stat-label">Unavailable</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form method="GET" style="display:contents;">
                <div class="filter-group">
                    <label>Speciality:</label>
                    <select name="speciality" onchange="this.form.submit()">
                        <option value="">All Specialities</option>
                        <?php foreach ($specialities as $spec): ?>
                        <option value="<?php echo htmlspecialchars($spec['speciality']); ?>"
                                <?php echo $speciality_filter===$spec['speciality']?'selected':''; ?>>
                            <?php echo htmlspecialchars($spec['speciality']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Availability:</label>
                    <select name="availability" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="available"   <?php echo $availability_filter==='available'  ?'selected':''; ?>>Available</option>
                        <option value="unavailable" <?php echo $availability_filter==='unavailable'?'selected':''; ?>>Unavailable</option>
                    </select>
                </div>
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by name, speciality or degree..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                <?php if (!empty($speciality_filter)||!empty($availability_filter)||!empty($search)): ?>
                <a href="doctors-list.php" class="reset-btn"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Doctor cards -->
        <?php if (!empty($doctors)): ?>
        <div class="doctors-grid">
            <?php foreach ($doctors as $doctor):
                $img_path = getDoctorImagePath($doctor['profile_image']);
                $fallback = 'https://placehold.co/400x500/DBEAFE/3B82F6?text=' . rawurlencode($doctor['name']);
                $initial  = strtoupper(substr($doctor['name'], 0, 1));
            ?>
            <div class="doctor-card">

                <div class="doctor-img-wrap">
                    <?php if ($img_path): ?>
                        <img src="<?php echo htmlspecialchars($img_path); ?>"
                             alt="<?php echo htmlspecialchars($doctor['name']); ?>"
                             loading="lazy"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="doctor-img-fallback" style="display:none;"><?php echo $initial; ?></div>
                    <?php else: ?>
                        <div class="doctor-img-fallback"><?php echo $initial; ?></div>
                    <?php endif; ?>
                </div>

                <div class="doctor-info">
                    <div class="doctor-header">
                        <h3 class="doctor-name"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                        <span class="availability-badge <?php echo $doctor['is_available']?'available':'unavailable'; ?>">
                            <i class="fas fa-circle" style="font-size:0.5rem;"></i>
                            <?php echo $doctor['is_available']?'Available':'Unavailable'; ?>
                        </span>
                    </div>
                    <p class="doctor-speciality"><?php echo htmlspecialchars($doctor['speciality']); ?></p>
                    <p class="doctor-details">
                        <?php echo htmlspecialchars($doctor['degree']); ?>
                        &bull;
                        <?php echo htmlspecialchars($doctor['experience']); ?>
                    </p>
                    <div class="doctor-footer">
                        <span class="doctor-fees">K<?php echo number_format($doctor['fees'],2); ?></span>
                        <?php if (!empty($doctor['rating']) && $doctor['rating'] > 0): ?>
                        <div class="doctor-rating">
                            <i class="fas fa-star"></i>
                            <span><?php echo number_format($doctor['rating'],1); ?></span>
                            <span style="color:#94a3b8;">(<?php echo intval($doctor['total_reviews']); ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="action-buttons" onclick="event.stopPropagation()">
                        <button class="action-btn delete" onclick="confirmDelete('<?php echo $doctor['doctor_id']; ?>','<?php echo htmlspecialchars(addslashes($doctor['name'])); ?>')">
                            <i class="fas fa-trash-alt"></i> Delete Doctor
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-md"></i>
            <p>No doctors found matching your criteria</p>
            <a href="add-doctor.php" class="reset-btn" style="display:inline-flex;margin-top:1rem;">
                <i class="fas fa-plus"></i> Add New Doctor
            </a>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- Confirm delete modal -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1050;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:18px;padding:2rem;max-width:380px;width:100%;text-align:center;box-shadow:0 20px 48px rgba(0,0,0,0.2);">
        <div style="width:60px;height:60px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.1rem;font-size:1.6rem;color:#ef4444;">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h3 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin-bottom:0.5rem;">Delete Doctor?</h3>
        <p style="font-size:0.875rem;color:#64748b;margin-bottom:1.5rem;" id="deleteMsg">This action cannot be undone.</p>
        <div style="display:flex;gap:0.75rem;justify-content:center;">
            <button onclick="closeDelete()" style="padding:0.65rem 1.5rem;border-radius:50px;border:1.5px solid #e2e8f0;background:#fff;font-size:0.875rem;font-weight:500;color:#64748b;cursor:pointer;font-family:'Outfit',sans-serif;">Cancel</button>
            <a id="deleteConfirmBtn" href="#" style="padding:0.65rem 1.5rem;border-radius:50px;background:#ef4444;color:#fff;font-size:0.875rem;font-weight:600;display:inline-flex;align-items:center;gap:0.4rem;">
                <i class="fas fa-trash-alt"></i> Yes, Delete
            </a>
        </div>
    </div>
</div>

<script>
/* Sidebar */
(function(){
    var toggle  = document.getElementById('mobileMenuToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    function open(){sidebar.classList.add('open');overlay.classList.add('active');document.body.style.overflow='hidden';}
    function close(){sidebar.classList.remove('open');overlay.classList.remove('active');document.body.style.overflow='';}
    if(toggle)  toggle.addEventListener('click',function(e){e.stopPropagation();sidebar.classList.contains('open')?close():open();});
    if(overlay) overlay.addEventListener('click',close);
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){close();closeDelete();}});
    window.addEventListener('resize',function(){if(window.innerWidth>768)close();});
})();

/* Delete modal */
var modal = document.getElementById('deleteModal');

function confirmDelete(id, name) {
    document.getElementById('deleteMsg').innerHTML = 'Are you sure you want to delete "<strong>' + name + '</strong>"?<br>All associated appointments and data will be affected.';
    document.getElementById('deleteConfirmBtn').href = 'delete-doctor.php?id=' + encodeURIComponent(id);
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeDelete() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

modal.addEventListener('click', function(e) {
    if (e.target === modal) closeDelete();
});

/* Auto-dismiss flash messages after 4 seconds */
(function(){
    var alerts = document.querySelectorAll('.alert');
    for (var i = 0; i < alerts.length; i++) {
        (function(el) {
            setTimeout(function() {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(function(){ el.style.display='none'; }, 500);
            }, 4000);
        })(alerts[i]);
    }
})();
</script>
</body>
</html>