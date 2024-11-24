<?php
// Mulai sesi untuk autentikasi pengguna
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Cek apakah parameter 'id' ada di URL dan valid
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID pesanan tidak ditemukan di URL.");
}

$order_id = intval($_GET['id']); // Pastikan ID adalah integer

// Koneksi ke database
$conn = new mysqli("localhost", "root", "Seychelles84", "suruhaja");

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil user_id dari sesi
$user_id = $_SESSION['user_id'];

// Gunakan prepared statement untuk memeriksa kepemilikan pesanan
$stmt = $conn->prepare("SELECT service_name, order_date, order_time, status, address, latitude, longitude, instructions FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
} else {
    die("Pesanan dengan ID $order_id tidak ditemukan atau akses ditolak.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - SuruhAja</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 400px;
            width: 100%;
            margin: 20px 0;
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
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <section id="order-detail" class="container my-5">
        <h2 class="text-center mb-4">Detail Pesanan</h2>
        <div class="card order-details mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <p><strong>Layanan:</strong> <?php echo htmlspecialchars($order['service_name']); ?></p>
                <p><strong>Tanggal Pemesanan:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
                <p><strong>Jam Pemesanan:</strong> <?php echo htmlspecialchars($order['order_time']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status'] ?? 'Belum ada status'); ?></p>
                <p><strong>Alamat:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                <p><strong>Instruksi:</strong> <?php echo htmlspecialchars($order['instructions']); ?></p>

                <?php if ($order['status'] !== 'Selesai'): ?>
                    <form action="" method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <button type="submit" name="complete_order" class="btn btn-primary">Selesaikan Pesanan</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div id="map"></div>
    </section>

    <?php
    // Handle order completion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
        $order_id = intval($_POST['order_id']); // Get the order ID from POST

        // Database connection
        $conn = new mysqli("localhost", "root", "Seychelles84", "suruhaja");

        // Check connection
        if ($conn->connect_error) {
            die("Koneksi gagal: " . $conn->connect_error);
        }

        // Proses selesaikan order
        $stmt = $conn->prepare("UPDATE orders SET status = 'Selesai' WHERE id = ?");
        $stmt->bind_param("i", $order_id);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            die("Gagal menyelesaikan pesanan: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();
    }
    ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var lat = <?php echo $order['latitude'] ?: '-6.200000'; ?>;
            var lon = <?php echo $order['longitude'] ?: '106.816666'; ?>;
            var address = "<?php echo addslashes($order['address']); ?>";

            var map = L.map('map').setView([lat, lon], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            L.marker([lat, lon]).addTo(map)
                .bindPopup(address)
                .openPopup();
        });
    </script>
</body>
</html>
