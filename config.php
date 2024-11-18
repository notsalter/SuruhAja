<?php
// config.php
// Mengatur koneksi database
$host = 'localhost';
$user = 'root';
$password = 'Seychelles84';
$dbname = 'suruhaja';

// Membuat koneksi
$conn = new mysqli($host, $user, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}