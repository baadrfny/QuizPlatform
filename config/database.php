<?php
// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$dbname = "user_table";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");
