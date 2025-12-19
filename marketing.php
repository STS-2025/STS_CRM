<?php 
// marketing.php

// 1. Start capturing the output buffer and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Campaigns";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php'; 

/**
 * Helper function to generate a table row for a campaign.
 */
if (!function_exists('generate_campaign_row')) {
    function generate_campaign_row($id, $name, $goal, $start_date, $end_date, $budget, $status) {
        // Determine status badge color
        $status_class = match ($status) {
            'Running' => 'bg-green-100 text-green-800',
            'Planned' => 'bg-blue-100 text-blue-800',
            'Completed' => 'bg-gray-100 text-gray-800',
            default => 'bg-red-100 text-red-800',
        };

        // Format budget as currency
        $formatted_budget = '$' . number_format($budget, 2);

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">' . htmlspecialchars($name) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($goal) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($start_date) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($end_date) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">' . htmlspecialchars($formatted_budget) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">'
                        . htmlspecialchars($status) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="analyze_campaign.php?id=' . $id . '" class="text-indigo-600 hover:text-indigo-900 ml-2">Analyze</a>
                </td>
            </tr>
        ';
    }
}


// --- 4. DATA FETCHING AND PAGINATION LOGIC ---

// 1. Define pagination variables
$limit = 10; // Campaigns per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// 2. Fetch Total Count
$count_result = $conn->query("SELECT COUNT(*) AS total FROM campaigns");
$total_campaigns = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_campaigns / $limit);

// 3. Calculate OFFSET and adjust page if necessary
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
if ($offset < 0) $offset = 0; // Prevent negative offset if $total_campaigns is 0

// 4. Build the SQL query with LIMIT and OFFSET
$campaigns = []; 
$campaign_rows = '';
$sql = "
    SELECT 
        id, name, goal, start_date, end_date, budget, status
    FROM campaigns
    ORDER BY start_date DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($campaign = $result->fetch_assoc()) {
        $campaigns[] = $campaign; 
        
        $campaign_rows .= generate_campaign_row(
            $campaign['id'],
            $campaign['name'], 
            $campaign['goal'], 
            $campaign['start_date'], 
            $campaign['end_date'], 
            $campaign['budget'], 
            $campaign['status']
        );
    }
}
$conn->close();

// 5. Calculate display range for the summary line
$current_page_count = count($campaigns);
$start_item = $current_page_count > 0 ? $offset + 1 : 0;
$end_item = $current_page_count > 0 ? $offset + $current_page_count : 0;

?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Marketing Campaigns</h1>
        <p class="text-gray-500 mt-1">Track performance, budgets, and lead generation for all marketing initiatives.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <!-- <button class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="bar-chart-2" class="w-4 h-4 inline mr-1"></i> Reports
        </button> -->
        <a href="campaign_reports.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
    <i data-lucide="bar-chart-2" class="w-4 h-4 inline mr-1"></i> Reports
</a>
        <a href="create_campaign.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> New Campaign
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Goal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                if (empty($campaign_rows)) {
                    // Adjusted colspan to 7
                    echo '<tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No campaigns found.</td></tr>';
                } else {
                    echo $campaign_rows; 
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <span>
            <?php if ($total_campaigns > 0): ?>
                Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_campaigns ?> campaigns
            <?php else: ?>
                No campaigns found.
            <?php endif; ?>
        </span>
        
        <div class="space-x-1 flex items-center">
            <?php
            // Previous Button Logic
            $prev_page = $page - 1;
            $prev_class = $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="marketing.php?page=<?= $prev_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $prev_class ?>">
                Previous
            </a>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php 
                $active_class = $i == $page ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100';
                ?>
                <a href="marketing.php?page=<?= $i ?>" 
                   class="px-3 py-1 rounded-lg <?= $active_class ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php
            // Next Button Logic
            $next_page = $page + 1;
            $next_class = $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="marketing.php?page=<?= $next_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $next_class ?>">
                Next
            </a>
        </div>
    </div>
</div>

<?php
// 5. Capture the content
$page_content = ob_get_clean();

// 6. Include the master layout file
include 'includes/layout.php'; 
?>