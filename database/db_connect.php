<?php
// DB Settings
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "mydb";

// Connect to DB
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>