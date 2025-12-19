<?php
// $host = "localhost";
// $user = "root";
// $pass = "";
// $db = "crm";

$host = "192.168.29.26";
$user = "mariselvam";
$pass = "mari@123";
$db = "crm";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
