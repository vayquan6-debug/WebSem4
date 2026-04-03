<?php
/**
 * Tìm kiếm tour — VULN: Reflected XSS (S05)
 * Input từ user được echo trực tiếp vào HTML mà không escape
 */
$pageTitle = 'Tìm kiếm tour';
require_once 'includes/header.php';

$keyword = isset($_GET['q']) ? $_GET['q'] : '';
$destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';

$results = [];

if ($keyword || $destination || $min_price || $max_price) {
    // VULN: SQL Injection qua keyword (S04)
    $sql = "SELECT * FROM tours WHERE active = 1";

    if ($keyword) {
        $sql .= " AND (name LIKE '%$keyword%' OR description LIKE '%$keyword%' OR destination LIKE '%$keyword%')";
    }
    if ($destination) {
        $sql .= " AND destination LIKE '%$destination%'";
    }
    if ($min_price) {
        $sql .= " AND price >= $min_price";
    }
    if ($max_price) {
        $sql .= " AND price <= $max_price";
    }

    $sql .= " ORDER BY featured DESC, price ASC";
    $result = mysqli_query($conn, $sql);
}

// Lấy danh sách điểm đến cho dropdown
$destinations = mysqli_query($conn, "SELECT DISTINCT destination FROM tours WHERE active = 1 ORDER BY destination");
?>

<section class="section">
  <h2 class="section-title">🔍 Tìm Kiếm Tour</h2>

  <!-- Form tìm kiếm -->
  <div class="search-box">
    <form method="GET" action="search.php" class="search-form">
      <div class="form-row">
        <div class="form-group">
          <label for="q">Từ khóa</label>
          <!-- VULN: Reflected XSS — giá trị $keyword in trực tiếp vào value (S05) -->
          <input type="text" id="q" name="q" value="<?= $keyword ?>" placeholder="Nhập tên tour, địa điểm..." class="form-control">
        </div>
        <div class="form-group">
          <label for="destination">Điểm đến</label>
          <select id="destination" name="destination" class="form-control">
            <option value="">-- Tất cả --</option>
            <?php while ($d = mysqli_fetch_assoc($destinations)): ?>
              <option value="<?= $d['destination'] ?>" <?= $destination == $d['destination'] ? 'selected' : '' ?>>
                <?= $d['destination'] ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="min_price">Giá từ</label>
          <input type="number" id="min_price" name="min_price" value="<?= $min_price ?>" placeholder="VNĐ" class="form-control">
        </div>
        <div class="form-group">
          <label for="max_price">Giá đến</label>
          <input type="number" id="max_price" name="max_price" value="<?= $max_price ?>" placeholder="VNĐ" class="form-control">
        </div>
        <div class="form-group form-group-btn">
          <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Kết quả tìm kiếm -->
  <?php if ($keyword || $destination || $min_price || $max_price): ?>
    <!-- VULN: Reflected XSS — $keyword in trực tiếp không escape (S05) -->
    <div class="search-results-header">
      <p>Kết quả tìm kiếm cho: <strong>"<?= $keyword ?>"</strong>
      <?php if ($destination): ?> tại <strong><?= $destination ?></strong><?php endif; ?>
      — Tìm thấy <strong><?= $result ? mysqli_num_rows($result) : 0 ?></strong> tour</p>
    </div>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
      <div class="tour-grid">
        <?php while ($tour = mysqli_fetch_assoc($result)): ?>
        <div class="tour-card">
          <div class="tour-card__image">
            <img src="assets/images/<?= $tour['image'] ?>" alt="<?= $tour['name'] ?>" onerror="this.src='assets/images/default-tour.jpg'">
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
            <a href="tour.php?id=<?= $tour['id'] ?>" class="btn btn-primary btn-sm">Chi tiết →</a>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>Không tìm thấy tour nào</h3>
        <p>Thử thay đổi từ khóa hoặc bộ lọc để có kết quả tốt hơn.</p>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon">🗺️</div>
      <h3>Nhập từ khóa để bắt đầu tìm kiếm</h3>
      <p>Tìm kiếm theo tên tour, điểm đến, hoặc khoảng giá.</p>
    </div>
  <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
