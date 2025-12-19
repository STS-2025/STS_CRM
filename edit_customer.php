<?php 
// edit_customer.php

// 1. Start capturing the output buffer
ob_start(); 

// 2. Set the specific page title for the layout
$page_title = "Edit Customer Account";

// 3. Include necessary files
// include 'includes/auth_check.php';
include 'api/db.php';

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id === 0) {
    echo "<script>alert('Invalid customer ID.');window.location='customers.php';</script>";
    exit();
}

// Fetch existing customer data
$stmt = $conn->prepare("SELECT name, company, tier, status, renewal_date, arr_value FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$customer) {
    echo "<script>alert('Customer not found.');window.location='customers.php';</script>";
    exit();
}
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Customer: <?php echo htmlspecialchars($customer['company']); ?></h1>
        <p class="text-gray-500 mt-1">Modify the details for this customer account.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/update_customer_process.php" method="POST" class="space-y-6">

        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="text-sm font-medium text-gray-700 block">Primary Contact Name</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($customer['name']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="company" class="text-sm font-medium text-gray-700 block">Company Name</label>
                <input type="text" id="company" name="company" required value="<?php echo htmlspecialchars($customer['company']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="tier" class="text-sm font-medium text-gray-700 block">Subscription Tier</label>
                <select id="tier" name="tier" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php 
                    $tiers = ['Basic', 'Professional', 'Enterprise'];
                    foreach($tiers as $t) {
                        $selected = ($t == $customer['tier']) ? 'selected' : '';
                        echo "<option value=\"$t\" $selected>$t</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Status</label>
                <select id="status" name="status" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php 
                    $statuses = ['Onboarding', 'Active', 'Churn Risk'];
                    foreach($statuses as $s) {
                        $selected = ($s == $customer['status']) ? 'selected' : '';
                        echo "<option value=\"$s\" $selected>$s</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="renewal_date" class="text-sm font-medium text-gray-700 block">Next Renewal Date</label>
                <input type="date" id="renewal_date" name="renewal_date" value="<?php echo htmlspecialchars($customer['renewal_date']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="arr_value" class="text-sm font-medium text-gray-700 block">Annual Recurring Revenue (ARR)</label>
                <input type="number" step="0.01" id="arr_value" name="arr_value" required value="<?php echo htmlspecialchars($customer['arr_value']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="customers.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Update Account
                </button>
            </div>
        </div>
    </form>
</div>

<?php
// 5. Capture the content
$page_content = ob_get_clean();

// 6. Include the master layout file
include 'includes/layout.php'; 
?>