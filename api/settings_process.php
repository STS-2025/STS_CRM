<?php
// api/settings_process.php

ob_start(); 
session_start();

require 'db.php'; 

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../settings.php');
    exit();
}

// Get the tab for redirection
$redirect_tab = $_GET['tab'] ?? 'general';

// 1. Collect and Sanitize Input
$settings_data = [];
$errors = [];

if ($redirect_tab == 'general') {
    $settings_data = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'default_currency' => trim($_POST['default_currency'] ?? 'INR'), 
        'timezone' => trim($_POST['timezone'] ?? 'Asia/Kolkata') 
    ];
    
    // Validation for General
    if (empty($settings_data['company_name'])) {
        $errors[] = 'The CRM Name field is required.';
    }

} elseif ($redirect_tab == 'security') {
    $settings_data = [
        'password_min_length' => filter_var($_POST['password_min_length'] ?? 8, FILTER_VALIDATE_INT),
        'session_timeout_minutes' => filter_var($_POST['session_timeout_minutes'] ?? 60, FILTER_VALIDATE_INT)
    ];

    // Validation for Security
    if ($settings_data['password_min_length'] === false || $settings_data['password_min_length'] < 6 || $settings_data['password_min_length'] > 32) {
        $errors[] = 'Minimum Password Length must be a number between 6 and 32.';
    } 
    if ($settings_data['session_timeout_minutes'] === false || $settings_data['session_timeout_minutes'] < 5 || $settings_data['session_timeout_minutes'] > 720) {
        $errors[] = 'Session Timeout must be a number (minutes) between 5 and 720.';
    }
    
} elseif ($redirect_tab == 'integrations') {
    $settings_data = [
        'google_maps_api_key' => trim(filter_var($_POST['google_maps_api_key'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)),
        'slack_webhook_url' => trim(filter_var($_POST['slack_webhook_url'] ?? '', FILTER_SANITIZE_URL)),
    ];

    // Validation for Integrations
    if (!empty($settings_data['slack_webhook_url']) && !filter_var($settings_data['slack_webhook_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Slack Webhook URL is invalid.';
    }

} elseif ($redirect_tab == 'email') {
    $settings_data = [
        // --- System Email Sender (FROM) ---
        'system_email_sender' => trim(filter_var($_POST['system_email_sender'] ?? '', FILTER_SANITIZE_EMAIL)),
        
        // --- Email Templates ---
        'welcome_email_subject' => trim(filter_var($_POST['welcome_email_subject'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)),
        'welcome_email_body' => trim($_POST['welcome_email_body'] ?? ''),
        
        // --- NEW SMTP FIELDS FOR PHPMailer ---
        'smtp_host' => trim(filter_var($_POST['smtp_host'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)),
        'smtp_port' => filter_var($_POST['smtp_port'] ?? 587, FILTER_VALIDATE_INT),
        'smtp_username' => trim(filter_var($_POST['smtp_username'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)),
        'smtp_password' => $_POST['smtp_password'] ?? '', // Password should not be sanitized
        'smtp_secure' => trim(filter_var($_POST['smtp_secure'] ?? 'tls', FILTER_SANITIZE_SPECIAL_CHARS)), // Should be 'tls' or 'ssl'
    ];

    // Validation for Email
    if (empty($settings_data['welcome_email_subject'])) {
        $errors[] = 'The Email Subject cannot be empty.';
    }
    
    // Validation for NEW SMTP Settings
    if (!filter_var($settings_data['system_email_sender'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'System Sender Email is invalid.';
    }
    
    if (empty($settings_data['smtp_host'])) {
        $errors[] = 'SMTP Host is required for sending mail.';
    }
    
    if (empty($settings_data['smtp_username'])) {
        $errors[] = 'SMTP Username is required for sending mail.';
    }
    
    if (empty($settings_data['smtp_password'])) {
        $errors[] = 'SMTP Password is required for sending mail.';
    }
    
    if ($settings_data['smtp_port'] === false || ($settings_data['smtp_port'] != 465 && $settings_data['smtp_port'] != 587)) {
        $errors[] = 'SMTP Port must be a valid number (usually 465 or 587).';
    }


} elseif ($redirect_tab == 'reports') {
    
    // Process Recipient Roles (NOTE: This seems to be older logic for a single report)
    $selected_roles = $_POST['daily_summary_roles'] ?? [];
    $role_string = '';

    if (!empty($selected_roles)) {
        $sanitized_roles = array_map(function($role) {
            return preg_replace('/[^a-z0-9]/i', '', $role); 
        }, $selected_roles);
        $role_string = implode(',', array_unique(array_filter($sanitized_roles)));
    }
    
    // Collect all report settings
    $settings_data = [
        'daily_summary_roles' => $role_string,
        'daily_summary_enabled' => isset($_POST['daily_summary_enabled']) ? '1' : '0', // Save '1' or '0'
        'daily_summary_time' => trim($_POST['daily_summary_time'] ?? '09:00') // Save time in HH:MM format
    ];
    
    // Validation for Reports
    if (empty($settings_data['daily_summary_time'])) {
        $errors[] = 'The Send Time for the report cannot be empty.';
    }
}


// 2. Handle Validation Failure
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header("Location: ../settings.php?tab={$redirect_tab}");
    exit();
}

// 3. Database Update (UPSERT logic)
$all_success = true;

if (!empty($settings_data)) {
    // Prepare the UPSERT statement
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
        $all_success = false;
    }

    if ($all_success) {
        foreach ($settings_data as $key => $value) {
            $value_str = (string)$value;
            $stmt->bind_param('ss', $key, $value_str);
            if (!$stmt->execute()) {
                $_SESSION['error'] = "Error updating setting '{$key}': " . $stmt->error;
                $all_success = false;
                break;
            }
        }
        $stmt->close();
    }
} else {
     $all_success = true;
}


// 4. Provide Feedback and Redirect
if ($all_success) {
    $_SESSION['message'] = ucfirst($redirect_tab) . " settings updated successfully!";
} else if (!isset($_SESSION['error'])) {
    $_SESSION['error'] = "An unknown error occurred while saving settings.";
}

$conn->close();
ob_end_flush(); 
header("Location: ../settings.php?tab={$redirect_tab}");
exit();
?>