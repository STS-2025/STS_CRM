<?php
// api/delete_company.php

require 'db.php'; 
session_start();

$company_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($company_id === 0) {
    $_SESSION['message'] = 'Error: Invalid company ID for deletion.';
    header('Location: ../companies.php');
    exit();
}

// Prepare SQL statement for deletion
// NOTE: Ensure your database handles cascading deletes if you link contacts/deals to companies, 
// or delete dependent records here first.
$stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
$stmt->bind_param("i", $company_id);

// Execute and redirect
if ($stmt->execute()) {
    $_SESSION['message'] = 'Company successfully deleted.';
    header('Location: ../companies.php');
} else {
    $_SESSION['message'] = 'Error deleting record: ' . $stmt->error;
    header('Location: ../companies.php');
}

$stmt->close();
$conn->close();
exit();
?>