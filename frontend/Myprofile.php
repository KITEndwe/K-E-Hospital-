<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Prescripto</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Sora:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary: #5F6FFF;
    --primary-light: #eef0ff;
    --accent: #3ECFCF;
    --text: #1a1a2e;
    --muted: #6b7280;
    --border: #e5e7eb;
    --bg: #f9fafb;
    --white: #ffffff;
    --green: #10b981;
    --red: #ef4444;
    --card-shadow: 0 2px 12px rgba(0,0,0,0.07);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  /* NAV */
  nav {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 0 48px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Sora', sans-serif;
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--primary);
    text-decoration: none;
  }

  .logo-icon {
    width: 32px;
    height: 32px;
    background: var(--primary);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
  }

  .nav-links {
    display: flex;
    gap: 32px;
    list-style: none;
  }

  .nav-links a {
    text-decoration: none;
    color: var(--muted);
    font-size: 0.875rem;
    font-weight: 500;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    transition: color 0.2s;
  }

  .nav-links a:hover, .nav-links a.active {
    color: var(--primary);
  }

  .nav-links a.active {
    border-bottom: 2px solid var(--primary);
    padding-bottom: 2px;
  }

  .nav-user {
    position: relative;
  }

  .nav-user-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    background: none;
    border: none;
    padding: 6px 10px;
    border-radius: 8px;
    transition: background 0.2s;
  }

  .nav-user-btn:hover { background: var(--bg); }

  .nav-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--primary-light);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
  }

  .nav-avatar img { width: 100%; height: 100%; object-fit: cover; }

  .dropdown-arrow {
    color: var(--muted);
    font-size: 12px;
    transition: transform 0.2s;
  }

  .dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    min-width: 160px;
    overflow: hidden;
    display: none;
  }

  .dropdown-menu.open { display: block; }

  .dropdown-menu a {
    display: block;
    padding: 10px 16px;
    font-size: 0.875rem;
    color: var(--text);
    text-decoration: none;
    transition: background 0.15s;
  }

  .dropdown-menu a:hover { background: var(--bg); }

  .dropdown-menu a.active-page { color: var(--primary); font-weight: 500; }

  /* PAGE TABS */
  .page { display: none; }
  .page.active { display: block; }

  /* PROFILE PAGE */
  .profile-container {
    max-width: 760px;
    margin: 48px auto;
    padding: 0 24px;
  }

  .profile-header {
    display: flex;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 32px;
  }

  .avatar-slot {
    width: 96px;
    height: 96px;
    border-radius: 14px;
    overflow: hidden;
    background: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 32px;
    cursor: pointer;
    border: 2px solid var(--border);
    transition: border-color 0.2s;
    position: relative;
  }

  .avatar-slot:hover { border-color: var(--primary); }

  .avatar-slot img { width: 100%; height: 100%; object-fit: cover; }

  .avatar-placeholder {
    width: 96px;
    height: 96px;
    border-radius: 14px;
    background: var(--primary-light);
    border: 2px dashed var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    font-size: 28px;
  }

  .profile-name {
    font-family: 'Sora', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text);
    padding-bottom: 8px;
    border-bottom: 2px solid var(--border);
    flex: 1;
  }

  .info-section {
    background: var(--white);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
  }

  .info-section-title {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 16px;
  }

  .info-row {
    display: grid;
    grid-template-columns: 120px 1fr;
    align-items: start;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
  }

  .info-row:last-child { border-bottom: none; }

  .info-label {
    font-size: 0.85rem;
    color: var(--muted);
    font-weight: 500;
  }

  .info-value {
    font-size: 0.9rem;
    color: var(--text);
  }

  .info-value.link { color: var(--primary); }

  .info-value input {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 6px 10px;
    font-family: inherit;
    font-size: 0.9rem;
    color: var(--text);
    outline: none;
    transition: border-color 0.2s;
  }

  .info-value input:focus { border-color: var(--primary); }

  .profile-actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
  }

  .btn {
    padding: 10px 24px;
    border-radius: 50px;
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid var(--border);
    background: var(--white);
    color: var(--text);
  }

  .btn:hover { background: var(--bg); border-color: var(--muted); }

  .btn-primary {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
  }

  .btn-primary:hover { background: #4a5aef; border-color: #4a5aef; }

  /* APPOINTMENTS PAGE */
  .appt-container {
    max-width: 860px;
    margin: 48px auto;
    padding: 0 24px;
  }

  .appt-title {
    font-family: 'Sora', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 24px;
    color: var(--text);
  }

  .appt-card {
    background: var(--white);
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    display: grid;
    grid-template-columns: 110px 1fr auto;
    gap: 0;
    margin-bottom: 16px;
    overflow: hidden;
    border: 1px solid var(--border);
    transition: box-shadow 0.2s;
  }

  .appt-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }

  .appt-img {
    width: 110px;
    height: 110px;
    object-fit: cover;
    background: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 36px;
  }

  .appt-img img { width: 100%; height: 100%; object-fit: cover; }

  .appt-info {
    padding: 16px 20px;
  }

  .appt-doctor {
    font-family: 'Sora', sans-serif;
    font-weight: 600;
    font-size: 1rem;
    color: var(--text);
    margin-bottom: 2px;
  }

  .appt-specialty {
    font-size: 0.8rem;
    color: var(--muted);
    margin-bottom: 10px;
  }

  .appt-detail {
    font-size: 0.82rem;
    color: var(--muted);
    margin-bottom: 3px;
  }

  .appt-detail strong {
    color: var(--text);
    font-weight: 500;
  }

  .appt-actions {
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 8px;
    min-width: 160px;
    border-left: 1px solid var(--border);
  }

  .btn-pay {
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 9px 18px;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
  }

  .btn-pay:hover { background: #4a5aef; }

  .btn-paid {
    background: var(--green);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 9px 18px;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: default;
  }

  .btn-cancel {
    background: transparent;
    color: var(--muted);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 9px 18px;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-cancel:hover { border-color: var(--red); color: var(--red); }

  @media (max-width: 600px) {
    nav { padding: 0 16px; }
    .nav-links { display: none; }
    .appt-card { grid-template-columns: 80px 1fr; }
    .appt-actions { grid-column: 1/-1; border-left: none; border-top: 1px solid var(--border); flex-direction: row; }
  }
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a class="logo" href="#">
    <div class="logo-icon">✦</div>
    Prescripto
  </a>
  <ul class="nav-links">
    <li><a href="#">Home</a></li>
    <li><a href="#" class="active">All Doctors</a></li>
    <li><a href="#">About</a></li>
    <li><a href="#">Contact</a></li>
  </ul>
  <div class="nav-user">
    <button class="nav-user-btn" onclick="toggleDropdown()">
      <div class="nav-avatar">👤</div>
      <span class="dropdown-arrow">▾</span>
    </button>
    <div class="dropdown-menu" id="dropdown">
      <a href="#" onclick="showPage('profile'); closeDropdown(); return false;" class="active-page">My Profile</a>
      <a href="#" onclick="showPage('appointments'); closeDropdown(); return false;">My Appointments</a>
      <a href="#">Logout</a>
    </div>
  </div>
</nav>

<!-- PROFILE PAGE -->
<div class="page active" id="page-profile">
  <div class="profile-container">
    <div class="profile-header">
      <div class="avatar-slot">
        <img src="https://i.pravatar.cc/96?img=12" alt="Edward Vincent"/>
      </div>
      <div class="avatar-placeholder">＋</div>
      <h1 class="profile-name">Edward Vincent</h1>
    </div>

    <div class="info-section">
      <div class="info-section-title">Contact Information</div>
      <div class="info-row">
        <span class="info-label">Email Id</span>
        <span class="info-value link" id="email-display">richardjameswap@gmail.com</span>
        <input class="info-value" id="email-input" style="display:none" value="richardjameswap@gmail.com"/>
      </div>
      <div class="info-row">
        <span class="info-label">Phone</span>
        <span class="info-value link" id="phone-display">+1 123 456 7890</span>
        <input class="info-value" id="phone-input" style="display:none" value="+1 123 456 7890"/>
      </div>
      <div class="info-row">
        <span class="info-label">Address</span>
        <span class="info-value" id="address-display">57th Cross, Richmond Circle, Church Road, London</span>
        <input class="info-value" id="address-input" style="display:none" value="57th Cross, Richmond Circle, Church Road, London"/>
      </div>
    </div>

    <div class="info-section">
      <div class="info-section-title">Basic Information</div>
      <div class="info-row">
        <span class="info-label">Gender</span>
        <span class="info-value" id="gender-display">Male</span>
        <input class="info-value" id="gender-input" style="display:none" value="Male"/>
      </div>
      <div class="info-row">
        <span class="info-label">Birthday</span>
        <span class="info-value" id="birthday-display">20 July, 2024</span>
        <input class="info-value" id="birthday-input" style="display:none" value="20 July, 2024"/>
      </div>
    </div>

    <div class="profile-actions">
      <button class="btn" id="edit-btn" onclick="toggleEdit()">Edit</button>
      <button class="btn btn-primary" id="save-btn" onclick="saveInfo()" style="display:none">Save information</button>
    </div>
  </div>
</div>

<!-- APPOINTMENTS PAGE -->
<div class="page" id="page-appointments">
  <div class="appt-container">
    <h2 class="appt-title">My Appointments</h2>

    <!-- Appointment 1 - Cancel only -->
    <div class="appt-card">
      <div class="appt-img"><img src="https://i.pravatar.cc/110?img=68" alt="Dr. Richard James"/></div>
      <div class="appt-info">
        <div class="appt-doctor">Dr. Richard James</div>
        <div class="appt-specialty">General physician</div>
        <div class="appt-detail"><strong>Address:</strong> 57th Cross, Richmond Circle, Church Road, London</div>
        <div class="appt-detail"><strong>Date &amp; Time:</strong> 25 July, 2024 | 8:30 PM</div>
      </div>
      <div class="appt-actions">
        <button class="btn-cancel" onclick="cancelAppt(this)">Cancel appointment</button>
      </div>
    </div>

    <!-- Appointment 2 - Pay + Cancel -->
    <div class="appt-card">
      <div class="appt-img"><img src="https://i.pravatar.cc/110?img=68" alt="Dr. Richard James"/></div>
      <div class="appt-info">
        <div class="appt-doctor">Dr. Richard James</div>
        <div class="appt-specialty">General physician</div>
        <div class="appt-detail"><strong>Address:</strong> 57th Cross, Richmond Circle, Church Road, London</div>
        <div class="appt-detail"><strong>Date &amp; Time:</strong> 25 July, 2024 | 8:30 PM</div>
      </div>
      <div class="appt-actions">
        <button class="btn-pay" onclick="payNow(this)">Pay here</button>
        <button class="btn-cancel" onclick="cancelAppt(this)">Cancel appointment</button>
      </div>
    </div>

    <!-- Appointment 3 - Paid + Cancel -->
    <div class="appt-card">
      <div class="appt-img"><img src="https://i.pravatar.cc/110?img=68" alt="Dr. Richard James"/></div>
      <div class="appt-info">
        <div class="appt-doctor">Dr. Richard James</div>
        <div class="appt-specialty">General physician</div>
        <div class="appt-detail"><strong>Address:</strong> 57th Cross, Richmond Circle, Church Road, London</div>
        <div class="appt-detail"><strong>Date &amp; Time:</strong> 25 July, 2024 | 8:30 PM</div>
      </div>
      <div class="appt-actions">
        <button class="btn-paid">Paid</button>
        <button class="btn-cancel" onclick="cancelAppt(this)">Cancel appointment</button>
      </div>
    </div>

  </div>
</div>

<script>
  function showPage(name) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById('page-' + name).classList.add('active');
  }

  function toggleDropdown() {
    document.getElementById('dropdown').classList.toggle('open');
  }

  function closeDropdown() {
    document.getElementById('dropdown').classList.remove('open');
  }

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-user')) closeDropdown();
  });

  function toggleEdit() {
    const fields = ['email', 'phone', 'address', 'gender', 'birthday'];
    fields.forEach(f => {
      document.getElementById(f + '-display').style.display = 'none';
      document.getElementById(f + '-input').style.display = 'block';
    });
    document.getElementById('edit-btn').style.display = 'none';
    document.getElementById('save-btn').style.display = 'inline-block';
  }

  function saveInfo() {
    const fields = ['email', 'phone', 'address', 'gender', 'birthday'];
    fields.forEach(f => {
      const val = document.getElementById(f + '-input').value;
      document.getElementById(f + '-display').textContent = val;
      document.getElementById(f + '-display').style.display = 'block';
      document.getElementById(f + '-input').style.display = 'none';
    });
    document.getElementById('edit-btn').style.display = 'inline-block';
    document.getElementById('save-btn').style.display = 'none';
  }

  function cancelAppt(btn) {
    if (confirm('Cancel this appointment?')) {
      btn.closest('.appt-card').style.opacity = '0.4';
      btn.textContent = 'Cancelled';
      btn.disabled = true;
    }
  }

  function payNow(btn) {
    btn.textContent = 'Paid';
    btn.className = 'btn-paid';
    btn.disabled = true;
  }
</script>
</body>
</html>