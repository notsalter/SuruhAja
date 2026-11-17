<?php
date_default_timezone_set('Asia/Jakarta');

// Mulai sesi untuk autentikasi pengguna
session_start();
include 'csrf.php'; // CSRF utilities
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

// Ambil user_id dan email dari sesi
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email']; // Pastikan email disimpan dalam sesi saat login

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error = "Invalid CSRF token";
    } else {
    // Validate required fields
    $required = ['service_id','instructions','address','gross_amount'];
    foreach ($required as $field) {
        if (empty($_POST[$field] ?? '')) {
            $error = "Field $field is required";
        }
    }
    if (!isset($error)) {
        $service_id = (int)($_POST['service_id'] ?? 0);
        $order_date = date('Y-m-d');
        $order_time = date('H:i:s');
        $instructions = $_POST['instructions'];
        $latitude = $_POST['latitude'] ?? NULL;
        $longitude = $_POST['longitude'] ?? NULL;
        $address = $_POST['address'];
        $gross_amount = $_POST['gross_amount']; // Ambil harga dari input
        if (!is_numeric($gross_amount) || $gross_amount <= 0) {
            $error = "Invalid amount";
        }
    }
    if (isset($error)) {
        echo json_encode(['error' => $error]);
        exit();
    }

    // Simpan order ke database
    $stmt = $conn->prepare("INSERT INTO orders (user_id, service_id, order_date, order_time, instructions, latitude, longitude, address, price, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Proses', 'pending')");
    $stmt->bind_param("iisssddsd", $user_id, $service_id, $order_date, $order_time, $instructions, $latitude, $longitude, $address, $gross_amount);

    if ($stmt->execute()) {
        // Ambil ID order yang baru saja dibuat
        $order_id = $stmt->insert_id;

        // Konfigurasi Midtrans
        require 'vendor/autoload.php'; // Pastikan path ini sesuai dengan lokasi autoload.php Anda
        \Midtrans\Config::$serverKey = 'SB-Mid-server-Y_CLq_xm4dwRbb0xQquJVGv1'; // Ganti dengan server key Anda
        \Midtrans\Config::$isProduction = false; // Set ke true jika Anda ingin menggunakan mode produksi

        // Buat transaksi
        // Get service name for item details
$svc_name_stmt = $conn->prepare('SELECT name FROM services WHERE id = ?');
$svc_name_stmt->bind_param('i', $service_id);
$svc_name_stmt->execute();
$svc_name_res = $svc_name_stmt->get_result();
$service_name = $svc_name_res->fetch_assoc()['name'] ?? 'Service';
$svc_name_stmt->close();
$transaction_details = [
            'order_id' => $order_id,
            'gross_amount' => (int)$gross_amount, // Gunakan harga yang dikirim dari form
            'redirect_url' => 'dashboard.php' // Ganti dengan URL yang diinginkan
        ];

        $item_details = [
            [
                'id' => 'item1',
                'price' => (int)$gross_amount, // Gunakan harga yang dikirim dari form
                'quantity' => 1,
                'name' => $service_name,
            ],
        ];

        $customer_details = [
            'first_name' => $_SESSION['username'],
            'email' => $email, // Menggunakan email dari sesi
        ];

        $transaction_data = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
        ];

        // Dapatkan URL Snap
        $snapToken = \Midtrans\Snap::getSnapToken($transaction_data);
        
        // Kirim token ke halaman untuk memunculkan pop-up
        echo json_encode(['snapToken' => $snapToken]);
        exit(); // Pastikan exit di sini untuk menghentikan eksekusi lebih lanjut
    } else {
        echo json_encode(['error' => "Gagal membuat pesanan: " . $stmt->error]);
    }

    $stmt->close();
}
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 400px;
            margin: 20px auto;
            border-radius: 8px;
            width: 80%;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
                                    <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                        </div>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <section id="order" class="container my-5">
        <h2 class="text-center">Pesan Sekarang</h2>

        <div id="map"></div>

        <form id="orderForm">\n            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
<?php
// Fetch services for dropdown
$svc_stmt = $conn->prepare('SELECT id, name FROM services ORDER BY name');
$svc_stmt->execute();
$svc_res = $svc_stmt->get_result();
?>
<label for="service">Pilih Layanan:</label>
<select id="service" name="service_id" required>
<?php while ($svc = $svc_res->fetch_assoc()): ?>
    <option value="<?php echo $svc['id']; ?>"><?php echo htmlspecialchars($svc['name']); ?></option>
<?php endwhile; ?>
</select>
<?php $svc_stmt->close(); ?>

            <label for="order_date">Tanggal Pemesanan:</label>
            <input type="date" id="order_date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>

            <label for="order_time">Jam Pemesanan:</label>
            <input type="time" id="order_time" name="order_time" value="<?php echo date('H:i'); ?>" required>

            <label for="instructions">Instruksi atau Pesan Tambahan:</label>
            <textarea id="instructions" name="instructions" rows="4" required></textarea>

            <label for="gross_amount">Harga:</label>
            <input type="number" id="gross_amount" name="gross_amount" value="50000" required>

            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <label for="address">Alamat:</label>
            <input type="text" id="address" name="address" required>

            <button type="submit">Pesan</button>
        </form>
    </section>

    <footer class="bg-light text-center p-3">
        <p>&copy; 2024 SuruhAja. Semua Hak Dilindungi.</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-gWVLWvHL4VWGd-L7"></script>
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
                                let addressField = document.getElementById('address');

                                // Jika alamat tidak diisi manual, update dengan alamat yang didapat dari peta
                                if (!addressField.value) {
                                    addressField.value = data.display_name;
                                }
                            } else {
                                console.error('Address not found for this location');
                                document.getElementById('address').value = 'Alamat tidak ditemukan';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('address').value = 'Gagal mendapatkan alamat';
                        });
                } else {
                    console.error('Invalid latitude or longitude');
                    document.getElementById('address').value = 'Koordinat tidak valid';
                }
            }

            marker.on('dragend', function (e) {
                var latlng = marker.getLatLng();
                updateAddress(latlng);
            });

            // Event listener untuk form submit
            document.getElementById('orderForm').addEventListener('submit', function (e) {
                e.preventDefault(); // Mencegah pengalihan halaman

                // Ambil data dari form
                var formData = new FormData(this);

                // Kirim data ke server menggunakan fetch
                fetch('order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.snapToken) {
                        // Jika token berhasil didapat, panggil pop-up Snap
                        window.snap.pay(data.snapToken);
                    } else {
                        alert(data.error || 'Terjadi kesalahan saat memproses pesanan.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal menghubungi server: ' + error.message);
                });
            });
        });
    </script>
</body>
</html>
