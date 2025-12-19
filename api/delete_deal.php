<?php
// api/delete_deal.php

require 'db.php'; 
session_start();

// Ensure the request method is POST and the ID is present
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    $_SESSION['message'] = 'Error: Invalid request to delete deal.';
    header('Location: ../deals.php');
    exit();
}

$deal_id = (int)$_POST['id'];

if ($deal_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid Deal ID.';
    header('Location: ../deals.php');
    exit();
}

// 1. Prepare SQL statement for deletion
$stmt = $conn->prepare("DELETE FROM deals WHERE id = ?");

// Bind parameter: i(deal_id)
$stmt->bind_param("i", $deal_id);

// 2. Execute and redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = 'Deal deleted successfully!';
    } else {
        $_SESSION['message'] = 'Error: Deal not found or already deleted.';
    }
    
    // Always redirect to the deals pipeline after deletion
    header('Location: ../deals.php'); 
} else {
    $_SESSION['message'] = "Database Error: Could not delete deal. " . $stmt->error;
    header('Location: ../deals.php');
}

$stmt->close();
$conn->close();
exit();
?>