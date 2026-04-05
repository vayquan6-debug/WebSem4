<?php
/**
 * Upload biên lai — VULN: Unrestricted File Upload (S06)
 * Chỉ kiểm tra extension phía client, server validation yếu
 * Có thể bypass bằng double extension hoặc MIME spoofing
 */
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_code = $_POST['booking_code'] ?? '';

    if (!empty($_FILES['receipt']['name'])) {
        $file = $_FILES['receipt'];
        $filename = $file['name'];
        $tmpname = $file['tmp_name'];
        $size = $file['size'];

        // VULN: Chỉ kiểm tra extension — có thể bypass bằng double extension (S06)
        // Ví dụ: shell.php.jpg, shell.phtml, shell.php%00.jpg
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

        if (!in_array($ext, $allowed)) {
            setFlash('error', 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF) hoặc PDF.');
        } elseif ($size > 5 * 1024 * 1024) {
            setFlash('error', 'File quá lớn (tối đa 5MB).');
        } else {
            // VULN: Không kiểm tra MIME type thực sự (S06)
            // VULN: Giữ nguyên tên file gốc — có thể chứa path traversal (S06)
            $uploadDir = 'uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // VULN: Tên file giữ nguyên — attacker có thể upload shell.php.jpg (S06)
            $uploadPath = $uploadDir . $filename;

            if (move_uploaded_file($tmpname, $uploadPath)) {
                // Cập nhật booking nếu có code
                if ($booking_code) {
                    // VULN: SQL Injection (S04)
                    mysqli_query($conn, "UPDATE bookings SET payment_proof = '$uploadPath', status = 'paid' WHERE booking_code = '$booking_code'");
                }
                setFlash('success', "Upload thành công! File: <a href='$uploadPath' target='_blank'>$filename</a>");
                redirect("upload.php?code=$booking_code");
            } else {
                setFlash('error', 'Upload thất bại. Vui lòng thử lại.');
                redirect("upload.php");
            }
        }
    } else {
        setFlash('error', 'Vui lòng chọn file để upload.');
    }
}

$code = isset($_GET['code']) ? $_GET['code'] : '';

$pageTitle = 'Upload biên lai';
require_once 'includes/header.php';
?>

<section class="section">
  <h2 class="section-title">📤 Upload Biên Lai Thanh Toán</h2>

  <div class="upload-container">
    <div class="upload-info">
      <h3>📌 Hướng dẫn</h3>
      <ol>
        <li>Chuyển khoản theo thông tin ở trang Đặt Tour</li>
        <li>Chụp màn hình hoặc lưu biên lai thanh toán</li>
        <li>Upload file biên lai bên dưới (JPG, PNG, GIF, PDF — tối đa 5MB)</li>
        <li>Nhập mã booking để chúng tôi xác nhận nhanh hơn</li>
      </ol>
    </div>

    <form method="POST" action="upload.php" enctype="multipart/form-data" class="upload-form">
      <div class="form-group">
        <label for="booking_code">Mã booking</label>
        <input type="text" id="booking_code" name="booking_code" class="form-control"
               value="<?= htmlspecialchars($code) ?>" placeholder="VT-XXXXXXXX">
      </div>

      <div class="form-group">
        <label for="receipt">Chọn file biên lai *</label>
        <div class="upload-dropzone" id="dropzone">
          <div class="dropzone-icon">📁</div>
          <p>Kéo thả file vào đây hoặc click để chọn</p>
          <p class="text-muted">JPG, PNG, GIF, PDF — Tối đa 5MB</p>
          <!-- VULN: accept không ngăn được upload file khác loại (S06) -->
          <input type="file" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
        </div>
        <div id="file-preview" class="file-preview"></div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg">📤 Upload biên lai</button>
    </form>

    <!-- Kiểm tra trạng thái booking -->
    <div class="booking-check">
      <h3>🔍 Kiểm tra trạng thái booking</h3>
      <form method="GET" action="upload.php" class="check-form">
        <div class="form-row">
          <input type="text" name="code" class="form-control" placeholder="Nhập mã booking..." value="<?= htmlspecialchars($code) ?>">
          <button type="submit" class="btn btn-outline">Kiểm tra</button>
        </div>
      </form>
      <?php if ($code):
        // VULN: SQL Injection (S04)
        $bk = mysqli_query($conn, "SELECT b.*, t.name as tour_name FROM bookings b JOIN tours t ON b.tour_id = t.id WHERE b.booking_code = '$code'");
        $booking = mysqli_fetch_assoc($bk);
        if ($booking):
      ?>
        <div class="booking-status">
          <table class="info-table">
            <tr><td>Mã booking</td><td><strong><?= $booking['booking_code'] ?></strong></td></tr>
            <tr><td>Tour</td><td><?= $booking['tour_name'] ?></td></tr>
            <tr><td>Họ tên</td><td><?= $booking['full_name'] ?></td></tr>
            <tr><td>Số người</td><td><?= $booking['num_people'] ?></td></tr>
            <tr><td>Tổng tiền</td><td><strong><?= formatPrice($booking['total_price']) ?></strong></td></tr>
            <tr><td>Trạng thái</td><td>
              <span class="status-badge status-<?= $booking['status'] ?>">
                <?= $booking['status'] == 'pending' ? '⏳ Chờ thanh toán' : ($booking['status'] == 'paid' ? '✅ Đã thanh toán' : '🎉 Đã xác nhận') ?>
              </span>
            </td></tr>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted">Không tìm thấy booking với mã: <?= htmlspecialchars($code) ?></p>
      <?php endif; endif; ?>
    </div>
  </div>
</section>

<script>
// File preview
document.getElementById('receipt')?.addEventListener('change', function(e) {
  const preview = document.getElementById('file-preview');
  const file = e.target.files[0];
  if (file) {
    preview.innerHTML = `<p>📎 ${file.name} (${(file.size/1024).toFixed(1)} KB)</p>`;
  }
});
</script>

<?php require_once 'includes/footer.php'; ?>
