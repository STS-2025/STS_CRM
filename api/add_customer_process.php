<?php
// api/add_customer_process.php

// Use the existing database connection file and start session for user tracking
require 'db.php'; 
session_start();

// Check for required POST data
if (!isset($_POST['name'], $_POST['company'], $_POST['tier'], $_POST['arr_value'])) {
    echo "<script>alert('Error: Missing required fields.');window.location='../add_customer.php';</script>";
    exit();
}

// 1. Get form data (Sanitize/Validate thoroughly in a production environment!)
$name = $_POST['name'];
$company = $_POST['company'];
$tier = $_POST['tier'];
$status = $_POST['status'];
$renewal_date = empty($_POST['renewal_date']) ? NULL : $_POST['renewal_date'];
$arr_value = (float)$_POST['arr_value'];
$created_by = $_SESSION['user_id'] ?? 1; // Default to ID 1 if session is not set

// 2. Prepare SQL statement using prepared statements to prevent SQL injection
// Note: Assuming your 'customers' table has these columns.
$stmt = $conn->prepare("
    INSERT INTO customers 
    (name, company, tier, status, renewal_date, arr_value, created_by, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

// Bind parameters (s=string, d=double/float, i=integer)
$stmt->bind_param("sssssid", $name, $company, $tier, $status, $renewal_date, $arr_value, $created_by);

// 3. Execute and redirect
if ($stmt->execute()) {
    echo "<script>alert('Customer account added successfully!');window.location='../customers.php';</script>";
} else {
    // Basic error handling
    echo "Error inserting record: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>