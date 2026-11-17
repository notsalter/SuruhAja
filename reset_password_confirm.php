<?php
include 'config.php';
include 'csrf.php';
session_start();

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Invalid token');
}

// Verify token exists and not expired and not used
$stmt = $conn->prepare('SELECT email, expires_at, used FROM password_resets WHERE token = ?');
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
$reset = $res->fetch_assoc();
$stmt->close();
if (!$reset) {
    die('Invalid or expired token');
}
if ($reset['used']) {
    die('Token already used');
}
if (strtotime($reset['expires_at']) < time()) {
    die('Token expired');
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match';
        } else {
            // Update user password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
            $upd->bind_param('ss', $hashed, $reset['email']);
            if ($upd->execute()) {
                // Mark token used
                $mark = $conn->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
                $mark->bind_param('s', $token);
                $mark->execute();
                $success = 'Password updated successfully. You may now log in.';
            } else {
                $error = 'Failed to update password';
            }
            $upd->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Set New Password</h2>
        <?php if ($error): ?>
            <div class="mb-4 text-red-500"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="mb-4 text-green-600"><?php echo htmlspecialchars($success); ?></div>
            <a href="login.php" class="text-blue-500">Login</a>
        <?php else: ?>
            <form method="POST" action="reset_password_confirm.php?token=<?php echo urlencode($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="mb-4">
                    <label class="block">New Password</label>
                    <input type="password" name="password" required class="mt-1 p-2 border w-full rounded" />
                </div>
                <div class="mb-4">
                    <label class="block">Confirm Password</label>
                    <input type="password" name="confirm" required class="mt-1 p-2 border w-full rounded" />
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Set Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>