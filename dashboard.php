<?php
// Mulai sesi untuk autentikasi pengguna
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "suruhaja");

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil user_id dari sesi
$user_id = $_SESSION['user_id'];

// Ambil riwayat pesanan dari database
$sql = "SELECT id, service_name, order_date, order_time, status FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Tutup koneksi database
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna - SuruhAja</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#about">Tentang Kami</a></li>
                <li><a href="index.php#services">Layanan</a></li>
                <li><a href="order.php">Pesan Sekarang</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section id="dashboard">
        <h2>Dashboard Pengguna</h2>
        <p>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?>!</strong></p>

        <h3>Riwayat Pesanan</h3>
        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>No</th>
                    <th>Layanan</th>
                    <th>Tanggal</th>
                    <th>Jam Pemesanan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php $no = 1; while ($order = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_time']); ?></td>
                        <td><?php echo htmlspecialchars($order['status'] ?? 'Belum ada status'); ?></td>
                        <td>
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>">Lihat Detail</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Tidak ada riwayat pesanan.</p>
        <?php endif; ?>
    </section>

    <footer>
        <p>&copy; 2024 SuruhAja. Semua Hak Dilindungi.</p>
    </footer>
</body>
</html>
