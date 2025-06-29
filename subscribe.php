<?php
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'Store';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get form data
$name = $conn->real_escape_string($_POST['name']);
$email = $conn->real_escape_string($_POST['email']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Insert into database
$sql = "INSERT INTO subscribers (name, email) VALUES ('$name', '$email')";

if ($conn->query($sql) === TRUE) {
    // Store in a simple text file for the update checker
    file_put_contents('last_update.txt', time());
    
    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$conn->close();
?>