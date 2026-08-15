<?php
session_start();
$error = '';

if (file_exists(__DIR__ . '/../config/env.php')) {
    require_once __DIR__ . '/../config/env.php';
} else {
    define('ADMIN_PASSWORD', 'admin123!!'); // Fallback if env missing
}

if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('admin_rem', '', time() - 3600, '/');
    header("Location: /admin/");
    exit;
}

// Verify Remember Me Cookie
if (!isset($_SESSION['admin_logged_in']) && isset($_COOKIE['admin_rem'])) {
    if ($_COOKIE['admin_rem'] === md5(ADMIN_PASSWORD . '_salt')) {
        $_SESSION['admin_logged_in'] = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        if (isset($_POST['remember'])) {
            setcookie('admin_rem', md5(ADMIN_PASSWORD . '_salt'), time() + (86400 * 30), '/');
        }
        header("Location: /admin/");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    session_write_close(); // Release session lock for login page
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #F8FAFC;
      --surface: #FFFFFF;
      --text: #0F172A;
      --text-muted: #64748B;
      --border: #E2E8F0;
      --primary: #2563EB; /* Biru tema utama */
      --primary-hover: #1D4ED8;
    }
    body {
      background: var(--bg);
      display: flex; align-items: center; justify-content: center; 
      min-height: 100vh; margin: 0; font-family: 'Inter', sans-serif;
      color: var(--text);
    }
    .login-wrapper { width: 100%; max-width: 380px; padding: 2rem; }
    
    .login-box { 
      background: var(--surface); 
      padding: 2.5rem; 
      border-radius: 12px; 
      border: 1px solid var(--border); 
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); 
    }
    .login-box h1 { margin: 0 0 2rem; font-size: 1.5rem; font-weight:700; text-align:center; }
    
    .input-group { margin-bottom: 1.25rem; }
    .input-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--text); }
    .input-group input[type="text"], .input-group input[type="password"] { 
      width: 100%; padding: 0.75rem 1rem; box-sizing: border-box;
      border: 1px solid var(--border); border-radius: 8px; 
      background: var(--surface); color: var(--text); font-size: 0.9375rem; 
      transition: border-color 0.2s;
    }
    .input-group input:focus { outline:none; border-color:var(--primary); }

    .remember-group {
      display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;
    }
    .remember-group input[type="checkbox"] {
      width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer;
    }
    .remember-group label {
      font-size: 0.875rem; color: var(--text-muted); cursor: pointer; user-select: none;
    }

    .login-box button { 
      width: 100%; padding: 0.75rem; 
      background: var(--primary); 
      color: white; border: none; border-radius: 8px; 
      font-weight: 600; font-size: 0.9375rem; cursor: pointer; 
      transition: background 0.2s;
    }
    .login-box button:hover { background: var(--primary-hover); }
    
    .error-msg { 
      color: #DC2626; background: #FEF2F2; 
      padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; 
      font-size: 0.875rem; border: 1px solid #FCA5A5; text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <h1>Login Admin</h1>

      <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="input-group">
          <label>Username</label>
          <input type="text" name="username" required autofocus autocomplete="off">
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <div class="remember-group">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Ingat saya</label>
        </div>
        <button type="submit" name="login">Masuk</button>
      </form>
    </div>
  </div>
</body>
</html>
<?php
    exit;
}

// Release session lock before sending the heavy HTML and executing API requests in parallel
session_write_close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | ceknomor.id</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <script src="/assets/js/chart.min.js"></script>
  <style>
    body { overflow-x: hidden; }

    /* ── Admin Layout ─────────────────────────────────────── */
    .admin-wrap { display: flex; min-height: 100vh; }

    .sidebar {
      width: 240px; flex-shrink: 0;
      background: var(--bg-1); border-right: 1px solid var(--border);
      position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto;
      display: flex; flex-direction: column;
      z-index: 60; transition: transform 0.25s ease;
    }

    .sidebar-logo {
      display: flex; align-items: center; gap: 0.625rem;
      padding: 1.125rem 1rem; border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .sidebar-logo-text { font-size: 0.9375rem; font-weight: 700; }
    .sidebar-logo-text span { color: var(--brand); }
    .sidebar-logo-sub { font-size: 0.625rem; color: var(--t-4); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }

    .sidebar-nav { flex: 1; padding: 0.75rem 0.625rem; overflow-y: auto; }
    .nav-group { margin-bottom: 1.25rem; }
    .nav-group-label { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: var(--t-4); padding: 0 0.5rem; margin-bottom: 0.375rem; }

    .nav-link {
      display: flex; align-items: center; gap: 0.625rem;
      padding: 0.5rem 0.625rem; border-radius: var(--r-md);
      color: var(--t-2); font-size: 0.8125rem; font-weight: 500;
      cursor: pointer; transition: all var(--ease);
      border: none; background: none; width: 100%;
      text-align: left; font-family: inherit; text-decoration: none;
      margin-bottom: 1px;
    }
    .nav-link svg { width: 15px; height: 15px; fill: none; stroke: currentColor; flex-shrink: 0; }
    .nav-link:hover { background: var(--bg-3); color: var(--t-1); }
    .nav-link.active { background: rgba(220,38,38,0.08); color: var(--brand); }
    .nav-link .badge-count { margin-left: auto; background: var(--brand); color: #fff; font-size: 0.6rem; font-weight: 800; padding: 0.1rem 0.375rem; border-radius: var(--r-full); }

    /* ── Main ─────────────────────────────────────────────── */
    .admin-main { flex: 1; margin-left: 240px; display: flex; flex-direction: column; min-height: 100vh; }

    .admin-topbar {
      height: 52px; display: flex; align-items: center; justify-content: space-between;
      padding: 0 1.25rem; background: rgba(9,9,11,0.9); backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50;
      flex-shrink: 0;
    }
    .topbar-left { display: flex; align-items: center; gap: 0.75rem; }
    .topbar-right { display: flex; align-items: center; gap: 0.5rem; }
    .topbar-title { font-size: 0.9375rem; font-weight: 700; }
    .topbar-subtitle { font-size: 0.75rem; color: var(--t-3); }

    .live-chip {
      display: flex; align-items: center; gap: 0.375rem;
      background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2);
      color: var(--c-aman); padding: 0.25rem 0.625rem;
      border-radius: var(--r-full); font-size: 0.6875rem; font-weight: 700;
    }
    .live-dot-green { width: 6px; height: 6px; border-radius: 50%; background: var(--c-aman); animation: pulse 1.5s ease infinite; }
    @keyframes pulse { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.8); } }

    .admin-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--brand); display: flex; align-items: center; justify-content: center; font-size: 0.6875rem; font-weight: 700; color: #fff; cursor: pointer; }

    /* ── Content ──────────────────────────────────────────── */
    .admin-content { padding: 1.25rem; flex: 1; }

    .section-label { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--t-3); margin-bottom: 0.875rem; }

    /* ── KPI Grid ─────────────────────────────────────────── */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
    .kpi-card { background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 1rem; transition: border-color var(--ease); }
    .kpi-card:hover { border-color: var(--border-hover); }
    .kpi-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
    .kpi-label { font-size: 0.6875rem; font-weight: 500; color: var(--t-3); }
    .kpi-icon { width: 28px; height: 28px; border-radius: var(--r-md); background: var(--bg-3); display: flex; align-items: center; justify-content: center; }
    .kpi-icon svg { width: 14px; height: 14px; fill: none; stroke: var(--t-2); }
    .kpi-val { font-size: 1.375rem; font-weight: 800; color: var(--t-1); line-height: 1; }
    .kpi-change { font-size: 0.6875rem; font-weight: 600; color: var(--c-aman); margin-top: 0.25rem; }

    /* ── Charts ───────────────────────────────────────────── */
    .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem; margin-bottom: 1rem; }
    .chart-card { background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 1rem; }
    .chart-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
    .chart-title { font-size: 0.875rem; font-weight: 700; }

    /* ── Data Table ───────────────────────────────────────── */
    .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; }
    .data-card { background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden; }
    .data-card-head { padding: 0.875rem 1rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .data-card-title { font-size: 0.8125rem; font-weight: 700; }

    .simple-table { width: 100%; border-collapse: collapse; }
    .simple-table th { padding: 0.5rem 1rem; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--t-3); text-align: left; background: var(--bg-3); border-bottom: 1px solid var(--border); }
    .simple-table td { padding: 0.5625rem 1rem; font-size: 0.8125rem; color: var(--t-2); border-bottom: 1px solid rgba(255,255,255,0.02); }
    .simple-table tr:last-child td { border-bottom: none; }
    .simple-table tr:hover td { background: rgba(255,255,255,0.015); }

    /* ── Live Activity ────────────────────────────────────── */
    .live-list { }
    .live-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.03); }
    .live-item:last-child { border-bottom: none; }
    .live-icon { width: 28px; height: 28px; border-radius: 50%; background: var(--bg-3); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .live-icon svg { width: 13px; height: 13px; fill: none; stroke: var(--t-2); }
    .live-text { flex: 1; font-size: 0.8rem; color: var(--t-2); }
    .live-text strong { color: var(--t-1); font-weight: 600; }
    .live-time { font-size: 0.6875rem; color: var(--t-4); white-space: nowrap; }

    /* ── Moderation ───────────────────────────────────────── */
    .mod-item { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.875rem 1rem; border-bottom: 1px solid var(--border); }
    .mod-item:last-child { border-bottom: none; }
    .mod-actions { display: flex; gap: 0.375rem; flex-shrink: 0; }
    .mod-btn { padding: 0.3rem 0.5rem; border-radius: var(--r-sm); border: 1px solid; font-size: 0.6875rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all var(--ease); display: flex; align-items: center; gap: 0.25rem; }
    .mod-btn svg { width: 11px; height: 11px; fill: none; stroke: currentColor; }
    .mod-approve { background: rgba(34,197,94,0.08); color: var(--c-aman); border-color: rgba(34,197,94,0.2); }
    .mod-approve:hover { background: rgba(34,197,94,0.15); }
    .mod-reject { background: rgba(239,68,68,0.08); color: var(--c-bahaya); border-color: rgba(239,68,68,0.2); }
    .mod-reject:hover { background: rgba(239,68,68,0.15); }

    /* ── Server ───────────────────────────────────────────── */
    .server-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .server-card { background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 0.875rem; }
    .server-lbl { font-size: 0.6875rem; color: var(--t-3); font-weight: 500; margin-bottom: 0.375rem; }
    .server-val { font-size: 1.125rem; font-weight: 800; margin-bottom: 0.375rem; }

    .status-dot-inline { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.75rem; font-weight: 600; }
    .status-dot-inline::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; }
    .status-online::before { background: var(--c-aman); }
    .status-warning-d::before { background: var(--c-waspada); }

    /* ── Panels ───────────────────────────────────────────── */
    [id^="panel-"].hidden { display: none; }

    /* ── Responsive ───────────────────────────────────────── */
    @media (max-width: 1024px) {
      .charts-grid { grid-template-columns: 1fr; }
      .data-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .admin-main { margin-left: 0; }
      .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
<div class="admin-wrap">

  <!-- ═══════════════════════════ SIDEBAR ════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark" style="width:28px;height:28px;border-radius:8px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div>
        <div class="sidebar-logo-text">cek<span>nomor</span>.id</div>
        <div class="sidebar-logo-sub">Admin Panel</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-group">
        <div class="nav-group-label">Overview</div>
        <button class="nav-link active" onclick="showPanel('dashboard',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
          Dashboard
        </button>
        <button class="nav-link" onclick="showPanel('live',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Live Activity
          <span class="badge-count">Live</span>
        </button>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Moderasi</div>
        <button class="nav-link" onclick="showPanel('moderation',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Laporan Pending
          <span class="badge-count">23</span>
        </button>
        <button class="nav-link" onclick="showPanel('comments',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          Komentar
          <span class="badge-count">7</span>
        </button>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Manajemen</div>
        <button class="nav-link" onclick="showPanel('users',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          Pengguna
        </button>
        <button class="nav-link" onclick="showPanel('numbers',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.27 9.09a19.79 19.79 0 01-3.07-8.67A2 2 0 012.18 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 9.91a16 16 0 006.72 6.72l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          Nomor Telepon
        </button>
        <button class="nav-link" onclick="showPanel('rekening',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          Rekening Bank
        </button>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Monetisasi & SEO</div>
        <button class="nav-link" onclick="showPanel('articles',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/><path d="M14 3v5h5M16 13H8M16 17H8M10 9H8"/></svg>
          Artikel SEO
        </button>
        <button class="nav-link" onclick="showPanel('banners',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          Banner Iklan
        </button>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Analitik</div>
        <button class="nav-link" onclick="showPanel('analytics',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
          Statistik Search
        </button>
        <button class="nav-link" onclick="showPanel('fraud',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          Fraud Detection
        </button>
        <button class="nav-link" onclick="showPanel('community',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 010-5H6"/><path d="M18 9h1.5a2.5 2.5 0 000-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0012 0V2z"/></svg>
          Komunitas
        </button>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Konten</div>
        <button class="nav-link" onclick="showPanel('seo',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
          SEO Dashboard
        </button>
        <button class="nav-link" onclick="showPanel('ads',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          Iklan
        </button>
        <button class="nav-link" onclick="showPanel('cms',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          CMS Artikel
        </button>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Sistem</div>
        <button class="nav-link" onclick="showPanel('server',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
          Server Monitor
        </button>
        <button class="nav-link" onclick="showPanel('logs',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Audit Log
        </button>
        <button class="nav-link" onclick="showPanel('roles',this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Role &amp; Permission
        </button>
      </div>
    </nav>

    <div style="padding:0.75rem;border-top:1px solid var(--border);flex-shrink:0;">
      <a href="/" class="nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Lihat Website
      </a>
      <a href="?logout=1" class="nav-link" style="color:var(--c-bahaya);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    </div>
  </aside>

  <!-- ═══════════════════════════ MAIN ════════ -->
  <main class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="topbar-left">
        <button onclick="document.getElementById('sidebar').classList.toggle('open')" class="btn btn-ghost btn-icon" id="sidebarToggle" style="display:none;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div>
          <div class="topbar-title" id="topbarTitle">Dashboard</div>
          <div class="topbar-subtitle">Admin › <span id="topbarSub">Overview</span></div>
        </div>
      </div>
      <div class="topbar-right">
        <div class="live-chip"><div class="live-dot-green"></div>Live</div>
        <button class="btn btn-ghost btn-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        </button>
        <div class="admin-avatar">SA</div>
      </div>
    </header>

    <!-- Content -->
    <div class="admin-content">

      <!-- ── Dashboard Panel ─────────────────────────── -->
      <div id="panel-dashboard">
        <div class="section-label">Statistik Hari Ini</div>
        <div class="kpi-grid" id="kpiToday"></div>

        <div class="section-label">Statistik Keseluruhan</div>
        <div class="kpi-grid" id="kpiOverall"></div>

        <div class="charts-grid" style="margin-top:1rem;">
          <div class="chart-card">
            <div class="chart-head">
              <span class="chart-title">Pertumbuhan Pencarian</span>
              <div style="display:flex;gap:0.25rem;">
                <button class="filter-pill active" style="font-size:0.6875rem;padding:0.25rem 0.5rem;">7H</button>
                <button class="filter-pill" style="font-size:0.6875rem;padding:0.25rem 0.5rem;">30H</button>
              </div>
            </div>
            <canvas id="chartSearches" height="160"></canvas>
          </div>
          <div class="chart-card">
            <div class="chart-head"><span class="chart-title">Distribusi Status</span></div>
            <canvas id="chartStatus" height="160"></canvas>
            <div style="display:flex;flex-direction:column;gap:0.375rem;margin-top:0.875rem;">
              <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.75rem;color:var(--t-2);"><div style="width:8px;height:8px;border-radius:2px;background:var(--c-aman);flex-shrink:0;"></div>Aman (72%)</div>
              <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.75rem;color:var(--t-2);"><div style="width:8px;height:8px;border-radius:2px;background:var(--c-waspada);flex-shrink:0;"></div>Waspada (14%)</div>
              <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.75rem;color:var(--t-2);"><div style="width:8px;height:8px;border-radius:2px;background:var(--c-hatihati);flex-shrink:0;"></div>Hati-hati (8%)</div>
              <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.75rem;color:var(--t-2);"><div style="width:8px;height:8px;border-radius:2px;background:var(--c-bahaya);flex-shrink:0;"></div>Berbahaya (6%)</div>
            </div>
          </div>
        </div>

        <div class="charts-grid" style="margin-top:0.75rem;">
          <div class="chart-card"><div class="chart-head"><span class="chart-title">Pendapatan Iklan (USD)</span></div><canvas id="chartRevenue" height="140"></canvas></div>
          <div class="chart-card"><div class="chart-head"><span class="chart-title">Laporan per Hari</span></div><canvas id="chartReports" height="140"></canvas></div>
        </div>

        <div class="data-grid" style="margin-top:0.75rem;">
          <div class="data-card">
            <div class="data-card-head"><span class="data-card-title">Top Pencarian Hari Ini</span></div>
            <table class="simple-table"><thead><tr><th>Nomor</th><th>Pencarian</th><th>Status</th></tr></thead><tbody id="topSearchTbody"></tbody></table>
          </div>
          <div class="data-card">
            <div class="data-card-head"><span class="data-card-title">Pengguna Baru</span></div>
            <table class="simple-table"><thead><tr><th>Nama</th><th>Trust</th><th>Bergabung</th></tr></thead><tbody id="newUserTbody"></tbody></table>
          </div>
        </div>
      </div>

      <!-- ── Live Panel ───────────────────────────────── -->
      <div id="panel-live" class="hidden">
        <div class="section-label">Real-time Activity</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Live Feed</span>
            <div class="live-chip"><div class="live-dot-green"></div>Auto-refresh</div>
          </div>
          <div class="live-list" id="liveList"></div>
        </div>
      </div>

      <!-- ── Moderation Panel ────────────────────────── -->
      <div id="panel-moderation" class="hidden">
        <div class="section-label">Laporan Menunggu Review</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">23 Laporan Pending</span>
            <button class="btn btn-sm" style="background:rgba(34,197,94,0.08);color:var(--c-aman);border-color:rgba(34,197,94,0.2);" onclick="showToast('Semua laporan disetujui','ok')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              Setujui Semua
            </button>
          </div>
          <div id="modList"></div>
        </div>
      </div>

      <!-- ── Users Panel ─────────────────────────────── -->
      <div id="panel-users" class="hidden">
        <div class="section-label">Manajemen Pengguna</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title" id="totalUsersTitle">Memuat...</span>
            <input type="text" id="userSearch" placeholder="Cari pengguna..." style="background:var(--bg-3);border:1px solid var(--border);border-radius:var(--r-md);color:var(--t-1);padding:0.375rem 0.75rem;font-size:0.8rem;outline:none;width:200px;">
          </div>
          <table class="simple-table"><thead><tr><th>Nama</th><th>Trust Score</th><th>Badge</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="userTbody"></tbody></table>
        </div>
      </div>

      <!-- ── Server Panel ────────────────────────────── -->
      <div id="panel-server" class="hidden">
        <div class="section-label">Server Monitoring</div>
        <div class="server-grid" id="serverMetrics"></div>
        <div class="charts-grid">
          <div class="chart-card"><div class="chart-head"><span class="chart-title">CPU &amp; RAM (%)</span></div><canvas id="chartServer" height="160"></canvas></div>
          <div class="data-card" style="border-radius:var(--r-lg);">
            <div class="data-card-head"><span class="data-card-title">Status Layanan</span></div>
            <div style="padding:0.75rem 1rem;display:flex;flex-direction:column;gap:0.625rem;">
              ${['Nginx','PHP-FPM','MySQL','Redis','Backup'].map((s,i) => `
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:0.8125rem;">${s}</span>
                <span class="status-dot-inline ${i<4?'status-online':'status-warning-d'}">${i<4?'Online':'Scheduled'}</span>
              </div>`).join('')}
            </div>
          </div>
        </div>
      </div>

      <!-- Placeholder panels -->
      <!-- ── Comments Panel ──────────────────────────── -->
      <div id="panel-comments" class="hidden">
        <div class="section-label">Moderasi Komentar</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Komentar Menunggu Review</span>
            <button class="mod-btn mod-approve" onclick="buildModeration()" style="font-size:0.75rem;">↻ Refresh</button>
          </div>
          <div id="moderationList" style="min-height:80px;">
            <p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat...</p>
          </div>
        </div>

        <!-- Fraud Logs -->
        <div class="section-label" style="margin-top:1.5rem;">Fraud Detection Log</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Riwayat Deteksi Fraud</span>
            <button class="mod-btn" onclick="buildFraudLogs()" style="font-size:0.75rem;background:rgba(234,88,12,0.1);color:var(--c-hatihati);border:1px solid rgba(234,88,12,0.2);">↻ Refresh</button>
          </div>
          <div id="fraudLogsContainer" style="min-height:80px;overflow-x:auto;">
            <p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat fraud logs...</p>
          </div>
        </div>

        <!-- Activity Logs -->
        <div class="section-label" style="margin-top:1.5rem;">Activity Log</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Riwayat Aksi Admin & Sistem</span>
            <button class="mod-btn" onclick="buildActivityLogs()" style="font-size:0.75rem;">↻ Refresh</button>
          </div>
          <div id="activityLogsContainer" style="min-height:80px;max-height:400px;overflow-y:auto;">
            <p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat activity logs...</p>
          </div>
        </div>
      </div>

      <!-- ── Numbers Panel ───────────────────────────── -->
      <div id="panel-numbers" class="hidden">
        <div class="section-label">Manajemen Nomor Telepon</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title" id="phoneCountTitle">Memuat...</span>
            <input type="text" id="phoneSearch" placeholder="Cari nomor..." style="background:var(--bg-3);border:1px solid var(--border);border-radius:var(--r-md);color:var(--t-1);padding:0.375rem 0.75rem;font-size:0.8rem;outline:none;width:200px;">
          </div>
          <table class="simple-table">
            <thead><tr><th>Nomor & Identitas</th><th>Status</th><th>Skor Keamanan</th><th>Laporan</th><th>Pencarian</th><th>Aksi</th></tr></thead>
            <tbody id="phoneTbody">
              <tr><td colspan="6" style="text-align:center;padding:1rem;">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Rekening Panel ──────────────────────────── -->
      <div id="panel-rekening" class="hidden">
        <div class="section-label">Manajemen Rekening Bank</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title" id="rekeningCountTitle">Memuat...</span>
            <input type="text" id="rekeningSearch" placeholder="Cari rekening..." style="background:var(--bg-3);border:1px solid var(--border);border-radius:var(--r-md);color:var(--t-1);padding:0.375rem 0.75rem;font-size:0.8rem;outline:none;width:200px;">
          </div>
          <table class="simple-table">
            <thead><tr><th>Bank</th><th>Rekening</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody id="rekeningTbody">
              <tr><td colspan="4" style="text-align:center;padding:1rem;">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Analytics Panel ─────────────────────────── -->
      <div id="panel-analytics" class="hidden">
        <div class="section-label">Statistik Pencarian</div>
        <div class="charts-grid">
          <div class="chart-card">
            <div class="chart-head"><span class="chart-title">Tren Pencarian (7 Hari)</span></div>
            <div style="height:160px;display:flex;align-items:flex-end;gap:0.5rem;padding-top:1rem;">
              <div style="flex:1;background:var(--brand);height:40%;border-radius:4px;"></div>
              <div style="flex:1;background:var(--brand);height:60%;border-radius:4px;"></div>
              <div style="flex:1;background:var(--brand);height:45%;border-radius:4px;"></div>
              <div style="flex:1;background:var(--brand);height:80%;border-radius:4px;"></div>
              <div style="flex:1;background:var(--brand);height:75%;border-radius:4px;"></div>
              <div style="flex:1;background:var(--brand);height:90%;border-radius:4px;"></div>
              <div style="flex:1;background:var(--brand);height:100%;border-radius:4px;"></div>
            </div>
          </div>
          <div class="data-card">
            <div class="data-card-head"><span class="data-card-title">Top Keyword</span></div>
            <table class="simple-table">
              <tbody>
                <tr><td style="color:var(--t-1);font-weight:600;">08123456789</td><td>1,204 kali</td></tr>
                <tr><td style="color:var(--t-1);font-weight:600;">BCA 0987123456</td><td>956 kali</td></tr>
                <tr><td style="color:var(--t-1);font-weight:600;">085733337777</td><td>842 kali</td></tr>
                <tr><td style="color:var(--t-1);font-weight:600;">BRI 11223344</td><td>610 kali</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── Fraud Panel ─────────────────────────────── -->
      <div id="panel-fraud" class="hidden">
        <div class="section-label">Fraud Detection</div>
        <div class="data-card">
          <div class="data-card-head"><span class="data-card-title">Log Aktivitas Mencurigakan</span></div>
          <table class="simple-table">
            <thead><tr><th>Waktu</th><th>IP / User</th><th>Aktivitas</th><th>Tingkat</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr><td>Baru saja</td><td><span style="font-family:monospace;">192.168.1.55</span></td><td>Spam laporan (10x / menit)</td><td><span class="badge badge-bahaya">Tinggi</span></td><td><button class="mod-btn mod-reject" onclick="showToast('IP Diblokir','warn')">Block IP</button></td></tr>
              <tr><td>5 mnt lalu</td><td>User: Ahmad F.</td><td>Review massal (5 bintang)</td><td><span class="badge badge-waspada">Sedang</span></td><td><button class="mod-btn" style="border-color:var(--border);" onclick="showToast('Detail investigasi','ok')">Investigasi</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Community Panel ─────────────────────────── -->
      <div id="panel-community" class="hidden">
        <div class="section-label">Analitik Komunitas</div>
        <div class="kpi-grid">
          <div class="kpi-card"><div class="kpi-label">Total Review</div><div class="kpi-val" id="commReviews" style="margin-top:0.5rem;">Memuat...</div></div>
          <div class="kpi-card"><div class="kpi-label">Helpful Votes</div><div class="kpi-val" id="commHelpful" style="margin-top:0.5rem;">Memuat...</div></div>
          <div class="kpi-card"><div class="kpi-label">Kontributor Aktif</div><div class="kpi-val" id="commActive" style="margin-top:0.5rem;">Memuat...</div></div>
        </div>
        <div class="data-card" style="margin-top:1rem;">
          <div class="data-card-head"><span class="data-card-title">Top Kontributor Bulan Ini</span></div>
          <table class="simple-table">
            <thead><tr><th>Nama</th><th>Trust Score</th><th>Kontribusi</th></tr></thead>
            <tbody id="commTbody">
              <tr><td colspan="3" style="text-align:center;">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── SEO Panel ───────────────────────────────── -->
      <div id="panel-seo" class="hidden">
        <div class="section-label">SEO Dashboard</div>
        <div class="kpi-grid">
          <div class="kpi-card"><div class="kpi-label">Terindeks Google</div><div class="kpi-val" id="seoIndexed" style="margin-top:0.5rem;">Memuat...</div></div>
          <div class="kpi-card"><div class="kpi-label">Organic Traffic / bln</div><div class="kpi-val" id="seoTraffic" style="margin-top:0.5rem;">Memuat...</div></div>
          <div class="kpi-card"><div class="kpi-label">Avg Position</div><div class="kpi-val" id="seoPos" style="margin-top:0.5rem;">Memuat...</div></div>
        </div>
        <div class="data-card" style="margin-top:1rem;">
          <div class="data-card-head"><span class="data-card-title">Top Landing Pages</span></div>
          <table class="simple-table">
            <thead><tr><th>URL</th><th>Views</th><th>Target Keyword</th></tr></thead>
            <tbody id="seoTbody">
              <tr><td colspan="3" style="text-align:center;">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Ads Panel ───────────────────────────────── -->
      <div id="panel-ads" class="hidden">
        <div class="section-label">Manajemen Iklan</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Banner Aktif</span>
            <button class="btn btn-sm btn-primary" onclick="showToast('Form tambah banner','ok')">Tambah Banner</button>
          </div>
          <table class="simple-table">
            <thead><tr><th>Posisi</th><th>Ukuran</th><th>Impresi</th><th>Klik</th><th>Status</th></tr></thead>
            <tbody id="adsTbody">
              <tr><td colspan="5" style="text-align:center;">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── CMS Panel ───────────────────────────────── -->
      <div id="panel-cms" class="hidden">
        <div class="section-label">CMS Artikel</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Daftar Artikel Edukasi</span>
            <button class="btn btn-sm btn-primary" onclick="showToast('Editor artikel','ok')">Tulis Artikel</button>
          </div>
          <table class="simple-table">
            <thead><tr><th>Judul</th><th>Penulis</th><th>Views</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr><td style="color:var(--t-1);font-weight:600;">Cara Menghindari Penipuan Pinjol</td><td>Admin</td><td>12.4k</td><td><span class="badge badge-aman">Published</span></td><td><button class="mod-btn" style="border-color:var(--border);" onclick="showToast('Edit','ok')">Edit</button></td></tr>
              <tr><td style="color:var(--t-1);font-weight:600;">Ciri-ciri Modus APK Kurir</td><td>Admin</td><td>8.1k</td><td><span class="badge badge-aman">Published</span></td><td><button class="mod-btn" style="border-color:var(--border);" onclick="showToast('Edit','ok')">Edit</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Logs Panel ──────────────────────────────── -->
      <div id="panel-logs" class="hidden">
        <div class="section-label">Audit Log</div>
        <div class="data-card">
          <div class="data-card-head"><span class="data-card-title">Aktivitas Sistem Terbaru</span></div>
          <table class="simple-table">
            <thead><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Target</th><th>IP</th></tr></thead>
            <tbody>
              <tr><td>10:42 AM</td><td style="color:var(--t-1);font-weight:600;">Super Admin</td><td>Approve Report</td><td>0812-9999...</td><td><span style="font-family:monospace;">10.0.0.1</span></td></tr>
              <tr><td>09:15 AM</td><td style="color:var(--t-1);font-weight:600;">Moderator A</td><td>Delete Comment</td><td>ID #4421</td><td><span style="font-family:monospace;">10.0.0.5</span></td></tr>
              <tr><td>08:30 AM</td><td style="color:var(--t-1);font-weight:600;">Super Admin</td><td>Login</td><td>-</td><td><span style="font-family:monospace;">10.0.0.1</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Roles Panel ─────────────────────────────── -->
      <div id="panel-roles" class="hidden">
        <div class="section-label">Role & Permission</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Daftar Admin</span>
            <button class="btn btn-sm btn-primary" onclick="showToast('Form admin baru','ok')">Tambah Admin</button>
          </div>
          <table class="simple-table">
            <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr><td style="color:var(--t-1);font-weight:600;">Super Admin</td><td>admin@ceknomor.id</td><td><span class="badge badge-bahaya">Super Admin</span></td><td><span class="status-dot-inline status-online">Aktif</span></td><td>-</td></tr>
              <tr><td style="color:var(--t-1);font-weight:600;">Moderator 1</td><td>mod1@ceknomor.id</td><td><span class="badge badge-waspada">Moderator</span></td><td><span class="status-dot-inline status-online">Aktif</span></td><td><button class="mod-btn" style="border-color:var(--border);" onclick="showToast('Edit','ok')">Edit</button></td></tr>
              <tr><td style="color:var(--t-1);font-weight:600;">SEO Staff</td><td>seo@ceknomor.id</td><td><span class="badge badge-aman">SEO Manager</span></td><td><span class="status-dot-inline status-online">Aktif</span></td><td><button class="mod-btn" style="border-color:var(--border);" onclick="showToast('Edit','ok')">Edit</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Articles Panel ─────────────────────────────── -->
      <div id="panel-articles" class="hidden">
        <div class="section-label">Manajemen Artikel SEO</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Daftar Artikel</span>
            <button class="btn btn-sm btn-primary" onclick="openArticleModal()">Tulis Artikel</button>
          </div>
          <table class="simple-table">
            <thead><tr><th>Judul</th><th>Slug</th><th>Status</th><th>View</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody id="articlesTbody">
              <tr><td colspan="6" style="text-align:center;">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Banners Panel ─────────────────────────────── -->
      <div id="panel-banners" class="hidden">
        <div class="section-label">Manajemen Banner Iklan</div>
        <div class="data-card">
          <div class="data-card-head">
            <span class="data-card-title">Daftar Banner & AdSense</span>
            <button class="btn btn-sm btn-primary" onclick="openBannerModal()">Buat Banner Baru</button>
          </div>
          <table class="simple-table">
            <thead><tr><th>Nama / Keterangan</th><th>Posisi</th><th>Tipe</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody id="bannersTbody">
              <tr><td colspan="5" style="text-align:center;">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Modal Edit Nomor -->
<div id="phoneEditModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9999;">
  <div class="data-card" style="width:100%;max-width:500px;background:var(--bg-1);">
    <div class="data-card-head">
      <span class="data-card-title">Edit Nomor: <span id="pemPhoneTitle"></span></span>
      <button onclick="closePhoneModal()" style="background:none;border:none;cursor:pointer;color:var(--t-2);font-size:1.2rem;">✕</button>
    </div>
    <div style="padding:1rem;">
      <input type="hidden" id="pemId">
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;color:var(--t-2);font-weight:500;">Status</label>
        <select id="pemStatus" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
          <option value="aman">Aman</option>
          <option value="waspada">Waspada</option>
          <option value="hatihati">Hati-hati</option>
          <option value="bahaya">Bahaya</option>
        </select>
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;color:var(--t-2);font-weight:500;">Skor Keamanan (0-100)</label>
        <input type="number" id="pemScore" min="0" max="100" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
      </div>
      <div style="margin-bottom:1.5rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;color:var(--t-2);font-weight:500;">Identitas Kontak Teratas (Bisa diubah)</label>
        <div id="pemContactsContainer" style="display:flex;flex-direction:column;gap:0.5rem;">
          <!-- Dinamis dari JS -->
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:0.5rem;">
        <button class="btn btn-sm" style="background:var(--bg-2);color:var(--t-1);" onclick="closePhoneModal()">Batal</button>
        <button class="btn btn-sm btn-primary" onclick="savePhoneModal()">Simpan Perubahan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Article -->
<div id="articleEditModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9999;">
  <div class="data-card" style="width:100%;max-width:700px;max-height:90vh;overflow-y:auto;background:var(--bg-1);">
    <div class="data-card-head">
      <span class="data-card-title">Editor Artikel SEO</span>
      <button onclick="closeArticleModal()" style="background:none;border:none;cursor:pointer;color:var(--t-2);font-size:1.2rem;">✕</button>
    </div>
    <div style="padding:1rem;">
      <input type="hidden" id="artId">
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Judul</label>
        <input type="text" id="artTitle" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Slug (Kosongkan untuk otomatis)</label>
        <input type="text" id="artSlug" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Ringkasan (Excerpt)</label>
        <textarea id="artExcerpt" rows="2" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;resize:vertical;"></textarea>
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Konten (HTML)</label>
        <div id="editor-container" style="height: 300px; background:var(--bg-1); color:var(--t-1);"></div>
        <!-- Hidden input to store HTML for submission -->
        <input type="hidden" id="artContent">
      </div>
      <div style="margin-bottom:1.5rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Status</label>
        <select id="artStatus" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="archived">Archived</option>
        </select>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:0.5rem;">
        <button class="btn btn-sm" style="background:var(--bg-2);color:var(--t-1);" onclick="closeArticleModal()">Batal</button>
        <button class="btn btn-sm btn-primary" onclick="saveArticle()">Simpan Artikel</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Banner -->
<div id="bannerEditModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9999;">
  <div class="data-card" style="width:100%;max-width:500px;background:var(--bg-1);">
    <div class="data-card-head">
      <span class="data-card-title">Manajemen Banner Iklan</span>
      <button onclick="closeBannerModal()" style="background:none;border:none;cursor:pointer;color:var(--t-2);font-size:1.2rem;">✕</button>
    </div>
    <div style="padding:1rem;">
      <input type="hidden" id="banId">
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Nama / Keterangan</label>
        <input type="text" id="banName" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Posisi</label>
        <select id="banPosition" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
          <option value="landing_top">Landing - Bawah Hero (landing_top)</option>
          <option value="landing_mid">Landing - Bawah Trending (landing_mid)</option>
          <option value="result_top">Hasil - Atas (result_top)</option>
          <option value="result_mid">Hasil - Tengah (result_mid)</option>
          <option value="sidebar">Sidebar Kanan (sidebar)</option>
          <option value="footer">Footer Bawah (footer)</option>
        </select>
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Tipe Iklan</label>
        <select id="banType" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
          <option value="image">Gambar + Link</option>
          <option value="html">AdSense / Custom HTML</option>
        </select>
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Konten (URL Gambar / Kode HTML)</label>
        <textarea id="banContent" rows="4" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;resize:vertical;"></textarea>
      </div>
      <div style="margin-bottom:1rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Link URL Tujuan (hanya untuk tipe Gambar)</label>
        <input type="text" id="banLink" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
      </div>
      <div style="margin-bottom:1.5rem;">
        <label style="display:block;margin-bottom:0.5rem;font-size:0.85rem;font-weight:500;">Status</label>
        <select id="banStatus" style="width:100%;padding:0.5rem;border:1px solid var(--border);border-radius:var(--r-md);background:var(--bg-3);color:var(--t-1);outline:none;">
          <option value="1">Aktif</option>
          <option value="0">Tidak Aktif</option>
        </select>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:0.5rem;">
        <button class="btn btn-sm" style="background:var(--bg-2);color:var(--t-1);" onclick="closeBannerModal()">Batal</button>
        <button class="btn btn-sm btn-primary" onclick="saveBanner()">Simpan Banner</button>
      </div>
    </div>
  </div>
</div>

<!-- Quill.js for Rich Text Editor -->
<link href="/assets/vendor/quill/quill.snow.css" rel="stylesheet" />
<script src="/assets/vendor/quill/quill.js"></script>

<div class="toast-wrap" id="toastWrap"></div>
<script src="/assets/js/icons.js"></script>
<script src="/assets/js/mock-data.js"></script>
<script src="/assets/js/app.js"></script>
<script>
/* ── Admin Init ──────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  if (window.innerWidth <= 768) document.getElementById('sidebarToggle').style.display = 'flex';
  
  // Default to Dashboard
  showPanel('dashboard');
  
  buildServerMetrics();
  setInterval(tickLiveFeed, 4000);
});

async function buildDashboard() {
  if (window._dashboardLoaded) return;
  try {
      const stats = await adminAPI('stats');
      buildKPI('kpiToday', todayKPIs(stats));
      buildKPI('kpiOverall', overallKPIs(stats));
      initCharts(stats.chart_data);
      buildTopSearch(stats.top_searches);
      buildNewUsers(stats.new_users);
      buildLiveFeed(stats.live_feed);
      window._dashboardLoaded = true;
  } catch(e) {
      console.error("Dashboard init error:", e);
  }
}

const panelTitles = { dashboard:'Dashboard', live:'Live Activity', moderation:'Laporan Pending', comments:'Komentar', users:'Pengguna', numbers:'Nomor Telepon', rekening:'Rekening Bank', analytics:'Statistik', fraud:'Fraud Detection', community:'Komunitas', seo:'SEO', ads:'Iklan', cms:'CMS Artikel', server:'Server Monitor', logs:'Audit Log', roles:'Role & Permission' };

function showPanel(name, el) {
  document.querySelectorAll('[id^="panel-"]').forEach(p => p.classList.add('hidden'));
  document.getElementById(`panel-${name}`)?.classList.remove('hidden');
  document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');
  document.getElementById('topbarTitle').textContent = panelTitles[name] || name;
  document.getElementById('topbarSub').textContent = panelTitles[name] || name;
  
  // Init server chart on first open
  if (name === 'server' && !window._serverChartDone) { window._serverChartDone = true; initServerChart(); }
  
  // Lazy load panels
  if (name === 'dashboard') buildDashboard();
  if (name === 'moderation') buildModeration();
  if (name === 'users') buildUsers();
  if (name === 'numbers') buildPhones();
  if (name === 'rekening') buildRekening();
  if (name === 'community') buildCommunity();
  if (name === 'seo') buildSEO();
  if (name === 'ads') buildAds();
  if (name === 'articles') buildArticles();
  if (name === 'banners') buildBanners();
}

/* ── KPI ─────────────────────────────────────────────────── */
function todayKPIs(t) {
  return [
    { label:'Pengunjung (Est)', val: (t.searches_today * 2).toLocaleString('id'), icon:'eye', change:null },
    { label:'Pengguna Baru', val: (t.new_users ? t.new_users.length : 0).toLocaleString('id'), icon:'users', change:null },
    { label:'Total Pencarian', val: t.searches_today.toLocaleString('id'), icon:'search', change:null },
    { label:'Laporan Baru', val: t.comments_24h.toLocaleString('id'), icon:'flag', change:null },
  ];
}

function overallKPIs(o) {
  return [
    { label:'Total User', val: o.total_users.toLocaleString('id'), icon:'users', change:null },
    { label:'Total Nomor', val: o.total_phones.toLocaleString('id'), icon:'phone', change:null },
    { label:'Total Rekening', val: o.total_rekening.toLocaleString('id'), icon:'credit', change:null },
    { label:'Total Laporan', val: o.total_comments.toLocaleString('id'), icon:'flag', change:null },
    { label:'Total Fraud', val: o.total_fraud_flags.toLocaleString('id'), icon:'alert', change:null },
    { label:'Total Pencarian', val: 'N/A', icon:'search', change:null }, // Total search history
  ];
}

function buildKPI(id, items) {
  const el = document.getElementById(id);
  if (!el) return;
  items.forEach(item => {
    const div = document.createElement('div');
    div.className = 'kpi-card';
    div.innerHTML = `
      <div class="kpi-head">
        <span class="kpi-label">${item.label}</span>
        <div class="kpi-icon">${svgStr(item.icon)}</div>
      </div>
      <div class="kpi-val">${item.val}</div>
      ${item.change ? `<div class="kpi-change">+ ${item.change} vs kemarin</div>` : ''}
    `;
    el.appendChild(div);
  });
}

/* ── Charts ──────────────────────────────────────────────── */
const chartOpts = (color) => ({
  responsive: true, maintainAspectRatio: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#52525B', font: { size: 10 } } },
    y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#52525B', font: { size: 10 } } },
  }
});

function initCharts(d) {
  if (!d) return;
  new Chart(document.getElementById('chartSearches'), { type:'line', data:{ labels:d.labels, datasets:[{ data:d.searches, borderColor:'#DC2626', backgroundColor:'rgba(220,38,38,0.07)', fill:true, tension:0.4, pointRadius:3, pointBackgroundColor:'#DC2626' }] }, options:chartOpts() });
  new Chart(document.getElementById('chartStatus'), { type:'doughnut', data:{ labels:['Aman','Waspada','Hati-hati','Berbahaya'], datasets:[{ data:[72,14,8,6], backgroundColor:['#22C55E','#EAB308','#F97316','#EF4444'], borderWidth:0, hoverOffset:3 }] }, options:{ responsive:true, cutout:'72%', plugins:{ legend:{ display:false } } } });
  new Chart(document.getElementById('chartRevenue'), { type:'bar', data:{ labels:d.labels, datasets:[{ data:d.revenue, backgroundColor:'rgba(34,197,94,0.6)', borderRadius:4, borderSkipped:false }] }, options:chartOpts() });
  new Chart(document.getElementById('chartReports'), { type:'bar', data:{ labels:d.labels, datasets:[{ data:d.reports, backgroundColor:'rgba(220,38,38,0.6)', borderRadius:4, borderSkipped:false }] }, options:chartOpts() });
}

function initServerChart() {
  const canvas = document.getElementById('chartServer');
  if (!canvas) return;
  new Chart(canvas, { type:'line', data:{ labels:['1h','2h','3h','4h','5h','6h','7h'], datasets:[
    { label:'CPU', data:[18,23,19,31,25,22,23], borderColor:'#DC2626', fill:false, tension:0.4, pointRadius:2 },
    { label:'RAM', data:[24,25,26,27,26,26,26], borderColor:'#4F6EF7', fill:false, tension:0.4, pointRadius:2 },
  ]}, options:{ responsive:true, plugins:{ legend:{ display:true, labels:{ color:'#71717A', font:{ size:10 } } } }, scales:{ x:{ grid:{ color:'rgba(255,255,255,0.03)' }, ticks:{ color:'#52525B', font:{ size:10 } } }, y:{ grid:{ color:'rgba(255,255,255,0.03)' }, ticks:{ color:'#52525B', font:{ size:10 } } } } } });
}

/* ── Tables ──────────────────────────────────────────────── */
function buildTopSearch(topSearches) {
  const tb = document.getElementById('topSearchTbody');
  tb.innerHTML = '';
  (topSearches || []).forEach(item => {
    const sc = DataHelper.getStatusConfig(item.status);
    const tr = document.createElement('tr');
    tr.innerHTML = `<td style="color:var(--t-1);font-weight:600;">${item.phone_number}</td><td>${item.search_count}</td><td><span class="badge badge-${item.status}">${sc.label}</span></td>`;
    tb.appendChild(tr);
  });
}

function buildNewUsers(users) {
  const tb = document.getElementById('newUserTbody');
  tb.innerHTML = '';
  (users || []).forEach(u => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td style="color:var(--t-1);">${u.name}</td><td><span style="font-weight:700;">${u.trust_score}</span></td><td>${new Date(u.created_at).toLocaleDateString('id-ID')}</td>`;
    tb.appendChild(tr);
  });
}

/* ── Live Feed ───────────────────────────────────────────── */
let liveIdx = 0;
let liveActivitiesExtra = [];

function buildLiveFeed(logs) {
  const list = document.getElementById('liveList');
  if (!list) return;
  list.innerHTML = '';
  
  if (!logs || logs.length === 0) return;
  
  const formattedLogs = logs.map(l => ({
    icon: l.action === 'Login' ? 'user' : (l.action.includes('Comment') ? 'message' : 'flag'),
    user: l.admin_name,
    content: l.action + (l.target_type ? ` pada ${l.target_type}` : ''),
    time: new Date(l.created_at).toLocaleString('id-ID')
  }));
  
  formattedLogs.slice(0, 5).forEach(a => list.appendChild(makeLiveItem(a)));
  liveActivitiesExtra = formattedLogs;
}

function tickLiveFeed() {
  const list = document.getElementById('liveList');
  if (!list || !liveActivitiesExtra || liveActivitiesExtra.length === 0) return;
  const item = makeLiveItem(liveActivitiesExtra[liveIdx % liveActivitiesExtra.length]);
  if (!item) return;
  item.style.animation = 'fadeUp 0.3s ease';
  list.insertBefore(item, list.firstChild);
  while (list.children.length > 12) list.removeChild(list.lastChild);
  liveIdx++;
}

function makeLiveItem(a) {
  const div = document.createElement('div');
  div.className = 'live-item';
  div.innerHTML = `
    <div class="live-icon">${svgStr(a.icon)}</div>
    <div class="live-text"><strong>${a.user}</strong> ${a.content}</div>
    <div class="live-time">${a.time}</div>
  `;
  return div;
}


// ── Admin API helper ──────────────────────────────────────────
const ADMIN_TOKEN = 'ceknomor_admin_2024';

async function adminAPI(action, params = {}) {
  const qs = new URLSearchParams({ action, token: ADMIN_TOKEN, ...params });
  const res = await fetch(`/api/admin.php?${qs}`);
  if (!res.ok) throw new Error('API Error ' + res.status);
  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) throw new Error('Not PHP response');
  return res.json();
}

async function adminPost(payload) {
  const res = await fetch('/api/admin.php?token=' + ADMIN_TOKEN, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Admin-Token': ADMIN_TOKEN },
    body: JSON.stringify(payload)
  });
  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) throw new Error('Not PHP response');
  return res.json();
}

/* ── Moderation (Real API) ─────────────────────────────────── */
async function buildModeration() {
  const list = document.getElementById('moderationList');
  if (!list) return;
  list.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat data...</p>';
  
  try {
    const data = await adminAPI('comments', { status: 'pending' });
    list.innerHTML = '';
    
    if (!data.comments || data.comments.length === 0) {
      list.innerHTML = '<p style="color:var(--c-aman);font-size:0.85rem;padding:1rem;text-align:center;">✅ Tidak ada laporan pending. Semua sudah diproses!</p>';
      return;
    }

    data.comments.forEach(c => {
      const targetLabel = c.phone_number 
        ? c.phone_number 
        : (c.bank_name ? `${c.bank_name} — ${c.account_number}` : `ID ${c.target_id}`);
      const div = document.createElement('div');
      div.className = 'mod-item';
      div.id = 'mod-' + c.id;
      div.innerHTML = `
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;flex-wrap:wrap;">
            <span style="font-size:0.875rem;font-weight:700;color:var(--t-1);">${targetLabel}</span>
            <span class="badge badge-cat">${c.category_name || 'Lainnya'}</span>
            ${c.fraud_score > 0 ? `<span class="badge" style="background:rgba(220,38,38,0.1);color:#DC2626;border:1px solid rgba(220,38,38,0.2);">Fraud ${c.fraud_score}</span>` : ''}
          </div>
          <div style="font-size:0.8rem;color:var(--t-2);margin-bottom:0.25rem;line-height:1.5;">${c.content}</div>
          <div style="font-size:0.6875rem;color:var(--t-3);">${c.is_anonymous ? 'Anonim' : 'User'} · ${c.ip_address} · ${new Date(c.created_at).toLocaleString('id-ID')}</div>
        </div>
        <div class="mod-actions">
          <button class="mod-btn mod-approve" onclick="moderateComment(${c.id}, 'approve')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Setujui
          </button>
          <button class="mod-btn mod-reject" onclick="moderateComment(${c.id}, 'reject')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Tolak
          </button>
        </div>
      `;
      list.appendChild(div);
    });
  } catch(e) {
    list.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Mode demo: koneksi PHP tidak tersedia.</p>';
    buildModerationMock();
  }
}

async function moderateComment(id, action) {
  const el = document.getElementById('mod-' + id);
  try {
    const data = await adminPost({ action: action + '_comment', id });
    if (data.success) {
      showToast(data.message, 'ok');
      if (el) { el.style.opacity = '0.3'; el.style.pointerEvents = 'none'; }
    } else {
      showToast(data.error || 'Gagal', 'err');
    }
  } catch(e) {
    // Demo fallback
    showToast(action === 'approve' ? 'Disetujui (demo)' : 'Ditolak (demo)', 'ok');
    if (el) { el.style.opacity = '0.3'; el.style.pointerEvents = 'none'; }
  }
}

function buildModerationMock() {
  const list = document.getElementById('moderationList');
  if (!list) return;
  const reports = [
    { num:'0812-3456-7890', cat:'Penipuan', desc:'Mengaku debt collector, tidak kooperatif.', time:'1 jam lalu' },
    { num:'BCA — 1234567890', cat:'Penipuan', desc:'Penjual fiktif marketplace.', time:'2 jam lalu' },
  ];
  reports.forEach(r => {
    const div = document.createElement('div');
    div.className = 'mod-item';
    div.innerHTML = `
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.375rem;">
          <span style="font-size:0.875rem;font-weight:700;color:var(--t-1);">${r.num}</span>
          <span class="badge badge-cat">${r.cat}</span>
        </div>
        <div style="font-size:0.8rem;color:var(--t-2);">${r.desc}</div>
        <div style="font-size:0.6875rem;color:var(--t-3);">Anonim · ${r.time}</div>
      </div>
      <div class="mod-actions">
        <button class="mod-btn mod-approve" onclick="this.closest('.mod-item').style.opacity='.3';showToast('Disetujui','ok');">Setujui</button>
        <button class="mod-btn mod-reject" onclick="this.closest('.mod-item').style.opacity='.3';showToast('Ditolak','warn');">Tolak</button>
      </div>
    `;
    list.appendChild(div);
  });
}

/* ── Users (Real API) ────────────────────────────────────────── */
async function buildUsers() {
  const tb = document.getElementById('userTbody');
  if (!tb) return;
  
  const q = document.getElementById('userSearch')?.value?.trim() || '';
  tb.innerHTML = '<tr><td colspan="5" style="color:var(--t-3);padding:1rem;">Memuat...</td></tr>';

  try {
    const data = await adminAPI('users', q ? { q } : {});
    tb.innerHTML = '';
    
    const titleEl = document.getElementById('totalUsersTitle');
    if (titleEl) {
      titleEl.innerText = (data.total || 0).toLocaleString('id-ID') + ' Pengguna';
    }

    if (!data.users || data.users.length === 0) {
      tb.innerHTML = '<tr><td colspan="5" style="color:var(--t-3);padding:1rem;">Tidak ada pengguna ditemukan.</td></tr>';
      return;
    }
    
    data.users.forEach(c => {
      const tsColor = c.trust_score >= 90 ? 'var(--c-aman)' : c.trust_score >= 70 ? 'var(--c-waspada)' : 'var(--t-3)';
      const bannedBadge = c.is_banned ? '<span style="color:var(--c-bahaya);font-size:0.7rem;font-weight:700;">BANNED</span>' : '';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="color:var(--t-1);font-weight:600;">${c.name} ${bannedBadge}</td>
        <td style="color:var(--t-2);font-size:0.8rem;">${c.email}</td>
        <td><span style="font-weight:800;color:${tsColor};">${c.trust_score}</span></td>
        <td><span class="badge badge-cat">${c.role}</span></td>
        <td>
          <div style="display:flex;gap:0.375rem;">
            ${!c.is_banned 
              ? `<button class="mod-btn mod-reject" onclick="banUser(${c.id})">Ban</button>` 
              : `<button class="mod-btn mod-approve" onclick="unbanUser(${c.id})">Unban</button>`}
          </div>
        </td>
      `;
      tb.appendChild(tr);
    });
  } catch(e) {
    tb.innerHTML = '';
    // Demo fallback
    if (typeof MockData !== 'undefined') {
      MockData.contributors?.forEach(c => {
        const tsColor = c.trustScore >= 90 ? 'var(--c-aman)' : 'var(--t-3)';
        const tr = document.createElement('tr');
        tr.innerHTML = `<td style="color:var(--t-1);font-weight:600;">${c.name}</td><td style="color:var(--t-2);">-</td><td><span style="font-weight:800;color:${tsColor};">${c.trustScore}</span></td><td><span class="badge badge-cat">${c.badge}</span></td><td></td>`;
        tb.appendChild(tr);
      });
    }
  }
}

async function banUser(id) {
  if (!confirm('Yakin ingin memblokir pengguna ini?')) return;
  try {
    const data = await adminPost({ action: 'ban_user', id });
    showToast(data.message || 'Berhasil', 'ok');
    buildUsers();
  } catch(e) {
    showToast('Ban gagal', 'warn');
  }
}

async function unbanUser(id) {
  if (!confirm('Yakin ingin membuka blokir pengguna ini?')) return;
  try {
    const data = await adminPost({ action: 'unban_user', id });
    showToast(data.message || 'Berhasil', 'ok');
    buildUsers();
  } catch(e) {
    showToast('Unban gagal', 'warn');
  }
}

// Search users on enter key
document.getElementById('userSearch')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') buildUsers();
});

/* ── Fraud Logs (Real API) ─────────────────────────────────── */
async function buildFraudLogs() {
  const cont = document.getElementById('fraudLogsContainer');
  if (!cont) return;
  cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat fraud logs...</p>';
  
  try {
    const data = await adminAPI('fraud_logs');
    cont.innerHTML = '';
    
    if (!data.logs || data.logs.length === 0) {
      cont.innerHTML = '<p style="color:var(--c-aman);font-size:0.85rem;padding:1rem;text-align:center;">✅ Tidak ada aktivitas fraud terdeteksi.</p>';
      return;
    }
    
    const table = document.createElement('table');
    table.style.cssText = 'width:100%;border-collapse:collapse;font-size:0.8rem;';
    table.innerHTML = `<thead><tr style="border-bottom:1px solid var(--border);">
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Waktu</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Target</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">IP</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Rule</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Score</th>
      <th style="padding:0.5rem;text-align:left;color:var(--t-3);">Aksi</th>
    </tr></thead><tbody id="fraudTbody"></tbody>`;
    cont.appendChild(table);
    
    const tbody = table.querySelector('#fraudTbody');
    data.logs.forEach(log => {
      const scoreColor = log.fraud_score >= 80 ? 'var(--c-bahaya)' : log.fraud_score >= 40 ? 'var(--c-hatihati)' : 'var(--c-waspada)';
      const tr = document.createElement('tr');
      tr.style.borderBottom = '1px solid var(--border)';
      tr.innerHTML = `
        <td style="padding:0.5rem;color:var(--t-3);">${new Date(log.created_at).toLocaleString('id-ID')}</td>
        <td style="padding:0.5rem;color:var(--t-1);">${log.target_id ? 'ID #' + log.target_id : '-'}</td>
        <td style="padding:0.5rem;color:var(--t-2);font-family:monospace;">${log.ip_address}</td>
        <td style="padding:0.5rem;"><span class="badge badge-cat">${log.action_type}</span></td>
        <td style="padding:0.5rem;font-weight:800;color:${scoreColor};">${log.fraud_score}</td>
        <td style="padding:0.5rem;color:var(--t-2);">${log.reason || '-'}</td>
      `;
      tbody.appendChild(tr);
    });
    
  } catch(e) {
    cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Fraud logs tersedia saat PHP backend aktif.</p>';
  }
}

/* ── Activity Logs (Real API) ──────────────────────────────── */
async function buildActivityLogs() {
  const cont = document.getElementById('activityLogsContainer');
  if (!cont) return;
  cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Memuat activity logs...</p>';
  
  try {
    const data = await adminAPI('audit_logs');
    cont.innerHTML = '';
    
    if (!data.logs || data.logs.length === 0) {
      cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;text-align:center;">Belum ada aktivitas tercatat.</p>';
      return;
    }
    
    data.logs.forEach(log => {
      const div = document.createElement('div');
      div.style.cssText = 'display:flex;align-items:center;gap:0.75rem;padding:0.625rem 0;border-bottom:1px solid var(--border);font-size:0.8rem;';
      div.innerHTML = `
        <div style="width:8px;height:8px;border-radius:50%;background:var(--brand);flex-shrink:0;"></div>
        <div style="flex:1;">
          <span style="color:var(--t-1);font-weight:600;">${log.action}</span>
          <span style="color:var(--t-3);"> (oleh ${log.admin_name || 'Admin'})</span>
          ${log.target_type ? `<span style="color:var(--t-3);"> → ${log.target_type} #${log.target_id || ''}</span>` : ''}
        </div>
        <div style="color:var(--t-3);font-family:monospace;font-size:0.75rem;">${log.ip_address || ''}</div>
        <div style="color:var(--t-4);font-size:0.7rem;flex-shrink:0;">${new Date(log.created_at).toLocaleString('id-ID')}</div>
      `;
      cont.appendChild(div);
    });
    
  } catch(e) {
    cont.innerHTML = '<p style="color:var(--t-3);font-size:0.85rem;padding:1rem;">Activity logs tersedia saat PHP backend aktif.</p>';
  }
}

/* ── Server Metrics (Static) ─────────────────────────────── */
function buildServerMetrics() {
  const grid = document.getElementById('serverMetrics');
  if (!grid) return;
  const metrics = [
    { label:'CPU Usage', val:'23%', color:'var(--c-aman)', pct:23 },
    { label:'RAM', val:'4.2 / 16 GB', color:'var(--c-aman)', pct:26 },
    { label:'Disk', val:'142 / 500 GB', color:'var(--c-waspada)', pct:28 },
    { label:'MySQL Conn', val:'124 aktif', color:'var(--c-aman)', pct:62 },
    { label:'Redis Hit', val:'98.7%', color:'var(--c-aman)', pct:98 },
    { label:'Queue', val:'34 pending', color:'var(--c-waspada)', pct:34 },
  ];
  metrics.forEach(m => {
    const div = document.createElement('div');
    div.className = 'server-card';
    div.innerHTML = `<div class="server-lbl">${m.label}</div><div class="server-val" style="color:${m.color};">${m.val}</div><div class="prog-bar"><div class="prog-fill" style="width:${m.pct}%;background:${m.color};"></div></div>`;
    grid.appendChild(div);
  });
}

// Override DOMContentLoaded section to call real API functions sequentially
document.addEventListener('DOMContentLoaded', async () => {
  try {
    await buildModeration();
    await buildFraudLogs();
    await buildActivityLogs();
  } catch (e) {
    console.error(e);
  }
});
/* ── Phones (Real API) ────────────────────────────────────── */
let phoneDataMap = {}; // Global store for loaded phones

async function buildPhones() {
  const tbody = document.getElementById('phoneTbody');
  const search = document.getElementById('phoneSearch')?.value || '';
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;">Memuat data...</td></tr>';
  
  try {
    const data = await adminAPI('phones', { q: search });
    tbody.innerHTML = '';
    
    if (document.getElementById('phoneCountTitle')) {
      document.getElementById('phoneCountTitle').innerText = `${data.total.toLocaleString('id-ID')} Nomor`;
    }

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;">Tidak ada nomor ditemukan.</td></tr>';
      return;
    }

    phoneDataMap = {}; // reset
    data.data.forEach(p => {
      phoneDataMap[p.id] = p;
      const bColor = p.status === 'bahaya' ? 'badge-bahaya' : (p.status === 'hatihati' ? 'badge-waspada' : (p.status === 'waspada' ? 'badge-waspada' : 'badge-aman'));
      
      let contactsHtml = '';
      if (p.contacts && p.contacts.length > 0) {
        contactsHtml = `<div style="margin-top:0.375rem;display:flex;flex-wrap:wrap;gap:0.25rem;">` + 
          p.contacts.map(c => `<span class="badge" style="background:var(--bg-2);color:var(--t-2);border:1px solid var(--border);font-size:0.65rem;font-weight:400;text-transform:none;">${c.contact_name}</span>`).join('') + 
          `</div>`;
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong style="color:var(--t-1);font-weight:600;">${p.phone_number}</strong>${contactsHtml}</td>
        <td><span class="badge ${bColor}">${p.status}</span></td>
        <td style="font-weight:700;color:var(--primary);">${p.security_score}/100</td>
        <td>${p.report_count}</td>
        <td>${p.search_count}</td>
        <td><button class="mod-btn" style="border-color:var(--border);" onclick="editPhone(${p.id})">Edit</button></td>
      `;
      tbody.appendChild(tr);
    });
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;">Gagal memuat data dari server.</td></tr>';
  }
}

document.getElementById('phoneSearch')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') buildPhones();
});

function editPhone(id) {
  const p = phoneDataMap[id];
  if (!p) return;
  
  document.getElementById('pemId').value = p.id;
  document.getElementById('pemPhoneTitle').innerText = p.phone_number;
  document.getElementById('pemStatus').value = p.status;
  document.getElementById('pemScore').value = p.security_score;
  
  const contactsContainer = document.getElementById('pemContactsContainer');
  contactsContainer.innerHTML = '';
  
  if (p.contacts && p.contacts.length > 0) {
    p.contacts.forEach((c, idx) => {
      contactsContainer.innerHTML += `
        <div style="display:flex;align-items:center;gap:0.5rem;" class="contact-edit-row">
          <input type="hidden" class="old-contact-name" value="${c.contact_name.replace(/"/g, '&quot;')}">
          <input type="text" class="new-contact-name" value="${c.contact_name.replace(/"/g, '&quot;')}" style="flex:1;padding:0.4rem;border:1px solid var(--border);border-radius:4px;background:var(--bg-3);color:var(--t-1);font-size:0.8rem;outline:none;" placeholder="Nama kontak">
        </div>
      `;
    });
  } else {
    contactsContainer.innerHTML = '<span style="font-size:0.8rem;color:var(--t-3);">Tidak ada riwayat nama kontak dari Google untuk nomor ini.</span>';
  }

  document.getElementById('phoneEditModal').style.display = 'flex';
}

function closePhoneModal() {
  document.getElementById('phoneEditModal').style.display = 'none';
}

async function savePhoneModal() {
  const id = document.getElementById('pemId').value;
  const p = phoneDataMap[id];
  if (!p) return;

  const newStatus = document.getElementById('pemStatus').value;
  const newScore = parseInt(document.getElementById('pemScore').value);
  if (isNaN(newScore) || newScore < 0 || newScore > 100) {
    showToast("Skor tidak valid! Harus angka 0-100.", "err");
    return;
  }

  // Gather contact updates
  const updates = [];
  document.querySelectorAll('.contact-edit-row').forEach(row => {
    const oldName = row.querySelector('.old-contact-name').value;
    const newName = row.querySelector('.new-contact-name').value;
    if (oldName !== newName && newName.trim() !== '') {
      updates.push({ old_name: oldName, new_name: newName.trim() });
    }
  });

  try {
    // 1. Update phone details
    let res = await adminPost({ action: 'update_phone', id: id, status: newStatus, score: newScore });
    if (!res.success) throw new Error(res.error || 'Gagal update nomor');

    // 2. Update contacts if any changed
    if (updates.length > 0) {
      res = await adminPost({ action: 'update_phone_contacts', phone_number: p.phone_number, updates: updates });
      if (!res.success) throw new Error(res.error || 'Gagal update kontak');
    }

    showToast('Data berhasil disimpan.', 'ok');
    closePhoneModal();
    buildPhones();
  } catch(e) {
    showToast(e.message || 'Terjadi kesalahan jaringan.', 'err');
  }
}

/* ── Rekening (Real API) ────────────────────────────────────── */
async function buildRekening() {
  const tbody = document.getElementById('rekeningTbody');
  const search = document.getElementById('rekeningSearch')?.value || '';
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1rem;">Memuat data...</td></tr>';
  
  try {
    const data = await adminAPI('rekening', { q: search });
    tbody.innerHTML = '';
    
    if (document.getElementById('rekeningCountTitle')) {
      document.getElementById('rekeningCountTitle').innerText = `${(data.total || 0).toLocaleString('id-ID')} Rekening`;
    }

    if (!data.data || data.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1rem;">Tidak ada rekening ditemukan.</td></tr>';
      return;
    }

    data.data.forEach(r => {
      const bColor = r.status === 'bahaya' ? 'badge-bahaya' : (r.status === 'hatihati' ? 'badge-waspada' : (r.status === 'waspada' ? 'badge-waspada' : 'badge-aman'));
      
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong style="color:var(--t-1);font-weight:600;">${r.bank_name || '-'}</strong></td>
        <td><strong>${r.account_number}</strong></td>
        <td><span class="badge ${bColor}">${r.status}</span></td>
        <td><button class="mod-btn" style="border-color:var(--border);" onclick="showToast('Edit mode (WIP)','ok')">Edit</button></td>
      `;
      tbody.appendChild(tr);
    });
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1rem;">Gagal memuat data dari server.</td></tr>';
  }
}

document.getElementById('rekeningSearch')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') buildRekening();
});

/* ── Community ────────────────────────────────────────────── */
async function buildCommunity() {
  try {
    const data = await adminAPI('community');
    document.getElementById('commReviews').innerText = (data.total_reviews || 0).toLocaleString('id-ID');
    document.getElementById('commHelpful').innerText = (data.helpful_votes || 0).toLocaleString('id-ID');
    document.getElementById('commActive').innerText = (data.active_contributors || 0).toLocaleString('id-ID');
    
    const tb = document.getElementById('commTbody');
    tb.innerHTML = '';
    if (!data.top_contributors || data.top_contributors.length === 0) {
      tb.innerHTML = '<tr><td colspan="3" style="text-align:center;">Belum ada kontributor</td></tr>';
      return;
    }
    data.top_contributors.forEach(c => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td style="color:var(--t-1);font-weight:600;">${c.name}</td><td><span style="color:var(--c-aman);font-weight:700;">${c.trust_score}</span></td><td>${c.kontribusi} laporan</td>`;
      tb.appendChild(tr);
    });
  } catch(e) { console.error("Error community:", e); }
}

/* ── SEO ─────────────────────────────────────────────────── */
async function buildSEO() {
  try {
    const data = await adminAPI('seo');
    // For now we just mock KPI because it's hard to get real indexed pages without Google Search Console API
    document.getElementById('seoIndexed').innerText = '1.2M'; 
    document.getElementById('seoTraffic').innerText = '450k';
    document.getElementById('seoPos').innerText = '5.4';
    
    const tb = document.getElementById('seoTbody');
    tb.innerHTML = '';
    if (!data || data.length === 0) {
      tb.innerHTML = '<tr><td colspan="3" style="text-align:center;">Belum ada SEO page config</td></tr>';
      return;
    }
    data.forEach(s => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td style="color:var(--t-1);font-weight:500;">${s.page_path}</td><td>-</td><td>${s.target_keyword}</td>`;
      tb.appendChild(tr);
    });
  } catch(e) { console.error("Error SEO:", e); }
}

/* ── Ads ─────────────────────────────────────────────────── */
async function buildAds() {
  try {
    const data = await adminAPI('ads');
    const tb = document.getElementById('adsTbody');
    tb.innerHTML = '';
    if (!data || data.length === 0) {
      tb.innerHTML = '<tr><td colspan="5" style="text-align:center;">Belum ada banner</td></tr>';
      return;
    }
    data.forEach(a => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td style="color:var(--t-1);font-weight:600;">${a.position}</td><td>${a.size}</td><td>-</td><td>-</td><td><span class="status-dot-inline status-${a.is_active ? 'online':'offline'}">${a.is_active ? 'Aktif' : 'Nonaktif'}</span></td>`;
      tb.appendChild(tr);
    });
  } catch(e) { console.error("Error Ads:", e); }
}

/* ── CMS Articles ──────────────────────────────────────────── */
let articlesData = [];
let quillEditor = null; // Global Quill instance

async function buildArticles() {
  try {
    const res = await fetch('/api/admin_cms.php?action=list_articles&token='+ADMIN_TOKEN).then(r => r.json());
    if (res.articles) {
      articlesData = res.articles;
      const tb = document.getElementById('articlesTbody');
      tb.innerHTML = '';
      if (articlesData.length === 0) {
        tb.innerHTML = '<tr><td colspan="6" style="text-align:center;">Belum ada artikel</td></tr>';
        return;
      }
      articlesData.forEach(a => {
        const tr = document.createElement('tr');
        const stClass = a.status === 'published' ? 'aman' : (a.status === 'draft' ? 'waspada' : 'bahaya');
        tr.innerHTML = `
          <td style="color:var(--t-1);font-weight:600;">${a.title}</td>
          <td>${a.slug}</td>
          <td><span class="badge badge-${stClass}">${a.status}</span></td>
          <td>${a.view_count}</td>
          <td>${a.published_at || a.created_at}</td>
          <td>
            <button class="mod-btn" style="border-color:var(--border);" onclick="editArticle(${a.id})">Edit</button>
            <button class="mod-btn mod-reject" onclick="deleteArticle(${a.id})">Hapus</button>
          </td>
        `;
        tb.appendChild(tr);
      });
    }
  } catch(e) { console.error(e); }
}

function openArticleModal(id = null) {
  document.getElementById('artId').value = '';
  document.getElementById('artTitle').value = '';
  document.getElementById('artSlug').value = '';
  document.getElementById('artExcerpt').value = '';
  document.getElementById('artContent').value = '';
  if (quillEditor) quillEditor.root.innerHTML = '';
  document.getElementById('artStatus').value = 'draft';
  document.getElementById('articleEditModal').style.display = 'flex';
  if (!quillEditor) initQuill();
}

async function editArticle(id) {
  try {
    const res = await fetch('/api/admin_cms.php?action=get_article&id=' + id + '&token=' + ADMIN_TOKEN).then(r => r.json());
    if (res.article) {
      document.getElementById('artId').value = res.article.id;
      document.getElementById('artTitle').value = res.article.title;
      document.getElementById('artSlug').value = res.article.slug;
      document.getElementById('artExcerpt').value = res.article.excerpt;
      document.getElementById('artContent').value = res.article.content;
      document.getElementById('artStatus').value = res.article.status;
      document.getElementById('articleEditModal').style.display = 'flex';
      
      if (!quillEditor) initQuill();
      quillEditor.root.innerHTML = res.article.content || '';
    } else {
      showToast("Artikel tidak ditemukan", "err");
    }
  } catch (e) {
    showToast("Gagal memuat artikel", "err");
  }
}

function initQuill() {
  quillEditor = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image', 'video'],
        ['clean']
      ]
    }
  });
}

async function saveArticle() {
  // Sync Quill content to hidden input
  if (quillEditor) {
    document.getElementById('artContent').value = quillEditor.root.innerHTML;
  }
  
  const payload = {
    id: document.getElementById('artId').value,
    title: document.getElementById('artTitle').value,
    slug: document.getElementById('artSlug').value,
    excerpt: document.getElementById('artExcerpt').value,
    content: document.getElementById('artContent').value,
    status: document.getElementById('artStatus').value,
  };
  if (!payload.title) return showToast('Judul wajib diisi', 'err');
  
  try {
    const res = await fetch('/api/admin_cms.php?action=save_article&token='+ADMIN_TOKEN, {
      method: 'POST',
      body: JSON.stringify(payload)
    }).then(r => r.json());
    if (res.success) {
      showToast('Artikel disimpan', 'ok');
      closeArticleModal();
      buildArticles();
    } else throw new Error(res.error);
  } catch(e) { showToast('Gagal menyimpan artikel', 'err'); }
}

function closeArticleModal() {
  document.getElementById('articleEditModal').style.display = 'none';
}

async function deleteArticle(id) {
  if (!confirm('Hapus artikel ini?')) return;
  try {
    const res = await fetch('/api/admin_cms.php?action=delete_article&token='+ADMIN_TOKEN, {
      method: 'POST', body: JSON.stringify({id})
    }).then(r => r.json());
    if (res.success) { showToast('Artikel dihapus', 'ok'); buildArticles(); }
  } catch(e) { showToast('Gagal menghapus', 'err'); }
}

/* ── Banners ──────────────────────────────────────────── */
let bannersData = [];

async function buildBanners() {
  try {
    const res = await fetch('/api/admin_cms.php?action=list_banners&token='+ADMIN_TOKEN).then(r => r.json());
    if (res.banners) {
      bannersData = res.banners;
      const tb = document.getElementById('bannersTbody');
      tb.innerHTML = '';
      if (bannersData.length === 0) {
        tb.innerHTML = '<tr><td colspan="5" style="text-align:center;">Belum ada banner</td></tr>';
        return;
      }
      bannersData.forEach(b => {
        const tr = document.createElement('tr');
        const stClass = parseInt(b.is_active) === 1 ? 'aman' : 'bahaya';
        const stText = parseInt(b.is_active) === 1 ? 'Aktif' : 'Non-Aktif';
        tr.innerHTML = `
          <td style="color:var(--t-1);font-weight:600;">${b.name}</td>
          <td><span style="font-family:monospace;font-size:0.75rem;padding:0.2rem 0.4rem;background:var(--bg-3);border-radius:4px;">${b.position}</span></td>
          <td>${b.type}</td>
          <td><span class="badge badge-${stClass}">${stText}</span></td>
          <td>
            <button class="mod-btn" style="border-color:var(--border);" onclick="editBanner(${b.id})">Edit</button>
            <button class="mod-btn mod-reject" onclick="deleteBanner(${b.id})">Hapus</button>
          </td>
        `;
        tb.appendChild(tr);
      });
    }
  } catch(e) { console.error(e); }
}

function openBannerModal(id = null) {
  document.getElementById('banId').value = '';
  document.getElementById('banName').value = '';
  document.getElementById('banPosition').value = 'landing_mid';
  document.getElementById('banType').value = 'image';
  document.getElementById('banContent').value = '';
  document.getElementById('banLink').value = '';
  document.getElementById('banStatus').value = '1';
  document.getElementById('bannerEditModal').style.display = 'flex';
}

async function editBanner(id) {
  try {
    const res = await fetch('/api/admin_cms.php?action=get_banner&id=' + id + '&token=' + ADMIN_TOKEN).then(r => r.json());
    if (res.banner) {
      document.getElementById('banId').value = res.banner.id;
      document.getElementById('banName').value = res.banner.name;
      document.getElementById('banPosition').value = res.banner.position;
      document.getElementById('banType').value = res.banner.type;
      document.getElementById('banContent').value = res.banner.content;
      document.getElementById('banLink').value = res.banner.link_url || '';
      document.getElementById('banStatus').value = res.banner.is_active;
      document.getElementById('bannerEditModal').style.display = 'flex';
    } else {
      showToast("Banner tidak ditemukan", "err");
    }
  } catch (e) {
    showToast("Gagal memuat banner", "err");
  }
}

async function saveBanner() {
  const payload = {
    id: document.getElementById('banId').value,
    name: document.getElementById('banName').value,
    position: document.getElementById('banPosition').value,
    type: document.getElementById('banType').value,
    content: document.getElementById('banContent').value,
    link_url: document.getElementById('banLink').value,
    is_active: parseInt(document.getElementById('banStatus').value),
  };
  if (!payload.name || !payload.content) return showToast('Nama dan Konten wajib diisi', 'err');
  
  try {
    const res = await fetch('/api/admin_cms.php?action=save_banner&token='+ADMIN_TOKEN, {
      method: 'POST',
      body: JSON.stringify(payload)
    }).then(r => r.json());
    if (res.success) {
      showToast('Banner disimpan', 'ok');
      closeBannerModal();
      buildBanners();
    } else throw new Error(res.error);
  } catch(e) { showToast('Gagal menyimpan banner', 'err'); }
}

function closeBannerModal() {
  document.getElementById('bannerEditModal').style.display = 'none';
}

async function deleteBanner(id) {
  if (!confirm('Hapus banner iklan ini?')) return;
  try {
    const res = await fetch('/api/admin_cms.php?action=delete_banner&token='+ADMIN_TOKEN, {
      method: 'POST', body: JSON.stringify({id})
    }).then(r => r.json());
    if (res.success) { showToast('Banner dihapus', 'ok'); buildBanners(); }
  } catch(e) { showToast('Gagal menghapus', 'err'); }
}
</script>
</body>
</html>

