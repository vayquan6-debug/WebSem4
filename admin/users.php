<?php
/**
 * Admin — Quản lý users — VULN: IDOR (S15)
 * Cho phép xem, sửa, xóa user mà không validate quyền đúng cách
 */
$pageTitle = 'Quản lý Users';
require_once '../config.php';

if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    redirect('../login.php');
}

// Xử lý xóa user
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    // VULN: SQL Injection, IDOR — xóa bất kỳ user nào (S04, S15)
    mysqli_query($conn, "DELETE FROM users WHERE id = $del_id");
    setFlash('success', "Đã xóa user #$del_id");
    redirect('users.php');
}

// Xử lý sửa role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $uid  = $_POST['user_id'];
    $role = $_POST['role'];
    // VULN: SQL Injection (S04)
    mysqli_query($conn, "UPDATE users SET role = '$role' WHERE id = $uid");
    setFlash('success', "Đã cập nhật role cho user #$uid");
    redirect('users.php');
}

// Lấy danh sách user
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý Users — VietTour Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

  <aside class="admin-sidebar">
    <div class="sidebar-brand"><h2>🏖️ VietTour</h2><span>Admin Panel</span></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
      <a href="users.php" class="nav-item active">👥 Quản lý Users</a>
      <a href="tours.php" class="nav-item">🗺️ Quản lý Tours</a>
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
      <h1>👥 Quản lý Users</h1>
      <span>Tổng: <?= mysqli_num_rows($users) ?> users</span>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <div class="admin-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Họ tên</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Last Login</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = mysqli_fetch_assoc($users)): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><code><?= $u['username'] ?></code></td>
            <td><?= $u['full_name'] ?></td>
            <td><?= $u['email'] ?></td>
            <td><?= $u['phone'] ?></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <select name="role" onchange="this.form.submit()" class="form-control-sm">
                  <option value="user" <?= $u['role'] == 'user' ? 'selected' : '' ?>>User</option>
                  <option value="manager" <?= $u['role'] == 'manager' ? 'selected' : '' ?>>Manager</option>
                  <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <input type="hidden" name="update_role" value="1">
              </form>
            </td>
            <td><?= $u['last_login'] ? date('d/m H:i', strtotime($u['last_login'])) : 'N/A' ?></td>
            <td>
              <a href="../profile.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline">Xem</a>
              <!-- VULN: Xóa user qua GET — không có xác nhận, CSRF (S16) -->
              <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Xóa user #<?= $u['id'] ?>?')">Xóa</a>
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
