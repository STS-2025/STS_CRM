<?php 
// edit_deal.php - UPDATED

// ... (Existing PHP code for session_start, db.php include, and data fetching remains here) ...
ob_start(); 
session_start();
$page_title = "Edit Deal";
include 'api/db.php'; 

$deal_id = (int)($_GET['id'] ?? 0); 
if ($deal_id === 0) {
    $_SESSION['message'] = 'Error: No Deal ID provided.';
    header('Location: deals.php');
    exit();
}

// Fetch the specific deal
$stmt_deal = $conn->prepare("
    SELECT * FROM deals 
    WHERE id = ?
");
$stmt_deal->bind_param("i", $deal_id);
$stmt_deal->execute();
$result_deal = $stmt_deal->get_result();
$deal = $result_deal->fetch_assoc();
$stmt_deal->close();

if (!$deal) {
    $_SESSION['message'] = 'Error: Deal not found.';
    header('Location: deals.php');
    exit();
}

// Fetch all companies and users (for dropdowns)
$companies_result = $conn->query("SELECT id, name FROM companies ORDER BY name ASC");
$companies = [];
if ($companies_result) {
    while ($company = $companies_result->fetch_assoc()) {
        $companies[] = $company;
    }
}

$users_result = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
$users = [];
if ($users_result) {
    while ($user = $users_result->fetch_assoc()) {
        $users[] = $user;
    }
}
$conn->close();
$pipeline_stages = ['New', 'Qualification', 'Proposal Sent', 'Negotiation', 'Closed Won', 'Closed Lost'];

?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Deal: <?= htmlspecialchars($deal['deal_name']) ?></h1>
        <p class="text-gray-500 mt-1">Modify the details of this sales opportunity.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/update_deal_process.php" method="POST" class="space-y-6">
        <input type="hidden" name="deal_id" value="<?= $deal_id ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="deal_name" class="text-sm font-medium text-gray-700 block">Deal Name</label>
                <input type="text" id="deal_name" name="deal_name" required
                       value="<?= htmlspecialchars($deal['deal_name']) ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="amount" class="text-sm font-medium text-gray-700 block">Deal Amount (₹)</label>
                <input type="number" id="amount" name="amount" step="0.01" required
                       value="<?= htmlspecialchars($deal['amount']) ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="company_id" class="text-sm font-medium text-gray-700 block">Associated Company</label>
                <select id="company_id" name="company_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Company --</option>
                    <?php 
                    foreach($companies as $c) {
                        $selected = ($c['id'] == $deal['company_id']) ? 'selected' : '';
                        echo "<option value=\"{$c['id']}\" $selected>" . htmlspecialchars($c['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="owner_id" class="text-sm font-medium text-gray-700 block">Deal Incharge</label>
                <select id="owner_id" name="owner_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Incharge --</option>
                    <?php 
                    foreach($users as $u) {
                        $selected = ($u['id'] == $deal['owner_id']) ? 'selected' : '';
                        echo "<option value=\"{$u['id']}\" $selected>" . htmlspecialchars($u['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Opening Date -->
    <div>
        <label for="opening_date" class="text-sm font-medium text-gray-700 block">Opening Date</label>
        <input type="date" id="opening_date" name="opening_date" required
               value="<?= htmlspecialchars($deal['opening_date']) ?>"
               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
    </div>

    <!-- Expected Close Date -->
    <div>
        <label for="close_date" class="text-sm font-medium text-gray-700 block">Expected Close Date</label>
        <input type="date" id="close_date" name="close_date"
               value="<?= htmlspecialchars($deal['close_date'] ?? '') ?>"
               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Pipeline Stage -->
    <div>
        <label for="stage" class="text-sm font-medium text-gray-700 block">Pipeline Stage</label>
        <select id="stage" name="stage" required
                 class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <?php 
            foreach ($pipeline_stages as $stage_option) {
                $selected = ($stage_option === $deal['stage']) ? 'selected' : '';
                echo "<option value=\"$stage_option\" $selected>$stage_option</option>";
            }
            ?>
        </select>
    </div>
</div>


        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-between items-center">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                
                <button type="button" onclick="confirmDelete(<?= $deal_id ?>)" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        <i data-lucide="trash-2" class="w-4 h-4 inline mr-2"></i> Delete Deal
                </button>
                <?php endif; ?>

                <div class="flex">
                    <a href="deals.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                        Cancel
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    /**
     * Prompts the user for confirmation and submits a deletion form if confirmed.
     */
    function confirmDelete(dealId) {
        if (confirm("Are you sure you want to permanently delete this deal? This action cannot be undone.")) {
            // Create a temporary form to submit the POST request for deletion
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api/delete_deal.php';

            const idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'id';
            idField.value = dealId;

            form.appendChild(idField);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php
// 5. Capture the content
$page_content = ob_get_clean();

// 6. Include the master layout file
include 'includes/layout.php'; 
?>