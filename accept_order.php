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
    $driver_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('UPDATE orders SET driver_id = ?, status = ? WHERE id = ? AND driver_id IS NULL');
    $new_status = 'Diterima';
    $stmt->bind_param('isi', $driver_id, $new_status, $order_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        header('Location: driver_dashboard.php');
        exit();
    } else {
        echo 'Failed to accept order.';
    }
    $stmt->close();
}
?>