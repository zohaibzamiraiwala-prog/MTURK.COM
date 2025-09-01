<?php
$host = 'localhost'; // Change to your actual host if not localhost (e.g., for ClearDB or similar, check your provider)
$dbname = 'dbzcg7a2caf3mu';
$username = 'unkuodtm3putf';
$password = 'htk2glkxl4n4';
 
$conn = new mysqli($host, $username, $password, $dbname);
 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
