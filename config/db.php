<?php
$host = 'localhost';  
$dbname = 'attendance_monitoring_db';
$username = 'root';  
$password = '';  
try {
    // Create a new PDO instance for the database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception for error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set the default fetch mode (optional, but useful for readability)
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    
} catch (PDOException $e) {
    // Catch connection errors and display the message
    echo "Connection failed: " . $e->getMessage();
}
?>
