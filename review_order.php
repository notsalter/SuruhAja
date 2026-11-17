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
// Fetch order details
$stmt = $conn->prepare('SELECT o.driver_id, o.status, u.username AS driver_name FROM orders o JOIN users u ON o.driver_id = u.id WHERE o.id = ? AND o.user_id = ?');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
if (!$order) {
    die('Order not found');
}
if ($order['status'] !== 'Selesai' || !$order['driver_id']) {
    die('Can only review completed orders with a driver');
}
// Check if already reviewed
$rev_stmt = $conn->prepare('SELECT id FROM reviews WHERE order_id = ?');
$rev_stmt->bind_param('i', $order_id);
$rev_stmt->execute();
$rev_res = $rev_stmt->get_result();
$already = $rev_res->fetch_assoc();
$rev_stmt->close();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token';
    } elseif ($already) {
        $error = 'You have already reviewed this order';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $error = 'Rating must be between 1 and 5';
        } else {
            $ins = $conn->prepare('INSERT INTO reviews (order_id, driver_id, rating, comment) VALUES (?, ?, ?, ?)');
            $ins->bind_param('iiis', $order_id, $order['driver_id'], $rating, $comment);
            if ($ins->execute()) {
                $success = 'Review submitted successfully';
                // Notify driver via email
                $emailStmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
                $emailStmt->bind_param('i', $order['driver_id']);
                $emailStmt->execute();
                $emailRes = $emailStmt->get_result();
                $emailRow = $emailRes->fetch_assoc();
                $emailStmt->close();
                if ($emailRow) {
                    require_once 'notifications.php';
                    $subject = "Your driver profile received a new rating";
                    $msg = "You received a rating of $rating for order #$order_id.\nComment: $comment";
                    send_status_email($emailRow['email'], $subject, $msg);
                }
            } else {
                $error = 'Failed to submit review';
            }
            $ins->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Driver</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Rate Driver for Order #<?php echo $order_id; ?></h1>
    <p class="mb-2">Driver: <?php echo htmlspecialchars($order['driver_name']); ?></p>
    <?php if ($already): ?>
        <p class="text-green-600">You have already reviewed this order.</p>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="mb-2 text-red-600"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="mb-2 text-green-600"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!$success): ?>
        <form method="POST" action="review_order.php?order_id=<?php echo $order_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="mb-4">
                <label class="block">Rating (1-5)</label>
                <select name="rating" class="mt-1 p-2 border w-full" required>
                    <option value="">Select rating</option>
                    <?php for ($i=1;$i<=5;$i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block">Comment (optional)</label>
                <textarea name="comment" class="mt-1 p-2 border w-full" rows="4"></textarea>
            </div>
            <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">Submit Review</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
    <p class="mt-4"><a href="customer_orders.php" class="text-blue-600">← Back to Orders</a></p>
</div>
</body>
</html>