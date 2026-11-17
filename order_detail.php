<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID pesanan tidak ditemukan di URL.');
}
$order_id = (int)$_GET['id'];

// Fetch order belonging to this user
$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
if (!$order) {
    die('Pesanan tidak ditemukan atau akses ditolak.');
}

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Rating must be between 1 and 5';
        }
        if (empty($errors)) {
            $stmt = $conn->prepare('INSERT INTO reviews (order_id, driver_id, rating, comment) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiis', $order_id, $order['driver_id'], $rating, $comment);
            if ($stmt->execute()) {
                $success = 'Terima kasih atas ulasan Anda';
            } else {
                $errors[] = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
// Check if already reviewed
$already_reviewed = false;
if ($order['driver_id']) {
    $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM reviews WHERE order_id = ?');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $already_reviewed = $row['cnt'] > 0;
    $stmt->close();
}
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

        <!-- Form untuk ulasan hanya jika tidak ada ulasan sebelumnya -->
        <h3 class="mt-5">Ulasan Pengguna</h3>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-200 text-red-800 p-2 mb-4">
                <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="bg-green-200 text-green-800 p-2 mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <h3 class="mt-5">Ulasan Pengguna</h3>
        <?php if ($already_reviewed): ?>
            <p class="text-green-600">Anda telah memberi ulasan untuk pesanan ini.</p>
        <?php else: ?>
            <form action="order_detail.php?id=<?php echo $order_id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="mb-3">
                    <label for="rating" class="form-label">Rating:</label>
                    <select id="rating" name="rating" class="form-select" required>
                        <option value="">Pilih Rating</option>
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> - <?php echo ['Sangat Buruk','Buruk','Cukup','Baik','Sangat Baik'][$i-1]; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="comment" class="form-label">Komentar:</label>
                    <textarea id="comment" name="comment" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary">Kirim Ulasan</button>
            </form>
        <?php endif; ?>
    </section>

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
