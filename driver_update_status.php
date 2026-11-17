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
    // Verify driver assignment and fetch current status
    $stmt = $conn->prepare('SELECT driver_id, status, payment_status FROM orders WHERE id = ?');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();
    if (!$order || $order['driver_id'] != $driver_id) {
        die('Unauthorized');
    }
    $current = $order['status'];
    // Ensure payment is completed before driver can accept
    if ($new_status === 'Diterima' && $order['payment_status'] !== 'paid') {
        die('Cannot accept order: payment not completed');
    }
    // Define allowed forward transitions
    $transitions = [
        'Proses' => ['Diterima'],
        'Diterima' => ['Dalam Perjalanan', 'Batal'],
        'Dalam Perjalanan' => ['Selesai', 'Batal']
    ];
    if (!isset($transitions[$current]) || !in_array($new_status, $transitions[$current], true)) {
        die('Invalid status transition');
    }
    $update = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $update->bind_param('si', $new_status, $order_id);
    if ($update->execute()) {
        // Notify customer via email about status change
        $emailStmt = $conn->prepare('SELECT u.email FROM users u JOIN orders o ON u.id = o.user_id WHERE o.id = ?');
        $emailStmt->bind_param('i', $order_id);
        $emailStmt->execute();
        $emailRes = $emailStmt->get_result();
        $emailRow = $emailRes->fetch_assoc();
        $emailStmt->close();
        if ($emailRow) {
            require_once 'notifications.php';
            $subject = "Order #$order_id status updated to $new_status";
            $msg = "Your order #$order_id is now $new_status.";
            send_status_email($emailRow['email'], $subject, $msg);
        }
        header('Location: driver_dashboard.php');
        exit();
    } else {
        echo 'Failed to update status';
    }
    $update->close();
}
?>