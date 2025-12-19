<?php
// layout.php - Common template for all authenticated pages

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) session_start(); 

// OPTIONAL: Basic redirect if not logged in
/*
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
*/

// $page_title should be set by the page including this layout (e.g., "Contacts", "Dashboard")
$page_title = $page_title ?? "STS CRM"; 

// --- TOAST/FLASH MESSAGE LOGIC ---
$flash_type = null;
$flash_message = null;
$flash_class = null;

if (isset($_SESSION['error'])) {
    $flash_type = 'Error';
    $flash_message = $_SESSION['error'];
    $flash_class = 'bg-red-100 border-red-400 text-red-700';
    unset($_SESSION['error']);
} elseif (isset($_SESSION['message'])) {
    $flash_type = 'Success';
    $flash_message = $_SESSION['message'];
    $flash_class = 'bg-green-100 border-green-400 text-green-700';
    unset($_SESSION['message']);
}
// ---------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | STS CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Sets the main background color for a clean, light look */
        body {
            background-color: #f7f9fc; 
        }
        /* Custom CSS for fade effect */
        .fade-out {
            opacity: 0;
            transition: opacity 1s ease-out;
        }
    </style>
</head>
<body class="min-h-screen">

    <?php include 'sidebar.php'; ?>

    <?php include 'header.php'; ?>

    <main class="lg:pl-72 pt-32 p-4">
        
        <?php echo $page_content ?? "Content not defined."; ?>

    </main>

    <?php
    // DISPLAY THE TOAST MESSAGE HERE, using the updated top-right position
    if ($flash_message) {
    ?>
        <div id="flash-message" 
             class="fixed top-4 right-4 z-50 p-4 max-w-sm 
                    <?= $flash_class ?> border-l-4 rounded-lg shadow-2xl transition duration-300 ease-out">
            <p class="font-bold text-sm"><?= htmlspecialchars($flash_type) ?> Notification</p>
            <p class="text-xs mt-1"><?= htmlspecialchars($flash_message) ?></p>
        </div>
    <?php
    }
    ?>
        <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
        
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('mobile-menu-toggle');
            const flashMessage = document.getElementById('flash-message');

            if (toggleButton && sidebar) {
                toggleButton.addEventListener('click', () => {
                    sidebar.classList.toggle('-translate-x-full');
                });
            }

            // Automatic message dismissal (5 seconds)
            if (flashMessage) {
                setTimeout(() => {
                    flashMessage.classList.add('fade-out');
                    // Remove the element from the DOM after the fade transition
                    setTimeout(() => {
                        flashMessage.remove();
                    }, 1000); // Matches the 'transition' duration in CSS
                }, 5000); // 5 seconds display time
            }
        });
    </script>
</body>
</html>