<?php
// api/delete_task_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Get and sanitize ID from URL
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($task_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid Task ID for deletion.';
    header('Location: ../tasks.php');
    exit();
}

// 2. Prepare SQL statement for deletion
$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);

// 3. Execute and redirect
if ($stmt->execute()) {
    // Check if any rows were affected (i.e., if the task existed)
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Task #{$task_id} deleted successfully.";
    } else {
        $_SESSION['message'] = "Warning: Task #{$task_id} not found to delete.";
    }
    header('Location: ../tasks.php');
} else {
    $_SESSION['message'] = "Database Error: Could not delete task. " . $stmt->error;
    header('Location: ../tasks.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>