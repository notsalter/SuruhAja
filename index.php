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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#about">Tentang Kami</a></li>
                <li><a href="#services">Layanan</a></li>
                <li><a href="order.php">Pesan Sekarang</a></li>

                <!-- Menampilkan tombol Login atau Logout secara dinamis -->
                <?php if ($is_logged_in): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.html">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section id="home">
        <h1>Selamat Datang di SuruhAja</h1>
        <p>Penyedia layanan asisten pribadi serba guna dengan pemesanan cepat dan transparan.</p>
        <a href="order.php" class="btn">Pesan Sekarang</a>
    </section>

    <section id="about">
        <h2>Tentang Kami</h2>
        <p>SuruhAja hadir untuk memberikan solusi praktis dalam pemesanan layanan asisten pribadi, mulai dari antar jemput barang hingga pembelian kebutuhan harian.</p>
    </section>

    <section id="services">
        <h2>Layanan Kami</h2>
        <div class="service">
            <img src="images/antar-jemput.jpg" alt="Layanan Antar Jemput Barang">
            <h3>Antar Jemput</h3>
            <p>Layanan pengantaran dan penjemputan barang atau orang.</p>
        </div>
        <div class="service">
            <img src="images/belanja.jpg" alt="Layanan Belanja dan Pembelian">
            <h3>Belanja dan Pembelian</h3>
            <p>Bantu belanja kebutuhan harian hingga pemesanan barang tertentu.</p>
        </div>
    </section>

    <section id="contact">
        <h2>Hubungi Kami</h2>
        <form action="send_message.php" method="POST">
            <label for="name">Nama:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="message">Pesan:</label>
            <textarea id="message" name="message" rows="4" required></textarea>

            <button type="submit">Kirim</button>
        </form>
    </section>

    <footer>
        <p>&copy; 2024 SuruhAja. Semua Hak Dilindungi.</p>
        <div class="social-media">
            <a href="#">Instagram</a> | 
            <a href="#">Facebook</a> | 
            <a href="#">Twitter</a>
        </div>
    </footer>

    <script>
        // Notifikasi saat form kontak dikirim
        document.querySelector("form").addEventListener("submit", function (e) {
            e.preventDefault(); // Mencegah halaman refresh
            alert("Pesan kamu telah terkirim. Terima kasih sudah menghubungi kami!");
        });
    </script>
</body>
</html>
