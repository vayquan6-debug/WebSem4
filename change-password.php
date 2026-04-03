<?php
/**
 * Đổi mật khẩu — VULN: IDOR, MD5, no CSRF (S15, S16)
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php');
}

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_POST['user_id'];
$current = $_POST['current_password'];
$new_pass = $_POST['new_password'];

// VULN: IDOR — không kiểm tra user_id có phải user hiện tại (S15)
// VULN: MD5 hash
$md5current = md5($current);
$check = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id AND password = '$md5current'");

if (mysqli_num_rows($check) == 0) {
    setFlash('error', 'Mật khẩu hiện tại không đúng.');
    redirect("profile.php?id=$user_id");
}

$md5new = md5($new_pass);
// VULN: SQL Injection (S04)
mysqli_query($conn, "UPDATE users SET password = '$md5new' WHERE id = $user_id");

setFlash('success', 'Đổi mật khẩu thành công! 🔑');
redirect("profile.php?id=$user_id");
