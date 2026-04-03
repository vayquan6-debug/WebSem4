<?php
/**
 * Hồ sơ cá nhân — VULN: IDOR (S15)
 * User có thể xem/sửa profile người khác bằng cách đổi ?id= parameter
 * Không kiểm tra quyền sở hữu
 */
$pageTitle = 'Hồ sơ cá nhân';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    setFlash('error', 'Vui lòng đăng nhập.');
    redirect('login.php');
}

// VULN: IDOR — lấy user_id từ URL thay vì session (S15)
// Attacker có thể đổi ?id=1, ?id=2, ... để xem profile người khác
$user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];

// Xử lý cập nhật profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $edit_id   = $_POST['user_id'];

    // VULN: IDOR — không kiểm tra $edit_id có phải user hiện tại không (S15)
    // VULN: SQL Injection (S04)
    $sql = "UPDATE users SET full_name = '$full_name', email = '$email', phone = '$phone' WHERE id = $edit_id";

    if (mysqli_query($conn, $sql)) {
        setFlash('success', 'Cập nhật thông tin thành công! ✅');
        // Cập nhật session nếu đang sửa chính mình
        if ($edit_id == $_SESSION['user_id']) {
            $_SESSION['full_name'] = $full_name;
        }
    } else {
        setFlash('error', 'Cập nhật thất bại: ' . mysqli_error($conn));
    }

    redirect("profile.php?id=$edit_id");
}

// Lấy thông tin user
// VULN: SQL Injection (S04)
$userResult = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($userResult);

if (!$user) {
    setFlash('error', 'Không tìm thấy người dùng.');
    redirect('index.php');
}

// Lấy booking của user
// VULN: IDOR — hiển thị booking của user khác (S15)
$bookings = mysqli_query($conn, "SELECT b.*, t.name as tour_name FROM bookings b JOIN tours t ON b.tour_id = t.id WHERE b.user_id = $user_id ORDER BY b.created_at DESC");

// Lấy review của user
$reviews = mysqli_query($conn, "SELECT r.*, t.name as tour_name FROM reviews r JOIN tours t ON r.tour_id = t.id WHERE r.user_id = $user_id ORDER BY r.created_at DESC");
?>

<section class="section">
  <h2 class="section-title">👤 Hồ Sơ Cá Nhân</h2>

  <div class="profile-container">
    <!-- Thông tin cá nhân -->
    <div class="profile-card">
      <div class="profile-header">
        <div class="profile-avatar">
          <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        </div>
        <div>
          <h3><?= $user['full_name'] ?></h3>
          <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
          <p class="text-muted">Tham gia: <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
        </div>
      </div>

      <!-- VULN: IDOR — form cho phép sửa bất kỳ user nào (S15) -->
      <form method="POST" action="profile.php" class="profile-form">
        <!-- VULN: Hidden field user_id có thể bị thay đổi trong DevTools (S15) -->
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

        <div class="form-group">
          <label>Họ và tên</label>
          <input type="text" name="full_name" class="form-control" value="<?= $user['full_name'] ?>">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>">
        </div>
        <div class="form-group">
          <label>Số điện thoại</label>
          <input type="tel" name="phone" class="form-control" value="<?= $user['phone'] ?>">
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" class="form-control" value="<?= $user['username'] ?>" disabled>
        </div>

        <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
      </form>
    </div>

    <!-- Đổi mật khẩu -->
    <div class="profile-card">
      <h3>🔑 Đổi mật khẩu</h3>
      <form method="POST" action="change-password.php" class="password-form">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        <div class="form-group">
          <label>Mật khẩu hiện tại</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Mật khẩu mới</label>
          <input type="password" name="new_password" class="form-control" minlength="3" required>
        </div>
        <button type="submit" class="btn btn-outline">Đổi mật khẩu</button>
      </form>
    </div>

    <!-- Lịch sử booking -->
    <div class="profile-card full-width">
      <h3>📋 Lịch sử đặt tour</h3>
      <?php if (mysqli_num_rows($bookings) > 0): ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Mã booking</th>
            <th>Tour</th>
            <th>Ngày đi</th>
            <th>Số người</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
          <tr>
            <td><code><?= $b['booking_code'] ?></code></td>
            <td><?= $b['tour_name'] ?></td>
            <td><?= date('d/m/Y', strtotime($b['start_date'])) ?></td>
            <td><?= $b['num_people'] ?></td>
            <td><?= formatPrice($b['total_price']) ?></td>
            <td>
              <span class="status-badge status-<?= $b['status'] ?>">
                <?= $b['status'] == 'pending' ? '⏳ Chờ' : ($b['status'] == 'paid' ? '✅ Đã TT' : '🎉 Xác nhận') ?>
              </span>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p class="text-muted">Chưa có booking nào.</p>
      <?php endif; ?>
    </div>

    <!-- Đánh giá -->
    <div class="profile-card full-width">
      <h3>⭐ Đánh giá của bạn</h3>
      <?php if (mysqli_num_rows($reviews) > 0): ?>
        <?php while ($r = mysqli_fetch_assoc($reviews)): ?>
        <div class="review-item">
          <div class="review-header">
            <span><strong><?= $r['tour_name'] ?></strong></span>
            <span><?= renderStars($r['rating']) ?></span>
            <span class="text-muted"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
          </div>
          <p><?= $r['comment'] ?></p>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-muted">Chưa có đánh giá nào.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
