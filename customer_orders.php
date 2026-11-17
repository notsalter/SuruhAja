<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
// Fetch orders for this customer
$stmt = $conn->prepare('SELECT o.id, o.service_name, o.order_date, o.order_time, o.status, o.driver_id FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - SuruhAja</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">My Orders</h1>
    <?php if (empty($orders)): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <table class="table-auto w-full bg-white rounded shadow">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Service</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Time</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Driver</th>
                    <th class="px-4 py-2">Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="border px-4 py-2"><?php echo $order['id']; ?></td>
                        <td class="border px-4 py-2"><?php echo htmlspecialchars($order['service_name']); ?></td>
                        <td class="border px-4 py-2"><?php echo $order['order_date']; ?></td>
                        <td class="border px-4 py-2"><?php echo $order['order_time']; ?></td>
                        <td class="border px-4 py-2"><?php echo $order['status']; ?></td>
                        <td class="border px-4 py-2">
                            <?php
                            if ($order['driver_id']) {
                                // fetch driver name
                                $dstmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
                                $dstmt->bind_param('i', $order['driver_id']);
                                $dstmt->execute();
                                $dres = $dstmt->get_result();
                                $driver = $dres->fetch_assoc();
                                $dstmt->close();
                                echo htmlspecialchars($driver['username'] ?? 'N/A');
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td class="border px-4 py-2">
                            <?php
                            if ($order['status'] === 'Selesai' && $order['driver_id']) {
                                $rstmt = $conn->prepare('SELECT AVG(rating) AS avg_rating FROM reviews WHERE driver_id = ?');
                                $rstmt->bind_param('i', $order['driver_id']);
                                $rstmt->execute();
                                $rres = $rstmt->get_result();
                                $rating = $rres->fetch_assoc()['avg_rating'];
                                $rstmt->close();
                                if ($rating !== null) {
                                    echo number_format($rating, 2) . ' / 5';
                                } else {
                                    echo 'No reviews';
                                }
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <p class="mt-4"><a href="dashboard.php" class="text-blue-600">← Back to Dashboard</a></p>
</div>
</body>
</html>