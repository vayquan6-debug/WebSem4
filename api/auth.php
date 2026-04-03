<?php
/**
 * API Auth — VULN: Weak JWT, user enumeration (S15, S03)
 * Đăng nhập trả về JWT token
 */
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password required']);
    exit;
}

// VULN: SQL Injection (S04)
$md5pass = md5($password);
$result = mysqli_query($conn, "SELECT id, username, full_name, role, email FROM users WHERE username = '$username' AND password = '$md5pass'");

if ($result && mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);

    // VULN: JWT với secret yếu + hỗ trợ alg=none (S15)
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + 3600
    ]));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    $token = "$header.$payload.$signature";

    echo json_encode([
        'status' => 'success',
        'token' => $token,
        'user' => $user
    ]);
} else {
    http_response_code(401);
    // VULN: User enumeration — phản hồi cho biết username có tồn tại (S03)
    $checkUser = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($checkUser) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
}
