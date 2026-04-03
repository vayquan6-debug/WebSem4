-- ==============================================
-- VietTour Database Setup
-- ⚠️ INTENTIONALLY VULNERABLE - Lab pentest only
-- ==============================================

DROP DATABASE IF EXISTS viettour_db;
CREATE DATABASE viettour_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE viettour_db;

-- Tạo user
CREATE USER IF NOT EXISTS 'viettour_user'@'localhost' IDENTIFIED BY 'P@ssw0rd123';
GRANT ALL PRIVILEGES ON viettour_db.* TO 'viettour_user'@'localhost';
FLUSH PRIVILEGES;

-- ========== TABLES ==========

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin','user') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200),
    destination VARCHAR(100),
    price DECIMAL(12,0) DEFAULT 0,
    duration_days INT DEFAULT 1,
    max_people INT DEFAULT 20,
    description TEXT,
    itinerary TEXT,
    image VARCHAR(255) DEFAULT 'default-tour.jpg',
    featured TINYINT(1) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(20) NOT NULL,
    tour_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    num_people INT DEFAULT 1,
    start_date DATE,
    notes TEXT,
    payment_proof VARCHAR(255),
    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    total_price DECIMAL(12,0) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES tours(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    author VARCHAR(100),
    content TEXT,
    rating INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES tours(id)
);

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    subject VARCHAR(200),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========== SEED DATA ==========

-- Admin & users (MD5 hash — cố ý yếu, KHÔNG dùng bcrypt)
INSERT INTO users (username, password, email, full_name, phone, role) VALUES
('admin',   MD5('admin123'),       'admin@viettour.local',   'Administrator',       '0901234567', 'admin'),
('manager', MD5('manager2026'),    'manager@viettour.local', 'Nguyễn Văn Manager',  '0907654321', 'admin'),
('editor',  MD5('editor!'),        'editor@viettour.local',  'Trần Thị Editor',     '0912345678', 'admin'),
('user1',   MD5('password'),       'user1@viettour.local',   'Lê Văn User',         '0923456789', 'user'),
('user2',   MD5('123456'),         'user2@viettour.local',   'Phạm Thị Khách',      '0934567890', 'user'),
('giamdoc', MD5('Welcome123!'),    'giamdoc@viettour.local', 'Nguyễn Văn Giám Đốc', '0945678901', 'user'),
('ketoan',  MD5('Welcome123!'),    'ketoan@viettour.local',  'Trần Thị Kế Toán',    '0956789012', 'user');

-- Tours
INSERT INTO tours (name, slug, destination, price, duration_days, max_people, description, itinerary, image, featured) VALUES
('Vịnh Hạ Long 3N2Đ', 'vinh-ha-long-3n2d', 'Quảng Ninh', 4500000, 3, 30,
 'Khám phá kỳ quan thiên nhiên thế giới UNESCO — Vịnh Hạ Long với hàng nghìn đảo đá vôi hùng vĩ. Tour bao gồm du thuyền 5 sao, chèo kayak, tham quan hang Sửng Sốt.',
 'Ngày 1: Hà Nội → Hạ Long, lên du thuyền, tham quan hang Sửng Sốt\nNgày 2: Chèo kayak, tắm biển, BBQ trên thuyền\nNgày 3: Ngắm bình minh, trở về Hà Nội',
 'halong.jpg', 1),

('Đà Nẵng - Hội An 4N3Đ', 'da-nang-hoi-an-4n3d', 'Đà Nẵng', 5200000, 4, 25,
 'Bãi biển Mỹ Khê top 6 thế giới, phố cổ Hội An lung linh đèn lồng, Bà Nà Hills với Cầu Vàng nổi tiếng.',
 'Ngày 1: Đà Nẵng, Bà Nà Hills\nNgày 2: Bãi biển Mỹ Khê, Ngũ Hành Sơn\nNgày 3: Phố cổ Hội An, đêm hoa đăng\nNgày 4: Chợ Hàn, bay về',
 'danang.jpg', 1),

('Phú Quốc Paradise 5N4Đ', 'phu-quoc-paradise-5n4d', 'Kiên Giang', 7800000, 5, 20,
 'Nghỉ dưỡng đảo ngọc Phú Quốc — resort 5 sao, lặn ngắm san hô, câu cá, sunset tại bãi Sao.',
 'Ngày 1: Bay đến Phú Quốc, nhận phòng resort\nNgày 2: Tour 4 đảo, lặn ngắm san hô\nNgày 3: VinWonders, Grand World\nNgày 4: Bãi Sao, chợ đêm Dinh Cậu\nNgày 5: Tự do, bay về',
 'phuquoc.jpg', 1),

('Sapa Trekking 3N2Đ', 'sapa-trekking-3n2d', 'Lào Cai', 3200000, 3, 15,
 'Trekking ruộng bậc thang Mù Cang Chải, homestay bản làng, đỉnh Fansipan — nóc nhà Đông Dương.',
 'Ngày 1: Hà Nội → Sapa, trekking bản Cát Cát\nNgày 2: Chinh phục Fansipan bằng cáp treo\nNgày 3: Chợ phiên Bắc Hà, về Hà Nội',
 'sapa.jpg', 0),

('Huế Cố Đô 2N1Đ', 'hue-co-do-2n1d', 'Thừa Thiên Huế', 2800000, 2, 30,
 'Khám phá di sản văn hóa cố đô Huế — Đại Nội, lăng tẩm vua Nguyễn, sông Hương thơ mộng.',
 'Ngày 1: Đại Nội Huế, Lăng Minh Mạng, Lăng Tự Đức\nNgày 2: Chùa Thiên Mụ, Cầu Trường Tiền, đặc sản Huế',
 'hue.jpg', 0),

('Nha Trang Biển Xanh 3N2Đ', 'nha-trang-bien-xanh-3n2d', 'Khánh Hòa', 3800000, 3, 25,
 'Thành phố biển xinh đẹp — Vinpearl Land, tháp Bà Ponagar, tắm bùn khoáng nóng.',
 'Ngày 1: Bay đến Nha Trang, Vinpearl Land\nNgày 2: Tour 4 đảo, lặn biển\nNgày 3: Tháp Bà Ponagar, tắm bùn, bay về',
 'nhatrang.jpg', 1),

('Đà Lạt Mộng Mơ 3N2Đ', 'da-lat-mong-mo-3n2d', 'Lâm Đồng', 3500000, 3, 20,
 'Thành phố ngàn hoa — Hồ Xuân Hương, Đồi Chè Cầu Đất, thác Datanla, chợ đêm Đà Lạt.',
 'Ngày 1: Bay đến Đà Lạt, Hồ Xuân Hương, chợ đêm\nNgày 2: Đồi Chè Cầu Đất, Thác Datanla\nNgày 3: QUÊ Garden, Thiền Viện Trúc Lâm, bay về',
 'dalat.jpg', 0),

('Mũi Né - Phan Thiết 2N1Đ', 'mui-ne-phan-thiet-2n1d', 'Bình Thuận', 2200000, 2, 30,
 'Đồi cát bay Mũi Né, Suối Tiên, làng chài Mũi Né, hải sản tươi sống.',
 'Ngày 1: TP.HCM → Mũi Né, Đồi Cát Bay, Suối Tiên\nNgày 2: Bãi biển, làng chài, về TP.HCM',
 'muine.jpg', 0);

-- Bookings mẫu
INSERT INTO bookings (booking_code, tour_id, user_id, full_name, email, phone, num_people, start_date, status, total_price) VALUES
('VT-2026-001', 1, 4, 'Lê Văn User', 'user1@viettour.local', '0923456789', 2, '2026-04-15', 'confirmed', 9000000),
('VT-2026-002', 2, 5, 'Phạm Thị Khách', 'user2@viettour.local', '0934567890', 3, '2026-05-01', 'pending', 15600000),
('VT-2026-003', 3, 6, 'Nguyễn Văn Giám Đốc', 'giamdoc@viettour.local', '0945678901', 4, '2026-04-20', 'confirmed', 31200000),
('VT-2026-004', 6, 4, 'Lê Văn User', 'user1@viettour.local', '0923456789', 1, '2026-06-10', 'pending', 3800000);

-- Reviews mẫu
INSERT INTO reviews (tour_id, user_id, author, content, rating) VALUES
(1, 4, 'Lê Văn User', 'Tour rất tuyệt vời! Du thuyền sang trọng, cảnh đẹp mê hồn. Highly recommend!', 5),
(1, 5, 'Phạm Thị Khách', 'Hạ Long đẹp lắm, HDV nhiệt tình. Sẽ quay lại!', 4),
(2, 6, 'Nguyễn Văn Giám Đốc', 'Hội An ban đêm rất lãng mạn. Ẩm thực tuyệt hảo.', 5),
(3, 4, 'Lê Văn User', 'Phú Quốc xứng đáng đảo ngọc. Resort siêu đẹp!', 5),
(6, 5, 'Phạm Thị Khách', 'Nha Trang biển đẹp, đồ ăn ngon, giá hợp lý.', 4);
