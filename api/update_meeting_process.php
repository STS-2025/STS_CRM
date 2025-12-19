<?php
// api/update_meeting_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Check for required POST data
if (!isset($_POST['id'], $_POST['subject'], $_POST['contact_id'], $_POST['date'], $_POST['time'], $_POST['type'], $_POST['status'], $_POST['user_id'])) {
    $_SESSION['message'] = 'Error: Missing required data for meeting update.';
    // Attempt to redirect back to the edit page if ID is known
    $redirect_id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    header('Location: ../edit_meeting.php?id=' . $redirect_id);
    exit();
}

// 2. Get and sanitize form data
$meeting_id = (int)$_POST['id'];
$subject = trim($_POST['subject']);
$contact_id = (int)$_POST['contact_id'];
$date = $_POST['date'];
$time = $_POST['time'];
$type = $_POST['type'];
$status = $_POST['status'];
$user_id = (int)$_POST['user_id']; 

// Combine date and time into the required DATETIME format
$date_time = $date . ' ' . $time . ':00'; 

// 3. Basic validation
if ($meeting_id <= 0 || empty($subject) || $contact_id <= 0 || $user_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid ID or missing required fields.';
    header('Location: ../edit_meeting.php?id=' . $meeting_id);
    exit();
}


// 4. Prepare SQL statement for updating
$stmt = $conn->prepare("
    UPDATE meetings 
    SET subject = ?, date_time = ?, type = ?, status = ?, contact_id = ?, user_id = ?, updated_at = NOW()
    WHERE id = ?
");

// Bind parameters: s (subject), s (date_time), s (type), s (status), i (contact_id), i (user_id), i (meeting_id)
$stmt->bind_param("ssssiii", $subject, $date_time, $type, $status, $contact_id, $user_id, $meeting_id);


// 5. Execute and redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Meeting #{$meeting_id} updated successfully!";
    } else {
        $_SESSION['message'] = "No changes made to Meeting #{$meeting_id}.";
    }
    
    // Redirect to the meetings list page
    header('Location: ../meetings.php'); 

} else {
    $_SESSION['message'] = "Database Error: Could not update meeting. " . $stmt->error;
    // Redirect back to the edit page with the error
    header('Location: ../edit_meeting.php?id=' . $meeting_id);
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>