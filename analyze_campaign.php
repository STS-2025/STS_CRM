<?php
// analyze_campaign.php (FINALIZED)

ob_start(); 
session_start();

$page_title = "Campaign Analysis";
include 'api/db.php'; 

// --- 1. Fetch Campaign Data ---
$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($campaign_id <= 0) {
    $_SESSION['message'] = "Error: Invalid Campaign ID provided.";
    header('Location: marketing.php');
    exit();
}

// SQL to fetch the specific campaign details
$campaign_sql = "
    SELECT 
        id, name, goal, start_date, end_date, budget, status, created_at
    FROM campaigns
    WHERE id = ?
";
$stmt_campaign = $conn->prepare($campaign_sql);
$stmt_campaign->bind_param("i", $campaign_id);
$stmt_campaign->execute();
$campaign_result = $stmt_campaign->get_result();
$campaign = $campaign_result->fetch_assoc();
$stmt_campaign->close();

if (!$campaign) {
    $_SESSION['message'] = "Error: Campaign with ID {$campaign_id} not found.";
    header('Location: marketing.php');
    exit();
}

$statuses = ['Planned', 'Running', 'Completed', 'Canceled'];

// --- 2. Fetch Associated Performance Data ---

// 2a. Fetch Lead Count
$lead_count_sql = "SELECT COUNT(id) AS total_leads FROM leads WHERE campaign_id = ?";
$stmt_count = $conn->prepare($lead_count_sql);
$stmt_count->bind_param("i", $campaign_id);
$stmt_count->execute();
$lead_count_result = $stmt_count->get_result()->fetch_assoc();
$total_leads_generated = $lead_count_result['total_leads'];
$stmt_count->close();

// 2b. Fetch List of Leads
$leads_list_sql = "
    SELECT 
        l.id, l.name, l.email, l.status, l.source, u.name AS owner_name
    FROM leads l
    LEFT JOIN users u ON l.owner_id = u.id
    WHERE l.campaign_id = ?
    ORDER BY l.created_at DESC
    LIMIT 10 -- Show top 10 leads
";
$stmt_leads = $conn->prepare($leads_list_sql);
$stmt_leads->bind_param("i", $campaign_id);
$stmt_leads->execute();
$leads_list = $stmt_leads->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_leads->close();

// --- 3. Form Submission Handling (Self-Processing for Update) ---
// Note: We need to re-open the connection if a POST request occurs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_update'])) {
    // Re-connect to DB if needed (or ensure $conn is available from global scope)
    // If $conn was closed on line 125, you must reconnect here. 
    // Assuming the original script keeps $conn open until the end of the script before the final close.
    // If you need to re-run the form submission, the original $conn must be used.
    
    $name = trim($_POST['name']);
    $goal = trim($_POST['goal']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $budget = (float)$_POST['budget'];
    $status = $_POST['status'];
    $campaign_id_to_update = (int)$_POST['id'];

    if (empty($name) || empty($goal) || $campaign_id_to_update !== $campaign_id) {
        $_SESSION['message'] = 'Error: Invalid data submitted.';
        header('Location: analyze_campaign.php?id=' . $campaign_id);
        exit();
    }

    $update_sql = "
        UPDATE campaigns 
        SET name=?, goal=?, start_date=?, end_date=?, budget=?, status=?
        WHERE id=?
    ";
    
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("ssssdsi", $name, $goal, $start_date, $end_date, $budget, $status, $campaign_id_to_update);

    if ($stmt_update->execute()) {
        $_SESSION['message'] = "Campaign #{$campaign_id} ('{$name}') updated successfully!";
        header('Location: marketing.php'); 
        exit();
    } else {
        $_SESSION['message'] = "Database Error: Could not update campaign. " . $stmt_update->error;
        header('Location: analyze_campaign.php?id=' . $campaign_id);
        exit();
    }
    
    $stmt_update->close();

}
$conn->close(); 
// The $conn->close() must be the last database operation if no POST occurred.
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Analyze Campaign: <?= htmlspecialchars($campaign['name']) ?></h1>
        <p class="text-gray-500 mt-1">Review performance, modify details, or update the status.</p>
    </div>
    
    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="marketing.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Campaign List
        </a>
        <a href="api/delete_campaign_process.php?id=<?= $campaign_id ?>"
           onclick="return confirm('WARNING: Are you sure you want to delete this campaign?');"
           class="px-3 py-2 text-sm font-medium rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition">
            <i data-lucide="trash-2" class="w-4 h-4 inline mr-1"></i> Delete
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Campaign Details</h2>
    <form action="analyze_campaign.php?id=<?= $campaign_id ?>" method="POST" class="space-y-6">
        <input type="hidden" name="id" value="<?= $campaign['id'] ?>">
        <input type="hidden" name="campaign_update" value="1">

        <div>
            <label for="name" class="text-sm font-medium text-gray-700 block">Campaign Name</label>
            <input type="text" id="name" name="name" required
                   value="<?= htmlspecialchars($campaign['name']) ?>"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div>
            <label for="goal" class="text-sm font-medium text-gray-700 block">Campaign Goal</label>
            <textarea id="goal" name="goal" rows="3" required
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= htmlspecialchars($campaign['goal']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div>
                <label for="start_date" class="text-sm font-medium text-gray-700 block">Start Date</label>
                <input type="date" id="start_date" name="start_date" required
                       value="<?= htmlspecialchars($campaign['start_date']) ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="end_date" class="text-sm font-medium text-gray-700 block">End Date</label>
                <input type="date" id="end_date" name="end_date" required
                       value="<?= htmlspecialchars($campaign['end_date']) ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div>
                <label for="budget" class="text-sm font-medium text-gray-700 block">Total Budget ($)</label>
                <input type="number" step="0.01" id="budget" name="budget" required
                       value="<?= number_format($campaign['budget'], 2, '.', '') ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Status</label>
                <select id="status" name="status" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($s == $campaign['status'] ? 'selected' : '') ?>>
                            <?= $s ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
            <div></div>
        </div>


        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ---

## 📊 Campaign Performance -->

<div class="mt-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Performance Metrics</h2>
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 grid grid-cols-3 gap-4 text-center">
        <div class="border p-4 rounded-lg">
            <p class="text-2xl font-semibold text-blue-600"><?= $total_leads_generated ?></p>
            <p class="text-sm text-gray-500">Total Leads Generated</p>
        </div>
        <div class="border p-4 rounded-lg">
            <?php
            $budget = $campaign['budget'];
            $cpl = ($total_leads_generated > 0) ? ($budget / $total_leads_generated) : 0;
            ?>
            <p class="text-2xl font-semibold text-indigo-600">
                $<?= number_format($cpl, 2) ?>
            </p>
            <p class="text-sm text-gray-500">Cost Per Lead (Est.)</p>
        </div>
        <div class="border p-4 rounded-lg">
            <p class="text-2xl font-semibold text-green-600">0</p>
            <p class="text-sm text-gray-500">Opportunities Created</p>
        </div>
    </div>
</div>

<!-- ---

## 📝 Associated Leads -->

<div class="mt-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Generated Leads (<?= $total_leads_generated ?>)</h2>
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($leads_list)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No leads have been associated with this campaign yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leads_list as $lead): 
                            $status_class = match ($lead['status']) {
                                'New' => 'bg-indigo-100 text-indigo-800',
                                'Attempted' => 'bg-yellow-100 text-yellow-800',
                                'Qualified' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?= htmlspecialchars($lead['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($lead['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                        <?= htmlspecialchars($lead['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($lead['owner_name'] ?? 'Unassigned') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="view_lead.php?id=<?= $lead['id'] ?>" class="text-blue-600 hover:text-blue-900">View/Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if ($total_leads_generated > 10): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                <a href="leads.php?campaign_id=<?= $campaign_id ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                    View all <?= $total_leads_generated ?> leads for this campaign...
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>