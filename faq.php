<?php
session_start();
require_once 'db_connect.php';

// Fetch FAQs
$faqs = $conn->query("SELECT question, answer FROM faqs")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - Pet Toys Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            background: #0d1b2a;
            color: #ececec;
            line-height: 1.6;
        }

        .header {
            background: transparent;
            overflow: hidden;
            padding: 1rem 2rem;
        }

        .header a {
            float: left;
            color: #66FCF1;
            padding: 14px 16px;
            text-decoration: none;
            font-size: 20px;
        }

        .header a.logo {
            font-size: 24px;
            font-weight: bold;
            color: #45A29E;
        }

        .header-right {
            float: right;
        }

        .header a:hover,
        .header a.active {
            background: #2c3e50;
            border-radius: 0.5rem;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 1rem;
            color: #21201e;
        }

        h2, h3 {
            text-align: center;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
        }

        button {
            background: #45A29E;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
        }

        button:hover {
            background: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 0.5rem;
            text-align: left;
        }

        th {
            background: #45A29E;
            color: white;
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

        .cart-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #ccc;
        }

        .cart-item img {
            width: 100px;
            margin-right: 1rem;
        }

        .cart-item-details {
            flex-grow: 1;
        }

        .cart-total {
            text-align: right;
            padding: 1rem;
            font-size: 1.2rem;
        }

        .faq-item {
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 0.5rem;
        }

        .faq-item h3 {
            text-align: left;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .footer {
            background: #45A29E;
            color: white;
            text-align: center;
            padding: 1rem;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        .footer a {
            color: #2c3e50;
            padding: 0 1rem;
            text-decoration: none;
        }

        .footer a:hover {
            color: #66FCF1;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.html" class="logo">Pet Toys Store</a>
        <div class="header-right">
            <a href="product.php">Toys</a>
            <a href="aboutus.html">About Us</a>
            <a href="faq.php">FAQ</a>
            <a href="account.php">Account</a>
            <a href="cart.php">Cart</a>
            <a href="history.php">History</a>
            <a href="tracking.php">Tracking</a>
            <a href="logout.php">Sign Out</a>
            <a href="register.html">Sign In/Sign Up</a>
            
        </div>
    </div>
    <div class="container">
        <h2>Frequently Asked Questions</h2>
        <?php if (empty($faqs)): ?>
            <p>No FAQs available at the moment.</p>
        <?php else: ?>
            <?php foreach ($faqs as $faq): ?>
                <div class="faq-item">
                    <h3><?= htmlspecialchars($faq['question']) ?></h3>
                    <p><?= htmlspecialchars($faq['answer']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="footer">
        <a href="index.html">Home</a>
        <a href="product.php">Toys</a>
        <a href="offer.html">Offer</a>
        <a href="aboutus.html">About Us</a>
        <a href="faq.php">FAQ</a>
        <p>© 2025 Pet Toys Store. All Rights Reserved.</p>
    </div>
</body>
</html>