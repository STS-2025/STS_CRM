<?php
// api/edit_user_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../users.php');
    exit();
}

// 1. Collect and Sanitize Input
$user_id = (int)($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$status = trim($_POST['status'] ?? '');
$team = trim($_POST['team'] ?? null); // Can be NULL
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Store form data temporarily for sticky form fields on error
$_SESSION['form_data'] = [
    'id' => $user_id, // Important to pass ID back
    'name' => $name,
    'email' => $email,
    'role' => $role,
    'status' => $status,
    'team' => $team
];

// 2. Comprehensive Validation
$errors = [];

if ($user_id <= 0) {
    $errors[] = 'Invalid User ID for update.';
}
if (empty($name) || empty($email) || empty($role) || empty($status)) {
    $errors[] = 'Name, Email, Role, and Status fields are required.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}

// Password Validation (Only if provided)
$password_update = !empty($password);
if ($password_update) {
    if ($password !== $confirm_password) {
        $errors[] = 'New Password and Confirm Password do not match.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }
}

// Check if email already exists for another user
if (empty($errors)) {
    $check_sql = "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('si', $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $errors[] = 'This email address is already in use by another user.';
    }
    $check_stmt->close();
}

// 3. Handle Validation Failure
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header("Location: ../edit_user.php?id={$user_id}");
    exit();
}

// 4. Build the Dynamic SQL Query
$fields = ['name = ?', 'email = ?', 'role = ?', 'status = ?', 'team = ?'];
$bind_types = 'sssss';
$bind_values = [$name, $email, $role, $status, $team];

if ($password_update) {
    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $fields[] = 'password = ?';
    $bind_types .= 's';
    $bind_values[] = $hashed_password;
}

$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ? LIMIT 1";
$bind_types .= 'i'; // Add type for user_id
$bind_values[] = $user_id; // Add user_id to the end of the values array

// 5. Prepare and Execute
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header("Location: ../edit_user.php?id={$user_id}");
    exit();
}

// Bind parameters dynamically
$stmt->bind_param($bind_types, ...$bind_values);

if ($stmt->execute()) {
    // Clear form data on success
    unset($_SESSION['form_data']); 

    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "User ID #{$user_id} updated successfully!";
    } else {
        $_SESSION['message'] = "User data unchanged for ID #{$user_id}.";
    }
    
    // Redirect to the updated user's view page
    header("Location: ../view_user.php?id={$user_id}"); 
    
} else {
    $_SESSION['error'] = "Error updating user #{$user_id}: " . $stmt->error;
    header("Location: ../edit_user.php?id={$user_id}");
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>