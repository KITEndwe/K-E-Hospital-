<?php
/**
 * K&E Hospital - Shared Navbar Include
 * Usage: require_once 'navbar.php';
 * Variables expected from parent (with fallbacks):
 *   $is_logged_in (bool) - optional, will check session if not set
 *   $user_name    (string) - optional, will get from session if not set
 *   $current_page (string) - optional, defaults to basename($_SERVER['PHP_SELF'])
 *   $profile_image (string, optional) - path to user profile image
 */

// Set default values if variables are not defined
if (!isset($is_logged_in)) {
    $is_logged_in = isset($_SESSION['user_id']);
}

if (!isset($user_name) && isset($_SESSION['full_name'])) {
    $user_name = $_SESSION['full_name'];
} elseif (!isset($user_name)) {
    $user_name = '';
}

if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

$profile_img = isset($profile_image) ? $profile_image : '';

// Get profile image from database if logged in and not provided
if ($is_logged_in && empty($profile_img) && isset($_SESSION['user_id'])) {
    try {
        $host = 'localhost';
        $dbname = 'ke_hospital';
        $username_db = 'root';
        $password_db = '';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data && !empty($user_data['profile_image'])) {
            $profile_img = $user_data['profile_image'];
        }
    } catch (PDOException $e) {
        // Silently fail - profile image not critical
    }
}
?>
<!-- ═════════════ SHARED NAVBAR CSS ═════════════ -->
<style>
/* ── Navbar base ── */
.navbar {
    position:sticky; top:0; z-index:999;
    background:#fff; border-bottom:1px solid #ebebeb;
    height:70px; display:flex; align-items:center;
    padding:0 6%; justify-content:space-between; gap:1rem;
}
.nav-logo {
    display:flex; align-items:center; gap:8px;
    font-size:1.25rem; font-weight:700; color:#1a1a2e; flex-shrink:0;
}
.nav-logo img { width:100px; height:auto; }
.nav-logo-icon {
    width:34px; height:34px; background:#5f6fff; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:1rem;
}
.nav-links {
    display:flex; align-items:center; gap:2rem;
    list-style:none; flex:1; justify-content:center;
}
.nav-links a {
    font-size:0.88rem; font-weight:500; color:#595959;
    transition:color 0.2s; position:relative; padding-bottom:4px;
    white-space:nowrap;
}
.nav-links a:hover { color:#1a1a2e; }
.nav-links a.active { color:#1a1a2e; font-weight:600; }
.nav-links a.active::after {
    content:''; position:absolute; bottom:0; left:0; right:0;
    height:2px; background:#5f6fff; border-radius:2px;
}
.nav-cta { display:flex; align-items:center; gap:0.75rem; flex-shrink:0; }
.btn-nav-login {
    background:#fff; color:#5f6fff; border:1.5px solid #5f6fff;
    padding:0.5rem 1.25rem; border-radius:50px; font-size:0.85rem;
    font-weight:600; transition:all 0.2s; white-space:nowrap;
    cursor:pointer; font-family:'Outfit',sans-serif;
}
.btn-nav-login:hover { background:#f0f1ff; }
.btn-nav-create {
    background:#5f6fff; color:#fff; border:none;
    padding:0.55rem 1.4rem; border-radius:50px; font-size:0.85rem;
    font-weight:600; transition:all 0.25s; white-space:nowrap;
    cursor:pointer; font-family:'Outfit',sans-serif;
}
.btn-nav-create:hover {
    background:#4a5af0;
    box-shadow:0 4px 14px rgba(95,111,255,0.35);
    transform:translateY(-1px);
}

/* ── User dropdown ── */
.user-dropdown-wrap {
    position:relative; flex-shrink:0;
}
.user-trigger {
    display:flex; align-items:center; gap:0.5rem;
    cursor:pointer; padding:0.25rem 0.5rem; border-radius:50px;
    transition:background 0.2s; user-select:none;
}
.user-trigger:hover { background:#f5f5ff; }

/* Avatar circle with photo or initial */
.user-avatar-img {
    width:36px; height:36px; border-radius:50%;
    object-fit:cover; object-position:center;
    border:2px solid #e0e3ff;
    display:block;
}
.user-avatar-init {
    width:36px; height:36px; border-radius:50%;
    background:#5f6fff; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:0.82rem; font-weight:700;
    border:2px solid #e0e3ff;
    flex-shrink:0;
}
.user-trigger-caret {
    font-size:0.7rem; color:#9ca3af; transition:transform 0.25s;
}
.user-dropdown-wrap.open .user-trigger-caret { transform:rotate(180deg); }

/* Dropdown panel */
.user-dropdown {
    position:absolute; top:calc(100% + 10px); right:0;
    background:#fff; border:1px solid #e8eaf0;
    border-radius:12px; min-width:180px;
    box-shadow:0 8px 32px rgba(0,0,0,0.12);
    padding:0.5rem 0;
    opacity:0; visibility:hidden; transform:translateY(-6px);
    transition:all 0.22s ease; z-index:1000;
}
.user-dropdown-wrap.open .user-dropdown {
    opacity:1; visibility:visible; transform:translateY(0);
}

/* Dropdown header */
.dd-header {
    padding:0.65rem 1rem 0.5rem;
    border-bottom:1px solid #f0f0f5; margin-bottom:0.35rem;
}
.dd-header .dd-name {
    font-size:0.85rem; font-weight:600; color:#1a1a2e;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    max-width:150px;
}
.dd-header .dd-role {
    font-size:0.72rem; color:#9ca3af; margin-top:1px;
}

/* Dropdown links */
.dd-item {
    display:flex; align-items:center; gap:0.65rem;
    padding:0.6rem 1rem;
    font-size:0.875rem; font-weight:500; color:#3c3c3c;
    cursor:pointer; transition:all 0.18s; text-decoration:none;
}
.dd-item:hover { background:#f5f5ff; color:#5f6fff; }
.dd-item i {
    width:16px; text-align:center; font-size:0.82rem; color:#9ca3af;
    transition:color 0.18s;
}
.dd-item:hover i { color:#5f6fff; }
.dd-divider { height:1px; background:#f0f0f5; margin:0.35rem 0; }
.dd-item.logout { color:#ef4444; }
.dd-item.logout i { color:#ef4444; }
.dd-item.logout:hover { background:#fff5f5; color:#dc2626; }
.dd-item.logout:hover i { color:#dc2626; }

/* Hamburger */
.hamburger {
    display:none; flex-direction:column; justify-content:center; gap:5px;
    width:40px; height:40px; background:none; border:none;
    cursor:pointer; padding:6px; flex-shrink:0;
}
.hamburger span {
    display:block; width:22px; height:2px;
    background:#1a1a2e; border-radius:2px; transition:all 0.3s ease;
}
.hamburger.open span:nth-child(1) { transform:translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity:0; transform:scaleX(0); }
.hamburger.open span:nth-child(3) { transform:translateY(-7px) rotate(-45deg); }

/* Mobile drawer */
.mobile-menu {
    position:fixed; top:70px; left:0; right:0;
    background:#fff; border-bottom:1px solid #ebebeb;
    box-shadow:0 8px 32px rgba(0,0,0,0.12);
    z-index:998; padding:0 6% 1.5rem;
    max-height:0; overflow:hidden;
    transition:max-height 0.35s ease, padding 0.35s ease;
}
.mobile-menu.open { max-height:600px; padding:1rem 6% 1.5rem; }
.mobile-menu a.mob-link {
    display:block; padding:0.85rem 0;
    font-size:0.95rem; font-weight:500; color:#3c3c3c;
    border-bottom:1px solid #f2f2f2; transition:color 0.2s;
}
.mobile-menu a.mob-link:hover,
.mobile-menu a.mob-link.active { color:#5f6fff; }
.mobile-menu .mob-actions {
    margin-top:1rem; display:flex; flex-direction:column; gap:0.75rem;
}
.mob-btn {
    display:block; text-align:center; padding:0.75rem;
    border-radius:50px; font-size:0.9rem; font-weight:600; transition:all 0.2s;
}
.mob-btn-outline { border:1.5px solid #5f6fff; color:#5f6fff; background:#fff; }
.mob-btn-fill { background:#5f6fff; color:#fff; border:none; }
.mob-btn-fill:hover { background:#4a5af0; }

/* Mobile user section */
.mob-user-section {
    display:flex; align-items:center; gap:0.75rem;
    padding:0.75rem 0; border-bottom:1px solid #f2f2f2;
    margin-bottom:0.25rem;
}
.mob-user-avatar {
    width:40px; height:40px; border-radius:50%;
    background:#5f6fff; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:0.875rem; font-weight:700; flex-shrink:0;
    overflow:hidden;
}
.mob-user-avatar img {
    width:100%; height:100%; object-fit:cover; object-position:center;
}
.mob-user-name { font-size:0.9rem; font-weight:600; color:#1a1a2e; }
.mob-user-role { font-size:0.72rem; color:#9ca3af; }
.mob-menu-link {
    display:flex; align-items:center; gap:0.65rem;
    padding:0.75rem 0; font-size:0.9rem; font-weight:500; color:#3c3c3c;
    border-bottom:1px solid #f2f2f2; transition:color 0.2s;
}
.mob-menu-link i { width:18px; text-align:center; color:#9ca3af; font-size:0.85rem; }
.mob-menu-link:hover { color:#5f6fff; }
.mob-menu-link.mob-logout { color:#ef4444; }
.mob-menu-link.mob-logout i { color:#ef4444; }

.nav-overlay {
    display:none; position:fixed; inset:0; top:70px;
    background:rgba(0,0,0,0.25); z-index:997;
}
.nav-overlay.open { display:block; }

/* Responsive */
@media (max-width:768px) {
    .nav-links  { display:none !important; }
    .nav-cta    { display:none !important; }
    .user-dropdown-wrap { display:none !important; }
    .hamburger  { display:flex; }
    .navbar { padding:0 5%; height:64px; }
    .mobile-menu { top:64px; }
    .nav-overlay { top:64px; }
}
</style>

<!-- ═════════════ NAVBAR HTML ═════════════ -->
<nav class="navbar">

    <!-- Logo -->
    <a href="index.php" class="nav-logo">
        <img src="assets/logo.svg" width="100px" alt="K&amp;E Hospital">
    </a>

    <!-- Desktop links -->
    <ul class="nav-links">
        <li><a href="index.php"      class="<?php echo ($current_page==='index.php')       ? 'active':''; ?>">HOME</a></li>
        <li><a href="Alldoctors.php" class="<?php echo ($current_page==='Alldoctors.php')  ? 'active':''; ?>">ALL DOCTORS</a></li>
        <li><a href="about.php"      class="<?php echo ($current_page==='about.php')       ? 'active':''; ?>">ABOUT</a></li>
        <li><a href="contact.php"    class="<?php echo ($current_page==='contact.php')     ? 'active':''; ?>">CONTACT</a></li>
    </ul>

    <!-- Desktop CTA / User dropdown -->
    <div class="nav-cta">
        <?php if ($is_logged_in): ?>

            <!-- ── User dropdown ── -->
            <div class="user-dropdown-wrap" id="userDropdown">
                <div class="user-trigger" onclick="toggleDropdown()">
                    <?php if (!empty($profile_img)): ?>
                        <img class="user-avatar-img"
                             src="<?php echo htmlspecialchars($profile_img); ?>"
                             alt="<?php echo htmlspecialchars($user_name); ?>"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="user-avatar-init" style="display:none;">
                            <?php echo strtoupper(substr($user_name,0,1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="user-avatar-init">
                            <?php echo strtoupper(substr($user_name,0,1)); ?>
                        </div>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down user-trigger-caret"></i>
                </div>

                <div class="user-dropdown">
                    <div class="dd-header">
                        <div class="dd-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="dd-role">Patient</div>
                    </div>
                    <a class="dd-item" href="Myprofile.php">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a class="dd-item" href="Myappointment.php">
                        <i class="fas fa-calendar-check"></i> My Appointments
                    </a>
                    <div class="dd-divider"></div>
                    <a class="dd-item logout" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

        <?php else: ?>
            <a href="login.php" class="btn-nav-login">Login</a>
            <a href="login.php?action=register" class="btn-nav-create">Create account</a>
        <?php endif; ?>
    </div>

    <!-- Hamburger -->
    <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- Mobile drawer -->
<div class="mobile-menu" id="mobileMenu">
    <a href="index.php"      class="mob-link <?php echo ($current_page==='index.php')      ? 'active':''; ?>">Home</a>
    <a href="Alldoctors.php" class="mob-link <?php echo ($current_page==='Alldoctors.php') ? 'active':''; ?>">All Doctors</a>
    <a href="about.php"      class="mob-link <?php echo ($current_page==='about.php')      ? 'active':''; ?>">About</a>
    <a href="contact.php"    class="mob-link <?php echo ($current_page==='contact.php')    ? 'active':''; ?>">Contact</a>

    <div class="mob-actions">
        <?php if ($is_logged_in): ?>
            <!-- Mobile user section -->
            <div class="mob-user-section">
                <div class="mob-user-avatar">
                    <?php if (!empty($profile_img)): ?>
                        <img src="<?php echo htmlspecialchars($profile_img); ?>"
                             alt="<?php echo htmlspecialchars($user_name); ?>"
                             onerror="this.style.display='none';this.parentElement.textContent='<?php echo strtoupper(substr($user_name,0,1)); ?>';">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name,0,1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="mob-user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="mob-user-role">Patient</div>
                </div>
            </div>
            <a href="Myprofile.php"     class="mob-menu-link"><i class="fas fa-user-circle"></i> My Profile</a>
            <a href="Myappointment.php" class="mob-menu-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
            <a href="logout.php"         class="mob-menu-link mob-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"                  class="mob-btn mob-btn-outline">Login</a>
            <a href="login.php?action=register"  class="mob-btn mob-btn-fill">Create account</a>
        <?php endif; ?>
    </div>
</div>
<div class="nav-overlay" id="navOverlay"></div>

<!-- ═════════════ NAVBAR SCRIPTS ═════════════ -->
<script>
(function() {
    var hamburger  = document.getElementById('hamburger');
    var mobileMenu = document.getElementById('mobileMenu');
    var overlay    = document.getElementById('navOverlay');
    var dropdown   = document.getElementById('userDropdown');

    /* Mobile menu */
    function openMenu() {
        hamburger.classList.add('open');
        mobileMenu.classList.add('open');
        overlay.classList.add('open');
        hamburger.setAttribute('aria-expanded','true');
        document.body.style.overflow = 'hidden';
    }
    function closeMenu() {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        overlay.classList.remove('open');
        hamburger.setAttribute('aria-expanded','false');
        document.body.style.overflow = '';
    }
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            mobileMenu.classList.contains('open') ? closeMenu() : openMenu();
        });
    }
    if (overlay) overlay.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeMenu(); closeDropdown(); }
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) closeMenu();
    });

    /* User dropdown */
    function closeDropdown() {
        if (dropdown) dropdown.classList.remove('open');
    }
    document.addEventListener('click', function(e) {
        if (dropdown && !dropdown.contains(e.target)) closeDropdown();
    });
})();

function toggleDropdown() {
    var dd = document.getElementById('userDropdown');
    if (dd) dd.classList.toggle('open');
}
</script>