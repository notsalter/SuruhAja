<?php
// Aktifkan laporan error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $service = $_POST['service'];
    $order_date = $_POST['order_date'];
    $order_time = !empty($_POST['order_time']) ? $_POST['order_time'] : date('H:i:s');
    $instructions = $_POST['instructions'];
    $latitude = $_POST['latitude'] ?? NULL;
    $longitude = $_POST['longitude'] ?? NULL;
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO orders (user_id, service_name, order_date, order_time, instructions, latitude, longitude, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssdds", $user_id, $service, $order_date, $order_time, $instructions, $latitude, $longitude, $address);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Gagal membuat pesanan: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Sekarang - SuruhAja</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map {
            height: 400px;
            margin-top: 20px;
            border-radius: 8px;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            margin-top: 20px;
            width: 100%;
        }
    </style>
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

    <section id="order">
        <h2>Pesan Sekarang</h2>

        <div id="map"></div>

        <form action="order.php" method="POST">
            <label for="service">Pilih Layanan:</label>
            <select id="service" name="service" required>
                <option value="Antar Jemput">Antar Jemput</option>
                <option value="Belanja dan Pembelian">Belanja dan Pembelian</option>
            </select>

            <label for="order_date">Tanggal Pemesanan:</label>
            <input type="date" id="order_date" name="order_date" required>

            <label for="order_time">Jam Pemesanan:</label>
            <input type="time" id="order_time" name="order_time">

            <label for="instructions">Instruksi atau Pesan Tambahan:</label>
            <textarea id="instructions" name="instructions" rows="4" required></textarea>

            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <label for="address">Alamat:</label>
            <input type="text" id="address" name="address" readonly required>

            <button type="submit">Pesan</button>
        </form>
    </section>

    <footer>
        <p>&copy; 2024 SuruhAja. Semua Hak Dilindungi.</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var map = L.map('map').setView([-6.200000, 106.816666], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var marker = L.marker([-6.200000, 106.816666], { draggable: true }).addTo(map);

            marker.on('dragend', function (e) {
                var latlng = marker.getLatLng();
                document.getElementById('latitude').value = latlng.lat;
                document.getElementById('longitude').value = latlng.lng;

                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latlng.lat}&lon=${latlng.lng}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('address').value = data.display_name;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Gagal mendapatkan alamat, coba lagi.');
                    });
            });
        });
    </script>
</body>
</html>
