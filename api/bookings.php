<?php
/**
 * API Bookings — VULN: JWT bypass, IDOR (S15)
 * Sử dụng JWT đơn giản với secret yếu, có thể decode/forge
 */
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

// VULN: JWT validation tự implement — yếu (S15)
function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    $header  = json_decode(base64_decode($parts[0]), true);
    $payload = json_decode(base64_decode($parts[1]), true);
    $signature = $parts[2];

    // VULN: Nếu algorithm = "none" → bỏ qua signature (S15)
    if (isset($header['alg']) && $header['alg'] === 'none') {
        return $payload;
    }

    // VULN: Secret key yếu, hardcoded (S15)
    $expectedSig = base64_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], JWT_SECRET, true));

    if ($signature === $expectedSig) {
        return $payload;
    }
    return false;
}

function generateJWT($userId, $role) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + 3600
    ]));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$signature";
}

// Auth check
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$payload = null;
if (preg_match('/Bearer\s+(.+)/', $authHeader, $m)) {
    $payload = verifyJWT($m[1]);
}

switch ($method) {
    case 'GET':
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Authentication required. Use POST /api/auth.php to get token']);
            break;
        }

        // VULN: IDOR — user_id từ JWT có thể bị forge (S15)
        $userId = $payload['user_id'];

        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            // VULN: IDOR — không kiểm tra booking thuộc user nào (S15)
            $result = mysqli_query($conn, "SELECT b.*, t.name as tour_name FROM bookings b JOIN tours t ON b.tour_id = t.id WHERE b.id = $id");
            $booking = mysqli_fetch_assoc($result);
            echo json_encode(['status' => 'success', 'data' => $booking]);
        } else {
            // VULN: Nếu role = admin trong JWT (có thể forge), xem tất cả bookings (S15)
            if ($payload['role'] === 'admin') {
                $result = mysqli_query($conn, "SELECT b.*, t.name as tour_name FROM bookings b JOIN tours t ON b.tour_id = t.id ORDER BY b.id DESC");
            } else {
                $result = mysqli_query($conn, "SELECT b.*, t.name as tour_name FROM bookings b JOIN tours t ON b.tour_id = t.id WHERE b.user_id = $userId ORDER BY b.id DESC");
            }
            $bookings = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $bookings[] = $row;
            }
            echo json_encode(['status' => 'success', 'count' => count($bookings), 'data' => $bookings]);
        }
        break;

    case 'POST':
        // Tạo booking qua API
        $input = json_decode(file_get_contents('php://input'), true);
        $code = generateBookingCode();
        $tour_id = $input['tour_id'] ?? 0;
        $name = $input['full_name'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $num = $input['num_people'] ?? 1;
        $date = $input['start_date'] ?? date('Y-m-d');
        $userId = $payload ? $payload['user_id'] : 'NULL';

        $tourData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM tours WHERE id = $tour_id"));
        $total = ($tourData['price'] ?? 0) * $num;

        // VULN: SQL Injection (S04)
        $sql = "INSERT INTO bookings (user_id, tour_id, booking_code, full_name, email, phone, num_people, start_date, total_price, status)
                VALUES ($userId, $tour_id, '$code', '$name', '$email', '$phone', $num, '$date', $total, 'pending')";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(['status' => 'success', 'booking_code' => $code, 'total' => $total]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
