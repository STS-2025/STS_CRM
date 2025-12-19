<!-- //<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header("Location: login.php");
//   exit();
// }
?>  -->
<?php
// includes/logout.php

// 1. Ensure the session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Clear all session variables (optional, but good practice)
$_SESSION = array();

// 3. Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy the session
session_destroy();

// 5. Redirect the user to the login page, optionally with a success message flag
header("Location: login.php?status=logged_out"); 
exit();
?>