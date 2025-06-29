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

        if (!isset($_SESSION['user_id'])) {
            header("Location: register.html");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT o.id, o.status, ot.status_update FROM orders o LEFT JOIN order_tracking ot ON o.id = ot.order_id WHERE o.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tracking = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();
        ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking</title>
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

        .container { 
            max-width: 800px; 
            margin: 2rem auto; 
            padding: 2rem; 
            background: #fff; 
            border-radius: 1rem; 
            color: #21201e; 
        }

        h2 { 
            text-align: center; 
        }

        .order { 
            margin-bottom: 1rem; 
            padding: 1rem; 
            background: #66FCF1; 
            border-radius: 0.5rem; 
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
            <a href="account.php">Account</a>
            <a href="cart.php">Cart</a>
            <a href="history.php">History</a>
            <a class="active" href="tracking.php">Tracking</a>
            <a href="logout.php">Sign Out</a>
        </div>
    </div>
    <div class="container">
        <h2>Order Tracking</h2>
        <?php if (empty($tracking)): ?>
            <p>No tracking information.</p>
        <?php else: ?>
            <?php $current_order_id = null; foreach ($tracking as $track): ?>
                <?php if ($current_order_id !== $track['id']): ?>
                    <?php if ($current_order_id !== null): ?></div><?php endif; ?>
                    <?php $current_order_id = $track['id']; ?>
                    <div class="order">
                        <h3>Order #<?= $track['id'] ?> - <?= ucfirst($track['status']) ?></h3>
                <?php endif; ?>
                <?php if ($track['status_update']): ?>
                    <p><?= htmlspecialchars($track['status_update']) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($current_order_id !== null): ?></div><?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>