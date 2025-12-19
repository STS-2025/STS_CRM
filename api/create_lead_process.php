<?php
// api/create_lead_process.php (UPDATED)

ob_start(); 
session_start();

require 'db.php'; 

// 1. Check for required POST data
if (!isset($_POST['name'], $_POST['email'], $_POST['status'], $_POST['source'], $_POST['owner_id'])) {
    $_SESSION['message'] = 'Error: Missing required lead data.';
    header('Location: ../create_lead.php');
    exit();
}

// 2. Get and sanitize form data
$name = trim($_POST['name']);
$company = trim($_POST['company'] ?? '');
$email = trim($_POST['email']);
$phone = trim($_POST['phone'] ?? '');
$status = $_POST['status'];
$source = $_POST['source'];
$owner_id = (int)$_POST['owner_id'];
// New field: Campaign ID (can be 0 or null if not set)
$campaign_id = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
$reminder_date = !empty($_POST['reminder_date']) ? trim($_POST['reminder_date']) : null;
$remarks = trim($_POST['remarks'] ?? '');



// 3. Basic validation
if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $owner_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid Name, Email, or Owner ID provided.';
    header('Location: ../create_lead.php');
    exit();
}

// 4. Prepare SQL statement for insertion
$stmt = $conn->prepare("
    INSERT INTO leads (name, company, email, phone, status, source, owner_id, campaign_id, reminder_date, remarks) 
    VALUES (?, ?, ?, ?, ?, ?, ?,?, ?, ?)
");

// Determine the type string: ssssssii (name, company, email, phone, status, source, owner_id, campaign_id)
// We must use 'i' for campaign_id even if it's null in PHP, as MySQLi expects the type.
if ($campaign_id === null) {
    // If campaign_id is null, we need to bind null separately, making this complex.
    // For simplicity with mysqli, we'll ensure $campaign_id is 0 if null, and let the DB handle the foreign key constraint.
    // However, since we set the column to NULL, we use the following standard approach:
    
    // We bind parameters: ssssssii (s-string, i-integer)
    $stmt->bind_param("ssssssiiss", $name, $company, $email, $phone, $status, $source, $owner_id, $campaign_id, $reminder_date, $remarks);
} else {
    $stmt->bind_param("ssssssiiss", $name, $company, $email, $phone, $status, $source, $owner_id, $campaign_id, $reminder_date, $remarks);
}


// // 5. Execute and redirect
// if ($stmt->execute()) {
//     $new_lead_id = $conn->insert_id;
//     $_SESSION['message'] = "Lead #{$new_lead_id} ('{$name}') created successfully!";
    
//     // Redirect to the leads list page
//     header('Location: ../leads.php'); 

// } else {
//     if ($conn->errno == 1062) {
//          $_SESSION['message'] = "Database Error: This email address is already registered as a lead.";
//     } else {
//         $_SESSION['message'] = "Database Error: Could not create lead. " . $stmt->error;
//     }
//     header('Location: ../create_lead.php');
// }

// $stmt->close();
// $conn->close();
// ob_end_flush(); 
// exit();
// 5. Execute and redirect
// Note: We check $stmt->execute() first. If it returns false, we check the error number.
if ($stmt->execute()) {
    $new_lead_id = $conn->insert_id;
    $_SESSION['message'] = "Lead #{$new_lead_id} ('{$name}') created successfully!";
    
    // Redirect to the leads list page
    header('Location: ../leads.php'); 
    exit(); // <-- CRUCIAL: Exit on success

} else {
    // Check for duplicate entry error (Error Code 1062 for MySQL duplicate key)
    if ($stmt->errno === 1062) {
         $_SESSION['message'] = "Error: This email address is already registered as a lead. Please use a unique email.";
    } else {
        $_SESSION['message'] = "Database Error: Could not create lead. " . $stmt->error;
    }
    // Redirect back to the creation page with the error
    header('Location: ../create_lead.php');
    exit(); // <-- CRUCIAL: Exit on error
}
$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>