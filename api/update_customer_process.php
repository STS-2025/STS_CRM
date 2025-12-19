<?php
// api/update_customer_process.php

require 'db.php'; 
session_start();
// include '../includes/auth_check.php'; // Uncomment if needed

// Check for required POST data
if (!isset($_POST['customer_id'], $_POST['name'], $_POST['company'], $_POST['tier'], $_POST['arr_value'])) {
    echo "<script>alert('Error: Missing required data for update.');window.location='../customers.php';</script>";
    exit();
}

$id = (int)$_POST['customer_id'];
$name = $_POST['name'];
$company = $_POST['company'];
$tier = $_POST['tier'];
$status = $_POST['status'];
$renewal_date = empty($_POST['renewal_date']) ? NULL : $_POST['renewal_date'];
$arr_value = (float)$_POST['arr_value'];

// Prepare SQL statement for updating data
$stmt = $conn->prepare("
    UPDATE customers 
    SET name = ?, company = ?, tier = ?, status = ?, renewal_date = ?, arr_value = ? 
    WHERE id = ?
");

// Bind parameters (s=string, d=double/float, i=integer)
$stmt->bind_param("sssssdi", $name, $company, $tier, $status, $renewal_date, $arr_value, $id);

// Execute and redirect
if ($stmt->execute()) {
    echo "<script>alert('Customer account updated successfully!');window.location='../customers.php';</script>";
} else {
    echo "Error updating record: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>