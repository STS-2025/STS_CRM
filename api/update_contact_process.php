<?php
// api/update_contact_process.php

require 'db.php'; 
session_start();

if (!isset($_POST['contact_id'], $_POST['name'], $_POST['email'])) {
    $_SESSION['message'] = 'Error: Missing required data for update.';
    header('Location: ../contacts.php');
    exit();
}

$id = (int)$_POST['contact_id'];
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'] ?? NULL;
$title = $_POST['title'] ?? NULL;
$company_name = $_POST['company_name'] ?? NULL;
$status = $_POST['status'] ?? 'Prospect';

$stmt = $conn->prepare("
    UPDATE contacts 
    SET name = ?, email = ?, phone = ?, title = ?, company_name = ?, status = ?
    WHERE id = ?
");
$stmt->bind_param("ssssssi", $name, $email, $phone, $title, $company_name, $status, $id);

if ($stmt->execute()) {
    $_SESSION['message'] = 'Contact updated successfully!';
    header('Location: ../contacts.php');
} else {
    if ($conn->errno === 1062) {
        $_SESSION['message'] = 'Error: That email address is already assigned to another contact.';
        header('Location: ../edit_contact.php?id=' . $id);
    } else {
        $_SESSION['message'] = "Database Error: " . $stmt->error;
        header('Location: ../edit_contact.php?id=' . $id);
    }
}

$stmt->close();
$conn->close();
exit();
?>