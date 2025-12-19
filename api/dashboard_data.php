<?php
// api/dashboard_data.php

// Set the response header to JSON
header('Content-Type: application/json');

// Include the database connection file
require 'db.php';

// Initialize the data structure
$dashboard_data = [
    'metrics' => [
        'total_leads' => 0,
        'revenue_pipeline' => '$0.00',
        'new_deals_week' => 0,
        'open_tasks' => 0,
    ],
    'pipeline_stages' => [],
    'recent_activity' => [],
    'error' => null,
];

// Ensure the database connection is available
if (!isset($conn)) {
    $dashboard_data['error'] = 'Database connection failed.';
    echo json_encode($dashboard_data);
    exit();
}

// Helper function to execute a query and return a single result
function fetch_single_value($conn, $sql, $default = 0) {
    if ($result = $conn->query($sql)) {
        if ($row = $result->fetch_row()) {
            return $row[0] ?? $default;
        }
    }
    return $default;
}

// --- 1. Fetch Key Metrics ---

// M1: Total Leads
$sql_leads = "SELECT COUNT(id) FROM leads";
$dashboard_data['metrics']['total_leads'] = (int)fetch_single_value($conn, $sql_leads);

// M2: Revenue Pipeline (Total amount of deals not yet 'Closed Won' or 'Closed Lost')
$sql_pipeline = "SELECT SUM(amount) FROM deals WHERE stage NOT IN ('Closed Won', 'Closed Lost')";
$pipeline_amount = (float)fetch_single_value($conn, $sql_pipeline, 0.00);
$dashboard_data['metrics']['revenue_pipeline'] = '$' . number_format($pipeline_amount, 2);

// M3: New Deals This Week
$sql_new_deals = "SELECT COUNT(id) FROM deals WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$dashboard_data['metrics']['new_deals_week'] = (int)fetch_single_value($conn, $sql_new_deals);

// M4: Open Tasks/Tickets
$sql_open_tasks = "SELECT COUNT(id) FROM tasks WHERE status != 'Completed'";
$dashboard_data['metrics']['open_tasks'] = (int)fetch_single_value($conn, $sql_open_tasks);

// --- 2. Fetch Sales Pipeline Stages (for the chart) ---
$sql_pipeline_stages = "
    SELECT 
        stage, 
        SUM(amount) AS total_amount
    FROM deals
    WHERE stage NOT IN ('Closed Won', 'Closed Lost')
    GROUP BY stage
    ORDER BY total_amount DESC
";
$result_pipeline = $conn->query($sql_pipeline_stages);
if ($result_pipeline) {
    while ($row = $result_pipeline->fetch_assoc()) {
        $dashboard_data['pipeline_stages'][] = [
            'stage' => $row['stage'],
            'amount' => (float)$row['total_amount']
        ];
    }
}

// --- 3. Fetch Recent Activity (Combining Deals and Meetings) ---
// Note: We use a simplified union here. In a large system, this would involve a dedicated activity log table.
$sql_activity = "
    (
        SELECT 
            'deal' AS type,
            deal_name AS item_name,
            amount,
            stage,
            company_id,
            owner_id,
            updated_at AS activity_time
        FROM deals
        WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND stage IN ('Closed Won', 'Closed Lost')
    ) 
    UNION ALL
    (
        SELECT
            'meeting' AS type,
            subject AS item_name,
            NULL AS amount,
            status AS stage,
            NULL AS company_id,
            user_id AS owner_id,
            created_at AS activity_time
        FROM meetings
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    )
    ORDER BY activity_time DESC
    LIMIT 8
";

$result_activity = $conn->query($sql_activity);
if ($result_activity) {
    while ($row = $result_activity->fetch_assoc()) {
        $dashboard_data['recent_activity'][] = $row;
    }
}


// --- 4. Final Output ---
// Close connection
$conn->close();

echo json_encode($dashboard_data);
?>