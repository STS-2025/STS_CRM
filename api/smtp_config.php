<?php
// smtp_config.php (DYNAMIC VERSION)

// 1. Session check (User ID edukkura kaga)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php'; // Database connection compulsory

// 2. Default Values (Oru velai user settings-la update pannala na idhu use aagum)
$mail_host = "smtp.gmail.com";
$mail_port = 587;
$mail_username = "mariselvam44559@gmail.com";
$mail_password = "dzfc chqv zpnu nxft"; 
$mail_from = "mariselvam44559@gmail.com";
$mail_from_name = "CRM";

// 3. Logged-in user details-ai database-la irundhu fetch pannuvom
if (isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name,email, email_password FROM users WHERE id = ?");
    $stmt->bind_param("i", $u_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user_smtp = $res->fetch_assoc();

    // 4. User settings-la data irundha, adhai replace pannuvom
    if (!empty($user_smtp['email']) && !empty($user_smtp['email_password'])) {
        $mail_username = $user_smtp['email'];
        $mail_password = $user_smtp['email_password'];
        $mail_from = $user_smtp['email'];
        $mail_from_name = $user_smtp['name'];
    }
}
?>