<?php
// Database configuration
$db_host = "localhost"; // Change this if your database is hosted elsewhere
$db_username = "root"; // Change this to your database username
$db_password = ""; // Change this to your database password
$db_name = "file_tracker"; // Change this to your database name

// Function to get database connection
function getDBConnection() {
    global $db_host, $db_username, $db_password, $db_name;
    
    // Attempt to connect to the database
    $conn = mysqli_connect($db_host, $db_username, $db_password, $db_name);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    return $conn;
}
?>
