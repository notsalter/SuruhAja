<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    exit('Unauthorized');
}
$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    die('Invalid order ID');
}
$stmt = $conn->prepare('SELECT o.id, s.name AS service, o.status, o.payment_status, o.price, o.address FROM orders o JOIN services s ON o.service_id = s.id WHERE o.id = ? AND o.user_id = ?');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
if (!$order) {
    die('Order not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Order #<?php echo $order['id']; ?></h1>
    <p><strong>Service:</strong> <?php echo htmlspecialchars($order['service']); ?></p>
    <p><strong>Status:</strong> <?php echo $order['status']; ?></p>
    <p><strong>Payment:</strong> <?php echo $order['payment_status']; ?></p>
    <p><strong>Price:</strong> <?php echo number_format($order['price'], 2); ?></p>
    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
    <?php if ($order['status'] === 'Proses'): ?>
        <form method="POST" action="cancel_order.php" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            <button type="submit" class="bg-red-500 text-white py-2 px-4 rounded">Cancel Order</button>
        </form>
    <?php endif; ?>
    <p class="mt-4"><a href="customer_orders.php" class="text-blue-600">← Back to Orders</a></p>
</div>
</body>
</html>