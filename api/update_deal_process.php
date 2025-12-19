<?php
// api/update_deal_process.php

// *** ADD THIS LINE to prevent "Headers already sent" errors ***
ob_start(); 

require 'db.php'; 
session_start();

// Check for required POST data
if (!isset($_POST['deal_id'], $_POST['deal_name'], $_POST['amount'], $_POST['stage'], $_POST['company_id'], $_POST['owner_id'])) {
    $_SESSION['message'] = 'Error: Missing required data for deal update.';
    header('Location: ../deals.php');
    exit();
}

// 1. Get and sanitize form data
$deal_id = (int)$_POST['deal_id'];
$deal_name = $_POST['deal_name'];
$amount = (float)$_POST['amount'];
$stage = $_POST['stage'];
$company_id = (int)$_POST['company_id'];
$owner_id = (int)$_POST['owner_id'];
// Set close_date to NULL if empty, otherwise use the date string
$close_date = empty($_POST['close_date']) ? NULL : $_POST['close_date'];

// Basic validation
if ($deal_id <= 0 || empty($deal_name) || $company_id <= 0 || $owner_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid Deal ID or missing required fields.';
    header('Location: ../edit_deal.php?id=' . $deal_id);
    exit();
}

// 2. Prepare SQL statement for updating
$stmt = $conn->prepare("
    UPDATE deals 
    SET deal_name = ?, amount = ?, stage = ?, company_id = ?, owner_id = ?, close_date = ?, updated_at = NOW() 
    WHERE id = ?
");

// Bind parameters: s (name), d (amount), s (stage), i (company_id), i (owner_id), s (close_date), i (deal_id)
$stmt->bind_param("sdsiisi", $deal_name, $amount, $stage, $company_id, $owner_id, $close_date, $deal_id);


// 3. Execute and redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Optional: Update the associated company's latest_activity
        $update_activity = $conn->prepare("UPDATE companies SET latest_activity = NOW() WHERE id = ?");
        $update_activity->bind_param("i", $company_id);
        $update_activity->execute();
        $update_activity->close();

        $_SESSION['message'] = 'Deal updated successfully!';
    } else {
        $_SESSION['message'] = 'No changes made to the deal.';
    }
    
    // Redirect to the deals list
    header('Location: ../deals.php'); 

} else {
    $_SESSION['message'] = "Database Error: Could not update deal. " . $stmt->error;
    header('Location: ../edit_deal.php?id=' . $deal_id);
}

$stmt->close();
$conn->close();

// *** ADD THIS LINE to finalize output buffering ***
ob_end_flush(); 
exit();
?>