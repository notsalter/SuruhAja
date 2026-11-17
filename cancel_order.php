<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        die('Invalid CSRF token');
    }
    $order_id = (int)($_POST['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    // Verify ownership and current status
    $stmt = $conn->prepare('SELECT status FROM orders WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();
    if (!$order) {
        die('Order not found or unauthorized');
    }
    if ($order['status'] !== 'Proses') {
        die('Only orders in Proses can be cancelled');
    }
    $update = $conn->prepare('UPDATE orders SET status = "Batal" WHERE id = ?');
    $update->bind_param('i', $order_id);
    if ($update->execute()) {
        header('Location: dashboard.php');
        exit();
    } else {
        echo 'Failed to cancel order';
    }
    $update->close();
}
?>