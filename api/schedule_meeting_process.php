<?php
// api/schedule_meeting_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Check for required POST data
if (!isset($_POST['subject'], $_POST['contact_id'], $_POST['date'], $_POST['time'], $_POST['type'], $_POST['user_id'])) {
    $_SESSION['message'] = 'Error: Missing required data for scheduling meeting.';
    header('Location: ../schedule_meeting.php');
    exit();
}

// 2. Get and sanitize form data
$subject = trim($_POST['subject']);
$contact_id = (int)$_POST['contact_id'];
$date = $_POST['date'];
$time = $_POST['time'];
$type = $_POST['type'];
$user_id = (int)$_POST['user_id']; 

// Combine date and time into the required DATETIME format
$date_time = $date . ' ' . $time . ':00'; // Append seconds for proper DATETIME format

// 3. Basic validation
if (empty($subject) || $contact_id <= 0 || $user_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid subject, contact, or organizer.';
    header('Location: ../schedule_meeting.php');
    exit();
}

// Set initial status
$status = 'Scheduled'; 

// 4. Prepare SQL statement for insertion
$stmt = $conn->prepare("
    INSERT INTO meetings (subject, date_time, type, status, contact_id, user_id) 
    VALUES (?, ?, ?, ?, ?, ?)
");

// Bind parameters: s (subject), s (date_time), s (type), s (status), i (contact_id), i (user_id)
$stmt->bind_param("ssssii", $subject, $date_time, $type, $status, $contact_id, $user_id);


// 5. Execute and redirect
if ($stmt->execute()) {
    $_SESSION['message'] = "Meeting '" . htmlspecialchars($subject) . "' scheduled successfully!";
    // Redirect to the meetings list page
    header('Location: ../meetings.php'); 
} else {
    $_SESSION['message'] = "Database Error: Could not schedule meeting. " . $stmt->error;
    // Redirect back to the form
    header('Location: ../schedule_meeting.php'); 
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>