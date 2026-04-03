<?php
/**
 * Đăng nhập — VULN: MD5 password, SQL Injection (S04)
 * Sử dụng MD5 thay vì bcrypt, dễ bị brute-force
 */
$pageTitle = 'Đăng nhập';
require_once 'includes/header.php';

if (isLoggedIn()) {
    redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // VULN: SQL Injection — nối chuỗi trực tiếp (S04)
    // VULN: MD5 password — dễ crack bằng rainbow table
    $md5pass = md5($password);
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$md5pass'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // VULN: Không regenerate session ID — Session Fixation (S05)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        // Cập nhật last login
        mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);

        setFlash('success', 'Đăng nhập thành công! 🎉 Xin chào ' . $user['full_name']);

        if ($user['role'] === 'admin' || $user['role'] === 'manager') {
            redirect('admin/dashboard.php');
        } else {
            redirect('index.php');
        }
    } else {
        // VULN: Thông báo lỗi khác nhau cho username sai vs password sai — User Enumeration
        $checkUser = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($checkUser) == 0) {
            setFlash('error', 'Tài khoản không tồn tại.');
        } else {
            setFlash('error', 'Mật khẩu không chính xác.');
        }
    }
}
?>

<section class="section">
  <div class="auth-container">
    <div class="auth-box">
      <h2 class="auth-title">🔐 Đăng Nhập</h2>

      <form method="POST" action="login.php" class="auth-form">
        <div class="form-group">
          <label for="username">Tên đăng nhập</label>
          <input type="text" id="username" name="username" class="form-control"
                 placeholder="Nhập username" required autofocus>
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Nhập mật khẩu" required>
        </div>

        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Đăng nhập</button>
      </form>

      <div class="auth-footer">
        <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
      </div>
    </div>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
