<?php
// api/create_project_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../create_project.php');
    exit();
}

// 1. Collect and sanitize input
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$manager_id = (int)($_POST['manager_id'] ?? 0);
$company_id = (int)($_POST['company_id'] ?? null); // Can be NULL
$status = trim($_POST['status'] ?? 'Planning');
$start_date = trim($_POST['start_date'] ?? null);
$due_date = trim($_POST['due_date'] ?? null); // Matches 'due_date' in your schema

// Set company_id to NULL if 0 or empty string
$company_id = $company_id > 0 ? $company_id : null;

// Convert empty date strings to NULL
$start_date = !empty($start_date) ? $start_date : null;
$due_date = !empty($due_date) ? $due_date : null;

// 2. Simple Validation
if (empty($title) || $manager_id <= 0) {
    $_SESSION['error'] = 'Project Title and Manager are required.';
    header('Location: ../create_project.php');
    exit();
}

// 3. Prepare SQL statement (Progress defaults to 0)
$sql = "INSERT INTO projects 
        (title, description, status, manager_id, company_id, start_date, due_date, progress) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: ../create_project.php');
    exit();
}

// 4. Bind parameters
// Types: s (string), s (string), s (string), i (int), i or NULL (int), s or NULL (date), s or NULL (date)
// We need to handle the potentially NULL values for company_id, start_date, and due_date.

// Using call_user_func_array for complex binding with NULLs and integers
$types = 'sssiiss'; // title, description, status, manager_id, company_id, start_date, due_date

// The $params array must contain the type string first, followed by variables by reference.
// Since we are using mysqli, we need to ensure values are passed correctly.
$stmt->bind_param($types, $title, $description, $status, $manager_id, $company_id, $start_date, $due_date);


// 5. Execute and redirect
if ($stmt->execute()) {
    $new_project_id = $stmt->insert_id;
    $_SESSION['message'] = "Project '{$title}' created successfully! (#{$new_project_id})";
    
    // Redirect to the new project's view page (assuming view_project.php exists later)
    header("Location: ../view_project.php?id={$new_project_id}"); 
    
} else {
    $_SESSION['error'] = "Error creating project: " . $stmt->error;
    header('Location: ../create_project.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>