<?php
/**
 * K&E Hospital - Shared Navbar Include
 * Usage: require_once 'navbar.php';
 * Variables expected from parent (all optional - have fallbacks):
 *   $is_logged_in  (bool)
 *   $user_name     (string)
 *   $current_page  (string)
 *   $profile_image (string) - web path to user photo
 */

/* ── Defaults ── */
if (!isset($is_logged_in)) {
    $is_logged_in = isset($_SESSION['user_id']);
}
if (!isset($user_name)) {
    $user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
}
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

$profile_img = '';

/* Priority 1: passed in by page */
if (!empty($profile_image)) {
    $profile_img = $profile_image;
}

/* Priority 2: session cache */
if (empty($profile_img) && !empty($_SESSION['profile_image'])) {
    $profile_img = $_SESSION['profile_image'];
}

/* Priority 3: fresh DB lookup */
if ($is_logged_in && empty($profile_img) && isset($_SESSION['user_id'])) {
    try {
        $nb_pdo = new PDO(
            "mysql:host=localhost;dbname=ke_hospital;charset=utf8",
            'root', '',
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        $nb_stmt = $nb_pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?");
        $nb_stmt->execute(array($_SESSION['user_id']));
        $nb_row = $nb_stmt->fetch(PDO::FETCH_ASSOC);
        if ($nb_row && !empty($nb_row['profile_image'])) {
            /* DB stores path relative to project root e.g. uploads/profiles/user_1_xx.jpg */
            /* navbar.php is in frontend/ so prefix with ../ */
            $profile_img = '../' . ltrim($nb_row['profile_image'], '/');
            $_SESSION['profile_image'] = $profile_img; /* cache */
        }
    } catch (Exception $e) {
        /* silently ignore — avatar not critical */
    }
}

$_initial = $is_logged_in ? strtoupper(substr($user_name, 0, 1)) : 'U';
?>
<!-- ═══════════════════════════════════════════════
     NAVBAR CSS
═══════════════════════════════════════════════ -->
<style>
/* ── Base ── */
.navbar {
    position: sticky; top: 0; z-index: 999;
    background: #fff; border-bottom: 1px solid #ebebeb;
    height: 70px; display: flex; align-items: center;
    padding: 0 6%; justify-content: space-between; gap: 1rem;
}

/* ── Logo ── */
.nav-logo {
    display: flex; align-items: center; gap: 8px;
    font-size: 1.25rem; font-weight: 700; color: #1a1a2e;
    text-decoration: none; flex-shrink: 0;
}
.nav-logo-icon {
    width: 34px; height: 34px; background: #5f6fff;
    border-radius: 8px; display: flex; align-items: center;
    justify-content: center; color: #fff; font-size: 1rem;
    flex-shrink: 0;
}

/* ── Desktop nav links ── */
.nav-links {
    display: flex; align-items: center; gap: 2rem;
    list-style: none; flex: 1; justify-content: center; margin: 0; padding: 0;
}
.nav-links a {
    font-size: 0.88rem; font-weight: 500; color: #595959;
    text-decoration: none; position: relative; padding-bottom: 4px;
    white-space: nowrap; transition: color 0.2s;
}
.nav-links a:hover { color: #1a1a2e; }
.nav-links a.active { color: #1a1a2e; font-weight: 600; }
.nav-links a.active::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0;
    height: 2px; background: #5f6fff; border-radius: 2px;
}

/* ── Desktop CTA ── */
.nav-cta { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
.btn-nav-login {
    background: #fff; color: #5f6fff; border: 1.5px solid #5f6fff;
    padding: 0.5rem 1.25rem; border-radius: 50px; font-size: 0.85rem;
    font-weight: 600; transition: all 0.2s; white-space: nowrap;
    cursor: pointer; font-family: 'Outfit', sans-serif; text-decoration: none;
    display: inline-block;
}
.btn-nav-login:hover { background: #f0f1ff; }
.btn-nav-create {
    background: #5f6fff; color: #fff; border: none;
    padding: 0.55rem 1.4rem; border-radius: 50px; font-size: 0.85rem;
    font-weight: 600; transition: all 0.25s; white-space: nowrap;
    cursor: pointer; font-family: 'Outfit', sans-serif; text-decoration: none;
    display: inline-block;
}
.btn-nav-create:hover {
    background: #4a5af0;
    box-shadow: 0 4px 14px rgba(95,111,255,0.35);
    transform: translateY(-1px);
}

/* ── User avatar + dropdown ── */
.user-dropdown-wrap { position: relative; flex-shrink: 0; }
.user-trigger {
    display: flex; align-items: center; gap: 0.5rem;
    cursor: pointer; padding: 0.3rem 0.6rem; border-radius: 50px;
    transition: background 0.2s; user-select: none; border: none; background: none;
}
.user-trigger:hover { background: #f5f5ff; }
.user-avatar-img {
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; object-position: center;
    border: 2px solid #e0e3ff; display: block; flex-shrink: 0;
}
.user-avatar-init {
    width: 36px; height: 36px; border-radius: 50%;
    background: #5f6fff; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.82rem; font-weight: 700;
    border: 2px solid #e0e3ff; flex-shrink: 0;
}
.user-caret {
    font-size: 0.68rem; color: #9ca3af;
    transition: transform 0.25s; display: block;
}
.user-dropdown-wrap.open .user-caret { transform: rotate(180deg); }

/* Dropdown panel */
.user-dropdown {
    position: absolute; top: calc(100% + 8px); right: 0;
    background: #fff; border: 1px solid #e8eaf0;
    border-radius: 12px; min-width: 185px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    padding: 0.4rem 0;
    opacity: 0; visibility: hidden; transform: translateY(-8px);
    transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s;
    z-index: 1001; pointer-events: none;
}
.user-dropdown-wrap.open .user-dropdown {
    opacity: 1; visibility: visible; transform: translateY(0);
    pointer-events: auto;
}
.dd-header {
    padding: 0.6rem 1rem 0.5rem;
    border-bottom: 1px solid #f0f0f5; margin-bottom: 0.3rem;
}
.dd-name {
    font-size: 0.85rem; font-weight: 600; color: #1a1a2e;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 155px;
}
.dd-role { font-size: 0.7rem; color: #9ca3af; margin-top: 1px; }
.dd-item {
    display: flex; align-items: center; gap: 0.65rem;
    padding: 0.55rem 1rem; font-size: 0.875rem; font-weight: 500;
    color: #3c3c3c; text-decoration: none; transition: all 0.15s;
}
.dd-item:hover { background: #f5f5ff; color: #5f6fff; }
.dd-item i { width: 16px; text-align: center; font-size: 0.82rem; color: #b0b7c3; }
.dd-item:hover i { color: #5f6fff; }
.dd-divider { height: 1px; background: #f0f0f5; margin: 0.3rem 0; }
.dd-item.dd-logout { color: #ef4444; }
.dd-item.dd-logout i { color: #ef4444; }
.dd-item.dd-logout:hover { background: #fff5f5; color: #dc2626; }
.dd-item.dd-logout:hover i { color: #dc2626; }

/* ── Hamburger ── */
.hamburger {
    display: none; flex-direction: column;
    justify-content: center; align-items: center; gap: 5px;
    width: 40px; height: 40px; background: none; border: none;
    cursor: pointer; padding: 6px; flex-shrink: 0;
    border-radius: 8px; transition: background 0.2s;
}
.hamburger:hover { background: #f5f5ff; }
.hamburger span {
    display: block; width: 22px; height: 2px;
    background: #1a1a2e; border-radius: 2px;
    transition: transform 0.3s ease, opacity 0.3s ease, width 0.3s ease;
    transform-origin: center;
}
/* X state */
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ── Mobile drawer ── */
.mobile-menu {
    position: fixed; top: 70px; left: 0; right: 0;
    background: #fff; border-bottom: 1px solid #ebebeb;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    z-index: 998; overflow: hidden;
    max-height: 0; padding: 0 6%;
    transition: max-height 0.35s ease, padding 0.35s ease;
}
.mobile-menu.open {
    max-height: 700px;
    padding: 1rem 6% 1.5rem;
}
.mob-link {
    display: block; padding: 0.85rem 0;
    font-size: 0.95rem; font-weight: 500; color: #3c3c3c;
    border-bottom: 1px solid #f2f2f2; text-decoration: none;
    transition: color 0.2s;
}
.mob-link:last-of-type { border-bottom: none; }
.mob-link:hover, .mob-link.active { color: #5f6fff; }
.mob-actions {
    margin-top: 1rem; padding-top: 0.5rem;
    display: flex; flex-direction: column; gap: 0.65rem;
}

/* Mobile user block */
.mob-user-row {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.75rem 0; border-bottom: 1px solid #f2f2f2; margin-bottom: 0.25rem;
}
.mob-user-ava {
    width: 42px; height: 42px; border-radius: 50%;
    background: #5f6fff; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; font-weight: 700; flex-shrink: 0; overflow: hidden;
    border: 2px solid #e0e3ff;
}
.mob-user-ava img { width: 100%; height: 100%; object-fit: cover; }
.mob-user-name { font-size: 0.9rem; font-weight: 600; color: #1a1a2e; }
.mob-user-sub  { font-size: 0.7rem; color: #9ca3af; }
.mob-dd-link {
    display: flex; align-items: center; gap: 0.65rem;
    padding: 0.75rem 0; font-size: 0.9rem; font-weight: 500; color: #3c3c3c;
    border-bottom: 1px solid #f2f2f2; text-decoration: none; transition: color 0.2s;
}
.mob-dd-link i { width: 18px; text-align: center; color: #b0b7c3; font-size: 0.85rem; }
.mob-dd-link:hover { color: #5f6fff; }
.mob-dd-link:hover i { color: #5f6fff; }
.mob-dd-link.mob-logout-link { color: #ef4444; border-bottom: none; }
.mob-dd-link.mob-logout-link i { color: #ef4444; }
.mob-dd-link.mob-logout-link:hover { color: #dc2626; }
.mob-btn {
    display: block; text-align: center; padding: 0.75rem;
    border-radius: 50px; font-size: 0.9rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
}
.mob-btn-outline { border: 1.5px solid #5f6fff; color: #5f6fff; background: #fff; }
.mob-btn-outline:hover { background: #f0f1ff; }
.mob-btn-fill { background: #5f6fff; color: #fff; border: none; }
.mob-btn-fill:hover { background: #4a5af0; }

/* ── Overlay ── */
.nav-overlay {
    display: none; position: fixed; inset: 0; top: 70px;
    background: rgba(0,0,0,0.28); z-index: 997;
}
.nav-overlay.open { display: block; }

/* ── Responsive breakpoint ── */
@media (max-width: 768px) {
    .nav-links            { display: none !important; }
    .nav-cta              { display: none !important; }
    .user-dropdown-wrap   { display: none !important; }
    .hamburger            { display: flex; }
    .navbar               { padding: 0 5%; height: 64px; }
    .mobile-menu          { top: 64px; }
    .nav-overlay          { top: 64px; }
}
</style>

<!-- ═══════════════════════════════════════════════
     NAVBAR HTML
═══════════════════════════════════════════════ -->
<nav class="navbar">

    <!-- Logo -->
    <a href="index.php" >
        <div ><img src="assets/logo.svg" width="100px" alt=""></div>
        
    </a>

    <!-- Desktop nav links -->
    <ul class="nav-links">
        <li><a href="index.php"      class="<?php echo $current_page === 'index.php'      ? 'active' : ''; ?>">HOME</a></li>
        <li><a href="Alldoctors.php" class="<?php echo $current_page === 'Alldoctors.php' ? 'active' : ''; ?>">ALL DOCTORS</a></li>
        <li><a href="about.php"      class="<?php echo $current_page === 'about.php'      ? 'active' : ''; ?>">ABOUT</a></li>
        <li><a href="contact.php"    class="<?php echo $current_page === 'contact.php'    ? 'active' : ''; ?>">CONTACT</a></li>
    </ul>

    <!-- Desktop right side -->
    <div class="nav-cta">
        <?php if ($is_logged_in): ?>
            <div class="user-dropdown-wrap" id="userDropdown">
                <button class="user-trigger" id="userTrigger" type="button" aria-haspopup="true" aria-expanded="false">
                    <?php if (!empty($profile_img)): ?>
                        <img class="user-avatar-img"
                             src="<?php echo htmlspecialchars($profile_img); ?>"
                             alt="<?php echo htmlspecialchars($user_name); ?>"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="user-avatar-init" style="display:none;"><?php echo $_initial; ?></div>
                    <?php else: ?>
                        <div class="user-avatar-init"><?php echo $_initial; ?></div>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down user-caret"></i>
                </button>
                <div class="user-dropdown" id="userDropMenu" role="menu">
                    <div class="dd-header">
                        <div class="dd-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="dd-role">Patient</div>
                    </div>
                    <a class="dd-item" href="Myprofile.php" role="menuitem">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a class="dd-item" href="Myappointment.php" role="menuitem">
                        <i class="fas fa-calendar-check"></i> My Appointments
                    </a>
                    <div class="dd-divider"></div>
                    <a class="dd-item dd-logout" href="logout.php" role="menuitem">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php"                class="btn-nav-login">Login</a>
            <a href="login.php?action=register" class="btn-nav-create">Create account</a>
        <?php endif; ?>
    </div>

    <!-- Hamburger (mobile only) -->
    <button class="hamburger" id="navHamburger" aria-label="Open menu" aria-expanded="false" aria-controls="mobileDrawer">
        <span></span>
        <span></span>
        <span></span>
    </button>
</nav>

<!-- Mobile drawer -->
<div class="mobile-menu" id="mobileDrawer" role="navigation" aria-label="Mobile navigation">
    <a href="index.php"      class="mob-link <?php echo $current_page === 'index.php'      ? 'active' : ''; ?>">Home</a>
    <a href="Alldoctors.php" class="mob-link <?php echo $current_page === 'Alldoctors.php' ? 'active' : ''; ?>">All Doctors</a>
    <a href="about.php"      class="mob-link <?php echo $current_page === 'about.php'      ? 'active' : ''; ?>">About</a>
    <a href="contact.php"    class="mob-link <?php echo $current_page === 'contact.php'    ? 'active' : ''; ?>">Contact</a>

    <div class="mob-actions">
        <?php if ($is_logged_in): ?>
            <div class="mob-user-row">
                <div class="mob-user-ava">
                    <?php if (!empty($profile_img)): ?>
                        <img src="<?php echo htmlspecialchars($profile_img); ?>"
                             alt="<?php echo htmlspecialchars($user_name); ?>"
                             onerror="this.style.display='none';this.parentElement.textContent='<?php echo $_initial; ?>';">
                    <?php else: ?>
                        <?php echo $_initial; ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="mob-user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="mob-user-sub">Patient</div>
                </div>
            </div>
            <a href="Myprofile.php"     class="mob-dd-link"><i class="fas fa-user-circle"></i> My Profile</a>
            <a href="Myappointment.php" class="mob-dd-link"><i class="fas fa-calendar-check"></i> My Appointments</a>
            <a href="logout.php"         class="mob-dd-link mob-logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"                 class="mob-btn mob-btn-outline">Login</a>
            <a href="login.php?action=register" class="mob-btn mob-btn-fill">Create account</a>
        <?php endif; ?>
    </div>
</div>

<!-- Overlay -->
<div class="nav-overlay" id="navOverlayBg"></div>

<!-- ═══════════════════════════════════════════════
     NAVBAR JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Element references ── */
    var hamburger   = document.getElementById('navHamburger');
    var drawer      = document.getElementById('mobileDrawer');
    var overlay     = document.getElementById('navOverlayBg');
    var userWrap    = document.getElementById('userDropdown');
    var userTrigger = document.getElementById('userTrigger');
    var userMenu    = document.getElementById('userDropMenu');

    /* ════════════════════════
       MOBILE DRAWER
    ════════════════════════ */
    function openDrawer() {
        if (!hamburger || !drawer) return;
        hamburger.classList.add('open');
        drawer.classList.add('open');
        overlay.classList.add('open');
        hamburger.setAttribute('aria-expanded', 'true');
        hamburger.setAttribute('aria-label', 'Close menu');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (!hamburger || !drawer) return;
        hamburger.classList.remove('open');
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-label', 'Open menu');
        document.body.style.overflow = '';
    }

    function toggleDrawer() {
        if (drawer && drawer.classList.contains('open')) {
            closeDrawer();
        } else {
            openDrawer();
        }
    }

    if (hamburger) {
        hamburger.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleDrawer();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            closeDrawer();
            closeDropdown();
        });
    }

    /* Close drawer when a link inside it is tapped */
    if (drawer) {
        var drawerLinks = drawer.querySelectorAll('a');
        for (var i = 0; i < drawerLinks.length; i++) {
            drawerLinks[i].addEventListener('click', function () {
                closeDrawer();
            });
        }
    }

    /* Close on resize to desktop */
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            closeDrawer();
        }
    });

    /* ════════════════════════
       DESKTOP USER DROPDOWN
    ════════════════════════ */
    function openDropdown() {
        if (!userWrap) return;
        userWrap.classList.add('open');
        if (userTrigger) userTrigger.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
        if (!userWrap) return;
        userWrap.classList.remove('open');
        if (userTrigger) userTrigger.setAttribute('aria-expanded', 'false');
    }

    function toggleDropdown() {
        if (userWrap && userWrap.classList.contains('open')) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }

    /* Expose toggleDropdown globally (used by inline onclick as fallback) */
    window.toggleDropdown = toggleDropdown;

    if (userTrigger) {
        userTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleDropdown();
        });
    }

    /* Click outside → close dropdown */
    document.addEventListener('click', function (e) {
        if (userWrap && !userWrap.contains(e.target)) {
            closeDropdown();
        }
    });

    /* ════════════════════════
       SHARED: Escape key
    ════════════════════════ */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDrawer();
            closeDropdown();
        }
    });

})();
</script>