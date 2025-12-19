<?php
// contacts.php

// 1. Start capturing the output buffer
ob_start();

// 2. Set the specific page title
$page_title = "Contacts";

// 3. Include necessary files (auth check and DB connection)
session_start();
// include 'includes/auth_check.php'; 
include 'api/db.php'; // Includes the database connection

// --- FILTERING LOGIC ---
$current_filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : '';
$where_clause = '';
if (!empty($current_filter_status) && $current_filter_status !== 'All') {
    // Basic sanitization for status filter
    $where_clause = "WHERE c.status = '" . $conn->real_escape_string($current_filter_status) . "'";
}

// --- PAGINATION SETUP ---
$limit = 10; // Contacts per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$start = ($page - 1) * $limit;

// 1. Get total number of contacts (using 'c' alias for contacts table)
$count_sql = "SELECT COUNT(c.id) AS total FROM contacts c " . $where_clause;
$count_result = $conn->query($count_sql);
$total_contacts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_contacts / $limit);
// ------------------------

// 4. DEFINE HELPER FUNCTION HERE 
if (!function_exists('generate_contact_row')) {
    /**
     * Generates a single table row with Edit/Delete links.
     */
    function generate_contact_row($id, $name, $email, $phone, $title, $company_name, $status)
    {
        // Determine status badge color
        $status_class = match ($status) {
            'Customer' => 'bg-green-100 text-green-800',
            'Prospect' => 'bg-blue-100 text-blue-800',
            'Cold' => 'bg-gray-100 text-gray-800',
            default => 'bg-yellow-100 text-yellow-800',
        };

        // ✅ Role-based delete control
        $delete_button = '';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $delete_button = '
            <a href="javascript:void(0);" 
            onclick="deleteContact(' . (int)$id . ')" 
            class="text-red-600 hover:text-red-900">Delete</a>';
        }

        // If company_name is NULL (no company linked), display a dash or 'N/A'
        $company_display = $company_name ? htmlspecialchars($company_name) : 'N/A';

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">' . htmlspecialchars($name) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($email) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($phone) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-purple-600">' . htmlspecialchars($title) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800 cursor-pointer">' . $company_display . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">'
            . htmlspecialchars($status) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="edit_contact.php?id=' . $id . '" class="text-indigo-600 hover:text-indigo-900">Edit</a>' . $delete_button . '
                </td>
            </tr>
        ';
    }
}

// 5. DATA FETCHING LOGIC (Paginated and Filtered)
$contact_rows_html = '';

// *** IMPORTANT CHANGE HERE: LEFT JOIN companies table to get the company name ***
$sql = "SELECT 
            c.id, c.name, c.email, c.phone, c.title, c.status, 
            co.name AS company_name 
        FROM contacts c
        LEFT JOIN companies co ON c.company_id = co.id "
    . $where_clause .
    " ORDER BY c.name ASC 
        LIMIT $start, $limit";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $contact_rows_html .= generate_contact_row(
            $row['id'],
            $row['name'],
            $row['email'],
            $row['phone'],
            $row['title'],
            $row['company_name'],
            $row['status']
        );
    }
} else {
    $colspan = 7;
    $contact_rows_html = '<tr><td colspan="' . $colspan . '" class="px-6 py-4 text-center text-gray-500">No contacts found ' . (!empty($current_filter_status) && $current_filter_status !== 'All' ? 'for status "' . htmlspecialchars($current_filter_status) . '".' : '. Click "Create Contact" to get started.') . '</td></tr>';
}

// Close the database connection
$conn->close();

// Check for and store session message before outputting HTML
$toast_message = '';
$toast_type = 'success'; // Default type
if (isset($_SESSION['message'])) {
    $toast_message = $_SESSION['message'];
    // Simple check to determine if it's an error message
    if (str_contains($toast_message, 'Error') || str_contains($toast_message, 'Database Error')) {
        $toast_type = 'error';
    }
    unset($_SESSION['message']); // Clear the message after retrieval
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
        <h1 class="text-3xl font-bold text-gray-800">Contacts</h1>
        <p class="text-gray-500 mt-1">Manage and view all individual contacts in your pipeline.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">

        <form method="GET" action="contacts.php" class="inline-flex items-center">
            <select id="status_filter" name="status" onchange="this.form.submit()"
                class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Contacts</option>
                <?php
                $statuses = ['Prospect', 'Lead', 'Customer', 'Cold'];
                foreach ($statuses as $s) {
                    $selected = ($s === $current_filter_status) ? 'selected' : '';
                    echo "<option value=\"$s\" $selected>$s</option>";
                }
                ?>
            </select>
        </form>

        <a href="create_contact.php"
            class="px-4 py-2 text-sm font-medium rounded-lg 
                                            bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="user-plus" class="w-4 h-4 inline mr-1"></i> Create Contact
        </a>
    </div>
</div>
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php echo $contact_rows_html; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <?php
        $end = min($start + $limit, $total_contacts);
        $start_display = $total_contacts > 0 ? $start + 1 : 0;
        $base_url = 'contacts.php?status=' . urlencode($current_filter_status);
        ?>
        <span>Showing <?php echo $start_display; ?> to <?php echo $end; ?> of <?php echo $total_contacts; ?>
            contacts</span>

        <div class="space-x-1">
            <?php if ($page > 1): ?>
                <a href="<?php echo $base_url . '&page=' . ($page - 1); ?>"
                    class="px-3 py-1 border rounded-lg hover:bg-gray-100">Previous</a>
            <?php else: ?>
                <button disabled class="px-3 py-1 border rounded-lg text-gray-400 cursor-not-allowed">Previous</button>
            <?php endif; ?>

            <?php
            for ($i = 1; $i <= $total_pages; $i++) {
                $page_url = $base_url . '&page=' . $i;
                if ($i == $page) {
                    echo '<button class="px-3 py-1 border rounded-lg bg-blue-500 text-white">' . $i . '</button>';
                } else {
                    echo '<a href="' . $page_url . '" class="px-3 py-1 border rounded-lg hover:bg-gray-100">' . $i . '</a>';
                }
            }
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?php echo $base_url . '&page=' . ($page + 1); ?>"
                    class="px-3 py-1 border rounded-lg hover:bg-gray-100">Next</a>
            <?php else: ?>
                <button disabled class="px-3 py-1 border rounded-lg text-gray-400 cursor-not-allowed">Next</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    /**
     * JavaScript function to handle the Delete action.
     */
    function deleteContact(contactId) {
        if (confirm("Are you sure you want to delete this contact?")) {
            window.location.href = 'api/delete_contact.php?id=' + contactId;
        }
    }

    // ----------------------
    // TOAST NOTIFICATION LOGIC
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
// 6. Capture the content and include layout
$page_content = ob_get_clean();

// NOTE: Adjust the path based on your file structure
include 'includes/layout.php';
?>