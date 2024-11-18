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

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $service = $_POST['service'];
    $order_date = date('Y-m-d'); // Tanggal hari ini
    $order_time = date('H:i:s'); // Jam saat ini
    $instructions = $_POST['instructions'];
    $latitude = $_POST['latitude'] ?? NULL;
    $longitude = $_POST['longitude'] ?? NULL;
    $pickup_address = $_POST['pickup_address'] ?? ''; // Alamat jemput
    $dropoff_address = $_POST['dropoff_address'] ?? ''; // Alamat antar

    // Memasukkan riwayat pesanan ke dalam database dengan status 'Proses'
    $stmt = $conn->prepare("INSERT INTO orders (user_id, service_name, order_date, order_time, instructions, latitude, longitude, address, pickup_address, dropoff_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Proses')");
    $stmt->bind_param("issssddss", $user_id, $service, $order_date, $order_time, $instructions, $latitude, $longitude, $pickup_address, $dropoff_address);

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 400px;
            margin: 20px auto;
            border-radius: 8px;
            width: 50%; /* Gunakan 50% untuk responsif */
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="#">SuruhAja</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#about">Tentang Kami</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php#services">Layanan</a></li>
                        <li class="nav-item"><a class="nav-link" href="order.php">Pesan Sekarang</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <section id="order" class="container my-5">
        <h2 class="text-center">Pesan Sekarang</h2>

        <div id="map"></div>

        <form action="order.php" method="POST">
            <div class="mb-3">
                <label for="service" class="form-label">Pilih Layanan:</label>
                <select id="service" name="service" class="form-select" required onchange="togglePickupAddress()">
                    <option value="Antar Jemput">Antar Jemput</option>
                    <option value="Belanja dan Pembelian">Belanja dan Pembelian</option>
                </select>
            </div>

            <div class="mb-3" id="pickupAddressDiv">
                <label for="pickup_address" class="form-label">Alamat Jemput:</label>
                <input type="text" id="pickup_address" name="pickup_address" class="form-control" placeholder="Isikan alamat jemput" required>
            </div>

            <div class="mb-3" id="dropoffAddressDiv">
                <label for="dropoff_address" class="form-label">Alamat Antar:</label>
                <input type="text" id="dropoff_address" name="dropoff_address" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="order_date" class="form-label">Tanggal Pemesanan:</label>
                <input type="date" id="order_date" name="order_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="mb-3">
                <label for="order_time" class="form-label">Jam Pemesanan:</label>
                <input type="time" id="order_time" name="order_time" class="form-control" value="<?php echo date('H:i'); ?>" required>
            </div>

            <div class="mb-3">
                <label for="instructions" class="form-label">Instruksi atau Pesan Tambahan:</label>
                <textarea id="instructions" name="instructions" class="form-control" rows="4" required></textarea>
            </div>

            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">

            <button type="submit" class="btn btn-primary">Pesan</button>
        </form>
    </section>

    <footer class="bg-light text-center p-3">
        <p>&copy; 2024 SuruhAja. Semua Hak Dilindungi.</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var map = L.map('map').setView([-6.200000, 106.816666], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var marker = L.marker([-6.200000, 106.816666], { draggable: true }).addTo(map);

            function updateAddress(latlng) {
                if (!isNaN(latlng.lat) && !isNaN(latlng.lng)) {
                    document.getElementById('latitude').value = latlng.lat;
                    document.getElementById('longitude').value = latlng.lng;

                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.display_name) {
                                document.getElementById('pickup_address').value = data.display_name; // Update pickup address
                            } else {
                                console.error('Address not found for this location');
                                document.getElementById('pickup_address').value = 'Alamat tidak ditemukan';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('pickup_address').value = 'Gagal mendapatkan alamat';
                        });
                } else {
                    console.error('Invalid latitude or longitude');
                    document.getElementById('pickup_address').value = 'Koordinat tidak valid';
                }
            }

            marker.on('dragend', function (e) {
                var latlng = marker.getLatLng();
                updateAddress(latlng);
                var confirmation = confirm('Apakah Anda yakin ingin menggunakan lokasi ini sebagai alamat jemput?');
                if (confirmation) {
                    document.getElementById('pickup_address').value = 'Alamat berhasil diperbarui';
                } else {
                    document.getElementById('pickup_address').value = ''; // Reset aju jika pengguna tidak mengkonfirmasi
                }
            });
        });

        function togglePickupAddress() {
            var service = document.getElementById("service").value;
            var pickupAddressDiv = document.getElementById("pickupAddressDiv");
            var dropoffAddressDiv = document.getElementById("dropoffAddressDiv");

            // Mengatur tampilan kolom alamat sesuai dengan layanan yang dipilih
            if (service === "Antar Jemput") {
                pickupAddressDiv.style.display = "block";
                dropoffAddressDiv.style.display = "block"; // Menampilkan alamat antar
            } else {
                pickupAddressDiv.style.display = "none"; // Sembunyikan alamat jemput
                dropoffAddressDiv.style.display = "block"; // Tetap tampilkan alamat antar
            }
        }

        // Initialize address fields visibility
        document.addEventListener("DOMContentLoaded", togglePickupAddress);
    </script>
</body>
</html>
