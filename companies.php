<?php 
// companies.php

// 1. Start capturing the output buffer and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Companies";

// 3. Include necessary files
// include 'includes/auth_check.php'; // Uncomment when ready to enforce login
include 'api/db.php'; // Includes the database connection

// --- PAGINATION SETUP ---
$limit = 10; // Companies per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// 1. Get total number of companies
$count_sql = "SELECT COUNT(id) AS total FROM companies";
$count_result = $conn->query($count_sql);
$total_companies = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_companies / $limit);
// ------------------------

// 4. DATA FETCHING LOGIC (Paginated)
$company_rows_html = '';

// Fetch companies for the current page, joining with users to get the owner name
$sql = "SELECT c.id, c.name, c.industry, c.total_deals, c.latest_activity, u.name AS owner_name 
        FROM companies c
        JOIN users u ON c.owner_id = u.id 
        ORDER BY c.name ASC 
        LIMIT $start, $limit";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $activity_display = $row['latest_activity'] ? date('M d, Y', strtotime($row['latest_activity'])) : 'N/A';
        
        $delete_button = '';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $delete_button = '
            <a href="javascript:void(0);" 
            onclick="deleteCompanies(' . (int)$row['id'] . ')" 
            class="text-red-600 hover:text-red-900">Delete</a>';
        }
        $company_rows_html .= '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 hover:text-blue-800 cursor-pointer">' . htmlspecialchars($row['name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['industry']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['owner_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">' . htmlspecialchars($row['total_deals']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . $activity_display . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="edit_company.php?id=' . $row['id'] . '" class="text-indigo-600 hover:text-indigo-900">Edit</a> '. $delete_button . '
                </td>
            </tr>
        ';
    }
} else {
    $colspan = 6; 
    $company_rows_html = '<tr><td colspan="'.$colspan.'" class="px-6 py-4 text-center text-gray-500">No companies found. Click "Add Company" to get started.</td></tr>';
}

// Close the database connection
$conn->close();

// Check for and store session message before outputting HTML
$toast_message = '';
$toast_type = 'success'; 
if (isset($_SESSION['message'])) {
    $toast_message = $_SESSION['message'];
    if (str_contains($toast_message, 'Error') || str_contains($toast_message, 'Database Error')) {
        $toast_type = 'error';
    }
    unset($_SESSION['message']); // Clear the message after retrieval
}
?>

<div id="toast-message" aria-live="polite" aria-atomic="true" class="hidden fixed top-4 right-4 z-50 w-full max-w-xs">
    <div class="bg-green-500 text-white rounded-lg shadow-xl p-4 transition-opacity duration-300 flex items-center justify-between" role="alert">
        <div id="toast-text"></div>
        <button type="button" class="ml-4 text-white hover:text-gray-100" onclick="closeToast()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
</div>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Companies</h1>
        <p class="text-gray-500 mt-1">Manage all organizational accounts and associated details.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <button class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="filter" class="w-4 h-4 inline mr-1"></i> Filters
        </button>
        <a href="create_company.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Add Company
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Deals</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latest Activity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php echo $company_rows_html; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <?php
        $end = min($start + $limit, $total_companies);
        $start_display = $total_companies > 0 ? $start + 1 : 0;
        ?>
        <span>Showing <?php echo $start_display; ?> to <?php echo $end; ?> of <?php echo $total_companies; ?> companies</span>
        
        <div class="space-x-1">
            <?php if ($page > 1): ?>
                <a href="companies.php?page=<?php echo $page - 1; ?>" class="px-3 py-1 border rounded-lg hover:bg-gray-100">Previous</a>
            <?php else: ?>
                <button disabled class="px-3 py-1 border rounded-lg text-gray-400 cursor-not-allowed">Previous</button>
            <?php endif; ?>

            <?php 
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $page) {
                    echo '<button class="px-3 py-1 border rounded-lg bg-blue-500 text-white">'.$i.'</button>';
                } else {
                    echo '<a href="companies.php?page='.$i.'" class="px-3 py-1 border rounded-lg hover:bg-gray-100">'.$i.'</a>';
                }
            }
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="companies.php?page=<?php echo $page + 1; ?>" class="px-3 py-1 border rounded-lg hover:bg-gray-100">Next</a>
            <?php else: ?>
                <button disabled class="px-3 py-1 border rounded-lg text-gray-400 cursor-not-allowed">Next</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteCompanies(companyId) {
    if (confirm("Are you sure you want to delete this company?")) {
        window.location.href = 'api/delete_companies.php?id=' + companyId;
    }
}

// ----------------------
// TOAST NOTIFICATION LOGIC (Copied from contacts.php)
// ----------------------
const toastEl = document.getElementById('toast-message');
const toastTextEl = document.getElementById('toast-text');
const sessionMessage = "<?php echo $toast_message; ?>";
const sessionType = "<?php echo $toast_type; ?>"; 

function closeToast() {
    toastEl.classList.add('hidden');
    if (window.toastTimeout) {
        clearTimeout(window.toastTimeout);
    }
}

function showToast(message, type = 'success', duration = 5000) {
    if (!message) return;

    let bgColor = (type === 'error') ? 'bg-red-500' : 'bg-green-500';

    const innerToast = toastEl.querySelector('div');
    innerToast.className = 'text-white rounded-lg shadow-xl p-4 transition-opacity duration-300 flex items-center justify-between ' + bgColor;

    toastTextEl.textContent = message;
    toastEl.classList.remove('hidden');

    window.toastTimeout = setTimeout(closeToast, duration);
}

if (sessionMessage.length > 0) {
    showToast(sessionMessage, sessionType); 
}
</script>

<?php
// 4. Capture the content
$page_content = ob_get_clean();

// 5. Include the master layout file
include 'includes/layout.php'; 
?>