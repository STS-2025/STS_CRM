<?php
// leads.php (Role-based Filtering & Campaign Filter)

// 1. Start capturing the output buffer and session
ob_start();
session_start();

// 2. Set the specific page title for the layout
$page_title = "Leads";

// 3. Include necessary files and database connection
include 'api/db.php';

// Auth Check (Optional: Session illana login-ku redirect panna idhai enable pannunga)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/**
 * Helper function to generate a table row for a lead.
 */
if (!function_exists('generate_lead_row')) {
    function generate_lead_row($id, $name, $email, $status, $source, $owner, $reminder_date)
    {
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
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600 hover:text-blue-800">
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
                </td>
            </tr>';
    }
}

// --- 4. DATA FETCHING AND PAGINATION LOGIC ---

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Filter parameters
$filter_campaign_id = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : null;
$campaign_name = null;

// BUILD WHERE CLAUSE DYNAMICALLY
$where_conditions = [];
$bind_types = "";
$bind_params = [];

// Requirement: Admin can see all, User can see only assigned leads
if ($current_user_role !== 'admin') {
    $where_conditions[] = "l.owner_id = ?";
    $bind_types .= "i";
    $bind_params[] = $current_user_id;
}

// Campaign Filter
if ($filter_campaign_id > 0) {
    $where_conditions[] = "l.campaign_id = ?";
    $bind_types .= "i";
    $bind_params[] = $filter_campaign_id;

    // Get campaign name
    $stmt_c = $conn->prepare("SELECT name FROM campaigns WHERE id = ?");
    $stmt_c->bind_param("i", $filter_campaign_id);
    $stmt_c->execute();
    $c_res = $stmt_c->get_result()->fetch_assoc();
    $campaign_name = $c_res['name'] ?? null;
    $stmt_c->close();
}

$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";
$pagination_query = ($filter_campaign_id > 0) ? '&campaign_id=' . $filter_campaign_id : '';
$kanban_query = ($filter_campaign_id > 0) ? '?campaign_id=' . $filter_campaign_id : '';

// Pagination variables
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// 1. Get Total Count for Pagination
$count_sql = "SELECT COUNT(*) AS total FROM leads l" . $where_clause;
$stmt_count = $conn->prepare($count_sql);
if (!empty($bind_types)) {
    $stmt_count->bind_param($bind_types, ...$bind_params);
}
$stmt_count->execute();
$total_leads = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_leads / $limit);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $limit;

// 2. Fetch Leads Data
$sql = "SELECT l.id, l.name, l.email, l.status, l.source, l.reminder_date, u.name AS owner_name
        FROM leads l
        LEFT JOIN users u ON l.owner_id = u.id
        $where_clause
        ORDER BY l.created_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
if (!empty($bind_types)) {
    $stmt->bind_param($bind_types, ...$bind_params);
}
$stmt->execute();
$result = $stmt->get_result();

$lead_rows = '';
$leads_count = 0;
while ($lead = $result->fetch_assoc()) {
    $leads_count++;
    $lead_rows .= generate_lead_row(
        $lead['id'], $lead['name'], $lead['email'], $lead['status'],
        $lead['source'], $lead['owner_name'] ?? 'Unassigned',
        $lead['reminder_date'] ?? 'Not set'
    );
}
$stmt->close();
$conn->close();

$start_item = ($total_leads > 0) ? $offset + 1 : 0;
$end_item = min($offset + $limit, $total_leads);
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800"><?= ($current_user_role === 'admin') ? 'All Leads' : 'My Leads' ?></h1>
        <p class="text-gray-500 mt-1">Manage prospects in your pipeline.</p>
    </div>
    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="leads_kanban.php<?= $kanban_query ?>" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white hover:bg-gray-100 transition">
             Kanban View
        </a>
        <a href="create_lead.php" class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-800 text-white shadow-md">
             Add Lead
        </a>
    </div>
</div>

<?php if ($campaign_name): ?>
    <div class="mb-6 p-3 bg-indigo-50 border border-indigo-200 rounded-xl text-indigo-800 text-sm font-medium flex items-center">
        Viewing leads from: <span class="font-bold ml-1"><?= htmlspecialchars($campaign_name) ?></span>
        <a href="leads.php" class="ml-auto text-indigo-600 underline">Clear Filter</a>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reminder</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?= empty($lead_rows) ? '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No leads found.</td></tr>' : $lead_rows ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <span>Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_leads ?> leads</span>
        <div class="flex space-x-1">
            <a href="leads.php?page=<?= max(1, $page - 1) ?><?= $pagination_query ?>" class="px-3 py-1 border rounded-lg <?= $page <= 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100' ?>">Prev</a>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="leads.php?page=<?= $i ?><?= $pagination_query ?>" class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="leads.php?page=<?= min($total_pages, $page + 1) ?><?= $pagination_query ?>" class="px-3 py-1 border rounded-lg <?= $page >= $total_pages ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-100' ?>">Next</a>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>