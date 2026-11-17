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
        // Notify customer about status change
        $emailStmt = $conn->prepare('SELECT u.email FROM users u JOIN orders o ON u.id = o.user_id WHERE o.id = ?');
        $emailStmt->bind_param('i', $order_id);
        $emailStmt->execute();
        $emailRes = $emailStmt->get_result();
        $emailRow = $emailRes->fetch_assoc();
        $emailStmt->close();
        if ($emailRow) {
            require_once 'notifications.php';
            $subject = "Order #$order_id status updated to $new_status (by admin)";
            $msg = "Your order #$order_id status has been changed to $new_status by the administration.";
            send_status_email($emailRow['email'], $subject, $msg);
        }
        header('Location: admin_orders.php');
        exit();
    } else {
        echo 'Failed to update status';
    }
    $stmt->close();
}
?>