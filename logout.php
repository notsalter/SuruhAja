<?php
session_start(); // Memulai session
session_destroy(); // Menghapus semua session
header("Location: login.html"); // Redirect ke halaman login
exit();
?>
