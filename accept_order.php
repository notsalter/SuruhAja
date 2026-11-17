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
    // Verify order exists, is unassigned, and payment is completed
    $check = $conn->prepare('SELECT payment_status, driver_id, user_id FROM orders WHERE id = ?');
    $check->bind_param('i', $order_id);
    $check->execute();
    $res = $check->get_result();
    $order = $res->fetch_assoc();
    $check->close();
    if (!$order) {
        die('Order not found');
    }
    if ($order['driver_id']) {
        die('Order already assigned');
    }
    if ($order['payment_status'] !== 'paid') {
        die('Cannot accept unpaid order');
    }
    // Assign driver and set status to Diterima
    $stmt = $conn->prepare('UPDATE orders SET driver_id = ?, status = ? WHERE id = ?');
    $new_status = 'Diterima';
    $stmt->bind_param('isi', $driver_id, $new_status, $order_id);
    if ($stmt->execute()) {
        // Notify customer via email
        $emailStmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
        $emailStmt->bind_param('i', $order['user_id']);
        $emailStmt->execute();
        $emailRes = $emailStmt->get_result();
        $emailRow = $emailRes->fetch_assoc();
        $emailStmt->close();
        if ($emailRow) {
            require_once 'notifications.php';
            $subject = "Your order #$order_id has been accepted";
            $msg = "Driver has accepted your order. Status is now Diterima.";
            send_status_email($emailRow['email'], $subject, $msg);
        }
        header('Location: driver_dashboard.php');
        exit();
    } else {
        echo 'Failed to accept order.';
    }
    $stmt->close();
}
?>