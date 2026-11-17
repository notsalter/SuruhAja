<?php
include 'config.php';
include 'csrf.php'; // not needed but kept for consistency
session_start();
// Midtrans will POST JSON to this endpoint
$payload = @file_get_contents('php://input');
if (!$payload) {
    http_response_code(400);
    exit('No payload');
}
$data = json_decode($payload, true);
if (!is_array($data)) {
    http_response_code(400);
    exit('Invalid JSON');
}
// Expected fields: order_id, transaction_status
$order_id = $data['order_id'] ?? null;
$status = $data['transaction_status'] ?? null;
if (!$order_id || !$status) {
    http_response_code(400);
    exit('Missing data');
}
// Map Midtrans transaction_status to our payment_status
$payment_status = null;
if ($status === 'capture' || $status === 'settlement') {
    $payment_status = 'paid';
} elseif ($status === 'deny' || $status === 'expire' || $status === 'cancel') {
    $payment_status = 'failed';
} else {
    $payment_status = 'pending';
}
$stmt = $conn->prepare('UPDATE orders SET payment_status = ? WHERE id = ?');
$stmt->bind_param('si', $payment_status, $order_id);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    http_response_code(200);
    echo 'OK';
} else {
    http_response_code(500);
    echo 'Update failed';
}
$stmt->close();
?>