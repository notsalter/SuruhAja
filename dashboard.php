<?php
date_default_timezone_set('Asia/Jakarta');

// Mulai sesi untuk autentikasi pengguna
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Koneksi ke database
$conn = new mysqli("localhost", "root", "Seychelles84", "suruhaja");

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .table thead th {
            background-color: #333;
            color: white;
        }
        .navbar-nav .nav-link, .dropdown-item {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="index.php">SuruhAja</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#about">Tentang Kami</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#services">Layanan</a></li>
                        <li class="nav-item"><a class="nav-link" href="order.php">Pesan Sekarang</a></li>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userMenu">
                                <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <section id="dashboard" class="container my-5">
        <h2>Dashboard Pengguna</h2>
        <p>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?>!</strong></p>

        <h3>Riwayat Pesanan</h3>
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Layanan</th>
                        <th>Tanggal</th>
                        <th>Jam Pemesanan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($order = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                            <td><?php echo htmlspecialchars($order['order_time']); ?></td>
                            <td><?php echo htmlspecialchars($order['status'] ?? 'Belum ada status'); ?></td>
                            <td>
                                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">Lihat Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Tidak ada riwayat pesanan.</p>
        <?php endif; ?>
    </section>

    <footer class="bg-light text-center p-3">
        <p>&copy; 2024 SuruhAja. Semua Hak Dilindungi.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
