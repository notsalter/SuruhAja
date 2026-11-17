<?php
include 'config.php';
include 'csrf.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'driver') {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $vehicle = trim($_POST['vehicle'] ?? '');
        $license = trim($_POST['license_plate'] ?? '');
        $stmt = $conn->prepare('UPDATE users SET vehicle = ?, license_plate = ? WHERE id = ?');
        $stmt->bind_param('ssi', $vehicle, $license, $user_id);
        if ($stmt->execute()) {
            $success = 'Profile updated';
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    }
}
// Load current profile
$stmt = $conn->prepare('SELECT vehicle, license_plate FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();
$stmt->close();
// Fetch average rating for this driver
$avg_stmt = $conn->prepare('SELECT AVG(rating) AS avg_rating FROM reviews WHERE driver_id = ?');
$avg_stmt->bind_param('i', $user_id);
$avg_stmt->execute();
$avg_res = $avg_stmt->get_result();
$avg_row = $avg_res->fetch_assoc();
$average_rating = $avg_row['avg_rating'];
$avg_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Profile - SuruhAja</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Driver Profile</h1>\n    <?php if ($average_rating !== null): ?>\n        <p class="mt-2">Average Rating: <?php echo number_format($average_rating, 2); ?> / 5</p>\n    <?php else: ?>\n        <p class="mt-2">No reviews yet.</p>\n    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-200 text-red-800 p-2 mb-4">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
        </div>
    <?php elseif ($success): ?>
        <div class="bg-green-200 text-green-800 p-2 mb-4"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST" class="bg-white p-4 rounded shadow">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="mb-4">
            <label class="block">Vehicle</label>
            <input type="text" name="vehicle" value="<?php echo htmlspecialchars($profile['vehicle'] ?? ''); ?>" class="w-full border p-2" />
        </div>
        <div class="mb-4">
            <label class="block">License Plate</label>
            <input type="text" name="license_plate" value="<?php echo htmlspecialchars($profile['license_plate'] ?? ''); ?>" class="w-full border p-2" />
        </div>
        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">Save</button>
    </form>
    <p class="mt-4"><a href="driver_dashboard.php" class="text-blue-600">← Back to Dashboard</a></p>
</div>
</body>
</html>