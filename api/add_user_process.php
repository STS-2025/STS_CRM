<?php
// api/add_user_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../add_user.php');
    exit();
}

// 1. Collect and Sanitize Input
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'Employee');
$team = trim($_POST['team'] ?? null); // Can be NULL
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$status = trim($_POST['status'] ?? 'Active'); // Should be 'Active' for a new user

// Store form data temporarily for sticky form fields on error
$_SESSION['form_data'] = [
    'name' => $name,
    'email' => $email,
    'role' => $role,
    'team' => $team
];

// 2. Comprehensive Validation
$errors = [];

if (empty($name) || empty($email) || empty($role) || empty($password) || empty($confirm_password)) {
    $errors[] = 'All required fields (Name, Email, Role, Password) must be filled.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}
if ($password !== $confirm_password) {
    $errors[] = 'Password and Confirm Password do not match.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

// Check if email already exists
if (empty($errors)) {
    $check_sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $errors[] = 'A user with this email address already exists.';
    }
    $check_stmt->close();
}

// 3. Handle Validation Failure
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ../add_user.php');
    exit();
}

// 4. Hash the Password (Crucial Security Step)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 5. Prepare SQL statement for Insertion
$sql = "INSERT INTO users (name, email, role, team, status, password) VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: ../add_user.php');
    exit();
}

// Types: s, s, s, s, s, s (6 parameters)
$stmt->bind_param('ssssss', 
    $name, 
    $email, 
    $role, 
    $team, 
    $status,
    $hashed_password
);

// 6. Execute and Redirect
if ($stmt->execute()) {
    // Clear the form data upon success
    unset($_SESSION['form_data']); 
    
    $_SESSION['message'] = "User '{$name}' added successfully!";
    
    // Redirect to the main users list or the new user's view page
    $new_user_id = $stmt->insert_id;
    header("Location: ../users.php"); 
    
} else {
    $_SESSION['error'] = "Error adding user: " . $stmt->error;
    header('Location: ../add_user.php');
}

$stmt->close();
$conn->close();
ob_end_flush(); 
exit();
?>