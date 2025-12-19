<?php
// api/send_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
include 'db.php';
include 'smtp_config.php'; // Inga dhaan unga variables irukku
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $to = $_POST['to_email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $mail = new PHPMailer(true);

    try {
        // Server Settings (Using variables from smtp_config.php)
        $mail->isSMTP();
        $mail->Host       = $mail_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $mail_username;
        $mail->Password   = $mail_password; 
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
        echo json_encode(['status' => 'success', 'message' => 'Email sent!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Error: {$mail->ErrorInfo}"]);
    }
}
?>