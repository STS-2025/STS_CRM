<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "crm";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
