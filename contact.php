<?php
/**
 * Trang liên hệ — VULN: SQL Injection INSERT (S04), Stored XSS (S05)
 */
$pageTitle = 'Liên hệ';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // VULN: SQL Injection INSERT (S04)
    $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";

    if (mysqli_query($conn, $sql)) {
        setFlash('success', 'Cảm ơn bạn! Chúng tôi sẽ phản hồi sớm nhất.');
    } else {
        setFlash('error', 'Gửi thất bại: ' . mysqli_error($conn));
    }
}
?>

<section class="section">
  <h2 class="section-title">📞 Liên Hệ VietTour</h2>

  <div class="booking-container">
    <div class="booking-tour-info">
      <h3>🏖️ VietTour — Công ty Du lịch số 1 Việt Nam</h3>
      <p>📍 Q1, TP.HCM</p>
      <p>📞 Hotline: 123456 | ✉️ info@viettour.local</p>
      <p>🕐 Giờ làm việc: 8:00 - 20:00 (T2 - CN)</p>
    </div>

    <form method="POST" action="contact.php" class="booking-form">
      <div class="form-row">
        <div class="form-group">
          <label for="name">Họ và tên *</label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" class="form-control" required>
        </div>
      </div>

      <div class="form-group">
        <label for="subject">Tiêu đề *</label>
        <input type="text" id="subject" name="subject" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="message">Nội dung *</label>
        <textarea id="message" name="message" rows="5" class="form-control" required></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg">📤 Gửi liên hệ</button>
    </form>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
