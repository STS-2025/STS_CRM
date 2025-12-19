<?php
// dashboard.php

// 1. Authentication Check (MUST run first to start session and check login)
include 'includes/auth_check.php';

// Ensure session is active (auth_check normally starts it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'api/db.php';
// 2. Data Fetching Logic
// This file connects to the DB, runs the queries, and creates variables like 
// $total_leads, $total_accounts, $upcoming_meetings, and $active_campaigns.
include 'dashboard_backend.php'; 

// 🔧 TEMP TEST: Force reminder popup
$reminders = [
    [
        'id' => 6,
        'name' => 'Test Reminder Lead',
        'reminder_date' => date('Y-m-d')
    ]
];




$user_id = $_SESSION['user_id']; // employee login user ID

$today = date('Y-m-d');

// Fetch leads where reminder date is today or overdue
$sql = "SELECT id, name, reminder_date 
        FROM leads
        WHERE owner_id = ?
          AND reminder_date IS NOT NULL
          AND reminder_date <= ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$reminders = $result->fetch_all(MYSQLI_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | MyCRM</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="main-container">
  <?php include 'includes/sidebar.php'; ?>

  <main class="content">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>!</h1>
    <p>This is your CRM Dashboard. Manage leads, accounts, and meetings easily.</p>

    <div class="cards">
      
            <div class="card">Total Leads: <strong><?php echo $total_leads; ?></strong></div>
      
            <div class="card">Total Accounts: <strong><?php echo $total_accounts; ?></strong></div>
      
            <div class="card">Upcoming Meetings: <strong><?php echo $upcoming_meetings; ?></strong></div>
      
            <div class="card">Active Campaigns: <strong><?php echo $active_campaigns; ?></strong></div>
    </div>
  </main>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Reminder Popup -->
<?php if (!empty($reminders)): ?>
    <div id="reminder-popup" 
         class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center">
        
        <div class="bg-white rounded-xl shadow-xl p-6 w-96 animate__animated animate__fadeIn">
            <h2 class="text-lg font-bold text-gray-800 mb-3">🔔 Reminder Alert</h2>

            <?php foreach ($reminders as $r): ?>
                <p class="text-gray-700 mb-2">
                    ⚠️ You have a reminder for  
                    <strong><?= htmlspecialchars($r['name']) ?></strong><br>
                    📅 Date: <?= htmlspecialchars($r['reminder_date']) ?><br>

                    <a href="view_lead.php?id=<?= $r['id'] ?>" 
                       class="text-blue-600 underline mt-1 inline-block">
                        View Lead
                    </a>
                </p>
            <?php endforeach; ?>

            <button onclick="document.getElementById('reminder-popup').remove()"
                    class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md">
                OK
            </button>
        </div>
    </div>
<?php endif; ?>
</body>
</html>