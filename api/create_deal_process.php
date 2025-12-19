<?php
// api/create_deal_process.php

require 'db.php'; 
session_start();

// Check for required POST data
if (!isset($_POST['deal_name'], $_POST['amount'], $_POST['stage'], $_POST['company_id'], $_POST['owner_id'])) {
    $_SESSION['message'] = 'Error: Missing required data for deal creation.';
    header('Location: ../create_deal.php');
    exit();
}

// 1. Get and sanitize form data
$deal_name = $_POST['deal_name'];
$amount = (float)$_POST['amount'];
$stage = $_POST['stage'];
$company_id = (int)$_POST['company_id'];
$owner_id = (int)$_POST['owner_id'];
$close_date = empty($_POST['close_date']) ? NULL : $_POST['close_date'];

// 2. Prepare SQL statement for insertion
$stmt = $conn->prepare("
    INSERT INTO deals 
    (deal_name, amount, stage, company_id, owner_id, close_date) 
    VALUES (?, ?, ?, ?, ?, ?)
");

// Bind parameters: s(name), d(amount), s(stage), i(company_id), i(owner_id), s(close_date or NULL)
// Use 's' for close_date because it's a string representation of a date or NULL.
$stmt->bind_param("sdsiis", $deal_name, $amount, $stage, $company_id, $owner_id, $close_date);

// 3. Execute and redirect
if ($stmt->execute()) {
    // Optional: Update the associated company's latest_activity
    if ($company_id > 0) {
        $update_activity = $conn->prepare("UPDATE companies SET latest_activity = NOW() WHERE id = ?");
        $update_activity->bind_param("i", $company_id);
        $update_activity->execute();
        $update_activity->close();
    }

    $_SESSION['message'] = 'Deal created and added to the pipeline successfully!';
    header('Location: ../deals.php');
} else {
    $_SESSION['message'] = "Database Error: Could not create deal. " . $stmt->error;
    header('Location: ../create_deal.php');
}

$stmt->close();
$conn->close();
exit();
?>