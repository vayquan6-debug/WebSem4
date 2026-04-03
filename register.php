<?php
/**
 * Đăng ký — VULN: Weak password policy, SQL Injection (S04)
 * Không yêu cầu mật khẩu mạnh, MD5 hash
 */
$pageTitle = 'Đăng ký';
require_once 'includes/header.php';

if (isLoggedIn()) {
    redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = $_POST['username'];
    $password  = $_POST['password'];
    $email     = $_POST['email'];
    $full_name = $_POST['full_name'];
    $phone     = $_POST['phone'] ?? '';

    // Validate tối thiểu
    if (empty($username) || empty($password) || empty($email) || empty($full_name)) {
        setFlash('error', 'Vui lòng điền đầy đủ thông tin.');
    } else {
        // Kiểm tra username đã tồn tại
        // VULN: SQL Injection (S04)
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            setFlash('error', 'Tên đăng nhập đã tồn tại.');
        } else {
            // VULN: MD5 hash — dễ crack
            // VULN: Không yêu cầu password mạnh
            $md5pass = md5($password);

            // VULN: SQL Injection INSERT (S04)
            $sql = "INSERT INTO users (username, password, email, full_name, phone, role)
                    VALUES ('$username', '$md5pass', '$email', '$full_name', '$phone', 'user')";

            if (mysqli_query($conn, $sql)) {
                setFlash('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
                redirect('login.php');
            } else {
                setFlash('error', 'Đăng ký thất bại: ' . mysqli_error($conn));
            }
        }
    }
}
?>

<section class="section">
  <div class="auth-container">
    <div class="auth-box">
      <h2 class="auth-title">📝 Đăng Ký Tài Khoản</h2>

      <form method="POST" action="register.php" class="auth-form">
        <div class="form-group">
          <label for="full_name">Họ và tên *</label>
          <input type="text" id="full_name" name="full_name" class="form-control"
                 placeholder="Nguyễn Văn A" required>
        </div>

        <div class="form-group">
          <label for="username">Tên đăng nhập *</label>
          <input type="text" id="username" name="username" class="form-control"
                 placeholder="Chọn username" required>
        </div>

        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="email@example.com" required>
        </div>

        <div class="form-group">
          <label for="phone">Số điện thoại</label>
          <input type="tel" id="phone" name="phone" class="form-control"
                 placeholder="0912345678">
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu *</label>
          <!-- VULN: Không yêu cầu mật khẩu mạnh — minlength chỉ 3 (weak policy) -->
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Nhập mật khẩu" minlength="3" required>
          <small class="text-muted">Tối thiểu 3 ký tự</small>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Đăng ký</button>
      </form>

      <div class="auth-footer">
        <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
