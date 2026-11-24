<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
// Fetch basic stats
$stats = [];
// Total orders
$res = $conn->query('SELECT COUNT(*) AS total FROM orders');
$stats['total_orders'] = $res->fetch_assoc()['total'];
// Orders per status
$res = $conn->query('SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status');
while ($row = $res->fetch_assoc()) {
    $stats['status_counts'][$row['status']] = $row['cnt'];
}
// Total revenue (assuming orders have a 'price' column)
$res = $conn->query('SELECT SUM(price) AS revenue FROM orders');
$stats['total_revenue'] = $res->fetch_assoc()['revenue'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - SuruhAja</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="manifest" href="manifest.webmanifest">
<meta name="theme-color" content="#683ce1">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('sw.js').catch(function (err) {
            console.error('Service worker registration failed:', err);
        });
    });
}
</script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Admin Dashboard</h1>
    <div class="bg-white p-4 rounded shadow mb-4">
        <p><strong>Total Orders:</strong> <?php echo $stats['total_orders']; ?></p>
        <p><strong>Total Revenue:</strong> <?php echo number_format($stats['total_revenue'], 2); ?></p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-2">Orders by Status</h2>
        <ul>
            <?php foreach ($stats['status_counts'] as $status => $count): ?>
                <li><?php echo htmlspecialchars($status); ?>: <?php echo $count; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <p class="mt-4"><a href="dashboard.php" class="text-blue-600">← Back to Main Dashboard</a></p>
</div>
</body>
</html>