<?php
/**
 * Đăng xuất
 */
require_once 'config.php';

// Xóa toàn bộ session
session_unset();
session_destroy();

// Redirect về trang chủ
setcookie(session_name(), '', time() - 3600, '/');
session_start();
setFlash('success', 'Đã đăng xuất thành công. Hẹn gặp lại! 👋');
header('Location: index.php');
exit;
