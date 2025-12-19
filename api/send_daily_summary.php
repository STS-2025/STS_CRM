<?php
// api/send_daily_summary.php
// This script must be run by the server's Cron Job scheduler (ideally *every minute*).

// --- 1. PHPMailer SETUP ---
// You must ensure these files are correctly located relative to this script.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Assuming PHPMailer is installed via Composer or manually in a vendor folder
require 'vendor/autoload.php'; // RECOMMENDED: Use composer autoload
// OR manually:
// require 'vendor/PHPMailer/PHPMailer/src/Exception.php';
// require 'vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
// require 'vendor/PHPMailer/PHPMailer/src/SMTP.php';


// --- INITIAL SETUP AND TIMEZONE CONFIGURATION ---
date_default_timezone_set('Asia/Kolkata'); 

// 2. Include the database connection
require 'db.php'; 

// Fetch required settings from the database
$settings_defaults = [
    'timezone' => 'Asia/Kolkata',
    'company_name' => 'STS CRM',
    'system_email_sender' => 'mmichealmithra@gmail.com', // The sender email you added to settings.php
    // --- SMTP Settings (NEW REQUIRED KEYS) ---
    'smtp_host' => 'smtp.gmail.com', // e.g., 'smtp.sendgrid.net' or 'smtp.gmail.com'
    'smtp_port' => 587,
    'smtp_username' => 'mmichealmithra@gmail.com',
    'smtp_password' => 'rcin eaab txzg kily',
    'smtp_secure' => 'tls' // or 'ssl'
];

$settings = $settings_defaults;

if (isset($conn) && $conn->ping()) {
    $keys = "'" . implode("','", array_keys($settings_defaults)) . "'";
    $sql_settings = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ({$keys})";
    $result_settings = $conn->query($sql_settings);

    if ($result_settings) {
        while($row = $result_settings->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Apply settings
    $timezone_setting = $settings['timezone'];
    $company_name = $settings['company_name'];
    $sender_email = $settings['system_email_sender']; // Set dynamic sender email

    date_default_timezone_set($timezone_setting);
} else {
    error_log("FATAL: Database connection failed. Cannot proceed with scheduled reports.");
    exit();
}


// --- 3. QUERY DUE REPORTS ---
$current_datetime = date('Y-m-d H:i:s');
$sql_due_reports = "SELECT * FROM scheduled_reports 
                    WHERE is_active = TRUE 
                    AND next_run_datetime <= '{$current_datetime}' 
                    ORDER BY next_run_datetime ASC";

$result_due_reports = $conn->query($sql_due_reports);

if (!$result_due_reports || $result_due_reports->num_rows === 0) {
    if (date('H:i') === '00:00') { 
        error_log("INFO: No CRM reports due today at this time.");
    }
    $conn->close();
    exit();
}

// --- 4. PROCESS EACH DUE REPORT ---
$processed_count = 0;

while ($report = $result_due_reports->fetch_assoc()) {
    $report_id = $report['id'];
    $report_name = $report['report_name'];
    $subject_template = $report['report_subject'];
    $body_template = $report['report_body'];
    $schedule_type = $report['schedule_type'];
    $recipients_json = $report['recipients_json'];
    $last_run_datetime_str = $report['last_run_datetime'] ?? '1970-01-01 00:00:00';

    error_log("Processing report ID {$report_id}: '{$report_name}' (Type: {$schedule_type})");

    // --- A. FETCH RECIPIENTS (Emails) ---
    $recipient_emails = [];
    $recipients_data = json_decode($recipients_json, true);
    $user_ids_to_fetch = $recipients_data['users'] ?? [];
    
    if (!empty($user_ids_to_fetch)) {
        $safe_ids = implode(',', array_map('intval', $user_ids_to_fetch));
        $sql_emails = "SELECT email, name FROM users WHERE id IN ({$safe_ids}) AND status = 'Active'";
        $result_emails = $conn->query($sql_emails);
        
        if ($result_emails) {
            while ($row = $result_emails->fetch_assoc()) {
                // Store both email and name for better PHPMailer usage
                $recipient_emails[] = ['email' => $row['email'], 'name' => $row['name']];
            }
        }
    }

    if (empty($recipient_emails)) {
        error_log("WARNING: Report ID {$report_id} skipped. No active recipients found.");
        goto calculate_next_run; 
    }

    // --- B. DYNAMIC DATA FETCH & TEMPLATE POPULATION (Same as before) ---
    
    $start_period = (new DateTime($last_run_datetime_str))->format('Y-m-d H:i:s');
    $end_period = $current_datetime;

    // 1. New Leads Count
    // ... (SQL queries for data remain the same)
    $new_leads_count = 0;
    $sql_leads = "SELECT COUNT(*) AS count FROM leads WHERE created_at >= '{$start_period}' AND created_at <= '{$end_period}'";
    $result_leads = $conn->query($sql_leads);
    if ($result_leads) {
        $new_leads_count = $result_leads->fetch_assoc()['count'];
    }

    // 2. Closed Deals Count (Example)
    $deals_closed_count = 0;
    $sql_deals = "SELECT COUNT(*) AS count FROM deals WHERE status = 'Closed' AND updated_at >= '{$start_period}' AND updated_at <= '{$end_period}'";
    $result_deals = $conn->query($sql_deals);
    if ($result_deals) {
        $deals_closed_count = $result_deals->fetch_assoc()['count'];
    }

    // Replace common placeholders
    $subject = str_replace(
        ['[COMPANY_NAME]', '[REPORT_DATE]'], 
        [$company_name, date('Y-m-d')], 
        $subject_template
    );
    
    $body_base = str_replace(
        ['[NEW_LEADS_COUNT]', '[DEALS_CLOSED_COUNT]', '[START_DATE]', '[END_DATE]'], 
        [$new_leads_count, $deals_closed_count, $start_period, $end_period], 
        $body_template
    );


    // --- C. SEND EMAIL USING PHPMailer (The main change) ---
    
    try {
        // Create a base PHPMailer instance
        $mail = new PHPMailer(true);
        $mail->IsSMTP(); 
        $mail->SMTPAuth = true;
        
        // SMTP Configuration from Settings
        $mail->Host = $settings['smtp_host'];
        $mail->Port = $settings['smtp_port'];
        $mail->Username = $settings['smtp_username'];
        $mail->Password = $settings['smtp_password'];
        $mail->SMTPSecure = $settings['smtp_secure'];
        $mail->SMTPDebug = 0; // Set to 2 for debugging

        // Sender Configuration
        $mail->setFrom($sender_email, $company_name . ' Reports');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        // Set the common subject
        $mail->Subject = $subject;

        foreach ($recipient_emails as $recipient) {
            // Clone the PHPMailer object for each recipient to clear addresses (optional, but safer)
            $user_mail = clone $mail; 
            
            // Customize body for the specific recipient (e.g., [Recipient Name])
            $final_body = str_replace('[Recipient Name]', $recipient['name'], $body_base);

            // Add the recipient
            $user_mail->addAddress($recipient['email'], $recipient['name']);
            $user_mail->Body = $final_body;
            $user_mail->AltBody = strip_tags($final_body); // Plain text fallback

            if ($user_mail->send()) {
                error_log("Report ID {$report_id} sent successfully to {$recipient['email']}");
            } else {
                error_log("FATAL: Failed to send report ID {$report_id} email to {$recipient['email']}. PHPMailer Error: " . $user_mail->ErrorInfo);
            }
        }
    } catch (Exception $e) {
        error_log("FATAL: PHPMailer Configuration Error for report ID {$report_id}: " . $e->getMessage());
    }
    
    $processed_count++;

    // --- D. CALCULATE NEXT RUN DATE & E. UPDATE THE SCHEDULE RECORD (Same as before) ---
    calculate_next_run:
    
    $current_schedule = new DateTime($report['next_run_datetime']);

    switch ($schedule_type) {
        case 'Daily':
            $current_schedule->modify('+1 day');
            break;
        case 'Weekly':
            $current_schedule->modify('+1 week');
            break;
        case 'Monthly':
            $current_schedule->modify('+1 month');
            break;
        case 'Once':
            $next_run_datetime_str = NULL;
            $is_active_update = 0;
            break;
        default:
            $current_schedule->modify('+1 day'); 
            break;
    }
    
    if ($schedule_type !== 'Once') {
        $next_run_datetime_str = $current_schedule->format('Y-m-d H:i:s');
        $is_active_update = 1;
    }

    if ($stmt = $conn->prepare("UPDATE scheduled_reports SET last_run_datetime = ?, next_run_datetime = ?, is_active = ? WHERE id = ?")) {
        $last_run = $current_datetime; 
        $next_run = $next_run_datetime_str; 
        $is_active = $is_active_update; 
        
        $stmt->bind_param('ssii', $last_run, $next_run, $is_active, $report_id);
        
        if (!$stmt->execute()) {
            error_log("FATAL: Failed to update next_run_datetime for report ID {$report_id}. Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("FATAL: Failed to prepare update statement for report ID {$report_id}. Error: " . $conn->error);
    }
}

// 5. Final Cleanup
error_log("SUCCESS: Finished processing {$processed_count} scheduled CRM reports.");
if (isset($conn)) {
    $conn->close();
}
?>