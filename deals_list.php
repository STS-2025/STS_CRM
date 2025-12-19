<?php
// deals_list.php

// 1. Start output buffering and session
ob_start();
session_start();

// 2. Set the specific page title
$page_title = "Deals List View";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php';

// --- 4. DATA FETCHING LOGIC ---
// Fetch all deals, joining with companies and users to get names
$sql = "
    SELECT 
        d.id, d.deal_name, d.amount, d.stage, d.close_date, d.created_at,
        c.name AS company_name, 
        u.name AS owner_name
    FROM deals d
    LEFT JOIN companies c ON d.company_id = c.id
    LEFT JOIN users u ON d.owner_id = u.id
    ORDER BY d.id DESC 
";

$result = $conn->query($sql);

$deals = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $deals[] = $row;
    }
}
$conn->close();

/**
 * Helper function to render a stage with color
 */
function render_stage_badge($stage)
{
    $colors = [
        'New' => 'bg-blue-100 text-blue-800',
        'Qualification' => 'bg-purple-100 text-purple-800',
        'Proposal Sent' => 'bg-yellow-100 text-yellow-800',
        'Negotiation' => 'bg-indigo-100 text-indigo-800',
        'Closed Won' => 'bg-green-100 text-green-800',
        'Closed Lost' => 'bg-red-100 text-red-800',
    ];


    $color_class = $colors[$stage] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $color_class . '">' . htmlspecialchars($stage) . '</span>';
}

// Check for and store session message (for toast notifications)
$toast_message = '';
$toast_type = 'success';
if (isset($_SESSION['message'])) {
    $toast_message = $_SESSION['message'];
    if (str_contains($toast_message, 'Error') || str_contains($toast_message, 'Database Error')) {
        $toast_type = 'error';
    }
    unset($_SESSION['message']);
}

?>

<div id="toast-message" aria-live="polite" aria-atomic="true" class="hidden fixed top-4 right-4 z-50 w-full max-w-xs">
    <div class="bg-green-500 text-white rounded-lg shadow-xl p-4 transition-opacity duration-300 flex items-center justify-between"
        role="alert">
        <div id="toast-text"></div>
        <button type="button" class="ml-4 text-white hover:text-gray-100" onclick="closeToast()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Deals List</h1>
        <p class="text-gray-500 mt-1">A comprehensive list of all sales opportunities.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="deals.php"
            class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="columns-2" class="w-4 h-4 inline mr-1"></i> Kanban View
        </a>
        <a href="create_deal.php"
            class="px-4 py-2 text-sm font-medium rounded-lg 
                                            bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> New Deal
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deal Name
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Close Date
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($deals)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No deals found. Create a new deal to
                        populate the list.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($deals as $deal): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <a href="edit_deal.php?id=<?= $deal['id'] ?>"
                            class="text-blue-600 hover:text-blue-900"><?= htmlspecialchars($deal['deal_name']) ?></a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">
                        ₹<?= number_format($deal['amount'], 2) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= htmlspecialchars($deal['company_name'] ?? 'N/A') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?= render_stage_badge($deal['stage']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= htmlspecialchars($deal['owner_name'] ?? 'N/A') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= $deal['close_date'] ? date('M d, Y', strtotime($deal['close_date'])) : 'N/A' ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="edit_deal.php?id=<?= $deal['id'] ?>" class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="#"
                                onclick="if(confirm('Are you sure you want to delete this deal?')) { document.getElementById('delete-form-<?= $deal['id'] ?>').submit(); }"
                                class="text-red-600 hover:text-red-900 ml-4">
                                Delete
                            </a>

                            <form id="delete-form-<?= $deal['id'] ?>" action="api/delete_deal.php" method="POST"
                                style="display:none;">
                                <input type="hidden" name="id" value="<?= $deal['id'] ?>">
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    // ----------------------
    // TOAST NOTIFICATION LOGIC (Copied from deals.php)
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

    /**
     * Shows the popup message.
     */
    function showToast(message, type = 'success', duration = 5000) {
        if (!message) return;

        let bgColor = (type === 'error') ? 'bg-red-500' : 'bg-green-500';

        const innerToast = toastEl.querySelector('div');
        innerToast.className = 'text-white rounded-lg shadow-xl p-4 transition-opacity duration-300 flex items-center justify-between ' + bgColor;

        toastTextEl.textContent = message;
        toastEl.classList.remove('hidden');

        window.toastTimeout = setTimeout(closeToast, duration);
    }

    // Check if a message exists on page load and show it
    if (sessionMessage.length > 0) {
        showToast(sessionMessage, sessionType);
    }
</script>

<?php
// 5. Capture the content
$page_content = ob_get_clean();

// 6. Include the master layout file
include 'includes/layout.php';
?>