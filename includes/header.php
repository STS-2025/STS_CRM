<?php 
if (session_status() === PHP_SESSION_NONE) session_start(); 
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'user'; // Role-ai variable-la eduthukkurom
?>

<header class="flex items-center justify-between bg-white text-gray-700 px-6 py-3 shadow-md fixed top-0 right-0 z-50 lg:left-64 left-0 border-b border-gray-200">
    
    <div class="flex items-center space-x-4">
        <button id="mobile-menu-toggle" class="block focus:outline-none text-gray-600 hover:text-blue-600 transition">
            <i data-lucide="menu" class="h-6 w-6"></i>
        </button>
        <h1 class="text-xl font-bold text-gray-900 tracking-wide">
            <?= ucfirst(str_replace('.php', '', $current_page)) ?>
        </h1>
    </div>

    <div class="hidden md:flex flex-1 mx-10">
        <input type="text" placeholder="Search contacts, deals, tasks..." class="w-full max-w-lg p-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-800 focus:ring-blue-500 focus:border-blue-500 transition duration-150 shadow-inner">
    </div>

    <div class="flex items-center space-x-2">
        <?php if ($user_role === 'admin'): ?>
        <a href="users.php" title="Users & Teams" class="p-3 text-gray-400 hover:text-blue-600 transition">
            <i data-lucide="users" class="w-5 h-5"></i>
        </a>
        <?php endif; ?>

        <div class="relative inline-block text-left mr-2">
            <button id="notificationBtn" class="relative flex items-center justify-center p-3 rounded-full text-gray-600 bg-white shadow-md transition-all duration-300 hover:text-blue-600 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)]">
                <i data-lucide="mail" class="w-5 h-5"></i>
                <span id="mailCount" class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full hidden">0</span>
            </button>
            <div id="notificationDropdown" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 z-[100] overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="p-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <span class="font-bold text-xs text-gray-700 uppercase tracking-wider">Recent Emails</span>
                </div>
                <div id="notificationList" class="max-h-80 overflow-y-auto">
                    <div class="p-4 text-center text-xs text-gray-400">Loading messages...</div>
                </div>
                <div class="p-2 border-t border-gray-100 text-center">
                    <a href="emails.php" class="text-[11px] font-bold text-blue-600 hover:text-blue-800">View All Emails</a>
                </div>
            </div>
        </div>

        <a href="reminder.php" class="relative flex items-center justify-center p-3 rounded-full text-gray-600 bg-white shadow-md transition-all duration-300 hover:text-blue-600 hover:shadow-[0_0_15px_rgba(59,130,246,0.7)]">
            <i data-lucide="bell" class="w-5 h-5 transition-transform duration-200 hover:animate-shake"></i>
        </a>
        
        <a href="create.php" class="hidden sm:block px-4 py-2 text-sm font-medium rounded-full bg-blue-600 hover:bg-blue-400 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Create
        </a>

        <span class="text-sm px-3 py-1 rounded-full border border-blue-200 bg-blue-50 text-blue-700 hidden sm:block font-medium">
            <?= ucfirst($_SESSION['user_name'] ?? 'Guest') ?>
        </span>

        <a href="logout.php" title="Logout" class="hover:text-blue-600 transition hidden sm:block">
            <i data-lucide="log-out" class="w-5 h-5"></i>
        </a>
    </div>

    <script>
    // 1. UI update function
    function updateNotificationUI() {
        fetch('api/get_notifications.php')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('mailCount');
                const list = document.getElementById('notificationList');
                
                // Badge Update
                if(data.count > 0) {
                    badge.innerText = data.count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                // List Update
                if(data.emails && data.emails.length > 0) {
                    list.innerHTML = data.emails.map(email => `
                        <a href="api/mark_read.php?id=${email.id}&lead_id=${email.lead_id}" class="block p-3 border-b border-gray-50 hover:bg-blue-50 transition border-l-4 border-transparent hover:border-blue-500">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-[11px] font-bold text-gray-800">${email.sender_name}</span>
                                <span class="text-[9px] text-gray-400">${email.time_ago}</span>
                            </div>
                            <p class="text-[11px] text-gray-600 truncate font-medium">${email.subject}</p>
                        </a>
                    `).join('');
                } else {
                    list.innerHTML = '<div class="p-6 text-center text-[11px] text-gray-400 italic">No new messages</div>';
                }
            }).catch(e => console.log("UI Update Error"));
    }

    // 2. Background Sync function
    function triggerEmailSync() {
        fetch('api/sync_emails.php')
            .then(res => res.json())
            .then(data => {
                console.log("Sync Status:", data.message);
                updateNotificationUI(); // Sync mudinjadhun badge-ah refresh pannu
            }).catch(e => console.log("Sync Error"));
    }

    // Event Listeners
    document.getElementById('notificationBtn').onclick = (e) => {
        e.stopPropagation();
        document.getElementById('notificationDropdown').classList.toggle('hidden');
    };
    document.addEventListener('click', () => document.getElementById('notificationDropdown').classList.add('hidden'));

    // Initial load & Intervals
    triggerEmailSync();
    setInterval(triggerEmailSync, 300000); // Sync every 5 mins
    setInterval(updateNotificationUI, 30000); // Refresh UI every 30 secs

    if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>

    <style>
    @keyframes shake { 0%, 100% { transform: rotate(0deg); } 20% { transform: rotate(-15deg); } 40% { transform: rotate(15deg); } 60% { transform: rotate(-10deg); } 80% { transform: rotate(10deg); } }
    .hover\:animate-shake:hover { animation: shake 0.5s ease-in-out 1; }
    </style>
</header>