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
        </script><?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: register.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if ($product && $product['stock_quantity'] > 0) {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
    }
    $stmt->close();
}

// Update quantity
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Remove item
if (isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Checkout
if (isset($_POST['checkout'])) {
    $stmt = $conn->prepare("SELECT c.id, c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($cart_items)) {
        $total = 0;
        foreach ($cart_items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $conn->begin_transaction();
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $user_id, $total);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }

        $status_update = "Order placed";
        $stmt = $conn->prepare("INSERT INTO order_tracking (order_id, status_update) VALUES (?, ?)");
        $stmt->bind_param("is", $order_id, $status_update);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: history.php");
    }
    $conn->close();
    exit();
}

// Fetch cart items
$stmt = $conn->prepare("SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image_path FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$total = 0;
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        body { font-family: sans-serif; background: #0d1b2a; color: #ececec; margin: 0; }
        .header { background: transparent; overflow: hidden; }
        .header a { float: left; color: #66FCF1; padding: 14px 16px; text-decoration: none; font-size: 25px; }
        .header a.logo { font-size: 25px; font-weight: bold; color: #45A29E; }
        .header-right { float: right; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 1rem; color: #21201e; }
        h2 { text-align: center; }
        .cart-item { display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid #ccc; }
        .cart-item img { width: 100px; margin-right: 1rem; }
        .cart-item-details { flex-grow: 1; }
        .cart-total { text-align: right; padding: 1rem; font-size: 1.2rem; }
        button { background: #45A29E; color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; }
        button:hover { background: #2c3e50; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.html" class="logo">Pet Toys Store</a>
        <div class="header-right">
            <a href="index.html">Home</a>
            <a href="product.php">Toys</a>
            <a href="aboutus.html">About Us</a>
            <a href="account.php">Account</a>
            <a class="active" href="cart.php">Cart</a>
            <a href="history.php">History</a>
            <a href="tracking.php">Tracking</a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    <div class="container">
        <h2>Shopping Cart</h2>
        <?php if (empty($cart_items)): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
                <?php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; ?>
                <div class="cart-item">
                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <div class="cart-item-details">
                        <h4><?= htmlspecialchars($item['name']) ?></h4>
                        <p>Price: Rs <?= number_format($item['price'], 2) ?></p>
                        <form method="POST">
                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                            <p>Quantity: <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1">
                            <button type="submit" name="update_quantity">Update</button></p>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                            <button type="submit" name="remove_item">Remove</button>
                        </form>
                        <p>Subtotal: Rs <?= number_format($subtotal, 2) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="cart-total">
                <p>Total: Rs <?= number_format($total, 2) ?></p>
                <form method="POST">
                    <button type="submit" name="checkout">Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>