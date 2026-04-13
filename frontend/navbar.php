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
<link rel="stylesheet" href="./Css/navbar.css">

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