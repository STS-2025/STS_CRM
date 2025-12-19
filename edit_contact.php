<?php 
// edit_contact.php

// 1. Start capturing the output buffer
ob_start(); 

// 2. Set the specific page title
$page_title = "Edit Contact";

// 3. Include necessary files
include 'api/db.php';

// Get contact ID from URL
$contact_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($contact_id === 0) {
    echo "<script>alert('Invalid contact ID.');window.location='contacts.php';</script>";
    exit();
}

// Fetch existing contact data using a prepared statement for safety
$stmt = $conn->prepare("SELECT name, email, phone, title, company_name, status FROM contacts WHERE id = ?");
$stmt->bind_param("i", $contact_id);
$stmt->execute();
$result = $stmt->get_result();
$contact = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$contact) {
    echo "<script>alert('Contact not found.');window.location='contacts.php';</script>";
    exit();
}
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Contact: <?php echo htmlspecialchars($contact['name']); ?></h1>
        <p class="text-gray-500 mt-1">Modify the details for this individual contact.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/update_contact_process.php" method="POST" class="space-y-6">

        <input type="hidden" name="contact_id" value="<?php echo $contact_id; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="text-sm font-medium text-gray-700 block">Full Name</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($contact['name']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="company_name" class="text-sm font-medium text-gray-700 block">Company Name</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($contact['company_name']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="email" class="text-sm font-medium text-gray-700 block">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($contact['email']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="phone" class="text-sm font-medium text-gray-700 block">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="text-sm font-medium text-gray-700 block">Job Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($contact['title']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Contact Status</label>
                <select id="status" name="status" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php 
                    $statuses = ['Prospect', 'Lead', 'Customer', 'Cold'];
                    foreach($statuses as $s) {
                        $selected = ($s == $contact['status']) ? 'selected' : '';
                        echo "<option value=\"$s\" $selected>$s</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="contacts.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Update Contact
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