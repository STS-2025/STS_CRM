<?php
ob_start();
session_start();
$page_title = "User Settings";

// 1. DATABASE CONNECTION
include 'api/db.php'; 

// 2. ACCESS CONTROL
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// ---------------------------------------------------------
// 3. HANDLE POST REQUESTS
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CASE A: Update Profile Name
    if (isset($_POST['update_profile'])) {
        $new_name = $conn->real_escape_string($_POST['name']);
        $sql = "UPDATE users SET name = '$new_name' WHERE id = $user_id";
        if ($conn->query($sql) === TRUE) {
            $msg = "Profile updated successfully!";
            $msg_type = "success";
        }
    }

    // CASE B: Update Password
    if (isset($_POST['update_password'])) {
        $current_pw = $_POST['current_password'];
        $new_pw = $_POST['new_password'];
        $confirm_pw = $_POST['confirm_password'];

        $res = $conn->query("SELECT password FROM users WHERE id = $user_id");
        $db_user = $res->fetch_assoc();

        if ($new_pw !== $confirm_pw) {
            $msg = "New passwords do not match.";
            $msg_type = "error";
        } elseif (!password_verify($current_pw, $db_user['password'])) {
            $msg = "Current password is incorrect.";
            $msg_type = "error";
        } else {
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = '$hashed_pw' WHERE id = $user_id";
            if ($conn->query($sql) === TRUE) {
                $msg = "Password changed successfully!";
                $msg_type = "success";
            }
        }
    }

    // CASE C: Update Email & Integration (Fixed logic for App Password)
    if (isset($_POST['update_integration'])) {
        $signature = $conn->real_escape_string($_POST['email_signature']);
        $provider = $conn->real_escape_string($_POST['email_provider']);
        $app_pass = $conn->real_escape_string($_POST['email_password']); // Added this
        $sync_enabled = isset($_POST['email_sync']) ? 1 : 0;
        
        // Database update with app_password
        $sql = "UPDATE users SET 
                email_signature = '$signature', 
                email_provider = '$provider',
                email_password = '$app_pass', 
                email_sync = $sync_enabled 
                WHERE id = $user_id";

        if ($conn->query($sql) === TRUE) {
            $msg = "Integration settings updated!";
            $msg_type = "success";
        }
    }
}

// 4. FETCH USER DATA
$result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
?>

<div class="max-w-4xl mx-auto py-12 px-6">
    <?php if ($msg): ?>
        <div class="mb-6 p-4 rounded-xl border <?php echo ($msg_type === 'success') ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <div class="space-y-4">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h3 class="text-sm font-bold text-gray-400 uppercase mb-4 text-[10px] tracking-widest">Account Details</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Team</p>
                        <p class="font-semibold text-gray-800"><?php echo $user['team'] ?? 'General'; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Joined</p>
                        <p class="font-semibold text-gray-800"><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Status</p>
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 rounded uppercase">
                            <?php echo $user['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:col-span-2 space-y-6">
            <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold mb-4">Public Profile</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Email Address</label>
                        <input type="email" value="<?php echo $user['email']; ?>" disabled class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-2 text-gray-400 cursor-not-allowed">
                    </div>
                    <button type="submit" name="update_profile" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-blue-700 transition">Save Profile</button>
                </div>
            </form>

            <form method="POST" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-6">Integrations & Performance</h2>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Email Provider</label>
                            <select name="email_provider" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition">
                                <option value="None" <?php echo ($user['email_provider'] == 'None' ? 'selected' : ''); ?>>Not Connected</option>
                                <option value="Gmail" <?php echo ($user['email_provider'] == 'Gmail' ? 'selected' : ''); ?>>Gmail</option>
                                <option value="Outlook" <?php echo ($user['email_provider'] == 'Outlook' ? 'selected' : ''); ?>>Outlook</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Monthly Quota</label>
                            <div class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-slate-700 font-bold">
                                $<?php echo number_format($user['monthly_quota'] ?? 0, 2); ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Email App Password</label>
                        <input type="password" name="email_password" value="<?php echo $user['email_password'] ?? ''; ?>" placeholder="Enter App Password" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl flex items-center justify-between border border-slate-100">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Automatic Email Sync</h4>
                            <p class="text-[10px] text-slate-500 italic">Logs incoming emails to Lead timeline.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_sync" class="sr-only peer" <?php echo ($user['email_sync'] == 1 ? 'checked' : ''); ?>>
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Email Signature</label>
                        <textarea name="email_signature" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none transition"><?php echo htmlspecialchars($user['email_signature']); ?></textarea>
                    </div>

                    <button type="submit" name="update_integration" class="w-full md:w-auto bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-sm hover:bg-blue-700 transition shadow-lg">Update Settings</button>
                </div>
            </form>

            <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold mb-4 text-red-600">Change Password</h2>
                <div class="space-y-4">
                    <input type="password" name="current_password" placeholder="Current Password" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 outline-none">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="password" name="new_password" placeholder="New Password" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 outline-none">
                        <input type="password" name="confirm_password" placeholder="Confirm" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 outline-none">
                    </div>
                    <button type="submit" name="update_password" class="bg-slate-800 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-black transition">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>