<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Change to your MySQL username
define('DB_PASS', '');         // Change to your MySQL password
define('DB_NAME', 'vending_machines');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
