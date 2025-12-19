<?php
// Get the current page filename to set the active link state
$current_page = basename($_SERVER['PHP_SELF']);

$dashboard_page = 'dashboard.php';
if (isset($_SESSION['role']) && $_SESSION['role'] != 'admin') {
    $dashboard_page = 'user_dash.php';
}
$settings_page = 'settings.php';
if (isset($_SESSION['role']) && $_SESSION['role'] != 'admin') {
    $settings_page = 'user_settings.php';
}

// Define a common link class for hover/focus states
$link_base_class = "flex items-center gap-3 px-6 py-2 rounded-lg transition-all duration-200 ease-in-out text-gray-700";
$link_hover_class = "hover:bg-blue-100 hover:text-blue-800";
$link_active_class = "bg-blue-600 text-white font-semibold shadow-md"; // Primary Blue for active state

/**
 * Function to conditionally return the active class
 */
function get_active_class($page_file, $current) {
    global $link_base_class, $link_hover_class, $link_active_class;
    
    $is_active = ($current === $page_file);
    
    $classes = $link_base_class;
    
    if ($is_active) {
        // If active, use the strong blue style and ensure text is white
        $classes = str_replace("text-gray-700", "text-white", $classes);
        $classes .= " " . $link_active_class;
    } else {
        // If not active, use hover style
        $classes .= " " . $link_hover_class;
    }
    
    return $classes;
}
?>

<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-gray-50 text-gray-700 shadow-xl flex flex-col z-40 
             lg:translate-x-0 lg:fixed 
             -translate-x-full transition-transform duration-300 ease-in-out border-r border-gray-200">

    <div class="p-5 flex items-center justify-center border-b border-gray-200 bg-white">
        <img src="./assets/images/logo.png" alt="STS Logo" class="w-10 h-10 mr-3 rounded-full object-cover">
        <h1 class="text-2xl font-bold text-gray-900 tracking-wider">STS CRM</h1>
    </div>

    <nav class="flex-1 overflow-y-auto py-5 px-4 space-y-4">
        
        <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 px-2 mt-4">Core</h3>
        <ul class="space-y-1">
            <li>
                <a href="<?= $dashboard_page ?>" class="<?php echo get_active_class($dashboard_page, $current_page); ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 px-2 mt-4">Sales</h3>
        <ul class="space-y-1">
            <li>
                <a href="companies.php" class="<?php echo get_active_class('companies.php', $current_page); ?>">
                    <i data-lucide="building-2" class="w-5 h-5"></i> <span>Companies</span>
                </a>
            </li>
            <li>
                <a href="contacts.php" class="<?php echo get_active_class('contacts.php', $current_page); ?>">
                    <i data-lucide="contact" class="w-5 h-5"></i> <span>Contacts</span>
                </a>
            </li>
            <li>
                <a href="deals.php" class="<?php echo get_active_class('deals.php', $current_page); ?>">
                    <i data-lucide="handshake" class="w-5 h-5"></i> <span>Deals</span>
                </a>
            </li>
            <li>
                <a href="meetings.php" class="<?php echo get_active_class('meetings.php', $current_page); ?>">
                    <i data-lucide="calendar" class="w-5 h-5"></i> <span>Meetings</span>
                </a>
            </li>
            <li>
                <a href="tasks.php" class="<?php echo get_active_class('tasks.php', $current_page); ?>">
                    <i data-lucide="check-square" class="w-5 h-5"></i> <span>Tasks</span>
                </a>
            </li>
        </ul>
        
        <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 px-2 mt-4">Customer Success</h3>
        <ul class="space-y-1">
            <li>
                <a href="customers.php" class="<?php echo get_active_class('customers.php', $current_page); ?>">
                    <i data-lucide="award" class="w-5 h-5"></i> <span>Customer Details</span>
                </a>
            </li>
        </ul>

        <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 px-2 mt-4">Marketing</h3>
        <ul class="space-y-1">
            <li>
                <a href="marketing.php" class="<?php echo get_active_class('marketing.php', $current_page); ?>">
                    <i data-lucide="megaphone" class="w-5 h-5"></i> <span>Campaigns</span>
                </a>
            </li>
            <li>
                <a href="leads.php" class="<?php echo get_active_class('leads.php', $current_page); ?>">
                    <i data-lucide="users" class="w-5 h-5"></i> <span>Leads</span>
                </a>
            </li>
        </ul>
        
        <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 px-2 mt-4">Admin</h3>
        <ul class="space-y-1">
            <li>
                <a href="projects.php" class="<?php echo get_active_class('projects.php', $current_page); ?>">
                    <i data-lucide="folder-open" class="w-5 h-5"></i> <span>Projects</span>
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo get_active_class('users.php', $current_page); ?>">
                    <i data-lucide="user-circle" class="w-5 h-5"></i> <span>Users & Teams</span>
                </a>
            </li>
            <li>
                <a href="<?= $settings_page ?>" class="<?php echo get_active_class($settings_page, $current_page); ?>">
                    <i data-lucide="settings" class="w-5 h-5"></i> <span>Settings</span>
                </a>
            </li>
        </ul>

    </nav>

    <div class="p-4 border-t border-gray-200 text-center bg-white">
        <a href="logout.php" class="flex items-center justify-center gap-2 bg-red-600 text-white py-2 rounded-lg 
                                    hover:bg-red-700 transition font-medium">
            <i data-lucide="log-out" class="w-5 h-5"></i> <span>Logout</span>
        </a>
    </div>
</aside>