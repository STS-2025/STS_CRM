<?php
// api/delete_user_process.php - Updated

ob_start(); 
session_start();

require 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    $_SESSION['error'] = 'Invalid request to delete user.';
    header('Location: ../users.php');
    exit();
}

$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    $_SESSION['error'] = 'Invalid User ID provided for deletion.';
    header('Location: ../users.php');
    exit();
}

// --- START: FOREIGN KEY PRE-DELETION HANDLING ---
// Define a fallback user ID (e.g., an Admin account, ID 1) to reassign orphaned records.
// You MUST ensure this user ID exists and is active.
$reassign_user_id = 1; 

if ($user_id === $reassign_user_id) {
     $_SESSION['error'] = 'You cannot delete the primary fallback account.';
     header('Location: ../users.php');
     exit();
}

// Reassign ownership in critical tables before deleting the user (parent row)
$conn->query("UPDATE companies SET owner_id = {$reassign_user_id} WHERE owner_id = {$user_id}");
$conn->query("UPDATE leads SET owner_id = {$reassign_user_id} WHERE owner_id = {$user_id}");
$conn->query("UPDATE deals SET owner_id = {$reassign_user_id} WHERE owner_id = {$user_id}");
$conn->query("UPDATE projects SET manager_id = {$reassign_user_id} WHERE manager_id = {$user_id}");

// For tables with ON DELETE SET NULL or ON DELETE CASCADE, explicit updates may not be needed, 
// but it's good to reassign where the ID is NOT NULL.

// --- END: FOREIGN KEY PRE-DELETION HANDLING ---


// 2. Prepare SQL statement for Deletion
$sql = "DELETE FROM users WHERE id = ? LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: ../users.php');
    exit();
}

$stmt->bind_param('i', $user_id);

// 3. Execute and Redirect
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "User ID #{$user_id} and associated ownerships were deleted/reassigned successfully!";
    } else {
        $_SESSION['error'] = "Error: User ID #{$user_id} was not found or could not be deleted.";
    }
} else {
    $_SESSION['error'] = "Error deleting user #{$user_id}: " . $stmt->error;
}

$stmt->close();
$conn->close();
ob_end_flush(); 
header('Location: ../users.php');
exit();
?>