<?php
// api/add_customer_process.php

require 'db.php'; // Database connection file
session_start();
// include '../includes/auth_check.php'; // Ensure user is logged in

// 1. Get form data (You should sanitize and validate this input!)
$name = $_POST['name'];
$company = $_POST['company'];
$tier = $_POST['tier'];
$renewal_date = $_POST['renewal_date'];
$arr_value = (float)$_POST['arr_value'];
$created_by = $_SESSION['user_id']; 

// 2. Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare("INSERT INTO customers (name, company, tier, renewal_date, arr_value, created_by) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiid", $name, $company, $tier, $renewal_date, $arr_value, $created_by);

// 3. Execute and redirect
if ($stmt->execute()) {
    echo "<script>alert('Customer account added successfully!');window.location='../customers.php';</script>";
} else {
    // Basic error handling
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>