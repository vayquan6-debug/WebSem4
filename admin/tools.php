<?php
/**
 * Admin — Server Tools — VULN: Command Injection, SSRF (S17, S16)
 * Trang công cụ quản trị — nhiều lỗ hổng injection
 */
require_once '../config.php';

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    redirect('../login.php');
}

$output = '';
$action = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'ping':
            // VULN: Command Injection (S17)
            $host = $_POST['host'];
            $output = shell_exec("ping -c 4 $host 2>&1");
            break;

        case 'nslookup':
            // VULN: Command Injection (S17)
            $domain = $_POST['domain'];
            $output = shell_exec("nslookup $domain 2>&1");
            break;

        case 'traceroute':
            // VULN: Command Injection (S17)
            $target = $_POST['target'];
            $output = shell_exec("traceroute $target 2>&1");
            break;

        case 'curl':
            // VULN: SSRF (S16)
            $url = $_POST['url'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $output = curl_exec($ch);
            if (curl_errno($ch)) $output = "Error: " . curl_error($ch);
            curl_close($ch);
            break;

        case 'phpinfo':
            ob_start();
            phpinfo();
            $output = ob_get_clean();
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Server Tools — VietTour Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
  <aside class="admin-sidebar">
    <div class="sidebar-brand"><h2>🏖️ VietTour</h2><span>Admin Panel</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
      <a href="users.php" class="nav-item">👥 Users</a>
      <a href="tours.php" class="nav-item">🗺️ Tours</a>
      <a href="bookings.php" class="nav-item">📋 Bookings</a>
      <a href="reviews.php" class="nav-item">⭐ Reviews</a>
      <hr>
      <a href="tools.php" class="nav-item active">🔧 Server Tools</a>
      <a href="../index.php" class="nav-item">🌐 Trang chủ</a>
      <a href="../logout.php" class="nav-item">🚪 Đăng xuất</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header"><h1>🔧 Server Tools</h1></div>

    <div class="tools-grid">
      <!-- Ping -->
      <div class="admin-card">
        <h3>📡 Ping</h3>
        <form method="POST" class="tool-form">
          <input type="hidden" name="action" value="ping">
          <input type="text" name="host" class="form-control" placeholder="IP / hostname" value="<?= htmlspecialchars($_POST['host'] ?? '172.99.10.10') ?>">
          <button type="submit" class="btn btn-primary btn-sm">Ping</button>
        </form>
      </div>

      <!-- NSLookup -->
      <div class="admin-card">
        <h3>🔍 NSLookup</h3>
        <form method="POST" class="tool-form">
          <input type="hidden" name="action" value="nslookup">
          <input type="text" name="domain" class="form-control" placeholder="domain name" value="<?= htmlspecialchars($_POST['domain'] ?? 'viettour.local') ?>">
          <button type="submit" class="btn btn-primary btn-sm">Lookup</button>
        </form>
      </div>

      <!-- Traceroute -->
      <div class="admin-card">
        <h3>🛤️ Traceroute</h3>
        <form method="POST" class="tool-form">
          <input type="hidden" name="action" value="traceroute">
          <input type="text" name="target" class="form-control" placeholder="IP / hostname" value="<?= htmlspecialchars($_POST['target'] ?? '') ?>">
          <button type="submit" class="btn btn-primary btn-sm">Trace</button>
        </form>
      </div>

      <!-- cURL -->
      <div class="admin-card">
        <h3>🌐 cURL Fetch</h3>
        <form method="POST" class="tool-form">
          <input type="hidden" name="action" value="curl">
          <input type="text" name="url" class="form-control" placeholder="URL" value="<?= htmlspecialchars($_POST['url'] ?? '') ?>">
          <button type="submit" class="btn btn-primary btn-sm">Fetch</button>
        </form>
      </div>

      <!-- PHPInfo -->
      <div class="admin-card">
        <h3>ℹ️ PHP Info</h3>
        <form method="POST" class="tool-form">
          <input type="hidden" name="action" value="phpinfo">
          <button type="submit" class="btn btn-outline btn-sm">Hiện phpinfo()</button>
        </form>
      </div>
    </div>

    <!-- Output -->
    <?php if ($output): ?>
    <div class="admin-card">
      <h2>📤 Kết quả: <?= htmlspecialchars($action) ?></h2>
      <?php if ($action === 'phpinfo'): ?>
        <div class="phpinfo-output"><?= $output ?></div>
      <?php else: ?>
        <pre class="tool-output"><?= htmlspecialchars($output) ?></pre>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </main>

  <script src="../assets/js/app.js"></script>
</body>
</html>
