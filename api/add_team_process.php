<?php
// api/add_team_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../manage_teams.php');
    exit();
}

// 1. Collect and Sanitize Input
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? null);
// Manager ID can be empty/NULL, treat it as an integer or NULL
$manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

// Store form data temporarily for sticky form fields on error
$_SESSION['form_data'] = [
    'name' => $name,
    'description' => $description
];

// 2. Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'The Team Name field is required.';
}
if (strlen($name) > 100) {
    $errors[] = 'Team Name cannot exceed 100 characters.';
}

// Check if team name already exists
if (empty($errors)) {
    $check_sql = "SELECT id FROM teams WHERE name = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $errors[] = "A team named '{$name}' already exists.";
    }
    $check_stmt->close();
}

// 3. Handle Validation Failure
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ../manage_teams.php');
    exit();
}

// 4. Prepare SQL statement for Insertion
$sql = "INSERT INTO teams (name, description, manager_id) VALUES (?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: ../manage_teams.php');
    exit();
}

// Determine bind type for manager_id: 'i' for integer, 's' for string (if using null for text column), or handle NULL
// Since manager_id is INT NULL, we can bind it directly as integer or NULL. mysqli requires a type.
// For manager_id, we will explicitly check if it's NULL before binding.
if ($manager_id === null) {
    // If manager_id is NULL, we bind null
    $bind_types = 'ssi'; // We still pass 'i' but use reference trick or use set_null
    $stmt->bind_param('ssi', $name, $description, $manager_id);
} else {
    $bind_types = 'ssi';
    $stmt->bind_param('ssi', $name, $description, $manager_id);
}

// 5. Execute and Redirect
if ($stmt->execute()) {
    // Clear the form data upon success
    unset($_SESSION['form_data']); 
    
    $_SESSION['message'] = "Team '{$name}' created successfully!";
    
    // Redirect back to the team list page
    header("Location: ../manage_teams.php"); 
    
} else {
    $_SESSION['error'] = "Error adding team: " . $stmt->error;
    header('Location: ../manage_teams.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>