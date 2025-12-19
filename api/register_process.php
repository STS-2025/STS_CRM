<?php
include 'db.php';

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role'];

$sql = "INSERT INTO users (name, email, password, role) VALUES ('$name','$email','$password','$role')";
if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Registration successful! Please login.');window.location='../index.php';</script>";
} else {
    echo "Error: " . $conn->error;
}
?>
