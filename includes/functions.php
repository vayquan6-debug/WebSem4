<?php
/**
 * Helper functions
 * ⚠️ INTENTIONALLY VULNERABLE
 */

/**
 * Format giá tiền VNĐ
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' ₫';
}

/**
 * Tạo booking code
 */
function generateBookingCode() {
    return 'VT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Hiển thị sao rating
 */
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '★' : '☆';
    }
    return '<span class="stars">' . $stars . '</span>';
}

/**
 * Cắt ngắn text
 */
function truncate($text, $length = 150) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Trả về URL ảnh tour — hỗ trợ cả URL online và file local
 */
function tourImage($image, $fallback = 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800&q=80') {
    if (!$image) return $fallback;
    if (str_starts_with($image, 'http')) return $image;
    return 'assets/images/' . $image;
}

/**
 * Flash messages
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect
 */
function redirect($url) {
    header("Location: $url");
    exit;
}
