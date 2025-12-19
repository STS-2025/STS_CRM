<?php
// view_lead.php

ob_start(); 
session_start();

$page_title = "View Lead";
include 'api/db.php'; 

// --- 1. Fetch Lead ID and Validation ---
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lead_id <= 0) {
    $_SESSION['message'] = "Error: Invalid Lead ID provided.";
    header('Location: leads.php');
    exit();
}

// --- 2. Fetch Lead Data ---
$lead_sql = "
    SELECT 
        l.*, 
        u.name AS owner_name,
        c.name AS campaign_name
    FROM leads l
    LEFT JOIN users u ON l.owner_id = u.id
    LEFT JOIN campaigns c ON l.campaign_id = c.id
    WHERE l.id = ?
";
$stmt = $conn->prepare($lead_sql);
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$lead_result = $stmt->get_result();
$lead = $lead_result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$lead) {
    $_SESSION['message'] = "Error: Lead with ID {$lead_id} not found.";
    header('Location: leads.php');
    exit();
}

// TRIGGER POINT:
$lead_email = $lead['email'];
?>

<script>
    // Page load aagum pothu background-la indha specific email-ai mattum sync pannu
    fetch('api/sync_emails.php?email=<?= urlencode($lead_email) ?>')
        .then(res => res.json())
        .then(data => console.log("Background Sync:", data.message))
        .catch(err => console.error("Sync failed", err));
</script>

<?php
// Determine status badge color for display
$status_class = match ($lead['status']) {
    'New' => 'bg-indigo-100 text-indigo-800',
    'Attempted' => 'bg-yellow-100 text-yellow-800',
    'Contacted' => 'bg-blue-100 text-blue-800',
    'Qualified' => 'bg-green-100 text-green-800',
    default => 'bg-gray-100 text-gray-800',
};
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">
            <?= htmlspecialchars($lead['name']) ?> 
            <span class="px-3 py-1 text-sm rounded-full font-semibold <?= $status_class ?> ml-3">
                <?= htmlspecialchars($lead['status']) ?>
            </span>
        </h1>
        <p class="text-gray-500 mt-1">Lead ID: <?= $lead['id'] ?> | Created: <?= date('M d, Y', strtotime($lead['created_at'])) ?></p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="leads.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Back to Leads
        </a>
        <a href="edit_lead.php?id=<?= $lead_id ?>" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="pencil" class="w-4 h-4 inline mr-1"></i> Edit Lead
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="api/delete_lead_process.php?id=<?= $lead_id ?>" 
              onclick="return confirm('Are you sure you want to delete this lead? This action cannot be undone.');"
              class="px-4 py-2 text-sm font-medium rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition duration-150">
              <i data-lucide="trash-2" class="w-4 h-4 inline mr-1"></i> Delete
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
        
        <div class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Contact Info</h2>
            <div>
                <p class="text-sm font-medium text-gray-500">Email Address</p>
                <p class="text-base text-gray-900"><?= htmlspecialchars($lead['email']) ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Phone</p>
                <p class="text-base text-gray-900"><?= htmlspecialchars($lead['phone'] ?: 'N/A') ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Company</p>
                <p class="text-base text-gray-900"><?= htmlspecialchars($lead['company'] ?: 'N/A') ?></p>
            </div>
        </div>

        <div class="space-y-4">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Sales & Source</h2>
            <div>
                <p class="text-sm font-medium text-gray-500">Assigned Owner</p>
                <p class="text-base text-gray-900"><?= htmlspecialchars($lead['owner_name'] ?: 'Unassigned') ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Lead Source</p>
                <p class="text-base text-gray-900"><?= htmlspecialchars($lead['source']) ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Associated Campaign</p>
                <?php if ($lead['campaign_name']): ?>
                    <a href="analyze_campaign.php?id=<?= $lead['campaign_id'] ?>" class="text-base text-blue-600 hover:text-blue-800 font-medium">
                        <?= htmlspecialchars($lead['campaign_name']) ?>
                    </a>
                <?php else: ?>
                    <p class="text-base text-gray-900">N/A</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="space-y-1">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Conversations</h2>
                <div>
                    <p class="text-sm font-medium text-gray-500">Remarks</p>
                    <p class="text-base text-gray-900 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($lead['remarks'] ?: 'No remarks added.')) ?></p>
                </div>
        </div>

    </div>
    
    <div class="mt-8 pt-6 border-t border-gray-200">
    <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
        <i data-lucide="history" class="w-5 h-5 mr-2 text-blue-500"></i>
        Activity Timeline
    </h2>

    <div class="relative">
        <?php
        // Database connection ippo close aagi irukkum (mela line 42-la), 
        // So thirumba connect panni emails-ah fetch panrom
        include 'api/db.php'; 
        
        $lead_email = $lead['email'];
        $email_sql = "SELECT * FROM crm_emails WHERE sender_email = ? ORDER BY received_at DESC";
        $stmt_mail = $conn->prepare($email_sql);
        $stmt_mail->bind_param("s", $lead_email);
        $stmt_mail->execute();
        $emails_res = $stmt_mail->get_result();

        if ($emails_res->num_rows > 0): ?>
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-100"></div>

            <div class="space-y-8">
                <?php while ($email = $emails_res->fetch_assoc()): ?>
                    <div class="relative pl-12">
                        <div class="absolute left-0 top-1 w-8 h-8 rounded-full bg-blue-50 border-4 border-white flex items-center justify-center shadow-sm z-10">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-blue-600"></i>
                        </div>

                        <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm hover:shadow-md transition duration-200">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest bg-blue-50 px-2 py-0.5 rounded">Incoming Email</span>
                                    <h4 class="text-sm font-bold text-gray-800 mt-2"><?= htmlspecialchars($email['subject']) ?></h4>
                                </div>
                                <span class="text-[11px] text-gray-400 font-medium">
                                    <?= date('M d, Y • h:i A', strtotime($email['received_at'])) ?>
                                </span>
                            </div>
                            
                            <div class="text-xs text-gray-600 leading-relaxed bg-gray-50 p-3 rounded-lg border border-dashed border-gray-200">
                                <?= nl2br(htmlspecialchars(substr(strip_tags($email['body']), 0, 300))) ?>...
                            </div>
                            
                            <div class="mt-3 flex space-x-3">
                                <button onclick="viewEmailDetails(<?= $email['id'] ?>)" class="text-[11px] font-bold text-blue-600 hover:underline">View Full Thread</button>
                                <button onclick="openEmailModalWithAddress('<?= $lead_email ?>')" class="text-[11px] font-bold text-gray-400 hover:text-gray-600 italic"><i data-lucide="reply" class="w-3 h-3"></i>Reply via CRM</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-center">
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                    <i data-lucide="mail-search" class="w-6 h-6 text-gray-300"></i>
                </div>
                <p class="text-sm text-gray-500">No email conversations found for this lead.</p>
                <p class="text-[10px] text-gray-400 mt-1">Check if Email Sync is enabled in your settings.</p>
            </div>
        <?php endif; 
        $stmt_mail->close();
        ?>
    </div>
</div>
</div>

<div id="viewEmailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[70] flex items-center justify-center">
    <div class="bg-white rounded-xl p-6 w-full max-w-2xl shadow-2xl">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h2 id="modal_subject" class="text-lg font-bold text-gray-800"></h2>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div id="modal_body" class="text-sm text-gray-700 overflow-y-auto max-h-[400px] leading-relaxed">
            </div>
    </div>
</div>

<div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[60] flex items-center justify-center">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Compose Email</h2>
            <button onclick="closeEmailModal()" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <form id="emailForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">To Email</label>
                <input type="email" name="to_email" id="to_email"
                    class="w-full border rounded-lg p-2 mt-1 bg-gray-50" readonly required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Subject</label>
                <input type="text" name="subject"
                    class="w-full border rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter subject" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Message</label>
                <textarea name="message" rows="4"
                    class="w-full border rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-500"
                    placeholder="Type your message here..." required></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeEmailModal()"
                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Send Now
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    // 1. Modal open panna (Auto-fill email)
    function openEmailModalWithAddress(email) {
        const modal = document.getElementById('emailModal');
        const emailInput = document.getElementById('to_email');
        if (emailInput) emailInput.value = email;
        if (modal) modal.classList.remove('hidden');
    }

    // 2. Modal close panna
    function closeEmailModal() {
        const modal = document.getElementById('emailModal');
        if (modal) modal.classList.add('hidden');
    }

    function viewEmailDetails(emailId) {
    // API-la irundhu antha email full details-ah edukkurathu
    fetch(`api/get_email_details.php?id=${emailId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modal_subject').innerText = data.subject;
            document.getElementById('modal_body').innerHTML = data.body; // Use nl2br in PHP for safety
            document.getElementById('viewEmailModal').classList.remove('hidden');
        });
}

function closeViewModal() {
    document.getElementById('viewEmailModal').classList.add('hidden');
}

    document.addEventListener("DOMContentLoaded", function () {
        // 3. Email Send Logic (AJAX)
        const eForm = document.getElementById('emailForm');
        if (eForm) {
            eForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = e.target.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                
                btn.innerText = "Sending...";
                btn.disabled = true;

                const formData = new FormData(this);
                fetch('api/send_email.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Email success-ah anuppiyaachu!');
                        closeEmailModal();
                        eForm.reset();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => alert('Failed to send email.'))
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        }
    });
</script>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>

