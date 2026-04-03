<?php
/**
 * Trang chủ VietTour — Hiển thị tour nổi bật
 */
$pageTitle = 'Trang chủ';
require_once 'includes/header.php';

// Lấy tour nổi bật
$featured = mysqli_query($conn, "SELECT * FROM tours WHERE featured = 1 AND active = 1 ORDER BY id ASC LIMIT 6");

// Lấy tất cả tour
$allTours = mysqli_query($conn, "SELECT * FROM tours WHERE active = 1 ORDER BY created_at DESC");
?>

<!-- Hero -->
<section class="hero">
  <div class="hero-content">
    <h1>Khám Phá Việt Nam<br>Cùng <span class="text-primary">VietTour</span></h1>
    <p>Trải nghiệm những hành trình tuyệt vời nhất — Hạ Long, Đà Nẵng, Phú Quốc và hơn thế nữa!</p>
    <div class="hero-actions">
      <a href="search.php" class="btn btn-primary btn-lg">🔍 Tìm tour ngay</a>
      <a href="booking.php" class="btn btn-outline btn-lg">📋 Đặt tour</a>
    </div>
  </div>
</section>

<!-- Tour nổi bật -->
<section class="section">
  <h2 class="section-title">🌟 Tour Nổi Bật</h2>
  <div class="tour-grid">
    <?php while ($tour = mysqli_fetch_assoc($featured)): ?>
    <div class="tour-card">
      <div class="tour-card__image">
        <img src="assets/images/<?= $tour['image'] ?>" alt="<?= $tour['name'] ?>" onerror="this.src='assets/images/default-tour.jpg'">
        <?php if ($tour['featured']): ?>
          <span class="badge badge-hot">🔥 Hot</span>
        <?php endif; ?>
      </div>
      <div class="tour-card__body">
        <h3><a href="tour.php?id=<?= $tour['id'] ?>"><?= $tour['name'] ?></a></h3>
        <p class="tour-meta">
          <span>📍 <?= $tour['destination'] ?></span>
          <span>📅 <?= $tour['duration_days'] ?> ngày</span>
          <span>👥 Tối đa <?= $tour['max_people'] ?></span>
        </p>
        <p class="tour-desc"><?= truncate($tour['description']) ?></p>
      </div>
      <div class="tour-card__footer">
        <span class="tour-price"><?= formatPrice($tour['price']) ?></span>
        <a href="tour.php?id=<?= $tour['id'] ?>" class="btn btn-primary btn-sm">Xem chi tiết →</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- Tất cả tour -->
<section class="section">
  <h2 class="section-title">📋 Tất Cả Tour</h2>
  <div class="tour-grid">
    <?php while ($tour = mysqli_fetch_assoc($allTours)): ?>
    <div class="tour-card">
      <div class="tour-card__image">
        <img src="assets/images/<?= $tour['image'] ?>" alt="<?= $tour['name'] ?>" onerror="this.src='assets/images/default-tour.jpg'">
      </div>
      <div class="tour-card__body">
        <h3><a href="tour.php?id=<?= $tour['id'] ?>"><?= $tour['name'] ?></a></h3>
        <p class="tour-meta">
          <span>📍 <?= $tour['destination'] ?></span>
          <span>📅 <?= $tour['duration_days'] ?> ngày</span>
        </p>
      </div>
      <div class="tour-card__footer">
        <span class="tour-price"><?= formatPrice($tour['price']) ?></span>
        <a href="tour.php?id=<?= $tour['id'] ?>" class="btn btn-primary btn-sm">Chi tiết →</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- Vì sao chọn VietTour -->
<section class="section section-why">
  <h2 class="section-title">🏆 Vì Sao Chọn VietTour?</h2>
  <div class="features-grid">
    <div class="feature">
      <div class="feature-icon">💰</div>
      <h3>Giá tốt nhất</h3>
      <p>Cam kết giá tour tốt nhất thị trường, hoàn tiền nếu tìm được giá rẻ hơn.</p>
    </div>
    <div class="feature">
      <div class="feature-icon">🛡️</div>
      <h3>An toàn</h3>
      <p>Bảo hiểm du lịch trọn gói, đội ngũ hướng dẫn viên chuyên nghiệp.</p>
    </div>
    <div class="feature">
      <div class="feature-icon">⭐</div>
      <h3>Đánh giá 4.8/5</h3>
      <p>Hơn 10,000 khách hàng hài lòng với dịch vụ của chúng tôi.</p>
    </div>
    <div class="feature">
      <div class="feature-icon">📞</div>
      <h3>Hỗ trợ 24/7</h3>
      <p>Hotline 1900-6868, hỗ trợ bạn mọi lúc mọi nơi.</p>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
