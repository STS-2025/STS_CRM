<?php
// api/update_lead_process.php

ob_start();
session_start();

require 'db.php';

// 1. Check for required POST data
if (!isset($_POST['id'], $_POST['name'], $_POST['email'], $_POST['status'], $_POST['source'], $_POST['owner_id'])) {
    $_SESSION['message'] = 'Error: Missing required lead data for update.';
    header('Location: ../leads.php');
    exit();
}

// 2. Get and sanitize form data
$lead_id = (int) $_POST['id'];
$name = trim($_POST['name']);
$company = trim($_POST['company'] ?? '');
$email = trim($_POST['email']);
$phone = trim($_POST['phone'] ?? '');
$status = $_POST['status'];
$source = $_POST['source'];
$owner_id = (int) $_POST['owner_id'];
$campaign_id = !empty($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;
$reminder_date = !empty($_POST['reminder_date']) ? trim($_POST['reminder_date']) : null;
$remarks = trim($_POST['remarks'] ?? '');

$deal_amount = isset($_POST['deal_amount']) ? (float) $_POST['deal_amount'] : 0;
$expected_close_date = $_POST['expected_close_date'] ?? null;
$deal_company = trim($_POST['deal_company'] ?? '');


// 3. Basic validation
if ($lead_id <= 0 || empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $owner_id <= 0) {
    $_SESSION['message'] = 'Error: Invalid Lead ID, Name, Email, or Owner ID provided.';
    header('Location: ../edit_lead.php?id=' . $lead_id);
    exit();
}


// Deal validation
/*if ($status === 'Converted') {
    if ($deal_amount <= 0 || empty($expected_close_date)) {
        $_SESSION['message'] = 'Deal amount and expected close date are required.';
        header('Location: ../edit_lead.php?id=' . $lead_id);
        exit();
    }
}*/

try {
    // 4. Begin transaction
    $conn->begin_transaction();

    // 5. Update lead
    $sql = "
        UPDATE leads 
        SET name=?, company=?, email=?, phone=?, status=?, source=?, owner_id=?, campaign_id=?, reminder_date=?, remarks=?
        WHERE id=?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssiissi", $name, $company, $email, $phone, $status, $source, $owner_id, $campaign_id, $reminder_date, $remarks, $lead_id);
    $stmt->execute();
    $stmt->close();

    // 6. If lead is converted, update related deals
    // 6. If lead is converted, create a new deal
    if ($status === 'Converted') {

        // Check if deal already exists for this lead
        $check_sql = "SELECT id FROM deals WHERE lead_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $lead_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        // Only create deal if it doesn't exist
        if ($check_stmt->num_rows === 0) {
            if ($deal_amount <= 0 || empty($expected_close_date)) {
                throw new Exception('Deal Amount and Expected Close Date are required.');
            }
            $deal_name = $name;
            $deal_sql = " INSERT INTO deals 
    (deal_name, amount, stage, opening_date, close_date, lead_id, owner_id)
    VALUES (?, ?, 'New', CURDATE(), ?, ?, ?)";

            $deal_stmt = $conn->prepare($deal_sql);
           
            // Default amount
            $deal_stmt->bind_param(
                "sdsii",
                $deal_name,
                $deal_amount,
                $expected_close_date,
                $lead_id,
                $owner_id
            );

            $deal_stmt->execute();
            $deal_stmt->close();
        }

        $check_stmt->close();
    }
    // 7. Commit transaction
    $conn->commit();

    $_SESSION['message'] = 'Lead updated successfully.';
    header('Location: ../leads.php');
} catch (Exception $e) {
    // 8. Rollback on error
    $conn->rollback();
    $_SESSION['message'] = 'Error updating lead: ' . $e->getMessage();
    header('Location: ../edit_lead.php?id=' . $lead_id);
}
ob_end_flush();
exit();
?>