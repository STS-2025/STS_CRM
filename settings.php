<?php 

ob_start(); 

session_start();
$page_title = "Settings";


require 'api/db.php'; 

// --- TAB HANDLING ---
$current_tab = $_GET['tab'] ?? 'general';

// 4. DEFINE HELPER FUNCTION HERE 

if (!function_exists('generate_setting_input')) {
    function generate_setting_input($id, $label, $type = 'text', $value = '', $placeholder = '', $extra_class = '') {
        return '
            <div class="space-y-1 ' . htmlspecialchars($extra_class) . '">
                <label for="' . htmlspecialchars($id) . '" class="text-sm font-medium text-gray-700 block">' . htmlspecialchars($label) . '</label>
                <input type="' . htmlspecialchars($type) . '" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($id) . '" 
                        value="' . htmlspecialchars($value) . '" placeholder="' . htmlspecialchars($placeholder) . '"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        ';
    }
}

// --- DATA FETCHING: Fetch all required settings from the database ---
$current_settings = [];
$sql_fetch = "SELECT setting_key, setting_value FROM settings";
$result_fetch = $conn->query($sql_fetch);

if ($result_fetch) {
    while($row = $result_fetch->fetch_assoc()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
    $result_fetch->free();
}

// --- NEW: Fetch all active users for the Reports Tab (Required for Recipient List) ---
$all_users = [];
$sql_users = "SELECT id, name, email, role, status FROM users WHERE status = 'Active' ORDER BY name ASC";
$result_users = $conn->query($sql_users);

if ($result_users) {
    while($row = $result_users->fetch_assoc()) {
        $row['role'] = strtolower($row['role']); 
        $all_users[] = $row;
    }
    $result_users->free();
}

$users_by_role = [];
foreach ($all_users as $user) {
    $role = ucfirst($user['role']);
    if (!isset($users_by_role[$role])) {
        $users_by_role[$role] = ['count' => 0, 'users' => []];
    }
    $users_by_role[$role]['count']++;
    $users_by_role[$role]['users'][] = $user;
}

// General
$company_name = $current_settings['company_name'] ?? 'STS CRM';
$default_currency = $current_settings['default_currency'] ?? 'INR'; 
$timezone = $current_settings['timezone'] ?? 'Asia/Kolkata'; 
$logo_path = 'assets/images/logo.png'; 
$logo_file_exists = file_exists($logo_path);

// Security
$password_min_length = $current_settings['password_min_length'] ?? 8;
$session_timeout_minutes = $current_settings['session_timeout_minutes'] ?? 60;

// Integrations (New)
$google_maps_api_key = $current_settings['google_maps_api_key'] ?? '';
$slack_webhook_url = $current_settings['slack_webhook_url'] ?? '';

// Email (Updated to include system sender)
$welcome_email_subject = $current_settings['welcome_email_subject'] ?? 'Welcome to the System!';
$system_email_sender = $current_settings['system_email_sender'] ?? 'noreply@default.com'; // <--- NEW SENDER VARIABLE

if (isset($conn)) {
    $conn->close();
}


$toast_script = '';
if (isset($_SESSION['error'])) {
    $error_message = json_encode(htmlspecialchars($_SESSION['error']));
    $toast_script = "<script>document.addEventListener('DOMContentLoaded', function() { showToast({$error_message}, 'error'); });</script>";
    unset($_SESSION['error']);
}
if (isset($_SESSION['message'])) {
    $success_message = json_encode(htmlspecialchars($_SESSION['message']));
    $toast_script = "<script>document.addEventListener('DOMContentLoaded', function() { showToast({$success_message}, 'success'); });</script>";
    unset($_SESSION['message']);
}
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"> 

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">System Settings</h1>
        <p class="text-gray-500 mt-1">Configure global application behavior, branding, and integrations.</p>
    </div>
</div>

<form action="<?= ($current_tab == 'reports') ? 'api/schedule_report_process.php' : 'api/settings_process.php?tab=' . htmlspecialchars($current_tab) ?>" method="POST">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="settings.php?tab=general" class="
                    <?= ($current_tab == 'general') 
                        ? 'border-b-2 border-blue-500 text-blue-600' 
                        : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' 
                    ?> 
                    whitespace-nowrap py-4 px-1 text-sm font-medium" 
                    aria-current="<?= ($current_tab == 'general') ? 'page' : 'false' ?>">
                    General
                </a>
                <a href="settings.php?tab=security" class="
                    <?= ($current_tab == 'security') 
                        ? 'border-b-2 border-blue-500 text-blue-600' 
                        : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' 
                    ?> 
                    whitespace-nowrap py-4 px-1 text-sm font-medium" 
                    aria-current="<?= ($current_tab == 'security') ? 'page' : 'false' ?>">
                    Security
                </a>
                
                <a href="settings.php?tab=integrations" class="
                    <?= ($current_tab == 'integrations') 
                        ? 'border-b-2 border-blue-500 text-blue-600' 
                        : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' 
                    ?> 
                    whitespace-nowrap py-4 px-1 text-sm font-medium" 
                    aria-current="<?= ($current_tab == 'integrations') ? 'page' : 'false' ?>">
                    Integrations
                </a>

                <a href="settings.php?tab=email" class="
                    <?= ($current_tab == 'email') 
                        ? 'border-b-2 border-blue-500 text-blue-600' 
                        : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' 
                    ?> 
                    whitespace-nowrap py-4 px-1 text-sm font-medium" 
                    aria-current="<?= ($current_tab == 'email') ? 'page' : 'false' ?>">
                    Email Templates
                </a>
                
                <a href="settings.php?tab=reports" class="
                    <?= ($current_tab == 'reports') 
                        ? 'border-b-2 border-blue-500 text-blue-600' 
                        : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' 
                    ?> 
                    whitespace-nowrap py-4 px-1 text-sm font-medium" 
                    aria-current="<?= ($current_tab == 'reports') ? 'page' : 'false' ?>">
                    Reports
                </a>
            </nav>
        </div>

        <div class="mt-6 space-y-8 divide-y divide-gray-200">
            
            <?php if ($current_tab == 'general'): ?>
                <div class="pt-8">
                    <h3 class="text-xl font-semibold text-gray-900">Company Branding</h3>
                    <p class="mt-1 text-sm text-gray-500">Update your CRM's displayed name and logo.</p>
                    
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <?php echo generate_setting_input('company_name', 'CRM Name', 'text', $company_name, 'Your Company Name'); ?>
                        </div>

                        <div class="sm:col-span-4">
                            <label class="text-sm font-medium text-gray-700 block">System Logo</label>
                            <div class="mt-1 flex items-center space-x-5">
                                <span class="inline-block h-12 w-12 rounded-lg overflow-hidden bg-gray-100 p-1 flex items-center justify-center" id="current_logo_preview">
                                    <?php if ($logo_file_exists): ?>
                                        <img src="<?= htmlspecialchars($logo_path) ?>?t=<?= time() ?>" alt="Current System Logo" class="h-full w-full object-contain">
                                    <?php else: ?>
                                        <i data-lucide="shield-check" class="h-full w-full text-blue-500"></i>
                                    <?php endif; ?>
                                </span>
                                
                                <form id="logoUploadForm" action="api/settings_logo_process.php" method="POST" enctype="multipart/form-data" style="display:inline;">
                                    <input type="file" id="logo_upload" name="logo_file" accept="image/png, image/jpeg, image/svg+xml" class="hidden">
                                    <button type="button" id="changeLogoButton" class="bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Change
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-8">
                    <h3 class="text-xl font-semibold text-gray-900">Localization</h3>
                    <p class="mt-1 text-sm text-gray-500">Set the default currency and timezone for all reports.</p>
                    
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="default_currency" class="text-sm font-medium text-gray-700 block">Default Currency</label>
                            <select id="default_currency" name="default_currency" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="INR" <?= ($default_currency == 'INR') ? 'selected' : '' ?>>INR (Indian Rupee)</option>
                                <option value="USD" <?= ($default_currency == 'USD') ? 'selected' : '' ?>>USD</option>
                                <option value="EUR" <?= ($default_currency == 'EUR') ? 'selected' : '' ?>>EUR</option>
                                <option value="GBP" <?= ($default_currency == 'GBP') ? 'selected' : '' ?>>GBP</option>
                            </select>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="timezone" class="text-sm font-medium text-gray-700 block">Timezone</label>
                            <select id="timezone" name="timezone" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="Asia/Kolkata" <?= ($timezone == 'Asia/Kolkata') ? 'selected' : '' ?>>Asia/Kolkata (Chennai, Mumbai, IST)</option>
                                <option value="UTC" <?= ($timezone == 'UTC') ? 'selected' : '' ?>>UTC</option>
                                <option value="Europe/London" <?= ($timezone == 'Europe/London') ? 'selected' : '' ?>>Europe/London</option>
                                <option value="America/New_York" <?= ($timezone == 'America/New_York') ? 'selected' : '' ?>>America/New_York</option>
                            </select>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($current_tab == 'security'): ?>
                <div class="pt-8">
                    <h3 class="text-xl font-semibold text-gray-900">Authentication & Passwords</h3>
                    <p class="mt-1 text-sm text-gray-500">Control user password policies and session limits.</p>
                    
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <?php echo generate_setting_input('password_min_length', 'Minimum Password Length', 'number', $password_min_length, 'e.g., 8'); ?>
                            <p class="mt-2 text-xs text-gray-500">Set the minimum required characters for new user passwords.</p>
                        </div>

                        <div class="sm:col-span-3">
                            <?php echo generate_setting_input('session_timeout_minutes', 'Session Timeout (minutes)', 'number', $session_timeout_minutes, 'e.g., 60'); ?>
                            <p class="mt-2 text-xs text-gray-500">Users will be logged out automatically after this period of inactivity.</p>
                        </div>
                    </div>
                </div>
            
            <?php elseif ($current_tab == 'integrations'): ?>
                <div class="pt-8">
                    <h3 class="text-xl font-semibold text-gray-900">External Services</h3>
                    <p class="mt-1 text-sm text-gray-500">Connect to third-party services like maps and notifications.</p>
                    
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <?php echo generate_setting_input('google_maps_api_key', 'Google Maps API Key', 'text', $google_maps_api_key, 'AIzaSy...XYZ'); ?>
                            <p class="mt-2 text-xs text-gray-500">Required for displaying location maps on contact pages.</p>
                        </div>
                        <div class="sm:col-span-4">
                            <?php echo generate_setting_input('slack_webhook_url', 'Slack Webhook URL', 'url', $slack_webhook_url, 'https://hooks.slack.com/services/...'); ?>
                            <p class="mt-2 text-xs text-gray-500">Used for sending immediate internal notifications about critical events.</p>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($current_tab == 'email'): ?>
                <div class="pt-8">
                    <h3 class="text-xl font-semibold text-gray-900">System Email Configuration</h3>
                    <p class="mt-1 text-sm text-gray-500">Configure the default sender and template for system-generated emails.</p>
                    
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <?php echo generate_setting_input('system_email_sender', 'Default Sender Email (FROM)', 'email', $system_email_sender, 'e.g., noreply@yourcompany.com'); ?>
                            <p class="mt-2 text-xs text-gray-500">This address will appear as the sender for all automated reports and system notifications (like password resets). This is the address that `send_daily_summary.php` will use.</p>
                        </div>
                        
                        <div class="sm:col-span-6 border-t pt-6 mt-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-3">Welcome Email Template</h4>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <?php echo generate_setting_input('welcome_email_subject', 'Email Subject', 'text', $welcome_email_subject, 'e.g., Your account is ready!'); ?>
                        </div>
                        <div class="sm:col-span-6">
                            <label for="welcome_email_body" class="text-sm font-medium text-gray-700 block">Email Body (HTML/Text)</label>
                            <textarea id="welcome_email_body" name="welcome_email_body" rows="10" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= htmlspecialchars($current_settings['welcome_email_body'] ?? 'Dear [Name], Welcome to [Company Name]! Your journey starts here.') ?></textarea>
                            <p class="mt-2 text-xs text-gray-500">Use bracketed variables like `[Name]` and `[Company Name]` for dynamic content.</p>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($current_tab == 'reports'):?>
                <div class="pt-8">
                    <h3 class="text-xl font-semibold text-gray-900">Create New Scheduled Report</h3>
                    <p class="mt-1 text-sm text-gray-500">Design a custom summary report, select recipients, and set the schedule.</p>
                    
                    <div class="mt-6 space-y-8">
                                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 pt-8 border-t border-gray-200">
                            <div class="sm:col-span-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-3">Recipients Selection (Request 1 & 2)</h4>
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label for="recipient_type_filter" class="text-sm font-medium text-gray-700 block">Filter Users By Role</label>
                                <select id="recipient_type_filter" name="recipient_type_filter" class="mt-1 block w-full max-w-xs border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="All" selected>All Active Users</option>
                                    <?php 
                                    $unique_roles = array_keys($users_by_role);
                                    foreach ($unique_roles as $role): ?>
                                        <option value="<?= htmlspecialchars(strtolower($role)) ?>"><?= htmlspecialchars(ucfirst($role)) ?> Only</option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-2 text-xs text-gray-500">Selecting a role will filter the table and **automatically select all users in that role**.</p>
                            </div>
                            
                            <div class="sm:col-span-6 bg-gray-50 p-4 rounded-lg mt-4">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Currently Selected Recipients (Summary)</p>
                                <ul class="flex space-x-4 flex-wrap text-sm text-gray-600" id="recipient_summary_list">
                                    <?php 
                                    $summary_roles = array_map('strtolower', array_keys($users_by_role)); 
                                    foreach ($summary_roles as $role): ?>
                                        <li class="inline-flex items-center">
                                            <i data-lucide="user" class="h-4 w-4 mr-1 text-blue-500"></i>
                                            <span class="font-medium text-gray-900" id="summary_<?= htmlspecialchars($role) ?>_count">0</span> <?= htmlspecialchars(ucfirst($role)) ?>
                                        </li>
                                    <?php endforeach; ?>
                                    <li class="inline-flex items-center ml-auto font-bold text-gray-800">
                                        Total Users: <span class="ml-1" id="total_recipients_count">0</span>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="sm:col-span-6 mt-4">
                                <label class="text-sm font-medium text-gray-700 block mb-2">Users to receive the report</label>
                                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200" id="user_list_table">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                                    <input id="select_all_users" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (!empty($all_users)): ?>
                                                <?php foreach ($all_users as $user): ?>
                                                <tr data-user-role="<?= htmlspecialchars($user['role']) ?>">
                                                    <td class="px-6 py-4 whitespace-nowrap w-12">
                                                        <input name="recipient_user_ids[]" type="checkbox" value="<?= (int)$user['id'] ?>" 
                                                               class="user-select-checkbox focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                                               data-role="<?= htmlspecialchars($user['role']) ?>">
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= (int)$user['id'] ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium"><?= htmlspecialchars($user['status']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No active users found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 pt-8 border-t border-gray-200">
                            <div class="sm:col-span-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-3">Scheduling Options (Request 3)</h4>
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label class="text-sm font-medium text-gray-700 block mb-2">Frequency</label>
                                <div class="flex space-x-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="schedule_frequency" value="Daily" checked class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700">Daily</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="schedule_frequency" value="Weekly" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700">Weekly</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="schedule_frequency" value="Monthly" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700">Monthly</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="schedule_frequency" value="Once" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                        <span class="ml-2 text-sm text-gray-700">Once</span>
                                    </label>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="schedule_start_date" class="text-sm font-medium text-gray-700 block">Start Date</label>
                                <input type="date" id="schedule_start_date" name="schedule_start_date" value="<?= date('Y-m-d') ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="schedule_time_hh" class="text-sm font-medium text-gray-700 block">Send Time</label>
                                <div class="mt-1 flex space-x-2">
                                    <select id="schedule_time_hh" name="schedule_time_hh" class="block w-1/2 border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm">
                                        <?php for($i=1; $i<=12; $i++): ?>
                                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= ($i == 9) ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select id="schedule_time_mm" name="schedule_time_mm" class="block w-1/2 border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm">
                                        <option value="00" selected>00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                    <select id="schedule_time_ampm" name="schedule_time_ampm" class="block w-1/3 border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm">
                                        <option value="AM" <?= (date('A') == 'AM') ? 'selected' : '' ?>>AM</option>
                                        <option value="PM" <?= (date('A') == 'PM') ? 'selected' : '' ?>>PM</option>
                                    </select>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Timezone: **<?= htmlspecialchars($timezone) ?>**</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-3">Email Content (Request 4)</h4>
                                <?php echo generate_setting_input('report_name', 'Report Name (Internal)', 'text', '', 'e.g., Weekly Sales Summary'); ?>
                            </div>
                            <div class="sm:col-span-6">
                                <?php echo generate_setting_input('report_subject', 'Email Subject', 'text', '', 'e.g., Your Weekly Performance Report'); ?>
                            </div>
                            <div class="sm:col-span-6">
                                <label for="report_body" class="text-sm font-medium text-gray-700 block">Email Body / Template (HTML/Text)</label>
                                <textarea id="report_body" name="report_body" rows="8" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">Hi [Recipient Name],

Here is your summary report:

[NEW_LEADS_COUNT] new leads were created today.

[DEALS_CLOSED_COUNT] deals were closed today.

Thank you.</textarea>
                                <p class="mt-2 text-xs text-gray-500">Use bracketed placeholders like `[NEW_LEADS_COUNT]` and dynamic user variables like `[Recipient Name]`.</p>
                            </div>
                            
                            <div class="sm:col-span-3 flex justify-start space-x-3">
                                <button type="button" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Preview Email (To Self)
                                </button>
                                <button type="submit" name="send_now" value="1" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Send Now (Test Run)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; // END OF REPORTS TAB ?>
            
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
</div>

<?php
// Inject the toast script if a message is set
echo $toast_script;
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- LOGO UPLOAD SCRIPT ---
        const fileInput = document.getElementById('logo_upload');
        const changeButton = document.getElementById('changeLogoButton');
        const uploadForm = document.getElementById('logoUploadForm');
        const logoPreviewContainer = document.getElementById('current_logo_preview');

        if (fileInput && changeButton && uploadForm) {
            changeButton.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', function(event) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        logoPreviewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" class="h-full w-full object-contain">`;
                        uploadForm.submit();
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        } 
        
        // --- REPORTS TAB: DYNAMIC LOGIC ---
        const selectAllCheckbox = document.getElementById('select_all_users');
        const roleFilter = document.getElementById('recipient_type_filter');
        const userRows = document.querySelectorAll('#user_list_table tbody tr[data-user-role]');
        const userCheckboxes = document.querySelectorAll('.user-select-checkbox');
        const totalRecipientsCount = document.getElementById('total_recipients_count');

        /**
         * Updates all summary counts based on checked boxes.
         */
        function updateRecipientSummary() {
            let totalChecked = 0;
            const roleCounts = {};

            // 1. Get all available summary span IDs (dynamic based on what's in the DOM)
            const summarySpans = document.querySelectorAll('[id^="summary_"][id$="_count"]');
            summarySpans.forEach(span => {
                const role = span.id.replace('summary_', '').replace('_count', '');
                roleCounts[role] = 0; // Initialize
            });

            // 2. Count checked users
            userCheckboxes.forEach(cb => {
                if (cb.checked) {
                    totalChecked++;
                    const role = cb.getAttribute('data-role').toLowerCase();
                    if (roleCounts.hasOwnProperty(role)) {
                        roleCounts[role]++;
                    }
                }
            });

            // 3. Update DOM
            totalRecipientsCount.textContent = totalChecked;
            for (const role in roleCounts) {
                const el = document.getElementById(`summary_${role}_count`);
                if (el) el.textContent = roleCounts[role];
            }
        }

        /**
         * Filters the table and handles auto-selection.
         */
        function handleFilter(selectedRole) {
            const isAll = selectedRole === 'All';
            const lowerSelectedRole = selectedRole.toLowerCase();

            userRows.forEach(row => {
                const rowRole = row.getAttribute('data-user-role').toLowerCase();
                const checkbox = row.querySelector('.user-select-checkbox');

                if (isAll) {
                    row.style.display = ''; // Show all
                    // We don't auto-select when 'All' is chosen, user does it manually
                } else if (rowRole === lowerSelectedRole) {
                    row.style.display = ''; // Show matches
                    if (checkbox) checkbox.checked = true; // AUTO-SELECT
                } else {
                    row.style.display = 'none'; // Hide others
                    if (checkbox) checkbox.checked = false; // Deselect hidden
                }
            });

            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            updateRecipientSummary();
        }

        // --- Event Listeners ---
        if (roleFilter) {
            roleFilter.addEventListener('change', (e) => handleFilter(e.target.value));
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                userRows.forEach(row => {
                    if (row.style.display !== 'none') {
                        const cb = row.querySelector('.user-select-checkbox');
                        if (cb) cb.checked = this.checked;
                    }
                });
                updateRecipientSummary();
            });
        }

        userCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateRecipientSummary);
        });

        // Initialize on load
        if (roleFilter) handleFilter(roleFilter.value);
    });
</script>

<?php
// 5. Capture the content
$page_content = ob_get_clean();

// 6. Include the master layout file (assuming this file exists)
include 'includes/layout.php'; 
?>