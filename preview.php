<?php
/**
 * Preview URL — VULN: Server-Side Request Forgery (S16)
 * Cho phép user nhập URL để preview nội dung, server sẽ fetch URL đó
 * Attacker có thể dùng để scan internal network hoặc đọc file nội bộ
 */
$pageTitle = 'Xem trước trang web';
require_once 'includes/header.php';

$url = isset($_GET['url']) ? $_GET['url'] : '';
$content = '';
$error = '';

if ($url) {
    // VULN: SSRF — fetch URL do user cung cấp không kiểm tra (S16)
    // Attacker có thể dùng: file:///etc/passwd, http://172.99.10.10/admin, gopher://, dict://
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // VULN: Không restrict protocol, không whitelist domain (S16)
    $content = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = 'Lỗi: ' . curl_error($ch);
    }
    curl_close($ch);
}
?>

<section class="section">
  <h2 class="section-title">🌐 Xem Trước Trang Web</h2>

  <div class="preview-container">
    <div class="preview-info">
      <p>📌 Nhập URL để xem trước nội dung trang web (ví dụ: trang thanh toán ngân hàng, thông tin khuyến mãi...).</p>
    </div>

    <form method="GET" action="preview.php" class="preview-form">
      <div class="form-row">
        <div class="form-group" style="flex: 1;">
          <input type="text" name="url" class="form-control" placeholder="https://example.com/page"
                 value="<?= htmlspecialchars($url) ?>">
        </div>
        <button type="submit" class="btn btn-primary">🔍 Xem trước</button>
      </div>
    </form>

    <?php if ($url): ?>
      <div class="preview-result">
        <h3>📄 Kết quả từ: <code><?= htmlspecialchars($url) ?></code></h3>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($content): ?>
          <!-- VULN: Nội dung từ URL bên ngoài hiển thị trực tiếp — có thể kết hợp XSS (S05+S16) -->
          <div class="preview-frame">
            <iframe srcdoc="<?= htmlspecialchars($content) ?>" sandbox="allow-same-origin" class="preview-iframe"></iframe>
          </div>
          <details class="preview-source">
            <summary>📝 Xem source code</summary>
            <pre><code><?= htmlspecialchars($content) ?></code></pre>
          </details>

          <!-- VULN: Hiển thị response headers — information disclosure -->
          <details class="preview-headers">
            <summary>📋 Thông tin kỹ thuật</summary>
            <pre><?php
              $ch2 = curl_init();
              curl_setopt($ch2, CURLOPT_URL, $url);
              curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch2, CURLOPT_HEADER, true);
              curl_setopt($ch2, CURLOPT_NOBODY, true);
              curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
              $headers = curl_exec($ch2);
              curl_close($ch2);
              echo htmlspecialchars($headers);
            ?></pre>
          </details>
        <?php else: ?>
          <div class="alert alert-warning">Không nhận được nội dung từ URL.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Gợi ý -->
    <div class="preview-suggestions">
      <h3>💡 Gợi ý</h3>
      <ul>
        <li><a href="preview.php?url=http://viettour.local">http://viettour.local</a> — Trang chủ VietTour</li>
        <li><a href="preview.php?url=http://172.99.100.30">http://172.99.100.30</a> — Web server nội bộ</li>
      </ul>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
