<?php
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

// Cek apakah parameter 'id' ada di URL dan valid
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID pesanan tidak ditemukan di URL.");
}

$order_id = intval($_GET['id']); // Pastikan ID adalah integer

// Ambil detail pesanan
$stmt = $conn->prepare("SELECT service_name, order_date, order_time, status, address, latitude, longitude, instructions FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
} else {
    die("Pesanan dengan ID $order_id tidak ditemukan.");
}

// Tangani pengiriman ulasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $comment = $_POST['comment'];

    $stmt = $conn->prepare("INSERT INTO reviews (order_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $order_id, $user_id, $rating, $comment);

    if ($stmt->execute()) {
        echo "<script>alert('Ulasan berhasil ditambahkan.');</script>";
    } else {
        echo "Gagal menambahkan ulasan: " . $stmt->error;
    }

    $stmt->close();
}

// Tutup koneksi database
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
        <h2 class="mb-4">Detail Pesanan</h2>
        <p><strong>Layanan:</strong> <?php echo htmlspecialchars($order['service_name']); ?></p>
        <p><strong>Tanggal Pemesanan:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
        <p><strong>Jam Pemesanan:</strong> <?php echo htmlspecialchars($order['order_time']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status'] ?? 'Belum ada status'); ?></p>
        <p><strong>Alamat:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
        <p><strong>Instruksi:</strong> <?php echo htmlspecialchars($order['instructions']); ?></p>

        <div id="map"></div>

        <!-- Form untuk ulasan dan rating -->
        <h3 class="mt-5">Ulasan dan Rating</h3>
        <form action="order_detail.php?id=<?php echo $order_id; ?>" method="POST">
            <div class="mb-3">
                <label for="rating" class="form-label">Rating:</label>
                <select id="rating" name="rating" class="form-select" required>
                    <option value="">Pilih Rating</option>
                    <option value="1">1 - Sangat Buruk</option>
                    <option value="2">2 - Buruk</option>
                    <option value="3">3 - Cukup</option>
                    <option value="4">4 - Baik</option>
                    <option value="5">5 - Sangat Baik</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="comment" class="form-label">Komentar:</label>
                <textarea id="comment" name="comment" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" name="submit_review" class="btn btn-primary">Kirim Ulasan</button>
        </form>
    </section>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Script untuk peta
        document.addEventListener("DOMContentLoaded", function () {
            var lat = <?php echo htmlspecialchars($order['latitude'] ?: '-6.200000'); ?>;
            var lon = <?php echo htmlspecialchars($order['longitude'] ?: '106.816666'); ?>;
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