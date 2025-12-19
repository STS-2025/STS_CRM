<?php
include 'db.php';
$email_id = $_GET['id'];
$lead_id = $_GET['lead_id'];

// Mark as read
$conn->query("UPDATE crm_emails SET is_read = 1 WHERE id = $email_id");

// Redirect to lead page
header("Location: ../view_lead.php?id=$lead_id");