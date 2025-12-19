<?php
// api/edit_team_process.php

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
$team_id = (int)($_POST['team_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? null);
// Manager ID can be empty/NULL, treat it as an integer or NULL
$manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

// Store form data temporarily for sticky form fields on error
$_SESSION['form_data'] = [
    'id' => $team_id,
    'name' => $name,
    'description' => $description,
    'manager_id' => $manager_id
];

// 2. Comprehensive Validation
$errors = [];

if ($team_id <= 0) {
    $errors[] = 'Invalid Team ID for update.';
}
if (empty($name)) {
    $errors[] = 'The Team Name field is required.';
}
if (strlen($name) > 100) {
    $errors[] = 'Team Name cannot exceed 100 characters.';
}

// Check if team name already exists for another team
if (empty($errors)) {
    $check_sql = "SELECT id FROM teams WHERE name = ? AND id != ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('si', $name, $team_id);
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
    header("Location: ../edit_team.php?id={$team_id}");
    exit();
}

// 4. Prepare SQL statement for Update
$sql = "UPDATE teams SET name = ?, description = ?, manager_id = ? WHERE id = ? LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header("Location: ../edit_team.php?id={$team_id}");
    exit();
}

// Bind parameters
$stmt->bind_param('ssii', $name, $description, $manager_id, $team_id);


// 5. Execute and Redirect
if ($stmt->execute()) {
    // Clear form data on success
    unset($_SESSION['form_data']); 

    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Team '{$name}' updated successfully!";
    } else {
        $_SESSION['message'] = "Team data unchanged for '{$name}'.";
    }
    
    // Redirect back to the team list page (or potentially an edit_team_view page)
    header("Location: ../manage_teams.php"); 
    
} else {
    $_SESSION['error'] = "Error updating team '{$name}': " . $stmt->error;
    header("Location: ../edit_team.php?id={$team_id}");
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>