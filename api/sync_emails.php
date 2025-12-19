
<?php
error_reporting(0);
// sync_emails.php
include 'db.php'; // Unga database connection file

// 1. Sync ON-la irukka users-ah mattum fetch panrom
$sql = "SELECT id, email, email_password FROM users WHERE email_sync = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        $user_id = $user['id'];
        $email_id = $user['email'];
        $app_pass = $user['email_password']; // Gmail App Password

        // Gmail IMAP settings
        $mailbox = "{imap.gmail.com:993/imap/ssl}INBOX";

        // Gmail-oda connect panrom
        $inbox = imap_open($mailbox, $email_id, $app_pass);

        if ($inbox) {
            // Last 2 days-la vandha emails-ah search panrom
            $emails = imap_search($inbox, 'SINCE ' . date("d-M-Y", strtotime("-2 days")));

            if ($emails) {
                foreach ($emails as $msg_no) {
                    $header = imap_headerinfo($inbox, $msg_no);
                    $msg_id = $header->message_id;

                    // Idhu munnadiye database-la irukkannu check panrom
                    $check = $conn->query("SELECT id FROM crm_emails WHERE message_id = '$msg_id'");

                    // ... mela irukka connection and imap logic appadiye irukkatum ...

                    if ($check->num_rows == 0) {
                        $from = $header->from[0]->mailbox . "@" . $header->from[0]->host;
                        $subject = $header->subject;

                        // Fetch body
                        $body = imap_fetchbody($inbox, $msg_no, 1);
                        $date = date("Y-m-d H:i:s", $header->udate);

                        // FIX: Prepared Statement use panni insert panrom
                        // Idhu special characters, quotes ellathayum auto-ah handle pannikollum
                        $stmt = $conn->prepare("INSERT INTO crm_emails (user_id, message_id, sender_email, subject, body, received_at) VALUES (?, ?, ?, ?, ?, ?)");

                        // "isssss" na (integer, string, string, string, string, string) nu artham
                        $stmt->bind_param("isssss", $user_id, $msg_id, $from, $subject, $body, $date);

                        if (!$stmt->execute()) {
                            echo "Error inserting email: " . $stmt->error;
                        }
                        $stmt->close();
                    }

                    // ... bakki code ...
                }
            }
            imap_close($inbox);
        }
    }
}
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Sync Completed!'
]);
exit();
?>