<?php
$config = require __DIR__ . '/../../config.php';
$dbConfig = $config['db'];

// Create connection
$conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8mb4");
?>
