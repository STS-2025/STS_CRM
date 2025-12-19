<?php
// api/delete_meeting_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Check for meeting ID
if (!isset($_GET['id']) || empty($_GET['id'])) { 
    $_SESSION['message'] = "Error: Meeting ID is missing for deletion.";
    header('Location: ../meetings.php'); 
    exit(); 
}

$meeting_id = (int)$_GET['id'];

// 2. Prepare SQL statement for deletion
$stmt = $conn->prepare("DELETE FROM meetings WHERE id = ?");

// Bind parameter: i (meeting_id)
$stmt->bind_param("i", $meeting_id);

// 3. Execute and redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Meeting #{$meeting_id} deleted successfully.";
    } else {
        $_SESSION['message'] = "Warning: Meeting #{$meeting_id} not found in the database.";
    }
    
    // Redirect to the meetings list page
    header('Location: ../meetings.php'); 

} else {
    $_SESSION['message'] = "Database Error: Could not delete meeting. " . $stmt->error;
    // Redirect back to the meetings page
    header('Location: ../meetings.php'); 
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>