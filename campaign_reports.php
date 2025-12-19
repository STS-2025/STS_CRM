<?php 
// campaign_reports.php

ob_start(); 
session_start();

$page_title = "Campaign Reports & Summary";
include 'api/db.php'; 

// --- 1. Fetch Aggregated Data ---

$report_data = [
    'total_campaigns' => 0,
    'total_budget' => 0.00,
    'status_counts' => [
        'Running' => 0,
        'Planned' => 0,
        'Completed' => 0,
        'Canceled' => 0,
    ]
];

// Query 1: Total count and total budget
$summary_sql = "SELECT COUNT(id) AS total_campaigns, SUM(budget) AS total_budget FROM campaigns";
$summary_result = $conn->query($summary_sql);
if ($summary_result) {
    $summary = $summary_result->fetch_assoc();
    $report_data['total_campaigns'] = $summary['total_campaigns'] ?? 0;
    $report_data['total_budget'] = $summary['total_budget'] ?? 0.00;
}

// Query 2: Count by Status
$status_sql = "SELECT status, COUNT(id) AS count FROM campaigns GROUP BY status";
$status_result = $conn->query($status_sql);
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $report_data['status_counts'][$row['status']] = $row['count'];
    }
}

$conn->close();
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Campaign Reports & Summary</h1>
        <p class="text-gray-500 mt-1">High-level view of all marketing campaign activity.</p>
    </div>
    <div class="mt-4 sm:mt-0">
        <a href="marketing.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Campaign List
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 text-center">
        <p class="text-sm font-medium text-gray-500">Total Campaigns</p>
        <p class="text-4xl font-bold text-indigo-600 mt-2">
            <?= $report_data['total_campaigns'] ?>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 text-center">
        <p class="text-sm font-medium text-gray-500">Total Budget Committed</p>
        <p class="text-4xl font-bold text-green-600 mt-2">
            $<?= number_format($report_data['total_budget'], 2) ?>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 text-center">
        <p class="text-sm font-medium text-gray-500">Currently Running</p>
        <p class="text-4xl font-bold text-blue-600 mt-2">
            <?= $report_data['status_counts']['Running'] ?? 0 ?>
        </p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Campaign Status Distribution</h2>
    
    <div class="space-y-4">
        <?php foreach ($report_data['status_counts'] as $status => $count): 
            $percent = $report_data['total_campaigns'] > 0 ? ($count / $report_data['total_campaigns']) * 100 : 0;
            
            $bg_color = match ($status) {
                'Running' => 'bg-green-500',
                'Planned' => 'bg-blue-500',
                'Completed' => 'bg-gray-400',
                'Canceled' => 'bg-red-500',
                default => 'bg-gray-300',
            };
        ?>
        <div class="flex items-center">
            <div class="w-1/4 text-sm font-medium text-gray-700"><?= htmlspecialchars($status) ?></div>
            <div class="w-3/4">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full <?= $bg_color ?>" style="width: <?= round($percent) ?>%"></div>
                </div>
            </div>
            <div class="w-1/12 text-right text-sm font-semibold text-gray-700"><?= $count ?> (<?= round($percent) ?>%)</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>


<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>