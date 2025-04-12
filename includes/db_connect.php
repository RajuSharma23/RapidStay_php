<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'rapid_stay';

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name,4306);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

