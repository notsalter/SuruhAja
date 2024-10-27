<?php
session_start(); // Memulai session untuk menyimpan data pengguna

// Konfigurasi koneksi database
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'suruhaja';

$conn = new mysqli($host, $user, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses saat form login disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Query untuk mencari pengguna berdasarkan email
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Jika pengguna ditemukan
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Simpan data pengguna dalam session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];

            // Redirect ke dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Password salah!'); window.location.href = 'login.html';</script>";
        }
    } else {
        echo "<script>alert('Pengguna tidak ditemukan!'); window.location.href = 'login.html';</script>";
    }

    // Tutup koneksi
    $stmt->close();
    $conn->close();
}
?>
