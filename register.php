<?php
include 'config.php'; // Sertakan koneksi database
session_start();

$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Mempersiapkan query untuk memasukkan data pengguna
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $password);

    if ($stmt->execute()) {
        $successMessage = "Registrasi berhasil! Anda sekarang bisa login.";
    } else {
        $error = "Terjadi kesalahan saat pendaftaran. Silakan coba lagi.";
    }

    // Tutup koneksi
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-center">Daftar</h2>
            <form method="POST" action="register.php">
                <?php if (isset($error)): ?>
                    <div class="mb-4 text-red-500"><?php echo $error; ?></div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="block text-gray-700">Nama</label>
                    <input type="text" name="name" required class="mt-1 p-2 border border-gray-300 rounded w-full" />
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Email</label>
                    <input type="email" name="email" required class="mt-1 p-2 border border-gray-300 rounded w-full" />
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Nomor Telepon</label>
                    <input type="text" name="phone" required class="mt-1 p-2 border border-gray-300 rounded w-full" />
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 p-2 border border-gray-300 rounded w-full" />
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Daftar</button>
            </form>
            <p class="mt-4 text-center">Sudah punya akun? <a href="login.php" class="text-blue-500 hover:underline">Login di sini</a></p>
        </div>
    </div>

    <!-- Popup Success Message -->
    <?php if (!empty($successMessage)): ?>
    <div id="success-popup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-4 rounded-lg shadow-lg">
            <h3 class="text-lg font-bold"><?php echo $successMessage; ?></h3>
            <button onclick="document.getElementById('success-popup').style.display='none'" class="mt-4 bg-blue-500 text-white py-2 px-4 rounded">OK</button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Function to automatically hide the popup after a few seconds (optional)
        <?php if (!empty($successMessage)): ?>
            setTimeout(function() {
                document.getElementById('success-popup').style.display = 'none';
            }, 5000); // Hide after 5 seconds
        <?php endif; ?>
    </script>
</body>
</html>
