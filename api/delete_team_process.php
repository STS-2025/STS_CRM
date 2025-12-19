<?php
// api/delete_team_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check for valid GET request with ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    $_SESSION['error'] = 'Invalid request to delete team.';
    header('Location: ../manage_teams.php');
    exit();
}

// 1. Get and Validate Team ID
$team_id = (int)($_GET['id'] ?? 0);

if ($team_id <= 0) {
    $_SESSION['error'] = 'Invalid Team ID provided for deletion.';
    header('Location: ../manage_teams.php');
    exit();
}

// 2. Fetch the Team Name before deletion (for user feedback)
$team_name_query = $conn->prepare("SELECT name FROM teams WHERE id = ?");
$team_name_query->bind_param('i', $team_id);
$team_name_query->execute();
$team_result = $team_name_query->get_result();
$team_to_delete = $team_result->fetch_assoc();
$team_name = $team_to_delete ? $team_to_delete['name'] : 'Unknown Team';
$team_name_query->close();

// --- START: FOREIGN KEY / DEPENDENCY HANDLING ---

// Since the 'team' column in the 'users' table is not a formal FK to 'teams.name', 
// we must manually find all users belonging to this team and set their 'team' field to NULL or empty.
// We use the fetched $team_name to identify the users to update.

if ($team_to_delete) {
    $update_users_sql = "UPDATE users SET team = NULL WHERE team = ?";
    $update_users_stmt = $conn->prepare($update_users_sql);
    $update_users_stmt->bind_param('s', $team_name);
    $update_users_stmt->execute();
    // No need to check affected_rows, just proceed
    $update_users_stmt->close();
}

// --- END: FOREIGN KEY / DEPENDENCY HANDLING ---


// 3. Prepare SQL statement for Deletion
$sql = "DELETE FROM teams WHERE id = ? LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: ../manage_teams.php');
    exit();
}

$stmt->bind_param('i', $team_id);

// 4. Execute and Redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Team '{$team_name}' and all associated user assignments were successfully deleted/cleared.";
    } else {
        $_SESSION['error'] = "Error: Team ID #{$team_id} was not found or could not be deleted.";
    }
} else {
    $_SESSION['error'] = "Error deleting team '{$team_name}': " . $stmt->error;
}

$stmt->close();
$conn->close();
ob_end_flush(); 
header('Location: ../manage_teams.php');
exit();
?>