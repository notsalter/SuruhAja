<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        die('Invalid CSRF token');
    }
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    if ($order_id <= 0 || empty($new_status)) {
        die('Invalid input');
    }
    $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $new_status, $order_id);
    if ($stmt->execute()) {
        header('Location: admin_orders.php');
        exit();
    } else {
        echo 'Failed to update status';
    }
    $stmt->close();
}
?>