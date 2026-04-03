<?php require_once __DIR__ . '/../config.php'; ?>
<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? "$pageTitle — " : '' ?>VietTour - Khám Phá Việt Nam</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= SITE_URL ?>" class="logo">
      <span class="logo-icon">✈️</span>
      <span class="logo-text">Viet<strong>Tour</strong></span>
    </a>
    <nav class="main-nav">
      <a href="<?= SITE_URL ?>/">Trang chủ</a>
      <a href="<?= SITE_URL ?>/search.php">Tìm tour</a>
      <a href="<?= SITE_URL ?>/booking.php">Đặt tour</a>
      <a href="<?= SITE_URL ?>/contact.php">Liên hệ</a>
      <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/profile.php">Tài khoản</a>
        <a href="<?= SITE_URL ?>/logout.php">Đăng xuất</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php">Đăng nhập</a>
      <?php endif; ?>
    </nav>
    <button class="nav-toggle" onclick="document.querySelector('.main-nav').classList.toggle('open')">☰</button>
  </div>
</header>

<main class="site-main">
  <div class="container">
    <?php
    $flash = getFlash();
    if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>
