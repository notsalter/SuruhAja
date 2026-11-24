<?php
include 'config.php';
include 'csrf.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error = 'Invalid CSRF token';
    } else {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $error = 'Invalid email address';
        } else {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            // Insert into password_resets
            $stmt = $conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $email, $token, $expires);
            if ($stmt->execute()) {
                // Send reset email
                require_once 'notifications.php';
                $subject = "SuruhAja Password Reset";
                $msg = "Your password reset token (valid for 1 hour): $token\nUse the following link to reset your password: https://yourdomain.com/reset_password_confirm.php?token=$token";
                send_status_email($email, $subject, $msg);
                $message = "Password reset email sent. Please check your inbox.";
            } else {
                $error = 'Failed to create reset token';
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="manifest" href="manifest.webmanifest">
<meta name="theme-color" content="#683ce1">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('sw.js').catch(function (err) {
            console.error('Service worker registration failed:', err);
        });
    });
}
</script>
</head>
<body class="bg-gray-100">
<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Reset Password</h2>
        <?php if (isset($error)): ?>
            <div class="mb-4 text-red-500"><?php echo $error; ?></div>
        <?php elseif (isset($message)): ?>
            <div class="mb-4 text-green-600"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="reset_password.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="mb-4">
                <label class="block text-gray-700">Email</label>
                <input type="email" name="email" required class="mt-1 p-2 border border-gray-300 rounded w-full" />
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Send Reset Link</button>
        </form>
        <p class="mt-4 text-center"><a href="login.php" class="text-blue-500 hover:underline">Back to login</a></p>
    </div>
</div>
</body>
</html>