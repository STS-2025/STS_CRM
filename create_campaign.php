<?php
// create_campaign.php

ob_start(); 
session_start();

$page_title = "Create New Campaign";
// We don't need db connection here, only the form itself.

$statuses = ['Planned', 'Running', 'Completed', 'Canceled'];
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Create New Campaign</h1>
        <p class="text-gray-500 mt-1">Define the goals, budget, and timeline for your marketing initiative.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/create_campaign_process.php" method="POST" class="space-y-6">

        <div>
            <label for="name" class="text-sm font-medium text-gray-700 block">Campaign Name</label>
            <input type="text" id="name" name="name" required
                   placeholder="e.g., Q4 Lead Generation Drive"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div>
            <label for="goal" class="text-sm font-medium text-gray-700 block">Campaign Goal</label>
            <textarea id="goal" name="goal" rows="3" required
                      placeholder="e.g., Generate 500 qualified leads at a cost per acquisition under $50."
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div>
                <label for="start_date" class="text-sm font-medium text-gray-700 block">Start Date</label>
                <input type="date" id="start_date" name="start_date" required
                       value="<?= date('Y-m-d') ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="end_date" class="text-sm font-medium text-gray-700 block">End Date</label>
                <input type="date" id="end_date" name="end_date" required
                       value="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="budget" class="text-sm font-medium text-gray-700 block">Total Budget ($)</label>
                <input type="number" step="0.01" id="budget" name="budget" required
                       placeholder="e.g., 15000.00"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Status</label>
                <select id="status" name="status" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($s == 'Planned' ? 'selected' : '') ?>>
                            <?= $s ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div></div> </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="marketing.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i data-lucide="plus-circle" class="w-4 h-4 inline mr-2"></i> Create Campaign
                </button>
            </div>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>