<?php
// leads.php (FINALIZED with Campaign Filter and Kanban Link)

// 1. Start capturing the output buffer and session
ob_start();
session_start();

// 2. Set the specific page title for the layout
$page_title = "Leads";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php';

/**
 * Helper function to generate a table row for a lead.
 */
if (!function_exists('generate_lead_row')) {
    function generate_lead_row($id, $name, $email, $status, $source, $owner, $reminder_date)
    {
        // Determine status badge color
        $status_class = match ($status) {
            'New' => 'bg-indigo-100 text-indigo-800',
            'Attempted' => 'bg-yellow-100 text-yellow-800',
            'Contacted' => 'bg-blue-100 text-blue-800',
            'Qualified' => 'bg-green-100 text-green-800',
            'Converted' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };

        $delete_button = '';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $delete_button = '
            <a href="api/delete_lead_process.php?id=' . htmlspecialchars($id) . '" 
               class="text-red-600 hover:text-red-900"
               onclick="return confirm(\'Are you sure you want to delete this lead?\')">
               Delete
            </a>';
        }

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600 hover:text-blue-800 cursor-pointer">
                    <a href="view_lead.php?id=' . htmlspecialchars($id) . '">' . htmlspecialchars($name) . '</a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($email) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">'
            . htmlspecialchars($status) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($source) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($owner) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($reminder_date) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="edit_lead.php?id=' . htmlspecialchars($id) . '" class="text-blue-600 hover:text-blue-900">Edit</a>' . $delete_button . '
                   <!--a href="api/delete_lead_process.php?id=' . htmlspecialchars($id) . '" 
                    class="text-red-600 hover:text-red-900" 
                    onclick="return confirm(\'Are you sure you want to delete this lead?\')">Delete</a-->
                </td>
            </tr>
        ';
    }
}


// --- 4. DATA FETCHING AND PAGINATION LOGIC ---

// 0. Check for campaign filter
$filter_campaign_id = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : null;
$campaign_name = null;

$where_clause = '';
$pagination_query = ''; // Used to append the filter to all pagination links

if ($filter_campaign_id > 0) {
    $where_clause = " WHERE l.campaign_id = ? ";
    $pagination_query = '&campaign_id=' . $filter_campaign_id;

    // Fetch campaign name for display purposes
    $campaign_name_sql = "SELECT name FROM campaigns WHERE id = ?";
    $stmt_c = $conn->prepare($campaign_name_sql);
    if ($stmt_c) {
        $stmt_c->bind_param("i", $filter_campaign_id);
        $stmt_c->execute();
        $c_result = $stmt_c->get_result();
        if ($c_row = $c_result->fetch_assoc()) {
            $campaign_name = $c_row['name'];
        }
        $stmt_c->close();
    }
}

// Set up the Kanban link query correction
$kanban_query = $filter_campaign_id > 0 ? '?campaign_id=' . $filter_campaign_id : '';


// 1. Define pagination variables
$limit = 15; // Leads per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;

// 2. Fetch Total Count (using prepared statement for safety if filtering)
$count_sql = "SELECT COUNT(*) AS total FROM leads l" . $where_clause;
$stmt_count = $conn->prepare($count_sql);

// Bind parameter for count if filtering
if ($filter_campaign_id > 0) {
    $stmt_count->bind_param("i", $filter_campaign_id);
}

$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_leads = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$stmt_count->close();

$total_pages = ceil($total_leads / $limit);

// 3. Calculate OFFSET and adjust page if necessary
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
if ($offset < 0)
    $offset = 0; // Prevent negative offset if $total_leads is 0

// 4. Build the SQL query for data fetching
$leads = [];
$lead_rows = '';
// Query to fetch leads, joining with users to get the owner name
// LIMIT and OFFSET are NOT bound, as it's often incompatible or complex.
$sql = "
    SELECT 
        l.id, l.name, l.email, l.status, l.source,l.reminder_date,
        u.name AS owner_name
    FROM leads l
    LEFT JOIN users u ON l.owner_id = u.id
    " . $where_clause . "
    ORDER BY l.created_at DESC
    LIMIT $limit OFFSET $offset
";

// Use prepared statement ONLY for the campaign ID filtering parameter
$stmt = $conn->prepare($sql);

if ($stmt) {
    if ($filter_campaign_id > 0) {
        // Bind only the campaign ID parameter
        $stmt->bind_param("i", $filter_campaign_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($lead = $result->fetch_assoc()) {
            $leads[] = $lead;

            $lead_rows .= generate_lead_row(
                $lead['id'],
                $lead['name'],
                $lead['email'],
                $lead['status'],
                $lead['source'],
                $lead['owner_name'] ?? 'Unassigned',
                $lead['reminder_date'] ?? 'Not set'
            );
        }
    }
    $stmt->close();
}
$conn->close();

// 5. Calculate display range for the summary line
$current_page_count = count($leads);
$start_item = $current_page_count > 0 ? $offset + 1 : 0;
$end_item = $current_page_count > 0 ? $offset + $current_page_count : 0;
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Leads</h1>
        <p class="text-gray-500 mt-1">Manage and qualify new prospects entering the sales pipeline.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="leads_kanban.php<?= $kanban_query ?>"
            class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="trello" class="w-4 h-4 inline mr-1"></i> Kanban View
        </a>
        <a href="create_lead.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                         bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Add Lead
        </a>
    </div>
</div>

<?php if ($campaign_name): ?>
    <div
        class="mb-6 p-3 bg-indigo-50 border border-indigo-200 rounded-xl text-indigo-800 text-sm font-medium flex items-center shadow-md">
        <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
        You are currently viewing leads from the campaign:
        <span class="font-bold ml-1"><?= htmlspecialchars($campaign_name) ?></span>
        <a href="leads.php" class="ml-auto text-indigo-600 hover:text-indigo-900 font-semibold underline">Clear Filter</a>
    </div>
<?php endif; ?>


<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reminder
                        Date</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                if (empty($lead_rows)) {
                    // Adjusted colspan to 7
                    $message = $campaign_name ? "No leads found for the campaign: " . htmlspecialchars($campaign_name) : "No leads found.";
                    echo '<tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">' . $message . '</td></tr>';
                } else {
                    echo $lead_rows;
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <span>
            <?php if ($total_leads > 0): ?>
                Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_leads ?> leads
            <?php else: ?>
                No leads found.
            <?php endif; ?>
        </span>

        <div class="space-x-1 flex items-center">
            <?php
            // Previous Button Logic
            $prev_page = $page - 1;
            $prev_class = $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="leads.php?page=<?= $prev_page ?><?= $pagination_query ?>"
                class="px-3 py-1 border rounded-lg <?= $prev_class ?>">
                Previous
            </a>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php
                $active_class = $i == $page ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100';
                ?>
                <a href="leads.php?page=<?= $i ?><?= $pagination_query ?>"
                    class="px-3 py-1 rounded-lg <?= $active_class ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php
            // Next Button Logic
            $next_page = $page + 1;
            $next_class = $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="leads.php?page=<?= $next_page ?><?= $pagination_query ?>"
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