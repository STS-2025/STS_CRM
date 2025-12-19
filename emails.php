<?php
// emails.php
ob_start(); 
session_start();

$page_title = "Email Inbox";
include 'api/db.php'; 

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
// Fetch all emails joined with lead info
$sql = "
    SELECT e.*, l.id as lead_id, l.name as lead_name 
    FROM crm_emails e 
    INNER JOIN leads l ON e.sender_email = l.email ";
if ($role !== 'admin') {
    $sql .= " WHERE l.owner_id = $user_id ";
}

$sql .= " ORDER BY e.received_at DESC";
$result = $conn->query($sql);
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Email Inbox</h1>
        <p class="text-sm text-gray-500">Manage all incoming communications from your leads.</p>
    </div>
    <button onclick="location.reload()" class="p-2 bg-white border rounded-lg hover:bg-gray-50 shadow-sm">
        <i data-lucide="refresh-cw" class="w-5 h-5 text-gray-600"></i>
    </button>
</div>

<div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Sender</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Subject</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Received</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Status</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 text-sm">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $is_unread = ($row['is_read'] == 0);
                    $row_class = $is_unread ? 'bg-blue-50/30 font-semibold' : 'hover:bg-gray-50';
                ?>
                <tr class="<?= $row_class ?> transition">
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-gray-900"><?= htmlspecialchars($row['lead_name'] ?: 'Unknown') ?></span>
                            <span class="text-xs text-gray-400"><?= htmlspecialchars($row['sender_email']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-700">
                        <div class="max-w-xs truncate"><?= htmlspecialchars($row['subject']) ?></div>
                    </td>
                    <td class="px-6 py-4 text-gray-500 text-xs">
                        <?= date('M d, Y h:i A', strtotime($row['received_at'])) ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if($is_unread): ?>
                            <span class="px-2 py-1 text-[10px] bg-blue-100 text-blue-700 rounded-full">New</span>
                        <?php else: ?>
                            <span class="px-2 py-1 text-[10px] bg-gray-100 text-gray-400 rounded-full">Read</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="api/mark_read.php?id=<?= $row['id'] ?>&lead_id=<?= $row['lead_id'] ?>" 
                           class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-blue-600 hover:bg-blue-50 transition">
                           <i data-lucide="eye" class="w-4 h-4 mr-1"></i> View Lead
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">
                        No emails found in the database.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>