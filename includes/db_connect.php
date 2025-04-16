<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'rapid_stay';

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if port 3306 is in use
// exec('netstat -aon | findstr 3306', $output, $return_var);
// if ($return_var === 0) {
//     echo "Port 3306 is in use.";
// } else {
//     echo "Port 3306 is not in use.";
// }
// ?>

