<?php
// api/add_task_process.php

ob_start(); 
session_start();

require 'db.php'; 

// 1. Check for required POST data
if (!isset($_POST['title'], $_POST['contact_id'], $_POST['due_date'], $_POST['priority'], $_POST['user_id'])) {
    $_SESSION['message'] = 'Error: Missing required data for task creation.';
    header('Location: ../add_task.php');
    exit();
}

// 2. Get and sanitize form data
$title = trim($_POST['title']);
$description = trim($_POST['description'] ?? ''); // Description is optional
$contact_id = (int)$_POST['contact_id'];
$due_date = $_POST['due_date'];
$priority = $_POST['priority'];
$user_id = (int)$_POST['user_id']; 

// 3. Basic validation
if (empty($title) || $contact_id <= 0 || $user_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid contact or user selection, or missing title.';
    header('Location: ../add_task.php');
    exit();
}


// 4. Prepare SQL statement for insertion
$stmt = $conn->prepare("
    INSERT INTO tasks (title, description, due_date, priority, status, contact_id, user_id) 
    VALUES (?, ?, ?, ?, 'Not Started', ?, ?)
");

// Bind parameters: s (title), s (description), s (due_date), s (priority), i (contact_id), i (user_id)
$stmt->bind_param("ssssii", $title, $description, $due_date, $priority, $contact_id, $user_id);


// 5. Execute and redirect
if ($stmt->execute()) {
    $new_task_id = $conn->insert_id;
    $_SESSION['message'] = "Task #{$new_task_id} ('{$title}') created successfully!";
    
    // Redirect to the tasks list page
    header('Location: ../tasks.php'); 

} else {
    $_SESSION['message'] = "Database Error: Could not create task. " . $stmt->error;
    // Redirect back to the add task page with the error
    header('Location: ../add_task.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>