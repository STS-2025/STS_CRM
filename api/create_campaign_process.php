<?php
// api/create_campaign_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Check for required POST data
if (!isset($_POST['name'], $_POST['goal'], $_POST['start_date'], $_POST['end_date'], $_POST['budget'], $_POST['status'])) {
    $_SESSION['message'] = 'Error: Missing required data for campaign creation.';
    header('Location: ../create_campaign.php');
    exit();
}

// 2. Get and sanitize form data
$name = trim($_POST['name']);
$goal = trim($_POST['goal']);
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$budget = (float)$_POST['budget'];
$status = $_POST['status'];

// 3. Basic validation
if (empty($name) || empty($goal) || $budget <= 0) {
    $_SESSION['message'] = 'Error: Campaign name, goal, or budget is invalid.';
    header('Location: ../create_campaign.php');
    exit();
}

// 4. Prepare SQL statement for insertion
$stmt = $conn->prepare("
    INSERT INTO campaigns (name, goal, start_date, end_date, budget, status) 
    VALUES (?, ?, ?, ?, ?, ?)
");

// Bind parameters: s (name), s (goal), s (start_date), s (end_date), d (budget), s (status)
$stmt->bind_param("ssssds", $name, $goal, $start_date, $end_date, $budget, $status);


// 5. Execute and redirect
if ($stmt->execute()) {
    $new_campaign_id = $conn->insert_id;
    $_SESSION['message'] = "Campaign #{$new_campaign_id} ('{$name}') created successfully!";
    
    // Redirect to the campaigns list page
    header('Location: ../marketing.php'); 

} else {
    $_SESSION['message'] = "Database Error: Could not create campaign. " . $stmt->error;
    // Redirect back to the creation page with the error
    header('Location: ../create_campaign.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>