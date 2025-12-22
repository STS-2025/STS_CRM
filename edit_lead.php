<?php
// edit_lead.php

ob_start();
session_start();

$page_title = "Edit Lead";
include 'api/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// --- 1. Fetch Lead ID and Validation ---
$lead_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($lead_id <= 0) {
    $_SESSION['message'] = "Error: Invalid Lead ID provided.";
    header('Location: leads.php');
    exit();
}

// --- 2. Fetch Lead Data, Users, and Campaigns ---
$lead_sql = "SELECT * FROM leads WHERE id = ?";
$stmt = $conn->prepare($lead_sql);
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$lead_result = $stmt->get_result();
$lead = $lead_result->fetch_assoc();
$stmt->close();

if (!$lead) {
    $_SESSION['message'] = "Error: Lead with ID {$lead_id} not found.";
    header('Location: leads.php');
    exit();
}

if ($current_user_role !== 'admin' && $lead['owner_id'] != $current_user_id) {
    $_SESSION['message'] = "Error: You do not have permission to edit this lead.";
    header('Location: leads.php');
    exit();
}

// Existing lead sources-ai fetch panrom suggestions-kaga
$sources_result = $conn->query("SELECT source FROM leads WHERE source IS NOT NULL AND source != '' GROUP BY TRIM(LOWER(source)) ORDER BY source ASC");
$existing_sources = $sources_result ? $sources_result->fetch_all(MYSQLI_ASSOC) : [];
// Fetch users for the owner dropdown
if ($current_user_role === 'admin') {
    $users_result = $conn->query("SELECT id, name FROM users WHERE role != 'admin' ORDER BY name ASC");
} else {
    // Staff-ku avanga name mattumae dropdown-la theriyum
    $stmt_u = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt_u->bind_param("i", $current_user_id);
    $stmt_u->execute();
    $users_result = $stmt_u->get_result();
}
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];
// Fetch Active/Planned Campaigns for source tracking
$campaigns_result = $conn->query("SELECT id, name FROM campaigns WHERE status IN ('Planned', 'Running', 'Completed') ORDER BY name ASC");
$campaigns = $campaigns_result ? $campaigns_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();

$statuses = ['New', 'Attempted', 'Contacted', 'Qualified', 'Unqualified', 'Converted'];

?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Lead: <?= htmlspecialchars($lead['name']) ?></h1>
        <p class="text-gray-500 mt-1">Update prospect information and sales pipeline status.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form id="editLeadForm" action="api/update_lead_process.php" method="POST" class="space-y-6">
        <input type="hidden" name="id" value="<?= $lead['id'] ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="text-sm font-medium text-gray-700 block">Full Name</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($lead['name']) ?>"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="company" class="text-sm font-medium text-gray-700 block">Company (Optional)</label>
                <input type="text" id="company" name="company" value="<?= htmlspecialchars($lead['company']) ?>"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="email" class="text-sm font-medium text-gray-700 block">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($lead['email']) ?>"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="phone" class="text-sm font-medium text-gray-700 block">Phone (Optional)</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($lead['phone']) ?>"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Status</label>
                <select id="status" name="status" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($s == $lead['status'] ? 'selected' : '') ?>>
                            <?= $s ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="date" class="text-sm font-medium text-gray-700 block">Reminder Date</label>
                <input type="date" id="reminder" name="reminder_date"
                    value="<?= htmlspecialchars($lead['reminder_date']) ?>" placeholder=""
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="source" class="text-sm font-medium text-gray-700 block">Lead Source</label>

                <input type="text" id="source" name="source" required list="source_list"
                    value="<?= htmlspecialchars($lead['source']) ?>" placeholder="Type or select a source"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">

                <datalist id="source_list">
                    <?php foreach ($existing_sources as $s): ?>
                        <option value="<?= htmlspecialchars($s['source']) ?>">
                    <?php endforeach; ?>
                </datalist>
                <p class="mt-1 text-xs text-gray-400 italic">Modify the source or start typing for suggestions.</p>
            </div>

            <div>
                <label for="owner_id" class="text-sm font-medium text-gray-700 block">Assigned Owner</label>
                <select id="owner_id" name="owner_id" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select User --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($u['id'] == $lead['owner_id'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($current_user_role !== 'admin'): ?>
                    <p class="mt-1 text-xs text-gray-400 italic">Only admins can reassign lead owners.</p>
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
                    <option value="<?= $c['id'] ?>" <?= ($c['id'] == $lead['campaign_id'] ? 'selected' : '') ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 block">Remarks</label>
            <textarea type="remarks" id="remarks" name="remarks" required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"> <?= htmlspecialchars($lead['remarks']) ?>    
            </textarea>
        </div>
        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="view_lead.php?id=<?= $lead['id'] ?>"
                    class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit"
                    class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Save Changes
                </button>
            </div>
        </div>
        <!-- Convert Lead Modal -->
        <div id="convertModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">

            <div class="bg-white w-full max-w-md rounded-lg shadow-xl p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Convert Lead to Deal
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deal Amount</label>
                        <input type="number" step="0.01" name="deal_amount"
                            class="w-full mt-1 rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expected Close Date</label>
                        <input type="date" name="expected_close_date"
                            class="w-full mt-1 rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Associated Company</label>
                        <input type="text" name="deal_company"
                            class="w-full mt-1 rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                            value="<?= htmlspecialchars($lead['company']) ?>">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="cancelConvert" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                        Convert & Save
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const form = document.getElementById('editLeadForm');
    const statusSelect = document.getElementById('status');
    const modal = document.getElementById('convertModal');
    const cancelBtn = document.getElementById('cancelConvert');

    statusSelect.addEventListener('change', () => {
        if (statusSelect.value === 'Converted') {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    });

    cancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        statusSelect.value = 'New';
    });
    form.addEventListener('submit', (e) => {
        if (statusSelect.value === 'Converted') {
            const dealAmount = document.getElementById('deal_amount').value;
            const expectedCloseDate = document.getElementById('expected_close_date').value;
            if (!dealAmount || !expectedCloseDate) {
                e.preventDefault();
                alert('Please provide Deal Amount and Expected Close Date to convert the lead.');
            }
        }
    });
</script>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>