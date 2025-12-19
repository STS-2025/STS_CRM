<?php
// api/delete_customer.php

require 'db.php'; 
session_start();
// include '../includes/auth_check.php'; // Uncomment if needed

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id === 0) {
    echo "<script>alert('Invalid customer ID for deletion.');window.location='../customers.php';</script>";
    exit();
}

// Prepare SQL statement for deletion
$stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);

// Execute and redirect
if ($stmt->execute()) {
    echo "<script>alert('Customer account successfully deleted.');window.location='../customers.php';</script>";
} else {
    echo "<script>alert('Error deleting record: " . $stmt->error . "');window.location='../customers.php';</script>";
}

$stmt->close();
$conn->close();
?>