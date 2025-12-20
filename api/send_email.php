<?php
// api/send_email.php

// 1. Session-ai top-la start pannunga
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
include 'db.php';
// Inga dhaan logic irukku: Idhu ippo user-oda credentials-ai fetch pannum
include 'smtp_config.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic validation
    if (empty($_POST['to_email']) || empty($_POST['subject']) || empty($_POST['message'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    $to = $_POST['to_email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $mail = new PHPMailer(true);

    try {
        // Server Settings
        $mail->isSMTP();
        $mail->Host       = $mail_host;      // Dynamic from smtp_config
        $mail->SMTPAuth   = true;
        $mail->Username   = $mail_username;  // Dynamic from smtp_config
        $mail->Password   = $mail_password;  // Dynamic from smtp_config
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mail_port;

        // Recipients
        $mail->setFrom($mail_from, $mail_from_name);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message);

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Mail Error: {$mail->ErrorInfo}"]);
    }
}
?>