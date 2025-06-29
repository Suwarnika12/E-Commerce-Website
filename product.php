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

    $products = [];
    $result = $conn->query("SELECT * FROM products WHERE stock_quantity > 0");
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <style>
        body { 
            font-family: sans-serif; 
            background: #0d1b2a; 
            color: #ececec; 
            margin: 0; 
        }
        .header { 
            background: transparent; 
            overflow: hidden; 
        }
        .header a { 
            float: left; 
            color: #66FCF1; 
            padding: 14px 16px; 
            text-decoration: none; 
            font-size: 25px; 
        }
        .header a.logo { 
            font-size: 25px; 
            font-weight: bold; 
            color: #45A29E; 
        }
        .header-right { 
            float: right; 
        }
        .products-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            gap: 2rem; 
            padding: 2rem; 
        }
        .product-card { 
            background: #fff; 
            color: #21201e; 
            text-align: center; 
            padding: 1rem; 
            border-radius: 1rem; 
        }
        .product-card img { 
            height: 100px; 
        }
        .price { 
            font-size: 18px; 
        }
        button { 
            background: #45A29E; 
            color: white; 
            padding: 0.5rem 1rem; 
            border: none; 
            border-radius: 0.5rem; 
            cursor: pointer; 
        }
        button:hover { 
            background: #2c3e50; 
        }
        h2 { 
            text-align: center; 
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.html" class="logo">Pet Toys Store</a>
        <div class="header-right">
            <a href="index.html">Home</a>
            <a href="product.php">Toys</a>
            <a href="aboutus.html">About Us</a>
            <a href="faq.php">FAQ</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="account.php">Account</a>
                <a href="cart.php">Cart</a>
                <a href="history.php">History</a>
                <a href="tracking.php">Tracking</a>
                <a href="logout.php">Sign Out</a>
            <?php else: ?>
                <a href="register.html">Sign In/Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
    <main class="products-container">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p class="price">Rs <?= number_format($product['price'], 2) ?></p>
                <p><?= substr(htmlspecialchars($product['description']), 0, 50) ?>...</p>
                <form method="POST" action="cart.php">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            </div>
        <?php endforeach; ?>
    </main>
</body>
</html>