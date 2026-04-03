<?php
/**
 * Đặt tour — VULN: CSRF (S16), SQL Injection INSERT (S04)
 * Không có CSRF token, dữ liệu INSERT không được escape
 */
$pageTitle = 'Đặt tour';
require_once 'includes/header.php';

// Xử lý POST — đặt tour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULN: Không kiểm tra CSRF token (S16)
    $tour_id    = $_POST['tour_id'];
    $full_name  = $_POST['full_name'];
    $email      = $_POST['email'];
    $phone      = $_POST['phone'];
    $num_people = $_POST['num_people'];
    $start_date = $_POST['start_date'];
    $notes      = $_POST['notes'] ?? '';
    $user_id    = isLoggedIn() ? $_SESSION['user_id'] : 'NULL';
    $booking_code = generateBookingCode();

    // Lấy giá tour
    // VULN: SQL Injection (S04)
    $tourResult = mysqli_query($conn, "SELECT price FROM tours WHERE id = $tour_id");
    $tourData = mysqli_fetch_assoc($tourResult);
    $total = $tourData['price'] * $num_people;

    // VULN: SQL Injection INSERT — nối chuỗi trực tiếp (S04)
    $sql = "INSERT INTO bookings (user_id, tour_id, booking_code, full_name, email, phone, num_people, start_date, notes, total_price, status)
            VALUES ($user_id, $tour_id, '$booking_code', '$full_name', '$email', '$phone', $num_people, '$start_date', '$notes', $total, 'pending')";

    if (mysqli_query($conn, $sql)) {
        setFlash('success', "Đặt tour thành công! Mã booking: <strong>$booking_code</strong>. Vui lòng thanh toán để xác nhận.");
        redirect("booking.php?success=1&code=$booking_code");
    } else {
        setFlash('error', 'Đặt tour thất bại: ' . mysqli_error($conn));
    }
}

// Lấy thông tin tour nếu có tour_id
$tour = null;
if (isset($_GET['tour_id'])) {
    $tid = $_GET['tour_id'];
    // VULN: SQL Injection (S04)
    $tourQ = mysqli_query($conn, "SELECT * FROM tours WHERE id = $tid AND active = 1");
    $tour = mysqli_fetch_assoc($tourQ);
}

// Hiển thị thông báo thành công
if (isset($_GET['success']) && isset($_GET['code'])) {
    $code = $_GET['code'];
}
?>

<section class="section">
  <h2 class="section-title">📋 Đặt Tour</h2>

  <?php if (isset($code)): ?>
    <!-- Booking thành công -->
    <div class="booking-success">
      <div class="success-icon">✅</div>
      <h3>Đặt tour thành công!</h3>
      <p>Mã booking của bạn: <strong class="booking-code"><?= htmlspecialchars($code) ?></strong></p>
      <p>Vui lòng chuyển khoản và upload biên lai tại trang bên dưới.</p>
      <div class="success-actions">
        <a href="upload.php?code=<?= htmlspecialchars($code) ?>" class="btn btn-primary">📤 Upload biên lai</a>
        <a href="index.php" class="btn btn-outline">← Về trang chủ</a>
      </div>
    </div>
  <?php else: ?>
    <!-- Form đặt tour -->
    <div class="booking-container">
      <?php if ($tour): ?>
      <div class="booking-tour-info">
        <h3>🗺️ <?= $tour['name'] ?></h3>
        <p>📍 <?= $tour['destination'] ?> — 📅 <?= $tour['duration_days'] ?> ngày</p>
        <p class="tour-price"><?= formatPrice($tour['price']) ?> /người</p>
      </div>
      <?php endif; ?>

      <!-- VULN: Không có CSRF token trong form (S16) -->
      <form method="POST" action="booking.php" class="booking-form">
        <?php if ($tour): ?>
          <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
        <?php else: ?>
          <div class="form-group">
            <label for="tour_id">Chọn tour *</label>
            <select name="tour_id" id="tour_id" class="form-control" required>
              <option value="">-- Chọn tour --</option>
              <?php
              $allTours = mysqli_query($conn, "SELECT id, name, destination, price FROM tours WHERE active = 1 ORDER BY name");
              while ($t = mysqli_fetch_assoc($allTours)):
              ?>
                <option value="<?= $t['id'] ?>"><?= $t['name'] ?> — <?= $t['destination'] ?> (<?= formatPrice($t['price']) ?>)</option>
              <?php endwhile; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="form-row">
          <div class="form-group">
            <label for="full_name">Họ và tên *</label>
            <input type="text" id="full_name" name="full_name" class="form-control" required
                   value="<?= isLoggedIn() ? currentUser()['full_name'] : '' ?>">
          </div>
          <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?= isLoggedIn() ? currentUser()['email'] : '' ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="phone">Số điện thoại *</label>
            <input type="tel" id="phone" name="phone" class="form-control" required
                   value="<?= isLoggedIn() ? currentUser()['phone'] : '' ?>">
          </div>
          <div class="form-group">
            <label for="num_people">Số người *</label>
            <input type="number" id="num_people" name="num_people" class="form-control" min="1" max="50" value="1" required>
          </div>
        </div>

        <div class="form-group">
          <label for="start_date">Ngày khởi hành *</label>
          <input type="date" id="start_date" name="start_date" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="notes">Ghi chú thêm</label>
          <textarea id="notes" name="notes" rows="3" class="form-control" placeholder="Yêu cầu đặc biệt, số phòng, khẩu phần ăn..."></textarea>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary btn-lg">✅ Xác nhận đặt tour</button>
        </div>
      </form>

      <!-- Thông tin thanh toán -->
      <div class="payment-info">
        <h3>💳 Thông tin chuyển khoản</h3>
        <table class="info-table">
          <tr><td>Ngân hàng</td><td><strong>Vietcombank</strong></td></tr>
          <tr><td>Số tài khoản</td><td><strong>1234567890</strong></td></tr>
          <tr><td>Chủ tài khoản</td><td><strong>CONG TY TNHH VIETTOUR</strong></td></tr>
          <tr><td>Nội dung</td><td><strong>[Mã booking] - [Họ tên]</strong></td></tr>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
