<?php
// create_lead.php (UPDATED)

ob_start();
session_start();

$page_title = "Add New Lead";
include 'api/db.php';

// --- Fetch data for dropdowns ---
// 1. Fetch users for the owner dropdown
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// --- 1. Role-Based User Fetching Logic ---
if ($current_user_role === 'admin') {
    // Admin-ku ella staff members-um therivaanga
    $users_result = $conn->query("SELECT id, name FROM users WHERE role != 'admin' ORDER BY name ASC");
} else {
    // Staff-ku avanga peyar mattumae dropdown-la varum
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $users_result = $stmt->get_result();
}
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

// 2. Fetch Active/Planned Campaigns for source tracking
$campaigns_result = $conn->query("SELECT id, name FROM campaigns WHERE status IN ('Planned', 'Running') ORDER BY name ASC");
$campaigns = $campaigns_result ? $campaigns_result->fetch_all(MYSQLI_ASSOC) : [];

// 3. Fetch existing lead sources for datalist
$sources_result = $conn->query("SELECT source FROM leads WHERE source IS NOT NULL AND source != '' GROUP BY TRIM(LOWER(source)) ORDER BY source ASC");
$existing_sources = $sources_result ? $sources_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();

$statuses = ['New', 'Attempted', 'Contacted', 'Qualified', 'Unqualified', 'Converted'];

?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Add New Lead</h1>
        <p class="text-gray-500 mt-1">Enter the prospect's details and assign an owner.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/create_lead_process.php" method="POST" class="space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="text-sm font-medium text-gray-700 block">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="e.g., Jane Doe"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="company" class="text-sm font-medium text-gray-700 block">Company (Optional)</label>
                <input type="text" id="company" name="company" placeholder="e.g., Acme Corp"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="email" class="text-sm font-medium text-gray-700 block">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="jane@example.com"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="phone" class="text-sm font-medium text-gray-700 block">Phone (Optional)</label>
                <input type="tel" id="phone" name="phone" placeholder="(555) 123-4567"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Status</label>
                <select id="status" name="status" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($s == 'New' ? 'selected' : '') ?>>
                            <?= $s ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="date" class="text-sm font-medium text-gray-700 block">Reminder Date</label>
                <input type="date" id="reminder" name="reminder_date" required placeholder=""
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label for="source" class="text-sm font-medium text-gray-700 block">Lead Source</label>

                <input type="text" id="source" name="source" required list="source_list"
                    placeholder="Type or select a source"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">

                <datalist id="source_list">
                    <?php foreach ($existing_sources as $s): ?>
                        <option value="<?= htmlspecialchars($s['source']) ?>">
                        <?php endforeach; ?>
                </datalist>
                <p class="mt-1 text-xs text-gray-400 italic">Start typing to see previous sources or enter a new one.
                </p>
            </div>

            <div>
                <label for="owner_id" class="text-sm font-medium text-gray-700 block">Assigned Owner</label>
                <select id="owner_id" name="owner_id" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php if ($current_user_role === 'admin'): ?>
                        <option value="">-- Select Staff --</option>
                    <?php endif; ?>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($current_user_role !== 'admin' || $u['id'] == $current_user_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?> <?= ($u['id'] == $current_user_id) ? '(Me)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($current_user_role !== 'admin'): ?>
                    <p class="mt-1 text-xs text-gray-400 italic">Leads you create are automatically assigned to you.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="pt-4">
            <label for="campaign_id" class="text-sm font-medium text-gray-700 block">Associated Campaign
                (Optional)</label>
            <select id="campaign_id" name="campaign_id"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">-- No Campaign (Manual/Organic) --</option>
                <?php foreach ($campaigns as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="mt-1 text-xs text-gray-500">If this lead was generated by a specific marketing campaign, select it
                here for ROI tracking.</p>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 block">Remarks</label>
            <textarea type="remarks" id="remarks" name="remarks" required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </textarea>
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="leads.php"
                    class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit"
                    class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i data-lucide="user-plus" class="w-4 h-4 inline mr-2"></i> Create Lead
                </button>
            </div>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>