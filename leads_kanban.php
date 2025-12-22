<?php
// leads_kanban.php

ob_start(); 
session_start();

$page_title = "Leads Kanban Board";
include 'api/db.php'; 

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// --- 1. Filter & Role Logic Setup ---
$filter_campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$filter_display_name = ($current_user_role === 'admin') ? "All Leads" : "My Leads";
$view_query_string = ""; 

$where_conditions = [];
$bind_types = "";
$bind_params = [];

// Requirement: User role based filtering
if ($current_user_role !== 'admin') {
    $where_conditions[] = "l.owner_id = ?";
    $bind_types .= "i";
    $bind_params[] = $current_user_id;
}

// Campaign Filter Logic
if ($filter_campaign_id > 0) {
    $camp_stmt = $conn->prepare("SELECT name FROM campaigns WHERE id = ?");
    $camp_stmt->bind_param("i", $filter_campaign_id);
    $camp_stmt->execute();
    $camp_result = $camp_stmt->get_result()->fetch_assoc();
    
    $campaign_name = htmlspecialchars($camp_result['name'] ?? 'Unknown');
    $filter_display_name .= " (Campaign: $campaign_name)";
    
    $where_conditions[] = "l.campaign_id = ?";
    $bind_types .= "i";
    $bind_params[] = $filter_campaign_id;
    
    $view_query_string = "?campaign_id=" . $filter_campaign_id;
    $camp_stmt->close();
}

$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// --- 2. Data Fetching ---
$sql = "
    SELECT 
        l.id, l.name, l.company, l.email, l.status, 
        u.name AS owner_name
    FROM leads l
    LEFT JOIN users u ON l.owner_id = u.id
    " . $where_clause . "
    ORDER BY l.status, l.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($bind_types)) {
    $stmt->bind_param($bind_types, ...$bind_params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- 3. Group Leads by Status ---
$leads_by_status = [
    'New' => [],
    'Attempted' => [],
    'Contacted' => [],
    'Qualified' => [],
    'Unqualified' => [],
];

if ($result) {
    while($lead = $result->fetch_assoc()) {
        $status = $lead['status'];
        if (isset($leads_by_status[$status])) {
            $leads_by_status[$status][] = $lead;
        }
    }
}
$stmt->close();
$conn->close();
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Leads Kanban: <?= $filter_display_name ?></h1>
        <p class="text-gray-500 mt-1">Visualize lead progression across different qualification stages.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="leads.php<?= $view_query_string ?>" 
           class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> List View
        </a>
        <a href="create_lead.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                                 bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Add Lead
        </a>
    </div>
</div>

<div class="flex overflow-x-auto pb-4 space-x-4">
    <?php foreach ($leads_by_status as $status => $leads): 
        // Simple column color coding
        $header_color = match ($status) {
            'New' => 'bg-indigo-600',
            'Attempted' => 'bg-yellow-600',
            'Contacted' => 'bg-blue-600',
            'Qualified' => 'bg-green-600',
            'Unqualified' => 'bg-gray-600',
            default => 'bg-gray-400',
        };
        $total_in_column = count($leads);
    ?>

    <div class="min-w-80 w-80 flex-shrink-0">
        <div class="rounded-xl shadow-md overflow-hidden">
            <div class="p-3 text-white font-semibold flex justify-between items-center <?= $header_color ?>">
                <span><?= htmlspecialchars($status) ?></span>
                <span class="text-xs bg-black bg-opacity-20 rounded-full px-2 py-0.5"><?= $total_in_column ?></span>
            </div>
            
            <div class="bg-gray-100 p-2 space-y-2 h-[80vh] overflow-y-auto lead-column" data-status="<?= $status ?>">
                
                <?php if (empty($leads)): ?>
                    <p class="text-center text-sm text-gray-500 italic p-4">No leads in this stage.</p>
                <?php endif; ?>

                <?php foreach ($leads as $lead): ?>
                    <a href="view_lead.php?id=<?= $lead['id'] ?>" class="block">
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-lg transition cursor-grab">
                            <h3 class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($lead['name']) ?></h3>
                            <p class="text-xs text-gray-600 truncate"><?= htmlspecialchars($lead['company'] ?: 'No Company') ?></p>
                            <p class="text-xs text-gray-500 mt-2">Owner: <?= htmlspecialchars($lead['owner_name'] ?: 'N/A') ?></p>
                            <p class="text-xs text-blue-500 hover:text-blue-700 mt-1">#<?= $lead['id'] ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <?php endforeach; ?>
</div>


<?php
// NOTE: For true drag-and-drop Kanban, you would need JavaScript (e.g., sortablejs) 
// and an API endpoint to handle the status update on drop.
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>