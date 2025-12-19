<?php
// api/create_company_process.php

require 'db.php'; 
session_start();

// 1. Get and sanitize form data
$name = $_POST['name'] ?? '';
$industry = $_POST['industry'] ?? NULL;
$phone = $_POST['phone'] ?? NULL;
$owner_id = (int)($_POST['owner_id'] ?? ($_SESSION['user_id'] ?? 1)); 

// Basic validation
if (empty($name) || $owner_id === 0) {
    $_SESSION['message'] = 'Error: Company Name and Owner are required.';
    header('Location: ../create_company.php');
    exit();
}

// 2. Prepare SQL statement using prepared statements (safer)
$stmt = $conn->prepare("
    INSERT INTO companies 
    (name, industry, phone, owner_id) 
    VALUES (?, ?, ?, ?)
");

// Bind parameters
$stmt->bind_param("sssi", $name, $industry, $phone, $owner_id);

// 3. Execute and redirect
if ($stmt->execute()) {
    $_SESSION['message'] = 'Company created successfully!';
    header('Location: ../companies.php');
} else {
    // Check for duplicate name error (1062)
    if ($conn->errno === 1062) {
        $_SESSION['message'] = 'Error: A company with this name already exists.';
        header('Location: ../create_company.php');
    } else {
        $_SESSION['message'] = "Database Error: " . $stmt->error;
        header('Location: ../create_company.php');
    }
}

$stmt->close();
$conn->close();
exit();
?>