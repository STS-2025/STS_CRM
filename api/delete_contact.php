<?php
// api/delete_contact.php

require 'db.php'; 
session_start();

$contact_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($contact_id === 0) {
    $_SESSION['message'] = 'Error: Invalid contact ID for deletion.';
    header('Location: ../contacts.php');
    exit();
}

$stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
$stmt->bind_param("i", $contact_id);

if ($stmt->execute()) {
    $_SESSION['message'] = 'Contact successfully deleted.';
    header('Location: ../contacts.php');
} else {
    $_SESSION['message'] = 'Error deleting record: ' . $stmt->error;
    header('Location: ../contacts.php');
}

$stmt->close();
$conn->close();
exit();
?>