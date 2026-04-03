<?php
/**
 * Xử lý đánh giá — VULN: Stored XSS (S05)
 * Comment được INSERT trực tiếp không sanitize, hiển thị không escape
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!isLoggedIn()) {
    setFlash('error', 'Bạn cần đăng nhập để gửi đánh giá.');
    redirect('login.php');
}

$tour_id = $_POST['tour_id'];
$rating  = (int)$_POST['rating'];
$comment = $_POST['comment'];
$user_id = $_SESSION['user_id'];

// Validate cơ bản
if ($rating < 1 || $rating > 5) {
    setFlash('error', 'Đánh giá không hợp lệ.');
    redirect("tour.php?id=$tour_id");
}

if (empty($comment)) {
    setFlash('error', 'Vui lòng nhập nhận xét.');
    redirect("tour.php?id=$tour_id");
}

// VULN: Stored XSS — $comment không được htmlspecialchars() hay htmlentities() (S05)
// VULN: SQL Injection — nối chuỗi trực tiếp (S04)
$sql = "INSERT INTO reviews (user_id, tour_id, rating, comment) VALUES ($user_id, $tour_id, $rating, '$comment')";

if (mysqli_query($conn, $sql)) {
    setFlash('success', 'Cảm ơn bạn đã gửi đánh giá! ⭐');
} else {
    setFlash('error', 'Gửi đánh giá thất bại: ' . mysqli_error($conn));
}

redirect("tour.php?id=$tour_id#reviews");
