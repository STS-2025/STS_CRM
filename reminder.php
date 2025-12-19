<?php
// reminder.php
session_start();
include 'api/db.php'; // Your DB connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Handle marking reminder as done
if (isset($_GET['done']) && is_numeric($_GET['done'])) {
    $reminder_id = $_GET['done'];
    $stmt_done = $conn->prepare("UPDATE leads SET reminder_done = 1 WHERE id = ? AND owner_id = ?");
    $stmt_done->bind_param("ii", $reminder_id, $user_id);
    $stmt_done->execute();
    header("Location: reminder.php");
    exit;
}

// Fetch active reminders
$sql = "SELECT id, name, reminder_date
        FROM leads
        WHERE owner_id = ?
          AND reminder_date IS NOT NULL
          AND reminder_date <= ?
            AND (reminder_done IS NULL OR reminder_done = 0)
        ORDER BY reminder_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$reminders = $result->fetch_all(MYSQLI_ASSOC);

$page_title = "Reminders";

// Start output buffering
ob_start();
?>

<div class="max-w-4xl mx-auto mt-8">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">🔔 Your Reminders</h1>

    <?php if (empty($reminders)): ?>
        <p class="text-gray-600">You have no active reminders today.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($reminders as $r): ?>
                <div class="relative p-4 rounded-xl bg-blue-50 border border-blue-300 shadow-md shadow-blue-400/50 hover:shadow-[0_0_20px_rgba(59,130,246,0.6)] transition-all duration-300">
                    <p class="text-gray-800 font-medium mb-2">
                        ⚠️ Reminder for
                        <a href="view_lead.php?id=<?= $r['id'] ?>"
                           class="text-blue-700 font-bold hover:underline">
                            <?= htmlspecialchars($r['name']) ?>
                        </a>
                    </p>
                    <p class="text-sm text-gray-600 mb-3">📅 <?= htmlspecialchars($r['reminder_date']) ?></p>
                    <a href="view_lead.php?id=<?= $r['id'] ?>"
                       class="inline-block text-center w-full text-white font-semibold bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 px-4 py-2 rounded-lg shadow-lg transition-all duration-200 hover:scale-[1.02] mb-2">
                        View Lead
                    </a>
                    <a href="?done=<?= $r['id'] ?>"
                       class="inline-block text-center w-full text-gray-700 font-semibold bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg shadow-sm transition-all duration-200">
                        Mark as Done
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Capture content and include layout
$page_content = ob_get_clean();
include 'includes/layout.php';
?>
