<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'driver') {
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        die('Invalid CSRF token');
    }
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $driver_id = $_SESSION['user_id'];
    // Ensure the driver is assigned to this order
    $stmt = $conn->prepare('SELECT driver_id FROM orders WHERE id = ?');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();
    if (!$order || $order['driver_id'] != $driver_id) {
        die('Unauthorized');
    }
    // Allowed status transitions
    $allowed = ['Diterima', 'Dalam Perjalanan', 'Selesai'];
    if (!in_array($new_status, $allowed, true)) {
        die('Invalid status');
    }
    $update = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $update->bind_param('si', $new_status, $order_id);
    if ($update->execute()) {
        header('Location: driver_dashboard.php');
        exit();
    } else {
        echo 'Failed to update status';
    }
    $update->close();
}
?>