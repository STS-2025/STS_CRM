<?php
include 'db.php';
session_start();

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$status = $_POST['status'];
$created_by = $_SESSION['user_id'];

$sql = "INSERT INTO leads (name,email,phone,status,created_by) VALUES ('$name','$email','$phone','$status','$created_by')";
if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Lead added successfully!');window.location='../leads.php';</script>";
} else {
    echo "Error: " . $conn->error;
}
?>
