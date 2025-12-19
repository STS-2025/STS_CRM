<?php 
// customers.php

// 1. Start capturing the output buffer
ob_start(); 

// 2. Set the specific page title for the layout
$page_title = "Customer Details";

// 3. Include necessary files (auth check and DB connection)
// NOTE: Adjust paths if files are located differently.
// include 'includes/auth_check.php'; 
include 'api/db.php'; // Includes the database connection

// --- PAGINATION SETUP ---
$limit = 10; // Customers per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// 1. Get total number of customers
$count_sql = "SELECT COUNT(id) AS total FROM customers";
$count_result = $conn->query($count_sql);
$total_customers = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_customers / $limit);
// ------------------------

// 4. DEFINE HELPER FUNCTION HERE 
if (!function_exists('generate_customer_row')) {
    /**
     * Generates a single table row with Edit/Delete links.
     * @param int $id The customer ID, crucial for action links.
     */
    function generate_customer_row($id, $name, $company, $tier, $status, $renewal_date, $arr_value) {
        // Determine status badge color
        $status_class = match ($status) {
            'Active' => 'bg-green-100 text-green-800',
            'Churn Risk' => 'bg-red-100 text-red-800',
            'Onboarding' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">' . htmlspecialchars($name) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800 cursor-pointer">' . htmlspecialchars($company) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-purple-600">' . htmlspecialchars($tier) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">'
                        . htmlspecialchars($status) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($renewal_date) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-bold">' . htmlspecialchars($arr_value) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="edit_customer.php?id=' . $id . '" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                    <a href="javascript:void(0);" onclick="deleteCustomer(' . $id . ')" class="text-red-600 hover:text-red-900">Delete</a>
                </td>
            </tr>
        ';
    }
}

// 5. DATA FETCHING LOGIC (Paginated)
$customer_rows_html = '';

// Fetch customers for the current page
$sql = "SELECT id, name, company, tier, status, renewal_date, arr_value FROM customers ORDER BY company ASC LIMIT $start, $limit";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Format values for display
        $arr_display = '$' . number_format($row['arr_value'], 0);
        $renewal_display = $row['renewal_date'] ? date('Y-m-d', strtotime($row['renewal_date'])) : 'N/A';
        
        // Generate the table row using the fetched data, passing ID
        $customer_rows_html .= generate_customer_row(
            $row['id'], 
            $row['name'], 
            $row['company'], 
            $row['tier'], 
            $row['status'], 
            $renewal_display, 
            $arr_display
        );
    }
} else {
    // Show this if no customers are found on the current page or total
    $colspan = 7; 
    $customer_rows_html = '<tr><td colspan="'.$colspan.'" class="px-6 py-4 text-center text-gray-500">No customer records found. Click "Add Account" to get started.</td></tr>';
}

// Close the database connection
$conn->close();

?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Customer Details</h1>
        <p class="text-gray-500 mt-1">Manage all active accounts, subscription tiers, and renewal dates.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <button onclick="toggleHealthScoreView()" 
            class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="gauge" class="w-4 h-4 inline mr-1"></i> Health Score View
        </button>
        <a href="add_customer.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                            bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Add Account
        </a>
    </div>
</div>
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Primary Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renewal Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ARR</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php echo $customer_rows_html; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <?php
        $end = min($start + $limit, $total_customers);
        $start_display = $total_customers > 0 ? $start + 1 : 0;
        ?>
        <span>Showing <?php echo $start_display; ?> to <?php echo $end; ?> of <?php echo $total_customers; ?> accounts</span>
        
        <div class="space-x-1">
            <?php if ($page > 1): ?>
                <a href="customers.php?page=<?php echo $page - 1; ?>" class="px-3 py-1 border rounded-lg hover:bg-gray-100">Previous</a>
            <?php else: ?>
                <button disabled class="px-3 py-1 border rounded-lg text-gray-400 cursor-not-allowed">Previous</button>
            <?php endif; ?>

            <?php 
            // Simple display of surrounding pages
            $range = 2; // Show 2 pages before and after current
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $page || ($i >= $page - $range && $i <= $page + $range) || $i == 1 || $i == $total_pages) {
                    if ($i == $page) {
                        echo '<button class="px-3 py-1 border rounded-lg bg-blue-500 text-white">'.$i.'</button>';
                    } else if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                        echo '<a href="customers.php?page='.$i.'" class="px-3 py-1 border rounded-lg hover:bg-gray-100">'.$i.'</a>';
                    }
                } else if ($i == $page - $range - 1 || $i == $page + $range + 1) {
                    echo '<span class="px-3 py-1 text-gray-500">...</span>';
                }
            }
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="customers.php?page=<?php echo $page + 1; ?>" class="px-3 py-1 border rounded-lg hover:bg-gray-100">Next</a>
            <?php else: ?>
                <button disabled class="px-3 py-1 border rounded-lg text-gray-400 cursor-not-allowed">Next</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * JavaScript function for the Health Score View button click.
 */
function toggleHealthScoreView() {
    alert("Switching to Health Score View (functionality to be implemented with AJAX/Database fetching).");
    // Example: Toggle a class for visual change
    const tableBody = document.querySelector('.bg-white.divide-y.divide-gray-200');
    tableBody.classList.toggle('view-health-score-active');
}

/**
 * JavaScript function to handle the Delete action.
 * Redirects to the API endpoint for processing upon confirmation.
 */
function deleteCustomer(customerId) {
    if (confirm("Are you sure you want to delete this customer? This action cannot be undone.")) {
        // Redirect to a PHP script that handles the deletion
        window.location.href = 'api/delete_customer.php?id=' + customerId;
    }
}
</script>

<?php
// 6. Capture the content and include layout
$page_content = ob_get_clean();

// NOTE: Adjust the path based on your file structure
include 'includes/layout.php'; 
?>