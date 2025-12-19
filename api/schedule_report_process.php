<?php
// api/schedule_report_process.php
// Handles the submission of a new scheduled report from the settings page.

ob_start(); 
session_start();

require 'db.php'; 
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --------------------------
// Detect "Send Now (Test Run)"
// --------------------------
if (isset($_POST['send_now']) && $_POST['send_now'] == "1") {

    require 'smtp_config.php';
    
    // Collect and sanitize input
    
    $report_subject = trim($_POST['report_subject'] ?? '');
    $report_body = trim($_POST['report_body'] ?? '');
    $recipient_user_ids = $_POST['recipient_user_ids'] ?? [];

    if (empty($recipient_user_ids)) {
        $_SESSION['error'] = "Please select at least 1 employee!";
        header("Location: ../settings.php?tab=reports");
        exit();
    }

    // Fetch selected employee email list
    $placeholders = implode(',', array_fill(0, count($recipient_user_ids), '?'));
    $types = str_repeat('i', count($recipient_user_ids));

    $stmt = $conn->prepare("SELECT email, name FROM users WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$recipient_user_ids);
    $stmt->execute();
    $res = $stmt->get_result();
    $recipients = $res->fetch_all(MYSQLI_ASSOC);

    foreach ($recipients as $emp) {

        $mail = new PHPMailer(true);

        try {
            // SMTP config
            $mail->isSMTP();
            $mail->Host = $mail_host;
            $mail->SMTPAuth = true;
            $mail->Username = $mail_username;
            $mail->Password = $mail_password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = $mail_port;

            // From / To
            $mail->setFrom($mail_from, $mail_from_name);
            $mail->addAddress($emp['email'], $emp['name']);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $report_subject;
            $mail->Body = str_replace("[Recipient Name]", $emp['name'], $report_body);

            $mail->send();

        } catch (Exception $e) {
            $_SESSION['error'] = "SMTP Error: {$mail->ErrorInfo}";
            header("Location: ../settings.php?tab=reports");
            exit();
        }
    }

    $_SESSION['success'] = "Test email sent successfully!";
    header("Location: ../settings.php?tab=reports");
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../settings.php?tab=reports');
    exit();
}

// 1. Collect and Sanitize Input
$report_name = trim($_POST['report_name'] ?? '');
$report_subject = trim($_POST['report_subject'] ?? '');
$report_body = trim($_POST['report_body'] ?? '');

$schedule_frequency = trim($_POST['schedule_frequency'] ?? 'Daily');
$schedule_start_date = trim($_POST['schedule_start_date'] ?? date('Y-m-d'));

$time_hh = trim($_POST['schedule_time_hh'] ?? '09');
$time_mm = trim($_POST['schedule_time_mm'] ?? '00');
$time_ampm = trim($_POST['schedule_time_ampm'] ?? 'AM');

// Recipients (User IDs from the checkboxes)
$recipient_user_ids = $_POST['recipient_user_ids'] ?? [];

$errors = [];

// 2. Validation
if (empty($report_name) || empty($report_subject) || empty($report_body)) {
    $errors[] = 'Report Name, Subject, and Body are required.';
}

if (empty($recipient_user_ids)) {
    $errors[] = 'Please select at least one recipient for the report.';
}

if (!in_array($schedule_frequency, ['Daily', 'Weekly', 'Monthly', 'Once'])) {
    $errors[] = 'Invalid schedule frequency.';
}

// Convert 12-hour time to 24-hour time format (HH:MM:SS)
try {
    $time_string = sprintf('%02d:%02d %s', $time_hh, $time_mm, $time_ampm);
    $time_obj = DateTime::createFromFormat('h:i A', $time_string);
    if ($time_obj === false) {
         $errors[] = 'Invalid time format.';
    }
    $schedule_time_24h = $time_obj->format('H:i:s');
} catch (Exception $e) {
    $errors[] = 'Error processing time input.';
}


// 3. Handle Validation Failure
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ../settings.php?tab=reports');
    exit();
}


// 4. Determine Recipients JSON (Store the selected user IDs)
// In a real application, you might also store the selected role, 
// but for simplicity, we store only the specific IDs that were checked.
$recipients_data = [
    'users' => array_map('intval', $recipient_user_ids)
];
$recipients_json = json_encode($recipients_data);


// 5. Calculate Next Run Date
// This is critical for the cron job to know when to start.
$dt = new DateTime($schedule_start_date . ' ' . $schedule_time_24h);
$next_run_datetime = $dt->format('Y-m-d H:i:s');


// 6. Insert into scheduled_reports table (Assuming current user is 1 for simplicity)
// NOTE: In a real system, you would get the actual ID from $_SESSION['user_id']
$created_by_id = $_SESSION['user_id'] ?? 1; 

$sql = "INSERT INTO scheduled_reports 
        (report_name, report_subject, report_body, recipients_json, schedule_type, schedule_time, schedule_date, next_run_datetime, created_by, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)";

if ($stmt = $conn->prepare($sql)) {
    // Bind parameters: ssssssssi (9 string, 1 int)
    $stmt->bind_param(
        'ssssssssi', 
        $report_name, 
        $report_subject, 
        $report_body, 
        $recipients_json, 
        $schedule_frequency, 
        $schedule_time_24h, 
        $schedule_start_date, // Note: schedule_date column can store the start date if needed for reference
        $next_run_datetime, 
        $created_by_id
    );
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "New report '{$report_name}' scheduled successfully. Next run: {$next_run_datetime}";
    } else {
        $_SESSION['error'] = "Database error: Failed to save schedule. " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Database error during preparation: " . $conn->error;
}

$conn->close();

// 7. Redirect back to settings page
header('Location: ../settings.php?tab=reports');
exit();
?>