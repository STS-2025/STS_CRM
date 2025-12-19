<?php 
// deals.php

// 1. Start output buffering and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Deals Pipeline";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php'; 

// Define the official pipeline stages (must match what's used in create_deal.php)
$pipeline_stages = ['New', 'Qualification', 'Proposal Sent', 'Negotiation', 'Closed Won', 'Closed Lost'];

// Initialize arrays to store deals grouped by stage and column totals
$stage_deals = array_fill_keys($pipeline_stages, '');
$stage_counts = array_fill_keys($pipeline_stages, 0);

// --- 4. DATA FETCHING LOGIC ---
// Fetch all deals, joining with companies and users to get names
$sql = "
    SELECT 
        d.id, d.deal_name, d.amount, d.stage, d.close_date,
        c.name AS company_name, 
        u.name AS owner_name
    FROM deals d
    LEFT JOIN companies c ON d.company_id = c.id
    LEFT JOIN users u ON d.owner_id = u.id
    ORDER BY d.amount DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($deal = $result->fetch_assoc()) {
        $deal_stage = $deal['stage'];
        $deal_amount_formatted = '₹' . number_format($deal['amount'], 2);
        
        // Ensure the stage exists in our defined pipeline before adding
        if (in_array($deal_stage, $pipeline_stages)) {
            $stage_deals[$deal_stage] .= generate_deal_card(
                $deal['id'],
                $deal['deal_name'], 
                $deal_amount_formatted, 
                $deal['company_name'] ?? 'N/A', // Use N/A if company is NULL
                $deal['owner_name'] ?? 'N/A'     // Use N/A if owner is NULL
            );
            $stage_counts[$deal_stage]++;
        }
    }
}
$conn->close();

/**
 * Helper function to generate a Deal Card within the Kanban board
 */
function generate_deal_card($deal_id, $deal_name, $amount, $company, $owner) {
    // Choose a random color for the deal card border for visual variety
    $colors = ['border-blue-500', 'border-green-500', 'border-yellow-500', 'border-red-500'];
    $border_color = $colors[array_rand($colors)];

    return '
        <div class="deal-card bg-white p-4 rounded-lg shadow-sm border-t-4 ' . $border_color . ' mb-4 cursor-grab hover:shadow-md transition duration-150" 
             data-deal-id="' . $deal_id . '" data-stage="' . htmlspecialchars($deal_name) . '" draggable="true">
            <h4 class="text-sm font-semibold text-gray-900">' . htmlspecialchars($deal_name) . '</h4>
            <p class="text-xl font-bold text-gray-800 mt-1">' . htmlspecialchars($amount) . '</p>
            <p class="text-xs text-gray-500 mt-2">Company: ' . htmlspecialchars($company) . '</p>
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center">
                <span class="text-xs text-gray-600">Owner: ' . htmlspecialchars($owner) . '</span>
                <a href="edit_deal.php?id=' . $deal_id . '" class="text-gray-400 hover:text-blue-600 transition">
                    <i data-lucide="ellipsis-vertical" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    ';
}

/**
 * Helper function to generate a Kanban Column (Stage)
 */
function generate_pipeline_column($title, $deals, $count) {
    // Use stage title for the column ID (e.g., "Proposal Sent" -> "proposal-sent")
    $column_id = strtolower(str_replace(' ', '-', $title));
    return '
        <div class="w-full lg:w-1/5 flex-shrink-0">
            <div class="bg-gray-100 p-3 rounded-xl shadow-inner h-full flex flex-col">
                <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">' . htmlspecialchars($title) . '</h3>
                    <span class="text-xs font-semibold bg-gray-300 text-gray-700 px-2 py-1 rounded-full">' . $count . '</span>
                </div>
                
                <div id="' . $column_id . '" class="pipeline-stage min-h-8 space-y-3 pb-2 flex-grow overflow-y-auto" data-stage="' . htmlspecialchars($title) . '">
                    ' . $deals . '
                </div>
                
                <a href="create_deal.php?stage=' . urlencode($title) . '" class="w-full mt-4 text-xs py-2 text-gray-500 hover:text-blue-600 transition block text-center border-t border-gray-200 pt-3">
                    <i data-lucide="plus" class="w-3 h-3 inline mr-1"></i> Add deal
                </a>
            </div>
        </div>
    ';
}

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
    <div class="bg-green-500 text-white rounded-lg shadow-xl p-4 transition-opacity duration-300 flex items-center justify-between" role="alert">
        <div id="toast-text"></div>
        <button type="button" class="ml-4 text-white hover:text-gray-100" onclick="closeToast()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
</div>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Deals Pipeline</h1>
        <p class="text-gray-500 mt-1">Visualize and manage your sales opportunities across the pipeline.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="deals_list.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list" class="w-4 h-4 inline mr-1"></i> List View
        </a>
        <a href="create_deal.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                            bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> New Deal
        </a>
    </div>
</div>

<div class="flex overflow-x-auto pb-4 space-x-4 h-[calc(100vh-200px)]">
    
    <?php 
    // Loop through the defined stages to render columns dynamically
    foreach ($pipeline_stages as $stage) {
        // Skip 'Closed Lost' from the main board view
        if ($stage === 'Closed Lost') continue; 

        echo generate_pipeline_column(
            $stage, 
            $stage_deals[$stage], 
            $stage_counts[$stage]
        );
    }
    ?>
    
</div>

<script>
// ----------------------
// TOAST NOTIFICATION LOGIC (Remains the same)
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

    // Determine color and icon (not implemented here, but good practice)
    let bgColor = (type === 'error') ? 'bg-red-500' : 'bg-green-500';

    const innerToast = toastEl.querySelector('div');
    // Set the full class list for the inner div
    innerToast.className = 'text-white rounded-lg shadow-xl p-4 transition-opacity duration-300 flex items-center justify-between ' + bgColor;

    toastTextEl.textContent = message;
    toastEl.classList.remove('hidden');

    window.toastTimeout = setTimeout(closeToast, duration);
}

// Check if a message exists on page load and show it
if (sessionMessage.length > 0) {
    showToast(sessionMessage, sessionType); 
}

// ----------------------
// DEAL STAGE UPDATE LOGIC (For Kanban) (Remains the same)
// ----------------------

/**
 * Sends an AJAX request to update a deal's stage in the database.
 */
function updateDealStage(dealId, newStage) {
    
    fetch('api/update_deal_stage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        // Send data as form-encoded string
        body: `id=${dealId}&stage=${encodeURIComponent(newStage)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Deal #' + dealId + ' moved to ' + newStage + ' successfully!', 'success');
            // Simple solution for updating counts and card movement
            setTimeout(() => {
                window.location.reload(); 
            }, 500);
        } else {
            showToast('Error updating deal stage: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Network error: Could not contact server.', 'error');
        console.error('Fetch Error:', error);
    });
}

// Simplified Drag-and-Drop Implementation (Remains the same)
document.addEventListener('DOMContentLoaded', () => {
    const deals = document.querySelectorAll('.deal-card');
    const stages = document.querySelectorAll('.pipeline-stage');

    deals.forEach(deal => {
        deal.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', e.target.dataset.dealId);
        });
    });

    stages.forEach(stage => {
        stage.addEventListener('dragover', (e) => {
            e.preventDefault(); // Allows drop
        });

        stage.addEventListener('drop', (e) => {
            e.preventDefault();
            const dealId = e.dataTransfer.getData('text/plain');
            const newStage = e.currentTarget.dataset.stage;
            const dealCard = document.querySelector(`[data-deal-id="${dealId}"]`);
            
            if (dealCard && newStage) {
                e.currentTarget.appendChild(dealCard); // Move visually immediately
                updateDealStage(dealId, newStage);    // Call API to update database
            }
        });
    });
});
</script>


<?php
// 4. Capture the content and store it in a variable
$page_content = ob_get_clean();

// 5. Include the master layout file
include 'includes/layout.php'; 
?>