<?php 
// deals.php (Role-Based Kanban Pipeline)

ob_start(); 
session_start();

$page_title = "Deals Pipeline";
include 'api/db.php'; 

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Define pipeline stages
$pipeline_stages = ['New', 'Qualification', 'Proposal Sent', 'Negotiation', 'Closed Won', 'Closed Lost'];
$stage_deals = array_fill_keys($pipeline_stages, '');
$stage_counts = array_fill_keys($pipeline_stages, 0);

// --- 4. DATA FETCHING LOGIC (With Security Filter) ---

$where_clause = "";
$params = [];
$types = "";

// Admin illaiyendral, owner_id-ai vaitthu filter seiyavum
if ($current_user_role !== 'admin') {
    $where_clause = " WHERE d.owner_id = ?";
    $params[] = $current_user_id;
    $types = "i";
}

$sql = "
    SELECT 
        d.id, d.deal_name, d.amount, d.stage, d.close_date,
        c.name AS company_name, 
        u.name AS owner_name
    FROM deals d
    LEFT JOIN companies c ON d.company_id = c.id
    LEFT JOIN users u ON d.owner_id = u.id
    $where_clause
    ORDER BY d.amount DESC
";

$stmt = $conn->prepare($sql);
if ($current_user_role !== 'admin') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while($deal = $result->fetch_assoc()) {
        $deal_stage = $deal['stage'];
        $deal_amount_formatted = '₹' . number_format($deal['amount'], 2);
        
        if (isset($stage_deals[$deal_stage])) {
            $stage_deals[$deal_stage] .= generate_deal_card(
                $deal['id'],
                $deal['deal_name'], 
                $deal_amount_formatted, 
                $deal['company_name'] ?? 'N/A', 
                $deal['owner_name'] ?? 'N/A'
            );
            $stage_counts[$deal_stage]++;
        }
    }
}
$stmt->close();
$conn->close();

/**
 * Helper function to generate a Deal Card
 */
function generate_deal_card($deal_id, $deal_name, $amount, $company, $owner) {
    $colors = ['border-blue-500', 'border-green-500', 'border-yellow-500', 'border-purple-500'];
    $border_color = $colors[$deal_id % 4]; // Consistent color per deal ID

    return '
        <div class="deal-card bg-white p-4 rounded-lg shadow-sm border-t-4 ' . $border_color . ' mb-4 cursor-grab hover:shadow-md transition duration-150" 
             data-deal-id="' . $deal_id . '" draggable="true">
            <h4 class="text-sm font-semibold text-gray-900 truncate">' . htmlspecialchars($deal_name) . '</h4>
            <p class="text-lg font-bold text-gray-800 mt-1">' . htmlspecialchars($amount) . '</p>
            <p class="text-[10px] text-gray-500 mt-2 uppercase tracking-tight">Company: ' . htmlspecialchars($company) . '</p>
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center">
                <span class="text-[10px] text-gray-400">Owner: ' . htmlspecialchars($owner) . '</span>
                <a href="edit_deal.php?id=' . $deal_id . '" class="text-gray-400 hover:text-blue-600">
                    <i data-lucide="edit-3" class="w-3 h-3"></i>
                </a>
            </div>
        </div>';
}

/**
 * Helper function to generate a Kanban Column
 */
function generate_pipeline_column($title, $deals, $count) {
    $column_id = strtolower(str_replace(' ', '-', $title));
    return '
        <div class="w-80 flex-shrink-0">
            <div class="bg-gray-100 p-3 rounded-xl h-full flex flex-col border border-gray-200">
                <div class="flex justify-between items-center mb-4 px-1">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest">' . htmlspecialchars($title) . '</h3>
                    <span class="text-xs font-bold bg-white border border-gray-300 text-gray-600 px-2 py-0.5 rounded-full shadow-sm">' . $count . '</span>
                </div>
                <div id="' . $column_id . '" class="pipeline-stage flex-grow min-h-[50px] space-y-3 overflow-y-auto" data-stage="' . htmlspecialchars($title) . '">
                    ' . ($deals ?: '<div class="text-center py-10 text-gray-400 text-xs italic">No deals</div>') . '
                </div>
                <a href="create_deal.php?stage=' . urlencode($title) . '" class="mt-4 text-center py-2 text-xs text-gray-400 hover:text-blue-600 hover:bg-white rounded-lg transition border border-dashed border-gray-300">
                    + Add Deal
                </a>
            </div>
        </div>';
}

// Toast logic...
$toast_message = $_SESSION['message'] ?? '';
$toast_type = (isset($_SESSION['message']) && (str_contains($_SESSION['message'], 'Error'))) ? 'error' : 'success';
unset($_SESSION['message']);
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