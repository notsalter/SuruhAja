<?php
include 'config.php';
include 'csrf.php';
session_start();

// Only admin can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';

// Handle add service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $image = trim($_POST['image'] ?? '');
        if ($name === '' || $price === '' || !is_numeric($price) || $price < 0) {
            $errors[] = 'Name and valid price are required';
        }
        if (empty($errors)) {
            $stmt = $conn->prepare('INSERT INTO services (name, description, price, image) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssds', $name, $description, $price, $image);
            if ($stmt->execute()) {
                $success = 'Service added';
            } else {
                $errors[] = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle update service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $image = trim($_POST['image'] ?? '');
        if ($id <= 0 || $name === '' || $price === '' || !is_numeric($price) || $price < 0) {
            $errors[] = 'Valid ID, name and price required';
        }
        if (empty($errors)) {
            $stmt = $conn->prepare('UPDATE services SET name = ?, description = ?, price = ?, image = ? WHERE id = ?');
            $stmt->bind_param('ssdsi', $name, $description, $price, $image, $id);
            if ($stmt->execute()) {
                $success = 'Service updated';
            } else {
                $errors[] = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle delete service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM services WHERE id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'Service removed';
            } else {
                $errors[] = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Invalid service ID';
        }
    }
}

// Fetch all services for display
$services = [];
$res = $conn->query('SELECT * FROM services ORDER BY name');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $services[] = $row;
    }
    $res->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin – Manage Services</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Manage Services</h1>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-200 text-red-800 p-2 mb-4">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
        </div>
    <?php elseif ($success): ?>
        <div class="bg-green-200 text-green-800 p-2 mb-4"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <!-- Add Service Form -->
    <form method="POST" class="mb-6 bg-white p-4 rounded shadow">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="action" value="add">
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block">Name</label><input type="text" name="name" required class="w-full border p-1"></div>
            <div><label class="block">Price</label><input type="number" step="0.01" name="price" required class="w-full border p-1"></div>
            <div class="col-span-2"><label class="block">Description</label><textarea name="description" class="w-full border p-1"></textarea></div>
            <div class="col-span-2"><label class="block">Image URL</label><input type="text" name="image" class="w-full border p-1"></div>
        </div>
        <button type="submit" class="mt-4 bg-blue-500 text-white py-2 px-4 rounded">Add Service</button>
    </form>

    <!-- Existing Services Table -->
    <table class="min-w-full bg-white">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Name</th>
                <th class="px-4 py-2">Price</th>
                <th class="px-4 py-2">Image</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($services as $svc): ?>
            <tr class="border-t">
                <td class="px-4 py-2"><?php echo $svc['id']; ?></td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($svc['name']); ?></td>
                <td class="px-4 py-2"><?php echo number_format($svc['price'], 2); ?></td>
                <td class="px-4 py-2"><?php if ($svc['image']): ?><img src="<?php echo htmlspecialchars($svc['image']); ?>" alt="img" class="h-12"><?php endif; ?></td>
                <td class="px-4 py-2">
                    <!-- Edit Form (inline) -->
                    <form method="POST" class="inline-block mr-2">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $svc['id']; ?>">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($svc['name']); ?>" class="border p-1 w-24" required>
                        <input type="number" step="0.01" name="price" value="<?php echo $svc['price']; ?>" class="border p-1 w-20" required>
                        <input type="text" name="image" value="<?php echo htmlspecialchars($svc['image']); ?>" class="border p-1 w-32">
                        <button type="submit" class="bg-yellow-500 text-white py-1 px-2 rounded">Save</button>
                    </form>
                    <!-- Delete Form -->
                    <form method="POST" class="inline-block" onsubmit="return confirm('Delete this service?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $svc['id']; ?>">
                        <button type="submit" class="bg-red-500 text-white py-1 px-2 rounded">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="mt-4"><a href="index.php" class="text-blue-600">← Back to Home</a></p>
</div>
</body>
</html>