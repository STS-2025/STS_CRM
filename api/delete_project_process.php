<?php
// api/delete_project_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check for the project ID in the GET request (linked from projects.php)
$project_id = (int)($_GET['id'] ?? 0);

if ($project_id <= 0) {
    $_SESSION['error'] = 'Invalid Project ID provided for deletion.';
    header('Location: ../projects.php');
    exit();
}

// 1. Prepare the SQL statement for deletion
$sql = "DELETE FROM projects WHERE id = ? LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: ../projects.php');
    exit();
}

// 2. Bind the parameter and execute
$stmt->bind_param('i', $project_id);

if ($stmt->execute()) {
    // Check if any rows were affected (i.e., if the project existed)
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Project ID #{$project_id} successfully deleted.";
    } else {
        $_SESSION['error'] = "Project ID #{$project_id} not found or already deleted.";
    }
} else {
    $_SESSION['error'] = "Error deleting project #{$project_id}: " . $stmt->error;
}

$stmt->close();
$conn->close();
ob_end_flush(); 

// 3. Redirect back to the main projects list
header('Location: ../projects.php');
exit();
?>