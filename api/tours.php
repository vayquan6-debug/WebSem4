<?php
/**
 * API Tours — VULN: No rate limiting, SQL Injection (S17, S04)
 * REST API trả về JSON — không có authentication cho GET
 */
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lấy 1 tour hoặc tất cả
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            // VULN: SQL Injection (S04)
            $result = mysqli_query($conn, "SELECT * FROM tours WHERE id = $id");
            $tour = mysqli_fetch_assoc($result);
            if ($tour) {
                echo json_encode(['status' => 'success', 'data' => $tour]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Tour not found']);
            }
        } else {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            // VULN: Information disclosure — trả về tất cả fields kể cả internal (S03)
            $result = mysqli_query($conn, "SELECT * FROM tours WHERE active = 1 ORDER BY id LIMIT $limit OFFSET $offset");
            $tours = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $tours[] = $row;
            }
            echo json_encode(['status' => 'success', 'count' => count($tours), 'data' => $tours]);
        }
        break;

    case 'POST':
        // Thêm tour (cần auth)
        $input = json_decode(file_get_contents('php://input'), true);

        // VULN: Kiểm tra auth bằng header tự tạo — dễ bypass (S15)
        $token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
        if ($token !== 'viettour-api-secret-2026') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid API token']);
            break;
        }

        $name = $input['name'] ?? '';
        $destination = $input['destination'] ?? '';
        $price = $input['price'] ?? 0;
        $duration = $input['duration_days'] ?? 1;

        // VULN: SQL Injection (S04)
        $sql = "INSERT INTO tours (name, destination, price, duration_days, active) VALUES ('$name', '$destination', $price, $duration, 1)";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['status' => 'success', 'id' => mysqli_insert_id($conn)]);
        } else {
            http_response_code(500);
            // VULN: Error info disclosure
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'DELETE':
        // VULN: Không kiểm tra auth cho DELETE (S15)
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            mysqli_query($conn, "DELETE FROM tours WHERE id = $id");
            echo json_encode(['status' => 'success', 'message' => "Tour #$id deleted"]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing tour id']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
