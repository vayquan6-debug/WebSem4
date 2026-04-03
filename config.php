<?php
/**
 * VietTour - Cấu hình kết nối Database
 * ⚠️ INTENTIONALLY VULNERABLE - Lab pentest only
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'viettour_user');
define('DB_PASS', 'P@ssw0rd123');
define('DB_NAME', 'viettour_db');

define('SITE_NAME', 'VietTour - Khám Phá Việt Nam');
define('SITE_URL', 'http://172.99.100.30');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('JWT_SECRET', 'viettour_secret_key_2026');

// Kết nối MySQL — không dùng prepared statements (cố ý yếu)
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Kết nối database thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Session
session_start();

/**
 * Helper: kiểm tra đăng nhập
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    $id = $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    return mysqli_fetch_assoc($result);
}
