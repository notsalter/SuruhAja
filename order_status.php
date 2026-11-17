<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}
$stmt = $conn->prepare('SELECT status FROM orders WHERE id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit();
}
echo json_encode(['status' => $order['status']]);
?>