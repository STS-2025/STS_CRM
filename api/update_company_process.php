<?php
// api/update_company_process.php

require 'db.php'; 
session_start();

// Check for required POST data
if (!isset($_POST['company_id'], $_POST['name'], $_POST['owner_id'])) {
    $_SESSION['message'] = 'Error: Missing required data for update.';
    header('Location: ../companies.php');
    exit();
}

// 1. Get and sanitize form data
$id = (int)$_POST['company_id'];
$name = $_POST['name'];
$industry = $_POST['industry'] ?? NULL;
$phone = $_POST['phone'] ?? NULL;
$owner_id = (int)$_POST['owner_id'];

// 2. Prepare SQL statement for updating data
$stmt = $conn->prepare("
    UPDATE companies 
    SET name = ?, industry = ?, phone = ?, owner_id = ?
    WHERE id = ?
");

// Bind parameters (s=string, i=integer). Types: s(name), s(industry), s(phone), i(owner_id), i(id)
$stmt->bind_param("sssii", $name, $industry, $phone, $owner_id, $id);

// 3. Execute and redirect
if ($stmt->execute()) {
    $_SESSION['message'] = 'Company updated successfully!';
    header('Location: ../companies.php');
} else {
    // Check for duplicate name error (1062)
    if ($conn->errno === 1062) {
        $_SESSION['message'] = 'Error: A company with this name already exists.';
        header('Location: ../edit_company.php?id=' . $id);
    } else {
        $_SESSION['message'] = "Database Error: " . $stmt->error;
        header('Location: ../edit_company.php?id=' . $id);
    }
}

$stmt->close();
$conn->close();
exit();
?>