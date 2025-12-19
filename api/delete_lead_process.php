<?php
// api/delete_lead_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Check for required GET data
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lead_id <= 0) {
    $_SESSION['message'] = 'Error: Missing or invalid Lead ID for deletion.';
    header('Location: ../leads.php');
    exit();
}

// 2. Prepare SQL statement for deletion
$sql = "DELETE FROM leads WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lead_id);


// 3. Execute and redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Lead #{$lead_id} deleted successfully!";
    } else {
        $_SESSION['message'] = "Warning: Lead #{$lead_id} not found or already deleted.";
    }
    
    // Redirect to the main leads list page
    header('Location: ../leads.php'); 

} else {
    // If there's a database constraint (though unlikely for a simple delete), this catches it.
    $_SESSION['message'] = "Database Error: Could not delete lead #{$lead_id}. " . $stmt->error;
    // Redirect back to the leads list
    header('Location: ../leads.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>