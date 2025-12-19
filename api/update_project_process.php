<?php
// api/update_project_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../projects.php');
    exit();
}

// 1. Collect and sanitize input
$project_id = (int)($_POST['project_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$manager_id = (int)($_POST['manager_id'] ?? 0);
$company_id = (int)($_POST['company_id'] ?? null); // Can be NULL
$status = trim($_POST['status'] ?? 'Planning');
$progress = (int)($_POST['progress'] ?? 0);
$start_date = trim($_POST['start_date'] ?? null);
$due_date = trim($_POST['due_date'] ?? null); 

// Set company_id to NULL if 0 or empty string
$company_id = $company_id > 0 ? $company_id : null;

// Convert empty date strings to NULL
$start_date = !empty($start_date) ? $start_date : null;
$due_date = !empty($due_date) ? $due_date : null;

// Ensure progress is within 0-100 range
$progress = max(0, min(100, $progress));

// 2. Simple Validation
if ($project_id <= 0 || empty($title) || $manager_id <= 0) {
    $_SESSION['error'] = 'Project ID, Title, and Manager are required.';
    header('Location: ../edit_project.php?id=' . $project_id);
    exit();
}

// 3. Prepare SQL statement
$sql = "UPDATE projects 
        SET title = ?, description = ?, status = ?, progress = ?, 
            manager_id = ?, company_id = ?, start_date = ?, due_date = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: ../edit_project.php?id=' . $project_id);
    exit();
}

// 4. Bind parameters
// Types: s, s, s, i, i, i, s, s, i (Total 9 parameters)
$stmt->bind_param('sssiissii', 
    $title, $description, $status, $progress, 
    $manager_id, $company_id, $start_date, $due_date, 
    $project_id
);

// 5. Execute and redirect
if ($stmt->execute()) {
    $_SESSION['message'] = "Project '{$title}' updated successfully!";
    
    // Redirect to the project's view page
    header("Location: ../view_project.php?id={$project_id}"); 
    
} else {
    $_SESSION['error'] = "Error updating project: " . $stmt->error;
    header('Location: ../edit_project.php?id=' . $project_id);
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>