<?php
// api/settings_logo_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Define the permanent storage location
$upload_dir = '../assets/images/'; 
$target_filename = 'logo.png'; // CORRECTED: Consistent filename for display and storage
$target_file = $upload_dir . $target_filename;
$public_logo_path = 'assets/images/logo.png'; // Path to store in settings DB

// Ensure the directory exists and is writable
if (!is_dir($upload_dir)) {
    // Attempt to create directory recursively with permissions 0777
    if (!mkdir($upload_dir, 0777, true)) {
        $_SESSION['error'] = 'Failed to create upload directory. Check file permissions (0777) for the assets/ folder.';
        header('Location: ../settings.php?tab=general');
        exit();
    }
}

// 1. Check if file was uploaded
if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'No file uploaded or an upload error occurred.';
    header('Location: ../settings.php?tab=general');
    exit();
}

$file = $_FILES['logo_file'];
$file_type = mime_content_type($file['tmp_name']);

// 2. Validation Checks
$errors = [];

// Check file size (e.g., max 500KB)
if ($file['size'] > 500000) {
    $errors[] = "Sorry, your file is too large. Max size is 500KB.";
}

// Allow certain file formats (including SVG+XML as accepted by the frontend form)
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml']; // SVG+XML added
if (!in_array($file_type, $allowed_types)) {
    $errors[] = "Sorry, only JPG, JPEG, PNG, GIF & SVG files are allowed (detected: " . $file_type . ").";
}

// 3. Handle Validation Failure
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ../settings.php?tab=general');
    exit();
}

// 4. Move the uploaded file
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    // Update the settings table with the logo path to record its existence
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('system_logo', ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $public_logo_path);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['message'] = "The new system logo was uploaded and saved successfully!";
} else {
    $_SESSION['error'] = "Sorry, there was an error moving the uploaded file. Check directory permissions (should be 0777) on the 'assets/images' folder."; // Improved error message
}

$conn->close();
ob_end_flush(); 
header('Location: ../settings.php?tab=general');
exit();
?>