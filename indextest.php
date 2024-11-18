<?php
// Mulai sesi
session_start();

// Periksa apakah pengguna sudah login
$is_logged_in = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SuruhAja adalah layanan asisten pribadi serba guna yang memudahkan pemesanan jasa antar jemput dan belanja dengan cepat dan transparan.">
    <meta name="keywords" content="asisten pribadi, jasa antar jemput, jasa belanja, layanan cepat, SuruhAja">
    <meta name="author" content="SuruhAja">
    <title>SuruhAja - Asisten Pribadi Serba Guna</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    
</head>

<body>
    <!-- Header dan Navigasi -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="#">SuruhAja</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#about">Tentang Kami</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#services">Layanan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="order.php">Pesan Sekarang</a>
                        </li>
                        <?php if ($is_logged_in): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Login</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Home Section -->
    <section id="home" class="text-center p-5">
        <div class="container">
            <h1 class="display-4">Selamat Datang di SuruhAja</h1>
            <p class="lead">Penyedia layanan asisten pribadi serba guna dengan pemesanan cepat dan transparan.</p>
            <a href="order.php" class="btn btn-primary btn-lg">Pesan Sekarang</a>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="p-5 bg-light">
        <div class="container text-center">
            <h2>Tentang Kami</h2>
            <p>SuruhAja hadir untuk memberikan solusi praktis dalam pemesanan layanan asisten pribadi, mulai dari antar jemput barang hingga pembelian kebutuhan harian.</p>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="p-5">
        <div class="container text-center">
            <h2>Layanan Kami</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <img src="img/antar-jemput.png" class="card-img-top" alt="Layanan Antar Jemput Barang">
                        <div class="card-body">
                            <h5 class="card-title">Antar Jemput</h5>
                            <p class="card-text">Layanan pengantaran dan penjemputan barang atau orang.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <img src="img/Belanja dan Pembelian.png" class="card-img-top" alt="Layanan Belanja dan Pembelian">
                        <div class="card-body">
                            <h5 class="card-title">Belanja dan Pembelian</h5>
                            <p class="card-text">Bantu belanja kebutuhan harian hingga pemesanan barang tertentu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-light text-center p-3">
        <p>&copy; 2024 SuruhAja. Semua Hak Dilindungi.</p>
        <div class="social-media">
            <a href="#" class="me-2">Instagram</a> |
            <a href="#" class="me-2">Facebook</a> |
            <a href="#">Twitter</a>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
