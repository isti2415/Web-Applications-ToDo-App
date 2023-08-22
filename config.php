<?php
// Database configuration parameters
$servername = "localhost";  // Host name or IP address
$username = "root";          // MySQL username
$password = ""; // MySQL password
$dbname = "todo";      // Database name

// Create a new connection to the MySQL database
$link = mysqli_connect($servername, $username, $password, $dbname);

// Check the connection
if($link === false){
    die("ERROR: Could not connect to the database. " . mysqli_connect_error());
}
?>
