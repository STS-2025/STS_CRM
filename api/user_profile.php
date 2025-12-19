<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];

/* Safe defaults to avoid undefined index */
$name         = $_POST['name'] ?? '';
$email_sync   = $_POST['email_sync'] ?? 'disabled';
$notify_email = isset($_POST['notify_email']) ? 1 : 0;
$notify_app   = isset($_POST['notify_app']) ? 1 : 0;

/* =========================
   UPDATE PROFILE INFO
========================= */
$stmt = $conn->prepare("
    UPDATE users 
    SET name = ?, email_sync = ?, notify_email = ?, notify_app = ?
    WHERE id = ?
");
$stmt->bind_param("ssiii", $name, $email_sync, $notify_email, $notify_app, $user_id);
$stmt->execute();
$stmt->close();

/* =========================
   CHANGE PASSWORD (NO HASH)
========================= */
if (
    isset($_POST['current_password'], $_POST['new_password']) &&
    $_POST['current_password'] !== '' &&
    $_POST['new_password'] !== ''
) {
    // Fetch existing password (plain text)
    $check = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();

    // Compare plain text passwords
    if (!$result || $_POST['current_password'] !== $result['password']) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: ../settings.php?tab=profile");
        exit;
    }

    // Update password as plain text
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $_POST['new_password'], $user_id);
    $update->execute();
    $update->close();
}

$_SESSION['message'] = "Profile updated successfully.";
header("Location: ../settings.php?tab=profile");
exit;
