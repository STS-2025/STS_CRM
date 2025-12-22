<?php
// dashboard.php
session_start();
include 'api/db.php';
// 1. Start output buffering to capture content
ob_start();


// 2. Set the specific page title for the layout
$page_title = "Dashboard";

/* ---------- DB REMINDER LOGIC (BEFORE HTML) ---------- */
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

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


/* ---------- DASHBOARD METRICS ---------- */

// 1️⃣ Total Leads (for logged-in user)
$stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_leads);
$stmt->fetch();
$stmt->close();

// 2️⃣ Revenue Pipeline (sum of open deals)
$stmt = $conn->prepare("
    SELECT IFNULL(SUM(amount),0)
    FROM deals
    WHERE owner_id = ?
      AND stage NOT IN ('Won','Lost')
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($pipeline_amount);
$stmt->fetch();
$stmt->close();

// 3️⃣ New Deals This Week
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM deals
    WHERE owner_id = ?
      AND WEEK(created_at) = WEEK(CURDATE())
      AND YEAR(created_at) = YEAR(CURDATE())
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($new_deals);
$stmt->fetch();
$stmt->close();

// 4️⃣ Open Service Tickets
/*$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM tickets
    WHERE assigned_to = ?
      AND status != 'Closed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($open_tickets);
$stmt->fetch();
$stmt->close();

// Format revenue as currency
$pipeline_amount = '$' . number_format($pipeline_amount, 2);*/


// Prepare last 30 days
$dates = [];
$deals = [];
$leads = [];
$revenue = [];

for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = $date;

    // Count deals
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM deals WHERE DATE(created_at)=? AND owner_id=?");
    if ($stmt === false) {
        die("Error preparing statement for deals count: " . $conn->error);
    }
    $stmt->bind_param("si", $date, $user_id);
    if ($stmt->execute() === false) {
        die("Error executing statement for deals count: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($result === false) {
        die("Error getting result for deals count: " . $stmt->error);
    }
    $row = $result->fetch_assoc();
    $deals[] = (int) $row['cnt'];

    // Count leads
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM leads WHERE DATE(created_at)=? AND owner_id=?");
    if ($stmt === false) {
        die("Error preparing statement for leads count: " . $conn->error);
    }
    $stmt->bind_param("si", $date, $user_id);
    if ($stmt->execute() === false) {
        die("Error executing statement for leads count: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($result === false) {
        die("Error getting result for leads count: " . $stmt->error);
    }
    $row = $result->fetch_assoc();
    $leads[] = (int) $row['cnt'];

    // Sum revenue (assumes `amount` column in deals table)
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM deals WHERE DATE(created_at)=? AND owner_id=?");
    if ($stmt === false) {
        die("Error preparing statement for revenue sum: " . $conn->error);
    }
    $stmt->bind_param("si", $date, $user_id);
    if ($stmt->execute() === false) {
        die("Error executing statement for revenue sum: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($result === false) {
        die("Error getting result for revenue sum: " . $stmt->error);
    }
    $row = $result->fetch_assoc();
    $revenue[] = (float) ($row['total'] ?? 0);
}


// New leads assigned today
$stmt = $conn->prepare("
    SELECT l.name as lead_name, u.name as employee_name
    FROM leads l
    JOIN users u ON l.owner_id = u.id
    WHERE DATE(l.created_at) = ? AND l.owner_id = ?
");
$stmt->bind_param("si", $today, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$new_leads_today = $result->fetch_all(MYSQLI_ASSOC);

// Confirmed deals today
$stmt = $conn->prepare("
    SELECT d.deal_name, u.name as employee_name
    FROM deals d
    JOIN users u ON d.owner_id = u.id
    WHERE DATE(d.created_at) = ? AND d.stage = 'confirmed' AND d.owner_id = ?
");
$stmt->bind_param("si", $today, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$confirmed_deals_today = $result->fetch_all(MYSQLI_ASSOC);

// Won deals today
$stmt = $conn->prepare("
    SELECT d.deal_name, u.name as employee_name
    FROM deals d
    JOIN users u ON d.owner_id = u.id
    WHERE DATE(d.updated_at) = ? AND d.stage = 'won' AND d.owner_id = ?
");
$stmt->bind_param("si", $today, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$won_deals_today = $result->fetch_all(MYSQLI_ASSOC);

// Lost deals today
$stmt = $conn->prepare("
    SELECT d.deal_name, u.name as employee_name
    FROM deals d
    JOIN users u ON d.owner_id = u.id
    WHERE DATE(d.updated_at) = ? AND d.stage = 'lost' AND d.owner_id = ?
");
$stmt->bind_param("si", $today, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$lost_deals_today = $result->fetch_all(MYSQLI_ASSOC);



// --- HELPER FUNCTION DEFINITION (MUST BE DEFINED BEFORE USE) ---
/**
 * Helper function to generate a styled metric card using Tailwind CSS and Lucide icons.
 *
 * @param string $title The metric title (e.g., 'Total Leads').
 * @param string $value The metric value (e.g., '3,450' or '$1.2M').
 * @param string $icon_color Tailwind color class for the icon (e.g., 'text-blue-500').
 * @param string $icon_name Name of the Lucide icon (e.g., 'users').
 * @return string The HTML for the metric card.
 */




if (!function_exists('generate_metric_card')) {
    function generate_metric_card($title, $value, $icon_color, $icon_name)
    {
        // Dynamic border color based on icon color (e.g., text-blue-500 -> border-blue-600)
        // Note: We strip the 'text-' and replace with 'border-' for the hover effect
        $base_color = str_replace('text-', '', $icon_color);
        $hover_border_color = 'border-' . str_replace('-500', '-600', $base_color);

        return '
            <div class="bg-white p-5 rounded-xl shadow-md border-l-4 border-gray-200 hover:' . $hover_border_color . ' transition duration-200 ease-in-out">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">' . htmlspecialchars($title) . '</p>
                        <h3 class="text-3xl font-extrabold text-gray-900 mt-1">' . htmlspecialchars($value) . '</h3>
                    </div>
                    <div class="p-3 rounded-full ' . $icon_color . ' bg-opacity-10 bg-' . $base_color . '-50">
                        <i data-lucide="' . htmlspecialchars($icon_name) . '" class="w-6 h-6 ' . $icon_color . '"></i>
                    </div>
                </div>
                <p class="text-xs text-green-500 mt-3 font-medium">↑ 10% vs last month</p>
            </div>
        ';
    }
}
// --- END HELPER FUNCTION DEFINITION ---

/*🔔 TEMP: Force reminder popup (remove later)
$reminders = [
    [
        'id' => 7,5,
        'name' => 'Test Reminder Lead',
        'reminder_date' => date('Y-m-d')
    ]
];*/


// --- START DASHBOARD CONTENT ---




?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
    <div>
    <button
        class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition duration-150 shadow-lg shadow-blue-500/50">
        <i data-lucide="download" class="w-4 h-4 inline mr-1"></i> Export Report
    </button>
    <button onclick="openEmailModal()"
        class="px-4 py-2 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition duration-150 shadow-lg">
        <i data-lucide="mail" class="w-4 h-4 inline mr-1"></i> Compose Email
    </button>
    </div>
</div>

<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Metric Cards -->
    <?php echo generate_metric_card('Total Leads', $total_leads, 'text-blue-500', 'users'); ?>

    <?php echo generate_metric_card('Revenue Pipeline', $pipeline_amount, 'text-green-500', 'trending-up'); ?>

    <?php echo generate_metric_card('New Deals This Week', $new_deals, 'text-yellow-500', 'handshake'); ?>

    <?php echo generate_metric_card('Service Tickets Open', '2', 'text-red-500', 'life-buoy'); ?>
</section>

<section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Primary Chart Area -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Sales Pipeline - Last 30 Days</h2>
        <canvas id="salesPipelineChart" class="w-full h-64"></canvas>
    </div>


    <!-- Activity Feed -->
    <div class="lg:col-span-1 bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Activity</h2>
        
        <ul class="space-y-3 text-sm h-64 overflow-y-auto pr-2">
            <?php foreach ($new_leads_today as $item): ?>
                <li class="border-b pb-2 text-gray-600 flex justify-between items-center">
                    <span><span class="font-medium text-gray-800"><?= htmlspecialchars($item['employee_name']) ?></span> got
                        a new lead: <span
                            class="text-blue-600 font-semibold"><?= htmlspecialchars($item['lead_name']) ?></span></span>
                    <span class="text-xs text-gray-400">Today</span>
                </li>
            <?php endforeach; ?>

            <?php foreach ($confirmed_deals_today as $item): ?>
                <li class="border-b pb-2 text-gray-600 flex justify-between items-center">
                    <span><span class="font-medium text-gray-800"><?= htmlspecialchars($item['employee_name']) ?></span>
                        confirmed deal: <span
                            class="text-green-600 font-semibold"><?= htmlspecialchars($item['deal_name']) ?></span></span>
                    <span class="text-xs text-gray-400">Today</span>
                </li>
            <?php endforeach; ?>

            <?php foreach ($won_deals_today as $item): ?>
                <li class="border-b pb-2 text-gray-600 flex justify-between items-center">
                    <span><span class="font-medium text-gray-800"><?= htmlspecialchars($item['employee_name']) ?></span> won
                        deal: <span
                            class="text-green-800 font-semibold"><?= htmlspecialchars($item['deal_name']) ?></span></span>
                    <span class="text-xs text-gray-400">Today</span>
                </li>
            <?php endforeach; ?>

            <?php foreach ($lost_deals_today as $item): ?>
                <li class="border-b pb-2 text-gray-600 flex justify-between items-center">
                    <span><span class="font-medium text-gray-800"><?= htmlspecialchars($item['employee_name']) ?></span>
                        lost deal: <span
                            class="text-red-600 font-semibold"><?= htmlspecialchars($item['deal_name']) ?></span></span>
                    <span class="text-xs text-gray-400">Today</span>
                </li>
            <?php endforeach; ?>

            <?php if (empty($new_leads_today) && empty($confirmed_deals_today) && empty($won_deals_today) && empty($lost_deals_today)): ?>
                <li class="text-gray-400 text-center">No activity for today.</li>
            <?php endif; ?>
        </ul>
    </div>
</section>

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
                <input type="email" name="to_email"
                    class="w-full border rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-500"
                    placeholder="recipient@example.com" required>
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

<?php if (!empty($reminders)): ?>
    <div id="reminder-popup" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
        <!-- Audio -->
        <audio id="reminder-sound" src="assets/notification.mp3" preload="auto"></audio>

        <div class="bg-white rounded-2xl shadow-2xl p-6 w-96">

            <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">
                🔔 Reminder Alert
            </h2>

            <?php foreach ($reminders as $r): ?>
                <div class="relative mb-5 p-4 rounded-xl 
                        bg-gradient-to-br from-blue-50 to-blue-100
                        border border-blue-300
                        shadow-[0_0_18px_rgba(59,130,246,0.55)]
                        transition-all duration-300
                        animate-[pulse_3s_ease-in-out_infinite]
                        hover:shadow-[0_0_28px_rgba(59,130,246,0.8)]">

                    <!-- Soft glow ring -->
                    <div class="absolute -inset-1 rounded-xl 
                            bg-blue-400 opacity-20 blur-lg -z-10"></div>

                    <div class="text-gray-800 font-medium mb-1">
                        ⚠️ Reminder for
                        <a href="view_lead.php?id=<?= $r['id'] ?>" class="text-blue-800 font-bold hover:underline">
                            <?= htmlspecialchars($r['name']) ?>
                        </a>
                    </div>

                    <div class="text-sm text-gray-600 mb-3">
                        📅 <?= htmlspecialchars($r['reminder_date']) ?>
                    </div>

                    <a href="view_lead.php?id=<?= $r['id'] ?>" class="block text-center text-white font-semibold
                          bg-gradient-to-r from-blue-600 to-blue-800
                          hover:from-blue-700 hover:to-blue-900
                          px-4 py-2 rounded-lg
                          shadow-lg shadow-blue-500/50
                          transition-all duration-200
                          hover:scale-[1.03]">
                        View Lead
                    </a>
                </div>
            <?php endforeach; ?>

            <button onclick="document.getElementById('reminder-popup').remove()"
                class="mt-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md w-full">
                OK
            </button>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function startEmailSync() {
            console.log("Background Email Sync Started...");

            // api folder-kulla irukka sync_emails.php-ah call panrom
            fetch('api/sync_emails.php')
                .then(response => response.text())
                .then(data => {
                    console.log("Sync Status: " + data);
                })
                .catch(error => {
                    console.error('Sync Error:', error);
                });
        }
        
        function openEmailModal() {
            const modal = document.getElementById('emailModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeEmailModal() {
            const modal = document.getElementById('emailModal');
            if (modal) modal.classList.add('hidden');
        }
    document.addEventListener("DOMContentLoaded", function () {
        const popup = document.getElementById('reminder-popup');
        if (popup) {
            const audio = document.getElementById('reminder-sound');
            if (audio) {
                audio.play().catch(e => console.log("Autoplay blocked:", e));
            }
        }
        

        // Handle Form Submission
        document.getElementById('emailForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
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
                        this.reset();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .finally(() => {
                    btn.innerText = "Send Now";
                    btn.disabled = false;
                });
        });

        const ctx = document.getElementById('salesPipelineChart').getContext('2d');
        if (ctx) {
            const salesPipelineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($dates) ?>,
                    datasets: [
                        {
                            label: 'Deals',
                            data: <?= json_encode($deals) ?>,
                            borderColor: 'rgba(59, 130, 246, 1)', // blue
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            tension: 0.4
                        },
                        {
                            label: 'Leads',
                            data: <?= json_encode($leads) ?>,
                            borderColor: 'rgba(16, 185, 129, 1)', // green
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            tension: 0.4
                        },
                        {
                            label: 'Revenue',
                            data: <?= json_encode($revenue) ?>,
                            borderColor: 'rgba(234, 179, 8, 1)', // yellow
                            backgroundColor: 'rgba(234, 179, 8, 0.2)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    stacked: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Count'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue (₹)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
// --- END DASHBOARD CONTENT ---

// 3. Capture the content and store it in a variable
$page_content = ob_get_clean();


// 4. Include the master layout file (assuming it handles HTML structure, Tailwind, and Lucide JS)
include 'includes/layout.php';
?>