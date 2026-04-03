<?php
/**
 * Chi tiết tour — VULN: SQL Injection (S04)
 * Tham số id được nối trực tiếp vào câu SQL mà không dùng prepared statement
 */
$pageTitle = 'Chi tiết tour';
require_once 'includes/header.php';

// VULN: SQL Injection — $_GET['id'] nối trực tiếp (S04)
$id = isset($_GET['id']) ? $_GET['id'] : '0';
$tour = mysqli_query($conn, "SELECT * FROM tours WHERE id = $id");

if (!$tour || mysqli_num_rows($tour) == 0) {
    echo '<div class="section"><div class="empty-state"><div class="empty-icon">❌</div><h3>Không tìm thấy tour</h3><p><a href="index.php">← Quay về trang chủ</a></p></div></div>';
    require_once 'includes/footer.php';
    exit;
}

$tour = mysqli_fetch_assoc($tour);
$pageTitle = $tour['name'];

// VULN: SQL Injection qua id — lấy reviews (S04)
$reviews = mysqli_query($conn, "SELECT r.*, u.full_name, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.tour_id = $id ORDER BY r.created_at DESC");

// Tính rating trung bình
$avgResult = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE tour_id = $id");
$avgData = mysqli_fetch_assoc($avgResult);
?>

<section class="section tour-detail">
  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="index.php">Trang chủ</a> &gt;
    <a href="search.php?destination=<?= urlencode($tour['destination']) ?>"><?= $tour['destination'] ?></a> &gt;
    <span><?= $tour['name'] ?></span>
  </div>

  <!-- Tour header -->
  <div class="tour-header">
    <div class="tour-header__image">
      <img src="<?= tourImage($tour['image']) ?>" alt="<?= $tour['name'] ?>" onerror="this.style.display='none'">
      <?php if ($tour['featured']): ?>
        <span class="badge badge-hot">🔥 Tour Hot</span>
      <?php endif; ?>
    </div>
    <div class="tour-header__info">
      <h1><?= $tour['name'] ?></h1>
      <div class="tour-meta-detail">
        <span class="meta-item">📍 <strong><?= $tour['destination'] ?></strong></span>
        <span class="meta-item">📅 <strong><?= $tour['duration_days'] ?> ngày <?= $tour['duration_days'] - 1 ?> đêm</strong></span>
        <span class="meta-item">👥 Tối đa <strong><?= $tour['max_people'] ?> người</strong></span>
        <span class="meta-item">⭐ <strong><?= number_format($avgData['avg_rating'] ?? 0, 1) ?>/5</strong> (<?= $avgData['total'] ?> đánh giá)</span>
      </div>
      <div class="tour-price-box">
        <span class="price-label">Giá chỉ từ</span>
        <span class="price-value"><?= formatPrice($tour['price']) ?></span>
        <span class="price-note">/người</span>
      </div>
      <div class="tour-actions">
        <a href="booking.php?tour_id=<?= $tour['id'] ?>" class="btn btn-primary btn-lg">📋 Đặt Tour Ngay</a>
        <a href="#reviews" class="btn btn-outline">💬 Xem đánh giá</a>
      </div>
    </div>
  </div>

  <!-- Mô tả chi tiết -->
  <div class="tour-description">
    <h2>📝 Mô tả tour</h2>
    <div class="desc-content">
      <?= nl2br($tour['description']) ?>
    </div>
  </div>

  <!-- Đánh giá -->
  <div class="tour-reviews" id="reviews">
    <h2>💬 Đánh giá từ khách hàng (<?= $avgData['total'] ?>)</h2>

    <?php if (isLoggedIn()): ?>
    <div class="review-form-box">
      <h3>Viết đánh giá của bạn</h3>
      <form action="review.php" method="POST" class="review-form">
        <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
        <div class="form-group">
          <label>Đánh giá sao</label>
          <div class="star-rating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?>>
              <label for="star<?= $i ?>">⭐</label>
            <?php endfor; ?>
          </div>
        </div>
        <div class="form-group">
          <label for="comment">Nhận xét</label>
          <!-- VULN: Stored XSS — comment sẽ được lưu và hiển thị không escape (S05) -->
          <textarea id="comment" name="comment" rows="4" class="form-control" placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
      </form>
    </div>
    <?php else: ?>
    <div class="login-prompt">
      <p>💡 <a href="login.php">Đăng nhập</a> để viết đánh giá.</p>
    </div>
    <?php endif; ?>

    <!-- Danh sách đánh giá -->
    <div class="reviews-list">
      <?php if (mysqli_num_rows($reviews) > 0): ?>
        <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
        <div class="review-item">
          <div class="review-header">
            <span class="review-author">👤 <?= $review['full_name'] ?></span>
            <span class="review-stars"><?= renderStars($review['rating']) ?></span>
            <span class="review-date"><?= date('d/m/Y', strtotime($review['created_at'])) ?></span>
          </div>
          <!-- VULN: Stored XSS — comment hiển thị trực tiếp không htmlspecialchars (S05) -->
          <div class="review-body"><?= $review['comment'] ?></div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state small">
          <p>Chưa có đánh giá nào. Hãy là người đầu tiên! 🎉</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
