<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'driver') {
    header('Location: login.php');
    exit();
}
$driver_id = $_SESSION['user_id'];

// Fetch orders assigned to this driver or unassigned
$sql = "SELECT id, service_name, order_date, order_time, status FROM orders WHERE driver_id = ? OR driver_id IS NULL ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang=\"id\">
<head>
    <meta charset=\"UTF-8\">
    <title>Driver Dashboard - SuruhAja</title>
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css\">
    <link rel=\"stylesheet\" href=\"styles.css\">
</head>
<body>
    <header>
        <nav class=\"navbar navbar-expand-lg navbar-light bg-light\">
            <div class=\"container\">
                <a class=\"navbar-brand\" href=\"#\">SuruhAja Driver</a>
                <div class=\"collapse navbar-collapse\" id=\"navbarNav\">
                    <ul class=\"navbar-nav ms-auto\">
                        <li class=\"nav-item\"><a class=\"nav-link\" href=\"driver_dashboard.php\">Orders</a></li>
                        <li class=\"nav-item\"><a class=\"nav-link\" href=\"logout.php\">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class=\"container my-5\">
        <h2>Orders for Driver</h2>
        <?php if ($result->num_rows > 0): ?>
            <table class=\"table table-striped\">
                <thead><tr><th>ID</th><th>Layanan</th><th>Tanggal</th><th>Jam</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                        <td><?php echo $order['order_date']; ?></td>
                        <td><?php echo $order['order_time']; ?></td>
                        <td><?php echo $order['status']; ?></td>
                        <td>
                        <?php if (is_null($order['driver_id'])): ?>
                                                    <form method="POST" action="accept_order.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <button class="btn btn-success btn-sm" type="submit">Accept</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="driver_update_status.php" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="Diterima" <?php if ($order['status'] === 'Diterima') echo 'selected'; ?>>Diterima</option>
                                                            <option value="Dalam Perjalanan" <?php if ($order['status'] === 'Dalam Perjalanan') echo 'selected'; ?>>Dalam Perjalanan</option>
                                                            <option value="Selesai" <?php if ($order['status'] === 'Selesai') echo 'selected'; ?>>Selesai</option>
                                                        </select>
                                                    </form>
                                                <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders available.</p>
        <?php endif; ?>
    </div>
</body>
</html>