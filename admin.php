<script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // Convert sheet to JSON to filter blank rows
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                // Filter out blank rows (rows where all cells are empty, null, or undefined)
                var filteredData = jsonData.filter(row => row.some(filledCell));

                // Heuristic to find the header row by ignoring rows with fewer filled cells than the next row
                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                // Fallback
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                // Convert filtered JSON back to CSV
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); // Create a new sheet from filtered array of arrays
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script>
<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $conn->query("SELECT role FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc()['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

// User Management
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $role, $user_id);
    $stmt->execute();
    $stmt->close();
} elseif (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
$users = $conn->query("SELECT id, name, email, role FROM users")->fetch_all(MYSQLI_ASSOC);

// Product Management
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $image_path = "uploads/" . basename($_FILES["image"]["name"]);
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $image_path)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_path, stock_quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsi", $name, $description, $price, $image_path, $stock_quantity);
        $stmt->execute();
        $stmt->close();
    }
} elseif (isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $image_path = $_POST['current_image'];
    if ($_FILES["image"]["name"]) {
        $image_path = "uploads/" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
    }
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_path = ?, stock_quantity = ? WHERE id = ?");
    $stmt->bind_param("ssdsi", $name, $description, $price, $image_path, $stock_quantity, $product_id);
    $stmt->execute();
    $stmt->close();
} elseif (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();
}
$products = $conn->query("SELECT * FROM products")->fetch_all(MYSQLI_ASSOC);

// Order Management
if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $status_update = trim($_POST['status_update']);
    $conn->begin_transaction();
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();
    if ($status_update) {
        $stmt = $conn->prepare("INSERT INTO order_tracking (order_id, status_update) VALUES (?, ?)");
        $stmt->bind_param("is", $order_id, $status_update);
        $stmt->execute();
        $stmt->close();
    }
    $conn->commit();
}
$orders = $conn->query("SELECT o.id, u.name AS user_name, o.total_amount, o.status FROM orders o JOIN users u ON o.user_id = u.id")->fetch_all(MYSQLI_ASSOC);

// FAQ Management
if (isset($_POST['add_faq'])) {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $stmt = $conn->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
    $stmt->bind_param("ss", $question, $answer);
    $stmt->execute();
    $stmt->close();
} elseif (isset($_POST['update_faq'])) {
    $faq_id = $_POST['faq_id'];
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ? WHERE id = ?");
    $stmt->bind_param("ssi", $question, $answer, $faq_id);
    $stmt->execute();
    $stmt->close();
} elseif (isset($_POST['delete_faq'])) {
    $faq_id = $_POST['faq_id'];
    $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
    $stmt->bind_param("i", $faq_id);
    $stmt->execute();
    $stmt->close();
}
$faqs = $conn->query("SELECT * FROM faqs")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #0d1b2a; color: #ececec; margin: 0; }
        .header { background: transparent; overflow: hidden; }
        .header a { float: left; color: #66FCF1; padding: 14px 16px; text-decoration: none; font-size: 25px; }
        .header a.logo { font-size: 25px; font-weight: bold; color: #45A29E; }
        .header-right { float: right; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 1rem; color: #21201e; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; color: black; }
        th { background: #45A29E; color: white; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; }
        input, select, textarea { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.5rem; }
        button { background: #45A29E; color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; }
        button:hover { background: #2c3e50; }
        .section { margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.html" class="logo">Pet Toys Store</a>
        <div class="header-right">
            <a href="index.html">Home</a>
            <a href="product.php">Toys</a>
            <a href="aboutus.html">About Us</a>
            <a class="active" href="admin.php">Admin</a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    <div class="container">
        <h2>Admin Dashboard</h2>

        <!-- User Management -->
        <div class="section">
            <h3>Manage Users</h3>
            <table>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>">
                        </td>
                        <td><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></td>
                        <td>
                            <select name="role">
                                <option value="customer" <?= $user['role'] == 'customer' ? 'selected' : '' ?>>Customer</option>
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </td>
                        <td>
                            <button type="submit" name="update_user">Update</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="delete_user">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Product Management -->
        <div class="section">
            <h3>Manage Products</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="image">Image:</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="stock_quantity">Stock:</label>
                    <input type="number" name="stock_quantity" min="0" required>
                </div>
                <button type="submit" name="add_product">Add Product</button>
            </form>
            <table>
                <tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['id'] ?></td>
                        <td>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="current_image" value="<?= htmlspecialchars($product['image_path']) ?>">
                                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                        </td>
                        <td><input type="number" name="price" step="0.01" value="<?= number_format($product['price'], 2) ?>"></td>
                        <td><input type="number" name="stock_quantity" value="<?= $product['stock_quantity'] ?>"></td>
                        <td>
                            <input type="file" name="image" accept="image/*">
                            <button type="submit" name="update_product">Update</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" name="delete_product">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Order Management -->
        <div class="section">
            <h3>Manage Orders</h3>
            <table>
                <tr><th>ID</th><th>User</th><th>Total</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['user_name']) ?></td>
                        <td>Rs <?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status">
                                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                        </td>
                        <td>
                            <input type="text" name="status_update" placeholder="Status note">
                            <button type="submit" name="update_order">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- FAQ Management -->
        <div class="section">
            <h3>Manage FAQs</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="question">Question:</label>
                    <input type="text" name="question" required>
                </div>
                <div class="form-group">
                    <label for="answer">Answer:</label>
                    <textarea name="answer" required></textarea>
                </div>
                <button type="submit" name="add_faq">Add FAQ</button>
            </form>
            <table>
                <tr><th>ID</th><th>Question</th><th>Answer</th><th>Actions</th></tr>
                <?php foreach ($faqs as $faq): ?>
                    <tr>
                        <td><?= $faq['id'] ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="faq_id" value="<?= $faq['id'] ?>">
                                <input type="text" name="question" value="<?= htmlspecialchars($faq['question']) ?>">
                        </td>
                        <td><textarea name="answer"><?= htmlspecialchars($faq['answer']) ?></textarea></td>
                        <td>
                            <button type="submit" name="update_faq">Update</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="faq_id" value="<?= $faq['id'] ?>">
                                <button type="submit" name="delete_faq">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>