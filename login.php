<?php 
include 'config.php';  // Menyertakan koneksi database
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Logika autentikasi pengguna
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Mempersiapkan query untuk mencari pengguna
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $username);
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
            $error = "Password salah!";
        }
    } else {
        $error = "Pengguna tidak ditemukan!";
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
            <form method="POST" action="login.php">
                <?php if (isset($error)): ?>
                    <div class="mb-4 text-red-500"><?php echo $error; ?></div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="block text-gray-700">Email</label>
                    <input type="email" name="username" required class="mt-1 p-2 border border-gray-300 rounded w-full" />
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 p-2 border border-gray-300 rounded w-full" />
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Login</button>
            </form>
            <p class="mt-4 text-center">Belum memiliki akun? <a href="register.php" class="text-blue-500 hover:underline">Daftar</a></p>
        </div>
    </div>
</body>
</html>
