<?php
/**
 * Admin Dashboard — VULN: Command Injection (S17), IDOR (S15)
 * - Chức năng "Ping server" cho phép command injection qua input
 * - Không kiểm tra role đúng cách — chỉ check session
 */
$pageTitle = 'Admin Dashboard';
require_once '../config.php';

// VULN: Kiểm tra quyền yếu — chỉ check session, không verify từ DB (S15)
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    setFlash('error', 'Bạn không có quyền truy cập.');
    redirect('../login.php');
}

// Thống kê tổng quan
$totalUsers    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$totalTours    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tours"))['c'];
$totalBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings"))['c'];
$totalRevenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) as s FROM bookings WHERE status IN ('paid','confirmed')"))['s'] ?? 0;
$pendingBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE status = 'pending'"))['c'];

// Recent bookings
$recentBookings = mysqli_query($conn, "SELECT b.*, t.name as tour_name, u.full_name FROM bookings b LEFT JOIN tours t ON b.tour_id = t.id LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC LIMIT 10");

// VULN: Command Injection — "Kiểm tra kết nối server" (S17)
$pingResult = '';
if (isset($_POST['ping_host'])) {
    $host = $_POST['ping_host'];
    // VULN: Command injection — $host nối trực tiếp vào system command (S17)
    // Attacker có thể nhập: 127.0.0.1; cat /etc/passwd
    // Hoặc: 127.0.0.1 && whoami
    // Hoặc: 127.0.0.1 | ls -la
    $pingResult = shell_exec("ping -c 4 " . $host);
}

// VULN: Backup database — Command Injection (S17)
$backupResult = '';
if (isset($_POST['backup_db'])) {
    $dbname = $_POST['db_name'];
    // VULN: Command injection qua tên database (S17)
    $backupResult = shell_exec("mysqldump -u root " . $dbname . " 2>&1");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VietTour Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

  <!-- Admin Sidebar -->
  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <h2>🏖️ VietTour</h2>
      <span>Admin Panel</span>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
      <a href="users.php" class="nav-item">👥 Quản lý Users</a>
      <a href="tours.php" class="nav-item">🗺️ Quản lý Tours</a>
      <a href="bookings.php" class="nav-item">📋 Bookings</a>
      <a href="reviews.php" class="nav-item">⭐ Reviews</a>
      <hr>
      <a href="tools.php" class="nav-item">🔧 Server Tools</a>
      <a href="../index.php" class="nav-item">🌐 Về trang chủ</a>
      <a href="../logout.php" class="nav-item">🚪 Đăng xuất</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-header">
      <h1>📊 Dashboard</h1>
      <span>Xin chào, <?= $_SESSION['full_name'] ?> (<?= $_SESSION['role'] ?>)</span>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
          <span class="stat-value"><?= $totalUsers ?></span>
          <span class="stat-label">Người dùng</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🗺️</div>
        <div class="stat-info">
          <span class="stat-value"><?= $totalTours ?></span>
          <span class="stat-label">Tour</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
          <span class="stat-value"><?= $totalBookings ?></span>
          <span class="stat-label">Bookings</span>
        </div>
      </div>
      <div class="stat-card highlight">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <span class="stat-value"><?= formatPrice($totalRevenue) ?></span>
          <span class="stat-label">Doanh thu</span>
        </div>
      </div>
      <div class="stat-card warning">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
          <span class="stat-value"><?= $pendingBookings ?></span>
          <span class="stat-label">Chờ xử lý</span>
        </div>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="admin-card">
      <h2>📋 Booking gần đây</h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>Mã</th>
            <th>Khách hàng</th>
            <th>Tour</th>
            <th>Ngày</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($b = mysqli_fetch_assoc($recentBookings)): ?>
          <tr>
            <td><code><?= $b['booking_code'] ?></code></td>
            <td><?= $b['full_name'] ?></td>
            <td><?= $b['tour_name'] ?></td>
            <td><?= date('d/m/Y', strtotime($b['start_date'])) ?></td>
            <td><?= formatPrice($b['total_price']) ?></td>
            <td><span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Server Tools -->
    <div class="admin-card">
      <h2>🔧 Server Tools</h2>

      <!-- VULN: Command Injection — Ping (S17) -->
      <div class="tool-section">
        <h3>📡 Kiểm tra kết nối server</h3>
        <form method="POST" class="tool-form">
          <div class="form-row">
            <input type="text" name="ping_host" class="form-control" placeholder="Nhập IP hoặc hostname (ví dụ: 172.99.10.10)" value="<?= htmlspecialchars($_POST['ping_host'] ?? '') ?>">
            <button type="submit" class="btn btn-primary">Ping</button>
          </div>
        </form>
        <?php if ($pingResult): ?>
          <pre class="tool-output"><?= htmlspecialchars($pingResult) ?></pre>
        <?php endif; ?>
      </div>

      <!-- VULN: Command Injection — Backup DB (S17) -->
      <div class="tool-section">
        <h3>💾 Backup Database</h3>
        <form method="POST" class="tool-form">
          <div class="form-row">
            <input type="text" name="db_name" class="form-control" placeholder="Tên database (ví dụ: viettour_db)" value="viettour_db">
            <button type="submit" name="backup_db" value="1" class="btn btn-outline">Backup</button>
          </div>
        </form>
        <?php if ($backupResult): ?>
          <pre class="tool-output"><?= htmlspecialchars(substr($backupResult, 0, 5000)) ?></pre>
        <?php endif; ?>
      </div>

      <!-- System Info (Info Disclosure) -->
      <div class="tool-section">
        <h3>ℹ️ Thông tin server</h3>
        <table class="info-table">
          <tr><td>PHP Version</td><td><?= phpversion() ?></td></tr>
          <tr><td>Server</td><td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td></tr>
          <tr><td>OS</td><td><?= php_uname() ?></td></tr>
          <tr><td>Document Root</td><td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></td></tr>
          <tr><td>Current User</td><td><?= get_current_user() ?></td></tr>
        </table>
      </div>
    </div>
  </main>

  <script src="../assets/js/app.js"></script>
</body>
</html>
