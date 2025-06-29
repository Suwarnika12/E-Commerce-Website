<?php
$host = 'localhost'; 
$user = 'root'; 
$pass = ''; 
$db = 'Store'; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8mb4");
?>