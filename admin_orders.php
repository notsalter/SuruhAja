<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
// Fetch all orders
$stmt = $conn->prepare('SELECT o.id, o.service_name, o.order_date, o.order_time, o.status, o.payment_status, u.username AS customer, d.username AS driver FROM orders o LEFT JOIN users u ON o.user_id = u.id LEFT JOIN users d ON o.driver_id = d.id ORDER BY o.order_date DESC');
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
    <title>Admin Orders - SuruhAja</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">All Orders (Admin)</h1>
    <?php if (empty($orders)): ?>
        <p>No orders found.</p>
    <?php else: ?>
        <table class="table-auto w-full bg-white rounded shadow">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Service</th>
                    <th class="px-4 py-2">Customer</th>
                    <th class="px-4 py-2">Driver</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Time</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Payment</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="border px-4 py-2"><?php echo $order['id']; ?></td>
                        <td class="border px-4 py-2"><?php echo htmlspecialchars($order['service_name']); ?></td>
                        <td class="border px-4 py-2"><?php echo htmlspecialchars($order['customer']); ?></td>
                        <td class="border px-4 py-2"><?php echo $order['driver'] ?? '—'; ?></td>
                        <td class="border px-4 py-2"><?php echo $order['order_date']; ?></td>
                        <td class="border px-4 py-2"><?php echo $order['order_time']; ?></td>
                        <td class="border px-4 py-2" id="status-<?php echo $order['id']; ?>"><?php echo $order['status']; ?></td>
                        <td class="border px-4 py-2"><?php echo $order['payment_status']; ?></td>
                        <td class="border px-4 py-2">
                            <form method="POST" action="admin_update_status.php" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="Proses" <?php if ($order['status'] === 'Proses') echo 'selected'; ?>>Proses</option>
                                    <option value="Diterima" <?php if ($order['status'] === 'Diterima') echo 'selected'; ?>>Diterima</option>
                                    <option value="Dalam Perjalanan" <?php if ($order['status'] === 'Dalam Perjalanan') echo 'selected'; ?>>Dalam Perjalanan</option>
                                    <option value="Selesai" <?php if ($order['status'] === 'Selesai') echo 'selected'; ?>>Selesai</option>
                                    <option value="Batal" <?php if ($order['status'] === 'Batal') echo 'selected'; ?>>Batal</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <p class="mt-4"><a href="dashboard.php" class="text-blue-600">← Back to Main Dashboard</a></p>
</div>
</body>
</html>