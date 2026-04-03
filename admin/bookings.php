<?php
/**
 * Admin — Bookings management
 */
require_once '../config.php';

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    redirect('../login.php');
}

// Cập nhật status
if (isset($_GET['confirm'])) {
    $bid = $_GET['confirm'];
    mysqli_query($conn, "UPDATE bookings SET status = 'confirmed' WHERE id = $bid");
    setFlash('success', "Đã xác nhận booking #$bid");
    redirect('bookings.php');
}

$bookings = mysqli_query($conn, "SELECT b.*, t.name as tour_name FROM bookings b LEFT JOIN tours t ON b.tour_id = t.id ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings — VietTour Admin</title>
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
      <a href="bookings.php" class="nav-item active">📋 Bookings</a>
      <a href="reviews.php" class="nav-item">⭐ Reviews</a>
      <hr>
      <a href="tools.php" class="nav-item">🔧 Server Tools</a>
      <a href="../index.php" class="nav-item">🌐 Trang chủ</a>
      <a href="../logout.php" class="nav-item">🚪 Đăng xuất</a>
    </nav>
  </aside>
  <main class="admin-main">
    <div class="admin-header"><h1>📋 Quản lý Bookings</h1></div>
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>
    <div class="admin-card">
      <table class="data-table">
        <thead>
          <tr><th>ID</th><th>Mã</th><th>Khách</th><th>Tour</th><th>Ngày đi</th><th>Người</th><th>Tổng tiền</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
          <tr>
            <td><?= $b['id'] ?></td>
            <td><code><?= $b['booking_code'] ?></code></td>
            <td><?= $b['full_name'] ?><br><small><?= $b['email'] ?></small></td>
            <td><?= $b['tour_name'] ?></td>
            <td><?= date('d/m/Y', strtotime($b['start_date'])) ?></td>
            <td><?= $b['num_people'] ?></td>
            <td><?= formatPrice($b['total_price']) ?></td>
            <td><span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
            <td>
              <?php if ($b['status'] !== 'confirmed'): ?>
                <a href="bookings.php?confirm=<?= $b['id'] ?>" class="btn btn-sm btn-primary">Xác nhận</a>
              <?php endif; ?>
              <?php if ($b['payment_proof']): ?>
                <a href="../<?= $b['payment_proof'] ?>" target="_blank" class="btn btn-sm btn-outline">Biên lai</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>
  <script src="../assets/js/app.js"></script>
</body>
</html>
