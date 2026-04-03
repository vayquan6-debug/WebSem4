<?php
/**
 * Admin — Reviews management (Stored XSS visible here — S05)
 */
require_once '../config.php';

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    redirect('../login.php');
}

if (isset($_GET['delete'])) {
    $rid = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $rid");
    setFlash('success', "Đã xóa review #$rid");
    redirect('reviews.php');
}

$reviews = mysqli_query($conn, "SELECT r.*, u.username, u.full_name, t.name as tour_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN tours t ON r.tour_id = t.id ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews — VietTour Admin</title>
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
      <a href="reviews.php" class="nav-item active">⭐ Reviews</a>
      <hr>
      <a href="tools.php" class="nav-item">🔧 Server Tools</a>
      <a href="../index.php" class="nav-item">🌐 Trang chủ</a>
      <a href="../logout.php" class="nav-item">🚪 Đăng xuất</a>
    </nav>
  </aside>
  <main class="admin-main">
    <div class="admin-header"><h1>⭐ Quản lý Reviews</h1></div>
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>
    <div class="admin-card">
      <table class="data-table">
        <thead>
          <tr><th>ID</th><th>User</th><th>Tour</th><th>⭐</th><th>Comment</th><th>Ngày</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php while ($r = mysqli_fetch_assoc($reviews)): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= $r['full_name'] ?></td>
            <td><?= $r['tour_name'] ?></td>
            <td><?= renderStars($r['rating']) ?></td>
            <!-- VULN: Stored XSS — comment hiển thị không escape (S05) -->
            <td><?= $r['comment'] ?></td>
            <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
            <td><a href="reviews.php?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa?')">Xóa</a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>
  <script src="../assets/js/app.js"></script>
</body>
</html>
