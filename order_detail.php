<?php
// Mulai sesi untuk autentikasi pengguna
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Cek apakah parameter 'id' ada di URL dan valid
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID pesanan tidak ditemukan di URL.");
}

$order_id = intval($_GET['id']); // Pastikan ID adalah integer

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "suruhaja");

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Gunakan prepared statement untuk mencegah SQL injection
$stmt = $conn->prepare("SELECT service_name, order_date, order_time, status, address, latitude, longitude, instructions FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
} else {
    die("Pesanan dengan ID $order_id tidak ditemukan.");
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map {
            height: 400px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section id="order-detail">
        <h2>Detail Pesanan</h2>
        <p><strong>Layanan:</strong> <?php echo htmlspecialchars($order['service_name']); ?></p>
        <p><strong>Tanggal Pemesanan:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
        <p><strong>Jam Pemesanan:</strong> <?php echo htmlspecialchars($order['order_time']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status'] ?? 'Belum ada status'); ?></p>
        <p><strong>Alamat:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
        <p><strong>Instruksi:</strong> <?php echo htmlspecialchars($order['instructions']); ?></p>

        <div id="map"></div>
    </section>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
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
