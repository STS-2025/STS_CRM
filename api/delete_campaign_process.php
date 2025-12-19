<?php
// api/delete_campaign_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Get and sanitize ID from URL
$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($campaign_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid Campaign ID for deletion.';
    header('Location: ../marketing.php');
    exit();
}

// 2. Prepare SQL statement for deletion
$stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
$stmt->bind_param("i", $campaign_id);

// 3. Execute and redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Campaign #{$campaign_id} deleted successfully.";
    } else {
        $_SESSION['message'] = "Warning: Campaign #{$campaign_id} not found to delete.";
    }
    header('Location: ../marketing.php');
} else {
    $_SESSION['message'] = "Database Error: Could not delete campaign. " . $stmt->error;
    header('Location: ../marketing.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>