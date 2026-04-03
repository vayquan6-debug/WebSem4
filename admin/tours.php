<?php
/**
 * Admin — Quản lý Tours
 */
$pageTitle = 'Quản lý Tours';
require_once '../config.php';

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    redirect('../login.php');
}

// Xử lý thêm tour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tour'])) {
    $name = $_POST['name'];
    $destination = $_POST['destination'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration_days'];
    $max_people = $_POST['max_people'];
    $image = $_POST['image'] ?? 'default-tour.jpg';
    $featured = isset($_POST['featured']) ? 1 : 0;

    // VULN: SQL Injection (S04)
    $sql = "INSERT INTO tours (name, destination, description, price, duration_days, max_people, image, featured, active)
            VALUES ('$name', '$destination', '$description', $price, $duration, $max_people, '$image', $featured, 1)";
    if (mysqli_query($conn, $sql)) {
        setFlash('success', 'Thêm tour thành công!');
    } else {
        setFlash('error', 'Lỗi: ' . mysqli_error($conn));
    }
    redirect('tours.php');
}

// Xử lý xóa tour
if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    mysqli_query($conn, "UPDATE tours SET active = 0 WHERE id = $del");
    setFlash('success', "Đã ẩn tour #$del");
    redirect('tours.php');
}

$tours = mysqli_query($conn, "SELECT * FROM tours ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý Tours — VietTour Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

  <aside class="admin-sidebar">
    <div class="sidebar-brand"><h2>🏖️ VietTour</h2><span>Admin Panel</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
      <a href="users.php" class="nav-item">👥 Quản lý Users</a>
      <a href="tours.php" class="nav-item active">🗺️ Quản lý Tours</a>
      <a href="bookings.php" class="nav-item">📋 Bookings</a>
      <a href="reviews.php" class="nav-item">⭐ Reviews</a>
      <hr>
      <a href="tools.php" class="nav-item">🔧 Server Tools</a>
      <a href="../index.php" class="nav-item">🌐 Về trang chủ</a>
      <a href="../logout.php" class="nav-item">🚪 Đăng xuất</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <h1>🗺️ Quản lý Tours</h1>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <!-- Form thêm tour -->
    <div class="admin-card">
      <h2>➕ Thêm tour mới</h2>
      <form method="POST" class="admin-form">
        <input type="hidden" name="add_tour" value="1">
        <div class="form-row">
          <div class="form-group">
            <label>Tên tour *</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Điểm đến *</label>
            <input type="text" name="destination" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label>Mô tả</label>
          <textarea name="description" rows="3" class="form-control"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Giá (VNĐ) *</label>
            <input type="number" name="price" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Số ngày *</label>
            <input type="number" name="duration_days" class="form-control" value="3" required>
          </div>
          <div class="form-group">
            <label>Tối đa người *</label>
            <input type="number" name="max_people" class="form-control" value="30" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Ảnh (filename)</label>
            <input type="text" name="image" class="form-control" value="default-tour.jpg">
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="featured"> Tour nổi bật
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Thêm tour</button>
      </form>
    </div>

    <!-- Danh sách tour -->
    <div class="admin-card">
      <h2>Danh sách tour</h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên tour</th>
            <th>Điểm đến</th>
            <th>Giá</th>
            <th>Ngày</th>
            <th>Featured</th>
            <th>Active</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($t = mysqli_fetch_assoc($tours)): ?>
          <tr class="<?= !$t['active'] ? 'row-inactive' : '' ?>">
            <td><?= $t['id'] ?></td>
            <td><a href="../tour.php?id=<?= $t['id'] ?>"><?= $t['name'] ?></a></td>
            <td><?= $t['destination'] ?></td>
            <td><?= formatPrice($t['price']) ?></td>
            <td><?= $t['duration_days'] ?></td>
            <td><?= $t['featured'] ? '⭐' : '' ?></td>
            <td><?= $t['active'] ? '✅' : '❌' ?></td>
            <td>
              <a href="tours.php?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Ẩn tour này?')">Ẩn</a>
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
